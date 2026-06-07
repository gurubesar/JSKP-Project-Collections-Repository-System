<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/security.php';

require_role(['admin']);

$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1));

require __DIR__ . '/admin_header.php';
?>

<div class="admin-shell">
    <?php require __DIR__ . '/admin_sidebar.php'; ?>

    <main>
        <header class="top-navbar d-flex align-items-center justify-content-between px-3 px-lg-4">
            <div class="welcome-text">
                <span class="text-muted">Welcome,</span>
                <strong><?= h($adminName) ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                <button class="icon-button" type="button" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                </button>
                <div class="profile-chip">
                    <div class="profile-avatar"><?= h($adminInitial) ?></div>
                    <div class="d-none d-sm-block pe-1">
                        <div class="fw-bold lh-sm"><?= h($adminName) ?></div>
                    </div>
                </div>
            </div>
        </header>

        <section class="main-content">
            <div class="empty-state" style="min-height: 400px;">
                <div style="text-align: center;">
                    <i class="bi bi-gear-fill" style="font-size: 64px; color: var(--admin-gold); margin-bottom: 24px;"></i>
                    <h2 style="color: var(--admin-sidebar); margin-bottom: 12px;">System Settings</h2>
                    <p style="color: var(--admin-muted); margin-bottom: 24px;">This feature is coming soon. Stay tuned!</p>
                    <p style="color: #999; font-size: 0.9rem;">Configure system settings and preferences.</p>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
