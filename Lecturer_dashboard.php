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
    if ($value === null || $value === '') {
        return '';
    }

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

$lecturerId = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';
$flashMessage = '';
$flashType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $projectId = (int) ($_POST['project_id'] ?? 0);

    try {
        if ($projectId > 0 && lecturerOwnsProject($db, $lecturerId, $projectId)) {
            if ($action === 'approve') {
                updateSubmissionStatus($db, $projectId, 'approved');
                $flashMessage = 'Project submission approved.';
            } elseif ($action === 'reject') {
                updateSubmissionStatus($db, $projectId, 'rejected');
                $flashMessage = 'Project submission rejected.';
                $flashType = 'danger';
            } elseif ($action === 'comment') {
                $comment = trim((string) ($_POST['comment'] ?? ''));
                if ($comment !== '') {
                    $stmt = $db->prepare('INSERT INTO comments (project_id, user_id, content_encrypted) VALUES (?, ?, ?)');
                    $stmt->execute([$projectId, $lecturerId, encryptData($comment)]);
                    $flashMessage = 'Feedback submitted.';
                } else {
                    $flashMessage = 'Please enter feedback before submitting.';
                    $flashType = 'danger';
                }
            }
        }
    } catch (Throwable $error) {
        $flashMessage = 'Unable to update the project right now.';
        $flashType = 'danger';
    }
}

$projects = [];

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

    foreach ($projectRows as $row) {
        $studentStmt->execute([(int) $row['project_id']]);
        $students = [];

        foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
            $name = decryptValue($student['name_encrypted'] ?? '');
            if ($name !== '') {
                $students[] = [
                    'id' => (int) $student['user_id'],
                    'name' => $name,
                    'role' => (string) ($student['role'] ?? ''),
                ];
            }
        }

        $projects[] = [
            'id' => (int) $row['project_id'],
            'code' => 'UTM-FYP-' . str_pad((string) $row['project_id'], 4, '0', STR_PAD_LEFT),
            'title' => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'description' => decryptValue($row['description_encrypted'] ?? ''),
            'study_year' => $row['study_year'] ?? '',
            'submitted_at' => $row['submitted_at'] ?? null,
            'status' => $row['status'] ?: 'pending',
            'students' => $students,
        ];
    }
} catch (Throwable $error) {
    $projects = [];
    $flashMessage = $flashMessage ?: 'Unable to load lecturer projects.';
    $flashType = 'danger';
}

$statusLabels = [
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
];

