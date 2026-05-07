<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lecturer_logged_in']) || $_SESSION['lecturer_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function decryptValue(?string $value): string
{
    if ($value === null || $value === '') return '';
    try {
        return decryptData($value);
    } catch (Throwable $error) {
        return '';
    }
}

function lecturerOwnsProject(PDO $db, int $lecturerId, int $projectId): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM projects WHERE project_id = ? AND lecturer_id = ?');
    $stmt->execute([$projectId, $lecturerId]);
    return (int) $stmt->fetchColumn() > 0;
}

function updateSubmissionStatus(PDO $db, int $projectId, string $status): void
{
    $stmt = $db->prepare('SELECT submission_id FROM submissions WHERE project_id = ? ORDER BY submitted_at DESC LIMIT 1');
    $stmt->execute([$projectId]);
    $submissionId = $stmt->fetchColumn();

    if ($submissionId) {
        $update = $db->prepare('UPDATE submissions SET status = ? WHERE submission_id = ?');
        $update->execute([$status, $submissionId]);
        return;
    }

    $insert = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
    $insert->execute([$projectId, $status]);
}

$lecturerId     = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName   = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

$flashMessage = '';
$flashType    = 'success';

// Handle POST actions (approve / reject / comment)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action    = $_POST['action'] ?? '';
    $projectId = (int) ($_POST['project_id'] ?? 0);

    try {
        if ($projectId > 0 && lecturerOwnsProject($db, $lecturerId, $projectId)) {
            if ($action === 'approve') {
                updateSubmissionStatus($db, $projectId, 'approved');
                $flashMessage = 'Project submission approved.';
            } elseif ($action === 'reject') {
                updateSubmissionStatus($db, $projectId, 'rejected');
                $flashMessage = 'Project submission rejected.';
                $flashType    = 'danger';
            } elseif ($action === 'comment') {
                $comment = trim((string) ($_POST['comment'] ?? ''));
                if ($comment !== '') {
                    $stmt = $db->prepare('INSERT INTO comments (project_id, user_id, content_encrypted) VALUES (?, ?, ?)');
                    $stmt->execute([$projectId, $lecturerId, encryptData($comment)]);
                    $flashMessage = 'Feedback submitted.';
                } else {
                    $flashMessage = 'Please enter feedback before submitting.';
                    $flashType    = 'danger';
                }
            }
        }
    } catch (Throwable $error) {
        $flashMessage = 'Unable to update the project right now.';
        $flashType    = 'danger';
    }
}

// Fetch projects + submissions + files
$projects     = [];
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.study_year, p.created_at,
                latest.submission_id, latest.submitted_at, latest.status
         FROM projects p
         LEFT JOIN LATERAL (
             SELECT submission_id, submitted_at, status
             FROM submissions
             WHERE submissions.project_id = p.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         ) latest ON TRUE
         WHERE p.lecturer_id = ?
         ORDER BY COALESCE(latest.submitted_at, p.created_at) DESC"
    );
    $stmt->execute([$lecturerId]);
    $projectRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $studentStmt = $db->prepare(
        "SELECT u.user_id, u.name_encrypted, pm.role
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY pm.role DESC, u.user_id ASC"
    );

    $fileStmt = $db->prepare(
        "SELECT f.file_id, f.file_name_encrypted, f.file_path_encrypted, f.uploaded_at,
                u.name_encrypted AS uploader_name
         FROM files f
         LEFT JOIN users u ON u.user_id = f.uploaded_by
         WHERE f.project_id = ?
         ORDER BY f.uploaded_at DESC"
    );

    foreach ($projectRows as $row) {
        $studentStmt->execute([(int) $row['project_id']]);
        $students = [];
        foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
            $name = decryptValue($student['name_encrypted'] ?? '');
            if ($name !== '') {
                $students[] = ['id' => (int) $student['user_id'], 'name' => $name, 'role' => (string) ($student['role'] ?? '')];
            }
        }

        $fileStmt->execute([(int) $row['project_id']]);
        $files = [];
        foreach ($fileStmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
            $fileName = decryptValue($file['file_name_encrypted'] ?? '');
            $filePath = decryptValue($file['file_path_encrypted'] ?? '');
            if ($fileName !== '') {
                $files[] = [
                    'id'          => (int) $file['file_id'],
                    'name'        => $fileName,
                    'path'        => $filePath,
                    'uploaded_at' => $file['uploaded_at'] ?? null,
                    'uploader'    => decryptValue($file['uploader_name'] ?? ''),
                ];
            }
        }

        $projects[] = [
            'id'           => (int) $row['project_id'],
            'code'         => 'UTM-FYP-' . str_pad((string) $row['project_id'], 4, '0', STR_PAD_LEFT),
            'title'        => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year'   => $row['study_year'] ?? '',
            'submitted_at' => $row['submitted_at'] ?? null,
            'status'       => $row['status'] ?: 'pending',
            'students'     => $students,
            'files'        => $files,
        ];
    }
} catch (Throwable $error) {
    $flashMessage = $flashMessage ?: 'Unable to load submissions.';
    $flashType    = 'danger';
}

