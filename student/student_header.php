<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';

require_role(['student']);

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
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

$studentId = (int) ($_SESSION['user_id'] ?? 0);
$studentName = trim((string) ($_SESSION['user_name'] ?? 'Student'));

$projects = [];
try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.category_encrypted, p.study_year, p.created_at,
                u.name_encrypted AS lecturer_name,
                (SELECT s.status FROM submissions s WHERE s.project_id = p.project_id ORDER BY s.submitted_at DESC LIMIT 1) AS submission_status,
                (SELECT s.submitted_at FROM submissions s WHERE s.project_id = p.project_id ORDER BY s.submitted_at DESC LIMIT 1) AS submitted_at
         FROM project_members pm
         JOIN projects p ON pm.project_id = p.project_id
         LEFT JOIN users u ON p.lecturer_id = u.user_id
         WHERE pm.user_id = ?
         ORDER BY COALESCE(
             (SELECT s.submitted_at FROM submissions s WHERE s.project_id = p.project_id ORDER BY s.submitted_at DESC LIMIT 1),
             p.created_at
         ) DESC"
    );
    $stmt->execute([$studentId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    $projects = [];
}

$summary = [
    'total' => count($projects),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];

foreach ($projects as $project) {
    $status = $project['submission_status'] ?: 'pending';
    if ($status === 'approved') {
        $summary['approved']++;
    } elseif ($status === 'rejected') {
        $summary['rejected']++;
    } else {
        $summary['pending']++;
    }
}

$statusLabel = static function (string $status): string {
    return match ($status) {
        'approved' => 'Approved',
        'rejected' => 'Needs Revision',
        default => 'Pending Review',
    };
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-pending',
    };
};