$totalProjects = count($projects);
$pendingProjects = count(array_filter($projects, static fn($project) => $project['status'] === 'pending'));
$approvedProjects = count(array_filter($projects, static fn($project) => $project['status'] === 'approved'));
$rejectedProjects = count(array_filter($projects, static fn($project) => $project['status'] === 'rejected'));
$studentMap = [];
foreach ($projects as $project) {
    foreach ($project['students'] as $student) {
        $studentMap[$student['id']] = $student['name'];
    }
}
$assignedStudents = count($studentMap);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Academic Project Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="utm-theme.css">
    <style>
        :root {
            --lecturer-maroon: #800020;
            --lecturer-maroon-dark: #540014;
            --lecturer-gold: #dca51c;
            --lecturer-bg: #f4f7fb;
            --lecturer-card: #ffffff;
            --lecturer-border: #e5e9f0;
            --lecturer-text: #182033;
            --lecturer-muted: #6e7686;
            --lecturer-shadow: 0 16px 38px rgba(28, 39, 60, 0.09);
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--lecturer-bg);
            color: var(--lecturer-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .lecturer-shell {
            min-height: 100vh;
            padding-left: 270px;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 270px;
            overflow-y: auto;
            background: linear-gradient(180deg, var(--lecturer-maroon) 0%, var(--lecturer-maroon-dark) 100%);
            color: #fff;
            box-shadow: 12px 0 32px rgba(84, 0, 20, 0.24);
        }

        .brand {
            min-height: 105px;
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .brand-mark {
            width: 58px;
            aspect-ratio: 1;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: rgba(220, 165, 28, 0.18);
            border: 1px solid rgba(220, 165, 28, 0.5);
            color: #f5c84b;
            font-weight: 800;
        }

        .brand-title {
            margin: 0;
            color: #f5c84b;
            font-weight: 800;
            font-size: 1.25rem;
            line-height: 1;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .sidebar-nav {
            display: grid;
            gap: 7px;
            padding: 18px 14px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 46px;
            padding: 11px 14px;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.84);
            font-weight: 700;
            text-decoration: none;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--lecturer-gold);
            color: #271700;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 14px 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .lecturer-main {
            min-width: 0;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            min-height: 76px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.93);
            border-bottom: 1px solid var(--lecturer-border);
            backdrop-filter: blur(12px);
        }

        .icon-button {
            width: 42px;
            aspect-ratio: 1;
            border-radius: 12px;
            border: 1px solid var(--lecturer-border);
            background: #fff;
            display: inline-grid;
            place-items: center;
            color: var(--lecturer-text);
        }

        .profile-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 6px;
            border-radius: 999px;
            border: 1px solid var(--lecturer-border);
            background: #fff;
        }

        .avatar {
            width: 38px;
            aspect-ratio: 1;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #f2e2b8;
            color: var(--lecturer-maroon);
            font-weight: 800;
        }

        .content {
            padding: 30px;
        }

        .dashboard-card,
        .project-card,
        .stat-card {
            background: var(--lecturer-card);
            border: 1px solid var(--lecturer-border);
            border-radius: 18px;
            box-shadow: var(--lecturer-shadow);
        }

        .stat-card {
            min-height: 124px;
            padding: 20px;
        }

        .stat-icon {
            width: 46px;
            aspect-ratio: 1;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(128, 0, 32, 0.1);
            color: var(--lecturer-maroon);
            font-size: 1.35rem;
        }

        .stat-value {
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
        }

        .toolbar {
            background: #fff;
            border: 1px solid var(--lecturer-border);
            border-radius: 18px;
            padding: 14px;
            box-shadow: var(--lecturer-shadow);
        }

        .search-control {
            min-height: 44px;
            border-radius: 12px;
            border: 1px solid var(--lecturer-border);
        }

        .project-card {
            display: flex;
            flex-direction: column;
            min-height: 272px;
            padding: 20px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff5d8;
            color: #856200;
        }

        .status-approved {
            background: #e7f6ed;
            color: #1f7a45;
        }

        .status-rejected {
            background: #fdecec;
            color: #a43131;
        }

        .project-title {
            color: var(--lecturer-maroon);
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .student-list {
            color: var(--lecturer-text);
            line-height: 1.55;
        }

        .meta-line {
            color: var(--lecturer-muted);
            font-size: 0.9rem;
        }

        .action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: auto;
            padding-top: 16px;
        }

        .btn-review {
            min-height: 38px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .btn-comment {
            border: 1px solid #d9ad37;
            color: var(--lecturer-maroon);
            background: #fff;
        }

        .btn-reject {
            border: 1px solid var(--lecturer-maroon);
            color: var(--lecturer-maroon);
            background: #fff;
        }

        .btn-approve {
            border: 1px solid var(--lecturer-gold);
            background: var(--lecturer-gold);
            color: #271700;
        }

        .dashboard-card {
            padding: 22px;
        }

        .empty-state {
            display: grid;
            place-items: center;
            min-height: 230px;
            color: var(--lecturer-muted);
            text-align: center;
            border: 1px dashed var(--lecturer-border);
            border-radius: 16px;
            background: #fbfcfe;
        }

        .table thead th {
            color: #5f6878;
            font-size: 0.82rem;
            text-transform: uppercase;
            background: #f6f8fb;
        }

        @media (max-width: 991.98px) {
            .lecturer-shell {
                padding-left: 0;
            }

            .sidebar {
                position: static;
                width: 100%;
            }

            .sidebar-nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content {
                padding: 20px 14px;
            }
        }

        @media (max-width: 575.98px) {
            .sidebar-nav {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 0 14px;
            }

            .welcome-text,
            .profile-meta {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="lecturer-shell">
    <aside class="sidebar d-flex flex-column">
        <div class="brand">
            <div class="brand-mark">UTM</div>
            <div>
                <p class="brand-title">UTM</p>
                <div class="brand-subtitle">Universiti Teknologi Malaysia<br>Academic Review</div>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Lecturer navigation">
            <a class="nav-link active" href="#dashboard">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="#projects">
                <i class="bi bi-folder-fill"></i>
                <span>Projects</span>
            </a>
            <a class="nav-link" href="#faculty">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Faculty</span>
            </a>
            <a class="nav-link" href="#students">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
            <a class="nav-link" href="#submissions">
                <i class="bi bi-file-earmark-check-fill"></i>
                <span>Submissions</span>
            </a>
            <a class="nav-link" href="#reports">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Reports</span>
            </a>
            <a class="nav-link" href="#settings">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar"><?= e($lecturerInitials) ?></div>
                <div class="min-w-0">
                    <div class="fw-bold text-truncate"><?= e($lecturerName) ?></div>
                    <small class="text-white-50">Lecturer</small>
                </div>
            </div>
            <a class="text-white-50 small" href="logout.php">Logout</a>
        </div>
    </aside>

    <main class="lecturer-main">
        <header class="topbar">
            <div class="welcome-text">
                <span class="text-muted">Welcome,</span>
                <strong><?= e($lecturerName) ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                <button class="icon-button" type="button" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                </button>
                <div class="profile-chip">
                    <div class="avatar"><?= e($lecturerInitials) ?></div>
                    <div class="profile-meta pe-1">
                        <div class="fw-bold lh-sm"><?= e($lecturerName) ?></div>
                        <small class="text-muted">UTM Lecturer</small>
                    </div>
                </div>
                <a class="icon-button text-decoration-none" href="logout.php" aria-label="Sign out">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </header>

        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4" id="dashboard">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color: var(--lecturer-maroon);">Academic Project Review</h1>
                    <p class="text-muted mb-0">Dashboard Overview</p>
                </div>
            </section>

            <section class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Total Projects</p>
                                <strong class="stat-value"><?= e($totalProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Pending Review</p>
                                <strong class="stat-value text-warning"><?= e($pendingProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Approved</p>
                                <strong class="stat-value text-success"><?= e($approvedProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Assigned Students</p>
                                <strong class="stat-value"><?= e($assignedStudents) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="projectSearch" class="form-control search-control border-start-0" type="search" placeholder="Search projects or students...">
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

            <section id="projects" class="mb-4">
                <?php if (!$projects): ?>
                    <div class="empty-state">
                        <div>
                            <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                            No data available
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4" id="projectGrid">
                        <?php foreach ($projects as $project): ?>
                            <?php
                            $studentNames = array_column($project['students'], 'name');
                            $searchText = strtolower($project['title'] . ' ' . implode(' ', $studentNames));
                            $status = $project['status'];
                            $submittedAt = $project['submitted_at']
                                ? date('d/m/Y', strtotime((string) $project['submitted_at']))
                                : 'No data available';
                            ?>
                            <div class="col-12 col-lg-6 col-xxl-4 project-item"
                                 data-status="<?= e($status) ?>"
                                 data-year="<?= e($project['study_year']) ?>"
                                 data-search="<?= e($searchText) ?>">
                                <article class="project-card">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <span class="status-badge status-<?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
                                        <small class="text-muted"><?= e($project['code']) ?></small>
                                    </div>

                                    <h2 class="project-title mb-2"><?= e($project['title']) ?></h2>
                                    <div class="student-list mb-3">
                                        <strong><?= e(count($studentNames)) ?> Student<?= count($studentNames) === 1 ? '' : 's' ?></strong>
                                        <?php if ($studentNames): ?>
                                            <?php foreach ($studentNames as $index => $studentName): ?>
                                                <div><?= e($index + 1) ?>. <?= e($studentName) ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div>No data available</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="meta-line">Submission Date: <?= e($submittedAt) ?></div>
                                    <div class="meta-line">Study Year: <?= $project['study_year'] !== '' ? e($project['study_year']) : 'No data available' ?></div>

                                    <div class="action-row">
                                        <button class="btn btn-review btn-comment" type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#commentModal"
                                                data-project-id="<?= e($project['id']) ?>"
                                                data-project-title="<?= e($project['title']) ?>">
                                            Comment
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button class="btn btn-review btn-reject" type="submit">Reject</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="btn btn-review btn-approve" type="submit">Approve</button>
                                        </form>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state d-none" id="filteredEmpty">No data available</div>
                <?php endif; ?>
            </section>

            <section class="dashboard-card" id="students">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Assigned Students</h2>
                        <p class="text-muted mb-0">Students attached to your supervised projects</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$projects || !$assignedStudents): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5">No data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <?php foreach ($project['students'] as $student): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e($student['name']) ?></td>
                                            <td><?= e($project['title']) ?></td>
                                            <td><span class="status-badge status-<?= e($project['status']) ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                                            <td><?= $project['submitted_at'] ? e(date('d/m/Y', strtotime((string) $project['submitted_at']))) : 'No data available' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>

<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="commentModalLabel">Add Feedback</h2>
                    <p class="text-muted mb-0 small" id="commentProjectTitle">Project feedback</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="project_id" id="commentProjectId">
                <label class="form-label fw-semibold" for="commentText">Comment</label>
                <textarea class="form-control" id="commentText" name="comment" rows="5" placeholder="Write your feedback or revision notes..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('projectSearch');
    const statusFilter = document.getElementById('statusFilter');
    const yearFilter = document.getElementById('yearFilter');
    const projectItems = Array.from(document.querySelectorAll('.project-item'));
    const filteredEmpty = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        const year = yearFilter?.value || 'all';
        let visible = 0;

        projectItems.forEach((item) => {
            const matchesSearch = !query || item.dataset.search.includes(query);
            const matchesStatus = status === 'all' || item.dataset.status === status;
            const matchesYear = year === 'all' || item.dataset.year === year;
            const shouldShow = matchesSearch && matchesStatus && matchesYear;
            item.classList.toggle('d-none', !shouldShow);
            if (shouldShow) visible++;
        });

        if (filteredEmpty) {
            filteredEmpty.classList.toggle('d-none', visible !== 0);
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    yearFilter?.addEventListener('change', applyFilters);

    const commentModal = document.getElementById('commentModal');
    commentModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        document.getElementById('commentProjectId').value = button?.dataset.projectId || '';
        document.getElementById('commentProjectTitle').textContent = button?.dataset.projectTitle || 'Project feedback';
        document.getElementById('commentText').value = '';
    });
</script>
</body>
</html>
