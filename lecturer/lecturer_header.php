<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lecturer_logged_in']) || $_SESSION['lecturer_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('decryptValue')) {
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
}

if (!function_exists('lecturerOwnsProject')) {
    function lecturerOwnsProject(PDO $db, int $lecturerId, int $projectId): bool
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM projects WHERE project_id = ? AND lecturer_id = ?');
        $stmt->execute([$projectId, $lecturerId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('updateSubmissionStatus')) {
    function updateSubmissionStatus(PDO $db, int $projectId, string $status): void
    {
        $insert = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
        $insert->execute([$projectId, $status]);
    }
}

$lecturerId = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

if (empty($lecturerHeaderSkipDashboardData)) {
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
                s.submission_id, s.submitted_at, s.status
         FROM projects p
         LEFT JOIN submissions s ON s.submission_id = (
             SELECT submission_id FROM submissions
             WHERE project_id = p.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         )
         WHERE p.lecturer_id = ?
         ORDER BY COALESCE(s.submitted_at, p.created_at) DESC"
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Academic Project Review</title>
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
            background: linear-gradient(180deg, #fffcf4 0%, #f8f0df 100%);
            color: var(--lecturer-maroon);
            border-right: 1px solid rgba(128, 0, 32, 0.12);
            box-shadow: 12px 0 32px rgba(84, 0, 20, 0.1);
        }

        .brand {
            min-height: 105px;
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(128, 0, 32, 0.12);
        }

        .brand-mark {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex: 0 0 auto;
            filter: drop-shadow(0 8px 14px rgba(128, 0, 32, 0.14));
        }

        .brand-title {
            margin: 0;
            color: var(--lecturer-maroon);
            font-weight: 800;
            font-size: 1.25rem;
            line-height: 1;
        }

        .brand-subtitle {
            color: #7d5b20;
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
            color: #6f273a;
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
            border-top: 1px solid rgba(128, 0, 32, 0.12);
        }

        .lecturer-main {
            min-width: 0;
        }

        .top-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
            padding: 18px 24px;
            position: sticky;
            top: 0;
            z-index: 20;
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

        .student-card,
        .grade-card {
            background: var(--lecturer-card);
            border: 1px solid var(--lecturer-border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: var(--lecturer-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .student-card:hover,
        .grade-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 48px rgba(28, 39, 60, 0.12);
        }

        .student-name {
            color: var(--lecturer-maroon);
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .student-meta {
            color: var(--lecturer-muted);
            font-size: 0.875rem;
            line-height: 1.7;
        }

        .progress-table th {
            color: #5f6878;
            font-size: 0.8rem;
            text-transform: uppercase;
            background: #f6f8fb;
        }

        .files-section {
            margin-top: 14px;
            padding: 14px;
            background: #f8f9fc;
            border: 1px solid var(--lecturer-border);
            border-radius: 12px;
        }

        .files-section h6 {
            color: var(--lecturer-maroon);
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #fff;
            border: 1px solid var(--lecturer-border);
            border-radius: 9px;
        }

        .file-item:last-child {
            margin-bottom: 0;
        }

        .file-name {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            color: var(--lecturer-text);
            font-size: 0.875rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-meta {
            color: var(--lecturer-muted);
            font-size: 0.78rem;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 32px;
            padding: 4px 12px;
            border: 0;
            border-radius: 8px;
            background: var(--lecturer-maroon);
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
        }

        .btn-download:hover {
            background: var(--lecturer-maroon-dark);
            color: #fff;
        }

        .grade-card {
            display: flex;
            flex-direction: column;
        }

        .grade-badge {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.4rem;
            font-weight: 900;
        }

        .grade-A\+,
        .grade-A {
            background: #e7f6ed;
            color: #1f7a45;
        }

        .grade-A- {
            background: #eaf7f0;
            color: #2e8a50;
        }

        .grade-B\+,
        .grade-B {
            background: #eef4ff;
            color: #2563eb;
        }

        .grade-B- {
            background: #f0f5ff;
            color: #3b75f0;
        }

        .grade-C\+,
        .grade-C,
        .grade-C- {
            background: #fff8e1;
            color: #b45309;
        }

        .grade-D {
            background: #fff0e0;
            color: #c2540a;
        }

        .grade-F {
            background: #fdecec;
            color: #a43131;
        }

        .grade-ungraded {
            background: #f1f3f7;
            color: #6e7686;
            font-size: 1rem;
        }

        .mark-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .mark-label {
            width: 120px;
            flex-shrink: 0;
            color: var(--lecturer-muted);
            font-size: 0.78rem;
        }

        .mark-bar-wrap {
            flex: 1;
            height: 8px;
            overflow: hidden;
            background: #f1f3f7;
            border-radius: 99px;
        }

        .mark-bar {
            height: 100%;
            background: var(--lecturer-maroon);
            border-radius: 99px;
            transition: width 0.4s ease;
        }

        .mark-score {
            min-width: 38px;
            color: var(--lecturer-text);
            font-size: 0.78rem;
            font-weight: 700;
            text-align: right;
        }

        .btn-assign,
        .btn-view {
            min-height: 38px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .btn-assign {
            border: 1px solid var(--lecturer-gold);
            background: var(--lecturer-gold);
            color: #271700;
        }

        .btn-assign:hover {
            border-color: #c49218;
            background: #c49218;
            color: #fff;
        }

        .btn-view {
            border: 1px solid var(--lecturer-border);
            background: #fff;
            color: var(--lecturer-text);
        }

        .btn-view:hover {
            border-color: var(--lecturer-maroon);
            color: var(--lecturer-maroon);
        }

        .modal-content {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.18);
        }

        .modal-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--lecturer-border);
        }

        .modal-body {
            padding: 20px 24px;
        }

        .modal-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--lecturer-border);
        }

        .mark-input-group label {
            margin-bottom: 6px;
            color: var(--lecturer-text);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .mark-input-group input {
            padding: 10px 12px;
            border: 1.5px solid var(--lecturer-border);
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .mark-input-group input:focus {
            border-color: var(--lecturer-maroon);
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
            outline: none;
        }

        .mark-cap {
            margin-top: 3px;
            color: var(--lecturer-muted);
            font-size: 0.75rem;
        }

        .total-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            padding: 14px 18px;
            background: #f8f9fc;
            border: 1px solid var(--lecturer-border);
            border-radius: 12px;
        }

        .total-preview .tp-label {
            color: var(--lecturer-muted);
            font-size: 0.85rem;
        }

        .total-preview .tp-value {
            color: var(--lecturer-maroon);
            font-size: 1.5rem;
            font-weight: 900;
        }

        .total-preview .tp-grade {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 800;
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

            .top-navbar {
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
    <?php require __DIR__ . '/lecturer_sidebar.php'; ?>

    <main class="lecturer-main">
        <header class="top-navbar d-flex align-items-center justify-content-between px-3 px-lg-4">
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
