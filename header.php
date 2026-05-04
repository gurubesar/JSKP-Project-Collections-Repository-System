<?php
$adminName = trim((string) ($_SESSION['user_name'] ?? 'Admin'));
$adminInitial = strtoupper(substr($adminName, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="utm-theme.css">
    <style>
        :root {
            --admin-sidebar: #800020;
            --admin-sidebar-dark: #5f0018;
            --admin-gold: #d6a01d;
            --admin-bg: #f4f7fb;
            --admin-card: #ffffff;
            --admin-border: #e6e9ef;
            --admin-text: #182033;
            --admin-muted: #737b8c;
            --admin-shadow: 0 16px 38px rgba(28, 39, 60, 0.09);
        }

        body {
            margin: 0;
            background: var(--admin-bg);
            color: var(--admin-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .admin-shell {
            min-height: 100vh;
            padding-left: 280px;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            width: 280px;
            overflow-y: auto;
            background: linear-gradient(180deg, #fffcf4 0%, #f8f0df 100%);
            color: var(--admin-sidebar);
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
            color: var(--admin-sidebar);
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

        .sidebar-link:hover,
        .sidebar-link.active {
            background: var(--admin-gold);
            color: #2b1800;
            transform: translateX(2px);
        }

        .top-navbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            min-height: 76px;
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid var(--admin-border);
            backdrop-filter: blur(12px);
        }

        .icon-button {
            width: 42px;
            aspect-ratio: 1;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            display: inline-grid;
            place-items: center;
            background: #fff;
            color: var(--admin-text);
        }

        .profile-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 10px 6px 6px;
            border: 1px solid var(--admin-border);
            border-radius: 999px;
            background: #fff;
        }

        .profile-avatar,
        .student-avatar {
            width: 36px;
            aspect-ratio: 1;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #f1e4c3;
            color: var(--admin-sidebar);
            font-weight: 800;
            overflow: hidden;
        }

        .profile-avatar img,
        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .main-content {
            padding: 28px;
        }

        .stat-card,
        .dashboard-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 18px;
            box-shadow: var(--admin-shadow);
        }

        .stat-card {
            min-height: 138px;
            padding: 22px;
        }

        .stat-icon {
            width: 54px;
            aspect-ratio: 1;
            border-radius: 15px;
            display: grid;
            place-items: center;
            background: rgba(128, 0, 32, 0.1);
            color: var(--admin-sidebar);
            font-size: 1.55rem;
        }

        .stat-value {
            font-size: clamp(1.6rem, 3vw, 2.15rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0;
        }

        .metric-change {
            color: #228349;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .dashboard-card {
            padding: 22px;
        }

        .chart-box {
            min-height: 285px;
        }

        .chart-frame {
            position: relative;
            width: 100%;
            height: 260px;
        }

        .chart-frame canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .table thead th {
            color: #5f6878;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0;
            background: #f6f8fb;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e9f7ef;
            color: #1f7a45;
            font-weight: 700;
            font-size: 0.82rem;
        }

        .empty-state {
            display: grid;
            place-items: center;
            min-height: 160px;
            color: var(--admin-muted);
            text-align: center;
            border: 1px dashed var(--admin-border);
            border-radius: 14px;
            background: #fbfcfe;
        }

        .chart-box .empty-state {
            min-height: 260px;
        }

        @media (max-width: 991.98px) {
            .admin-shell {
                padding-left: 0;
            }

            .sidebar {
                position: static;
                width: 100%;
                max-height: none;
            }

            .sidebar-nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .main-content {
                padding: 20px 14px;
            }
        }

        @media (max-width: 575.98px) {
            .sidebar-nav {
                grid-template-columns: 1fr;
            }

            .top-navbar .welcome-text {
                display: none;
            }
        }
    </style>
</head>
<body>