$totalProjects    = count($projects);
$pendingProjects  = count(array_filter($projects, static fn($p) => $p['status'] === 'pending'));
$approvedProjects = count(array_filter($projects, static fn($p) => $p['status'] === 'approved'));
$rejectedProjects = count(array_filter($projects, static fn($p) => $p['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions – UTM Lecturer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="utm-theme.css">
    <style>
        :root {
            --lecturer-maroon: #800020; --lecturer-maroon-dark: #540014;
            --lecturer-gold: #dca51c; --lecturer-bg: #f4f7fb;
            --lecturer-card: #ffffff; --lecturer-border: #e5e9f0;
            --lecturer-text: #182033; --lecturer-muted: #6e7686;
            --lecturer-shadow: 0 16px 38px rgba(28,39,60,.09);
        }
        body { margin:0; min-height:100vh; background:var(--lecturer-bg); color:var(--lecturer-text); font-family:Inter,system-ui,sans-serif; }
        .lecturer-shell { min-height:100vh; padding-left:270px; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:270px; overflow-y:auto; background:linear-gradient(180deg,#fffcf4 0%,#f8f0df 100%); border-right:1px solid rgba(128,0,32,.12); box-shadow:12px 0 32px rgba(84,0,20,.1); }
        .brand { min-height:105px; padding:24px 20px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(128,0,32,.12); }
        .brand-mark { width:64px; height:64px; object-fit:contain; }
        .brand-title { margin:0; color:var(--lecturer-maroon); font-weight:800; font-size:1.25rem; }
        .brand-subtitle { color:#7d5b20; font-size:.76rem; line-height:1.35; }
        .sidebar-nav { display:grid; gap:7px; padding:18px 14px; }
        .nav-link { display:flex; align-items:center; gap:12px; min-height:46px; padding:11px 14px; border-radius:12px; color:#6f273a; font-weight:700; text-decoration:none; }
        .nav-link:hover, .nav-link.active { background:var(--lecturer-gold); color:#271700; }
        .sidebar-footer { padding:16px 14px 22px; border-top:1px solid rgba(128,0,32,.12); }
        .top-navbar { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:28px; padding:18px 24px; position:sticky; top:0; z-index:20; background:rgba(255,255,255,.93); border-bottom:1px solid var(--lecturer-border); backdrop-filter:blur(12px); }
        .icon-button { width:42px; aspect-ratio:1; border-radius:12px; border:1px solid var(--lecturer-border); background:#fff; display:inline-grid; place-items:center; color:var(--lecturer-text); }
        .profile-chip { display:flex; align-items:center; gap:10px; padding:6px 12px 6px 6px; border-radius:999px; border:1px solid var(--lecturer-border); background:#fff; }
        .avatar { width:38px; aspect-ratio:1; border-radius:50%; display:grid; place-items:center; background:#f2e2b8; color:var(--lecturer-maroon); font-weight:800; }
        .content { padding:30px; }
        .stat-card { background:var(--lecturer-card); border:1px solid var(--lecturer-border); border-radius:18px; box-shadow:var(--lecturer-shadow); min-height:110px; padding:20px; }
        .stat-icon { width:46px; aspect-ratio:1; border-radius:14px; display:grid; place-items:center; background:rgba(128,0,32,.1); color:var(--lecturer-maroon); font-size:1.35rem; }
        .stat-value { font-size:2rem; line-height:1; font-weight:800; }
        .toolbar { background:#fff; border:1px solid var(--lecturer-border); border-radius:18px; padding:14px; box-shadow:var(--lecturer-shadow); }
        .search-control { min-height:44px; border-radius:12px; border:1px solid var(--lecturer-border); }
        .project-card { background:var(--lecturer-card); border:1px solid var(--lecturer-border); border-radius:16px; padding:22px; box-shadow:var(--lecturer-shadow); display:flex; flex-direction:column; }
        .project-card:hover { transform:translateY(-2px); box-shadow:0 24px 48px rgba(28,39,60,.12); transition:.2s; }
        .project-title { color:var(--lecturer-maroon); font-size:1.05rem; font-weight:800; line-height:1.35; }
        .status-badge { display:inline-flex; align-items:center; min-height:26px; padding:3px 10px; border-radius:999px; font-size:.76rem; font-weight:800; text-transform:uppercase; }
        .status-pending  { background:#fff5d8; color:#856200; }
        .status-approved { background:#e7f6ed; color:#1f7a45; }
        .status-rejected { background:#fdecec; color:#a43131; }
        .action-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:auto; padding-top:16px; }
        .btn-review { min-height:38px; border-radius:10px; font-size:.8rem; font-weight:800; text-transform:uppercase; }
        .btn-comment { border:1px solid #d9ad37; color:var(--lecturer-maroon); background:#fff; }
        .btn-reject  { border:1px solid var(--lecturer-maroon); color:var(--lecturer-maroon); background:#fff; }
        .btn-approve { border:1px solid var(--lecturer-gold); background:var(--lecturer-gold); color:#271700; }
        .files-section { background:#f8f9fc; border:1px solid var(--lecturer-border); border-radius:12px; padding:14px; margin-top:14px; }
        .files-section h6 { color:var(--lecturer-maroon); font-weight:700; font-size:.9rem; margin-bottom:10px; }
        .file-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:9px; background:#fff; border:1px solid var(--lecturer-border); margin-bottom:6px; }
        .file-item:last-child { margin-bottom:0; }
        .file-name { font-size:.875rem; font-weight:600; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .file-meta { font-size:.78rem; color:var(--lecturer-muted); }
        .btn-download { min-height:32px; padding:4px 12px; border-radius:8px; background:var(--lecturer-maroon); color:#fff; border:none; font-size:.78rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
        .btn-download:hover { background:var(--lecturer-maroon-dark); color:#fff; }
        .empty-state { display:grid; place-items:center; min-height:200px; color:var(--lecturer-muted); text-align:center; border:1px dashed var(--lecturer-border); border-radius:16px; background:#fbfcfe; }
        @media (max-width:991.98px) { .lecturer-shell { padding-left:0; } .sidebar { position:static; width:100%; } .content { padding:20px 14px; } }
    </style>
</head>
<body>
<div class="lecturer-shell">
    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column">
        <div class="brand">
            <img class="brand-mark" src="assets/utm-logo.png" alt="UTM logo"
                 onerror="this.outerHTML='<div style=\'width:64px;height:64px;border-radius:50%;background:rgba(128,0,32,.15);display:flex;align-items:center;justify-content:center;color:#800020;font-weight:800;\'>UTM</div>'">
            <div>
                <p class="brand-title">UTM</p>
                <div class="brand-subtitle">Universiti Teknologi Malaysia<br>Academic Review</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-link" href="Lecturer_dashboard.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
            <a class="nav-link active" href="lecturer_submissions.php"><i class="bi bi-file-earmark-check-fill"></i><span>Submissions</span></a>
            <a class="nav-link" href="lecturer_students.php"><i class="bi bi-people-fill"></i><span>Students</span></a>
            <a class="nav-link" href="lecturer_grades.php"><i class="bi bi-star-fill"></i><span>Grades</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar"><?= e($lecturerInitials) ?></div>
                <div>
                    <div class="fw-bold"><?= e($lecturerName) ?></div>
                    <small class="text-muted">Lecturer</small>
                </div>
            </div>
            <a class="text-muted small" href="logout.php">Logout</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="lecturer-main">
        <header class="top-navbar">
            <div><span class="text-muted">Welcome,</span><strong> <?= e($lecturerName) ?></strong></div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <div class="profile-chip">
                    <div class="avatar"><?= e($lecturerInitials) ?></div>
                    <div class="d-none d-sm-block pe-1">
                        <div class="fw-bold lh-sm"><?= e($lecturerName) ?></div>
                        <small class="text-muted">UTM Lecturer</small>
                    </div>
                </div>
                <a class="icon-button text-decoration-none" href="logout.php"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </header>

        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--lecturer-maroon);">Student Submissions</h1>
                    <p class="text-muted mb-0">Review, approve or reject project submissions and download files</p>
                </div>
            </section>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Total Submissions</p><strong class="stat-value"><?= e($totalProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Pending Review</p><strong class="stat-value text-warning"><?= e($pendingProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Approved</p><strong class="stat-value text-success"><?= e($approvedProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Rejected</p><strong class="stat-value text-danger"><?= e($rejectedProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Toolbar -->
            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="projectSearch" class="form-control search-control border-start-0" type="search" placeholder="Search projects or students…">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="yearFilter" class="form-select search-control">
                            <option value="all">All Study Years</option>
                            <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $year): ?>
                                <option value="<?= e($year) ?>">Year <?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select search-control">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Project Cards -->
            <?php if (!$projects): ?>
                <div class="empty-state"><div><i class="bi bi-folder2-open fs-2 d-block mb-2"></i>No submissions found.</div></div>
            <?php else: ?>
                <div class="row g-4" id="projectGrid">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $studentNames = array_column($project['students'], 'name');
                        $searchText   = strtolower($project['title'] . ' ' . implode(' ', $studentNames));
                        $submittedAt  = $project['submitted_at'] ? date('d/m/Y', strtotime((string) $project['submitted_at'])) : 'No data available';
                        ?>
                        <div class="col-12 col-lg-6 col-xxl-4 project-item"
                             data-status="<?= e($project['status']) ?>"
                             data-year="<?= e($project['study_year']) ?>"
                             data-search="<?= e($searchText) ?>">
                            <article class="project-card">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <span class="status-badge status-<?= e($project['status']) ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span>
                                    <small class="text-muted"><?= e($project['code']) ?></small>
                                </div>
                                <h2 class="project-title mb-2"><?= e($project['title']) ?></h2>
                                <div class="mb-2" style="color:var(--lecturer-text);line-height:1.55;">
                                    <strong><?= e(count($studentNames)) ?> Student<?= count($studentNames) === 1 ? '' : 's' ?></strong>
                                    <?php foreach ($studentNames as $i => $sn): ?>
                                        <div><?= e($i + 1) ?>. <?= e($sn) ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-muted mb-1" style="font-size:.9rem;">Submitted: <?= e($submittedAt) ?></div>
                                <div class="text-muted mb-2" style="font-size:.9rem;">Study Year: <?= $project['study_year'] !== '' ? e($project['study_year']) : 'N/A' ?></div>

                                <!-- Files -->
                                <?php if ($project['files']): ?>
                                <div class="files-section">
                                    <h6><i class="bi bi-paperclip me-1"></i>Uploaded Files (<?= count($project['files']) ?>)</h6>
                                    <?php foreach ($project['files'] as $file): ?>
                                        <div class="file-item">
                                            <i class="bi bi-file-earmark-fill text-muted"></i>
                                            <span class="file-name" title="<?= e($file['name']) ?>"><?= e($file['name']) ?></span>
                                            <div class="file-meta d-none d-sm-block">
                                                <?= $file['uploaded_at'] ? e(date('d/m/Y', strtotime((string)$file['uploaded_at']))) : '' ?>
                                            </div>
                                            <?php if ($file['path']): ?>
                                                <a href="<?= e($file['path']) ?>" class="btn-download" download>
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="files-section text-muted" style="font-size:.875rem;">
                                    <i class="bi bi-folder2-open me-1"></i>No files uploaded yet.
                                </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="action-row">
                                    <button class="btn btn-review btn-comment" type="button"
                                            data-bs-toggle="modal" data-bs-target="#commentModal"
                                            data-project-id="<?= e($project['id']) ?>"
                                            data-project-title="<?= e($project['title']) ?>">
                                        <i class="bi bi-chat-left-text me-1"></i>Comment
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-review btn-reject" type="submit"
                                                onclick="return confirm('Reject this submission?')">
                                            <i class="bi bi-x-lg me-1"></i>Reject
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-review btn-approve" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Approve
                                        </button>
                                    </form>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state d-none mt-4" id="filteredEmpty">No submissions found.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="commentModalLabel">Add Feedback</h2>
                    <p class="text-muted mb-0 small" id="commentProjectTitle">Project feedback</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="project_id" id="commentProjectId">
                <label class="form-label fw-semibold" for="commentText">Comment</label>
                <textarea class="form-control" id="commentText" name="comment" rows="5"
                          placeholder="Write your feedback or revision notes…" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn fw-bold" style="background:var(--lecturer-gold);color:#271700;">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput  = document.getElementById('projectSearch');
    const statusFilter = document.getElementById('statusFilter');
    const yearFilter   = document.getElementById('yearFilter');
    const items        = Array.from(document.querySelectorAll('.project-item'));
    const emptyDiv     = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query  = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        const year   = yearFilter?.value || 'all';
        let visible  = 0;
        items.forEach(item => {
            const show = (!query || item.dataset.search.includes(query))
                      && (status === 'all' || item.dataset.status === status)
                      && (year   === 'all' || item.dataset.year   === year);
            item.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        emptyDiv?.classList.toggle('d-none', visible !== 0);
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    yearFilter?.addEventListener('change', applyFilters);

    document.getElementById('commentModal')?.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('commentProjectId').value = btn?.dataset.projectId || '';
        document.getElementById('commentProjectTitle').textContent = btn?.dataset.projectTitle || '';
        document.getElementById('commentText').value = '';
    });
</script>
</body>
</html>