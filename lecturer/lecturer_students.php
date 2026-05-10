<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lecturer_logged_in']) || $_SESSION['lecturer_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';

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

$lecturerId     = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName   = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

$students = [];
$flashMessage = '';
$flashType    = 'success';

try {
    // Fetch all unique students assigned to the lecturer's projects
    $stmt = $db->prepare(
        "SELECT DISTINCT
             u.user_id,
             u.name_encrypted,
             s.matric_no,
             s.course,
             s.intake,
             p.project_id,
             p.title_encrypted,
             p.study_year,
             latest.status,
             latest.submitted_at
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         INNER JOIN students s ON s.user_id = u.user_id
         INNER JOIN projects p ON p.project_id = pm.project_id AND p.lecturer_id = ?
         LEFT JOIN LATERAL (
             SELECT status, submitted_at
             FROM submissions
             WHERE submissions.project_id = pm.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         ) latest ON TRUE
         ORDER BY u.user_id ASC, p.project_id ASC"
    );
    $stmt->execute([$lecturerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by student
    $studentMap = [];
    foreach ($rows as $row) {
        $uid = (int) $row['user_id'];
        if (!isset($studentMap[$uid])) {
            $studentMap[$uid] = [
                'id'       => $uid,
                'name'     => decryptValue($row['name_encrypted'] ?? '') ?: 'Unknown',
                'matric'   => (string) ($row['matric_no'] ?? ''),
                'course'   => (string) ($row['course'] ?? ''),
                'intake'   => (string) ($row['intake'] ?? ''),
                'projects' => [],
            ];
        }
        $studentMap[$uid]['projects'][] = [
            'project_id'   => (int) $row['project_id'],
            'title'        => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year'   => $row['study_year'] ?? '',
            'status'       => $row['status'] ?: 'pending',
            'submitted_at' => $row['submitted_at'] ?? null,
        ];
    }
    $students = array_values($studentMap);
} catch (Throwable $error) {
    $flashMessage = 'Unable to load student data.';
    $flashType    = 'danger';
}

$totalStudents  = count($students);
$statusLabels   = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Students – UTM Lecturer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/utm-theme.css">
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
            --lecturer-shadow: 0 16px 38px rgba(28,39,60,.09);
        }
        body { margin:0; min-height:100vh; background:var(--lecturer-bg); color:var(--lecturer-text); font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .lecturer-shell { min-height:100vh; padding-left:270px; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:270px; overflow-y:auto; z-index:30; background:linear-gradient(180deg,#fffcf4 0%,#f8f0df 100%); color:var(--lecturer-maroon); border-right:1px solid rgba(128,0,32,.12); box-shadow:12px 0 32px rgba(84,0,20,.1); }
        .brand { min-height:105px; padding:24px 20px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(128,0,32,.12); }
        .brand-mark { width:64px; height:64px; object-fit:contain; flex:0 0 auto; filter:drop-shadow(0 8px 14px rgba(128,0,32,.14)); }
        .brand-title { margin:0; color:var(--lecturer-maroon); font-weight:800; font-size:1.25rem; line-height:1; }
        .brand-subtitle { color:#7d5b20; font-size:.76rem; line-height:1.35; }
        .sidebar-nav { display:grid; gap:7px; padding:18px 14px; }
        .nav-link { display:flex; align-items:center; gap:12px; min-height:46px; padding:11px 14px; border-radius:12px; color:#6f273a; font-weight:700; text-decoration:none; }
        .nav-link:hover, .nav-link.active { background:var(--lecturer-gold); color:#271700; }
        .sidebar-footer { margin-top:auto; padding:16px 14px 22px; border-top:1px solid rgba(128,0,32,.12); }
        .lecturer-main { min-width:0; }
        .top-navbar { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:28px; padding:18px 24px; position:sticky; top:0; z-index:20; background:rgba(255,255,255,.93); border-bottom:1px solid var(--lecturer-border); backdrop-filter:blur(12px); }
        .icon-button { width:42px; aspect-ratio:1; border-radius:12px; border:1px solid var(--lecturer-border); background:#fff; display:inline-grid; place-items:center; color:var(--lecturer-text); }
        .profile-chip { display:flex; align-items:center; gap:10px; padding:6px 12px 6px 6px; border-radius:999px; border:1px solid var(--lecturer-border); background:#fff; }
        .avatar { width:38px; aspect-ratio:1; border-radius:50%; display:grid; place-items:center; background:#f2e2b8; color:var(--lecturer-maroon); font-weight:800; }
        .content { padding:30px; }
        .dashboard-card, .stat-card { background:var(--lecturer-card); border:1px solid var(--lecturer-border); border-radius:18px; box-shadow:var(--lecturer-shadow); }
        .stat-card { min-height:110px; padding:20px; }
        .stat-icon { width:46px; aspect-ratio:1; border-radius:14px; display:grid; place-items:center; background:rgba(128,0,32,.1); color:var(--lecturer-maroon); font-size:1.35rem; }
        .stat-value { font-size:2rem; line-height:1; font-weight:800; }
        .toolbar { background:#fff; border:1px solid var(--lecturer-border); border-radius:18px; padding:14px; box-shadow:var(--lecturer-shadow); }
        .search-control { min-height:44px; border-radius:12px; border:1px solid var(--lecturer-border); }
        .student-card { background:var(--lecturer-card); border:1px solid var(--lecturer-border); border-radius:16px; padding:22px; box-shadow:var(--lecturer-shadow); transition:transform .2s, box-shadow .2s; }
        .student-card:hover { transform:translateY(-2px); box-shadow:0 24px 48px rgba(28,39,60,.12); }
        .student-name { font-size:1.05rem; font-weight:800; color:var(--lecturer-maroon); margin-bottom:4px; }
        .student-meta { font-size:.875rem; color:var(--lecturer-muted); line-height:1.7; }
        .status-badge { display:inline-flex; align-items:center; min-height:26px; padding:3px 10px; border-radius:999px; font-size:.76rem; font-weight:800; text-transform:uppercase; }
        .status-pending  { background:#fff5d8; color:#856200; }
        .status-approved { background:#e7f6ed; color:#1f7a45; }
        .status-rejected { background:#fdecec; color:#a43131; }
        .progress-table th { color:#5f6878; font-size:.8rem; text-transform:uppercase; background:#f6f8fb; }
        .empty-state { display:grid; place-items:center; min-height:200px; color:var(--lecturer-muted); text-align:center; border:1px dashed var(--lecturer-border); border-radius:16px; background:#fbfcfe; }
        @media (max-width:991.98px) { .lecturer-shell { padding-left:0; } .sidebar { position:static; width:100%; } .content { padding:20px 14px; } }
    </style>
</head>
<body>
<div class="lecturer-shell">
    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column">
        <div class="brand">
            <img class="brand-mark" src="../assets/utm-logo.png" alt="UTM logo"
                 onerror="this.outerHTML='<div style=\'width:64px;height:64px;border-radius:50%;background:rgba(128,0,32,.15);display:flex;align-items:center;justify-content:center;color:#800020;font-weight:800;font-size:18px;\'>UTM</div>'">
            <div>
                <p class="brand-title">UTM</p>
                <div class="brand-subtitle">Universiti Teknologi Malaysia<br>Academic Review</div>
            </div>
        </div>
        <nav class="sidebar-nav" aria-label="Lecturer navigation">
            <a class="nav-link" href="Lecturer_dashboard.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
            <a class="nav-link" href="lecturer_submissions.php"><i class="bi bi-file-earmark-check-fill"></i><span>Submissions</span></a>
            <a class="nav-link active" href="lecturer_students.php"><i class="bi bi-people-fill"></i><span>Students</span></a>
            <a class="nav-link" href="lecturer_grades.php"><i class="bi bi-star-fill"></i><span>Grades</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar"><?= e($lecturerInitials) ?></div>
                <div class="min-w-0">
                    <div class="fw-bold text-truncate"><?= e($lecturerName) ?></div>
                    <small class="text-muted">Lecturer</small>
                </div>
            </div>
            <a class="text-muted small" href="../public/logout.php">Logout</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="lecturer-main">
        <header class="top-navbar">
            <div>
                <span class="text-muted">Welcome,</span>
                <strong> <?= e($lecturerName) ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <div class="profile-chip">
                    <div class="avatar"><?= e($lecturerInitials) ?></div>
                    <div class="d-none d-sm-block pe-1">
                        <div class="fw-bold lh-sm"><?= e($lecturerName) ?></div>
                        <small class="text-muted">UTM Lecturer</small>
                    </div>
                </div>
                <a class="icon-button text-decoration-none" href="../public/logout.php" aria-label="Sign out">
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

            <!-- Header -->
            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--lecturer-maroon);">Assigned Students</h1>
                    <p class="text-muted mb-0">Track student progress and submission status</p>
                </div>
            </section>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Total Students</p>
                                <strong class="stat-value"><?= e($totalStudents) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">With Pending Work</p>
                                <?php
                                $pendingCount = count(array_filter($students, static function($s) {
                                    foreach ($s['projects'] as $p) {
                                        if ($p['status'] === 'pending') return true;
                                    }
                                    return false;
                                }));
                                ?>
                                <strong class="stat-value text-warning"><?= e($pendingCount) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">All Approved</p>
                                <?php
                                $allApproved = count(array_filter($students, static function($s) {
                                    foreach ($s['projects'] as $p) {
                                        if ($p['status'] !== 'approved') return false;
                                    }
                                    return !empty($s['projects']);
                                }));
                                ?>
                                <strong class="stat-value text-success"><?= e($allApproved) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">With Rejections</p>
                                <?php
                                $rejectedCount = count(array_filter($students, static function($s) {
                                    foreach ($s['projects'] as $p) {
                                        if ($p['status'] === 'rejected') return true;
                                    }
                                    return false;
                                }));
                                ?>
                                <strong class="stat-value text-danger"><?= e($rejectedCount) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Search & Filter -->
            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="studentSearch" class="form-control search-control border-start-0"
                                   type="search" placeholder="Search by name, matric no or course…">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select search-control">
                            <option value="all">All Status</option>
                            <option value="pending">Has Pending</option>
                            <option value="approved">All Approved</option>
                            <option value="rejected">Has Rejection</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Student Cards + Progress -->
            <?php if (!$students): ?>
                <div class="empty-state">
                    <div>
                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                        No students assigned to your projects yet.
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4" id="studentGrid">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $statuses = array_column($student['projects'], 'status');
                        $hasRejected = in_array('rejected', $statuses, true);
                        $hasPending  = in_array('pending',  $statuses, true);
                        $allOk       = !$hasPending && !$hasRejected && !empty($statuses);
                        $cardStatus  = $hasRejected ? 'rejected' : ($hasPending ? 'pending' : 'approved');
                        $searchText  = strtolower($student['name'] . ' ' . $student['matric'] . ' ' . $student['course']);
                        ?>
                        <div class="col-12 col-lg-6 student-item"
                             data-status="<?= e($cardStatus) ?>"
                             data-search="<?= e($searchText) ?>">
                            <div class="student-card">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar" style="width:48px;height:48px;font-size:1.1rem;">
                                            <?= e(strtoupper(substr($student['name'], 0, 1))) ?>
                                        </div>
                                        <div>
                                            <div class="student-name"><?= e($student['name']) ?></div>
                                            <div class="student-meta"><?= e($student['matric']) ?> · <?= e($student['course']) ?></div>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?= e($cardStatus) ?>">
                                        <?= $allOk ? 'On Track' : ($hasPending ? 'Pending' : 'Issues') ?>
                                    </span>
                                </div>
                                <div class="student-meta mb-3">
                                    <strong>Intake:</strong> <?= e($student['intake']) ?><br>
                                    <strong>Projects:</strong> <?= e(count($student['projects'])) ?>
                                </div>

                                <!-- Progress Table -->
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 progress-table">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Year</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($student['projects'] as $proj): ?>
                                                <tr>
                                                    <td class="fw-semibold" style="max-width:180px;">
                                                        <span title="<?= e($proj['title']) ?>"><?= e(mb_strimwidth($proj['title'], 0, 30, '…')) ?></span>
                                                    </td>
                                                    <td><?= $proj['study_year'] !== '' ? 'Year ' . e($proj['study_year']) : '—' ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?= e($proj['status']) ?>">
                                                            <?= e($statusLabels[$proj['status']] ?? $proj['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted" style="font-size:.8rem;">
                                                        <?= $proj['submitted_at'] ? e(date('d/m/Y', strtotime((string)$proj['submitted_at']))) : '—' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state d-none mt-4" id="filteredEmpty">No students found matching your filters.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput  = document.getElementById('studentSearch');
    const statusFilter = document.getElementById('statusFilter');
    const items        = Array.from(document.querySelectorAll('.student-item'));
    const emptyDiv     = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query  = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        let visible  = 0;
        items.forEach(item => {
            const matchSearch = !query || item.dataset.search.includes(query);
            const matchStatus = status === 'all' || item.dataset.status === status;
            const show = matchSearch && matchStatus;
            item.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        emptyDiv?.classList.toggle('d-none', visible !== 0);
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
</script>
</body>
</html>