$statusProgress = static function (string $status): int {
    return match ($status) {
        'approved' => 100,
        'rejected' => 70,
        default => 45,
    };
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/utm-theme.css">
    <style>
        :root {
            --student-sidebar: #800020;
            --student-gold: #d6a01d;
            --student-bg: #f4f7fb;
            --student-card: #ffffff;
            --student-border: #e6e9ef;
            --student-text: #182033;
            --student-muted: #737b8c;
            --student-shadow: 0 16px 38px rgba(28, 39, 60, 0.09);
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--student-bg);
            color: var(--student-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .admin-shell {
            min-height: 100vh;
            padding-left: 280px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            width: 280px;
            overflow-y: auto;
            background: linear-gradient(180deg, #fffcf4 0%, #f8f0df 100%);
            color: var(--student-sidebar);
            border-right: 1px solid rgba(128, 0, 32, 0.12);
            box-shadow: 12px 0 32px rgba(86, 0, 22, 0.1);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 106px;
            padding: 24px 22px;
            border-bottom: 1px solid rgba(128, 0, 32, 0.12);
        }

        .utm-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex: 0 0 auto;
            filter: drop-shadow(0 8px 14px rgba(128, 0, 32, 0.14));
        }

        .brand-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--student-sidebar);
        }

        .brand-subtitle {
            display: block;
            color: #7d5b20;
            font-size: 0.76rem;
            line-height: 1.3;
        }

        .sidebar-nav {
            display: grid;
            gap: 6px;
            padding: 18px 14px 28px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 46px;
            padding: 11px 14px;
            border-radius: 12px;
            color: #6f273a;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .sidebar-link span {
            flex: 1;
            min-width: 0;
        }

        .sidebar-link:hover {
            background: #f0d982;
            color: #2b1800;
            transform: translateX(2px);
        }

        .sidebar-link.active {
            background: var(--student-sidebar);
            color: #ffffff;
            transform: none;
        }

        .sidebar-link.active:hover {
            background: var(--student-sidebar);
            color: #ffffff;
            transform: none;
        }

        .main-content {
            padding: 28px;
            min-height: 100vh;
        }

        .project-actions .btn {
            min-width: 155px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary {
            background: var(--student-gold);
            color: #2b1800;
            border-color: transparent;
        }

        .btn-secondary:hover {
            background: #c99408;
        }

        .top-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
            padding: 18px 24px;
            border-bottom: 1px solid var(--student-border);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 1050;
        }

        .top-navbar .welcome-text {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .top-navbar h1 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .top-navbar p {
            margin: 0;
            color: var(--student-muted);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .icon-button {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            border: 1px solid var(--student-border);
            background: #fff;
            color: var(--student-sidebar);
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .icon-button:hover {
            background: rgba(242, 169, 0, 0.08);
            transform: translateY(-1px);
        }

        .profile-chip {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 999px;
            border: 1px solid var(--student-border);
            background: #fff;
            box-shadow: var(--student-shadow);
        }

        .profile-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #f1e4c3;
            color: var(--student-sidebar);
            font-weight: 800;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            padding: 22px;
            border-radius: 18px;
            background: var(--student-card);
            border: 1px solid var(--student-border);
            box-shadow: var(--student-shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 46px rgba(28, 39, 60, 0.15);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }

        .stat-card-total .stat-icon { background: rgba(128, 0, 32, 0.12); color: var(--student-sidebar); }
        .stat-card-pending .stat-icon { background: rgba(255, 193, 7, 0.15); color: #a16a15; }
        .stat-card-approved .stat-icon { background: rgba(76, 175, 80, 0.15); color: #25732a; }
        .stat-card-rejected .stat-icon { background: rgba(244, 67, 54, 0.15); color: #a1271d; }

        .stat-content h3 {
            margin: 0 0 6px;
            font-size: 0.92rem;
            color: var(--student-muted);
            font-weight: 600;
        }

        .stat-content strong {
            display: block;
            font-size: 1.8rem;
            color: var(--student-text);
        }

        .projects-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 28px;
        }

        .project-card {
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 24px;
            border-radius: 28px;
            background: var(--student-card);
            border: 1px solid var(--student-border);
            box-shadow: var(--student-shadow);
            min-width: 0;
        }

        .project-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 14px;
        }

        .project-title-section {
            flex: 1;
            min-width: 0;
        }

        .project-card h2 {
            margin: 0 0 8px;
            font-size: 1.35rem;
            color: var(--student-sidebar);
        }

        .project-meta-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.9rem;
            color: var(--student-muted);
        }

        .project-code,
        .project-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .project-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .status-approved { background: rgba(76, 175, 80, 0.12); color: #25732a; }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: #a16a15; }
        .status-rejected { background: rgba(244, 67, 54, 0.12); color: #a1271d; }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            color: var(--student-muted);
        }

        .project-supervisor {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(128, 0, 32, 0.06);
            border-radius: 12px;
            color: var(--student-text);
            font-size: 0.95rem;
        }

        .project-supervisor i {
            font-size: 1.1rem;
            color: var(--student-sidebar);
        }

        .project-description {
            margin: 0;
            color: var(--student-muted);
            line-height: 1.75;
        }

        .project-footer {
            color: var(--student-muted);
            font-size: 0.95rem;
            margin-top: 10px;
        }

        .project-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-secondary {
            background: var(--student-gold);
            color: #2b1800;
        }

        .activity-panel {
            padding: 22px;
            border-radius: 22px;
            background: var(--student-card);
            border: 1px solid var(--student-border);
            box-shadow: var(--student-shadow);
        }

        .activity-header {
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--student-border);
        }

        .activity-panel h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--student-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-panel h3 i {
            color: var(--student-sidebar);
            font-size: 1.25rem;
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 10px;
        }

        .activity-list li {
            padding: 14px;
            border-radius: 12px;
            background: #faf8f5;
            border: 1px solid var(--student-border);
            color: var(--student-text);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            transition: background 0.2s ease;
        }

        .activity-list li:hover {
            background: rgba(128, 0, 32, 0.04);
        }

        .activity-list li i {
            color: var(--student-sidebar);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .activity-list li span {
            flex: 1;
        }

        @media (max-width: 1140px) {

        .project-actions .btn {
            gap: 8px;
            padding: 10px 16px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .project-actions .btn:hover {
            transform: translateY(-1px);
        }

        .project-actions .btn-primary {
            background: var(--student-sidebar);
            color: #fff;
            border: none;
        }

        .project-actions .btn-primary:hover {
            background: #5e0016;
            box-shadow: 0 8px 18px rgba(128, 0, 32, 0.25);
        }

        .project-actions .btn-secondary {
            background: var(--student-gold);
            color: #2b1800;
            border: none;
        }

        .project-actions .btn-secondary:hover {
            background: #c99408;
            box-shadow: 0 8px 18px rgba(201, 148, 8, 0.25);
        }
            .student-shell {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 840px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .project-meta {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-navbar {
                flex-direction: column;
                align-items: stretch;
            }

            .profile-chip {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <?php require __DIR__ . '/student_sidebar.php'; ?>

        <main class="main-content">
            <header class="top-navbar d-flex align-items-center justify-content-between px-3 px-lg-4">
                <div class="welcome-text">
                    <span class="text-muted">Welcome,</span>
                    <strong><?= e($studentName) ?></strong>
                </div>
                <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                    <button type="button" class="icon-button" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                    </button>
                    <div class="profile-chip">
                        <div class="profile-avatar"><?= e(strtoupper(substr($studentName, 0, 1))) ?></div>
                        <div class="d-none d-sm-block pe-1">
                            <div class="fw-bold lh-sm"><?= e($studentName) ?></div>
                            <small class="text-muted">UTM Student</small>
                        </div>
                    </div>
                    <a href="../public/logout.php" class="icon-button text-decoration-none" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>
