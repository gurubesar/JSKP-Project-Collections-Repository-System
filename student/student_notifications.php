<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../public/login.php');
    exit;
}

ob_start();
$studentHeaderSkipDashboardData = true;
require_once __DIR__ . '/student_header.php';

$flashMessage = '';
$flashType = 'success';
$notificationAction = $_GET['action'] ?? '';
$notificationId = (int) ($_GET['id'] ?? 0);
$returnUrl = $_GET['return'] ?? basename($_SERVER['PHP_SELF']);

$allowedReturnUrl = $returnUrl;
if (!preg_match('#^(\/|\.\/|\.\.\/|student\/|[A-Za-z0-9_\-\.]+).*#i', $allowedReturnUrl)) {
    $allowedReturnUrl = basename($_SERVER['PHP_SELF']);
}

try {
    if ($notificationAction === 'mark_read' && $notificationId > 0) {
        $update = $db->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND recipient_user_id = ?');
        $update->execute([$notificationId, $studentId]);
        ob_end_clean();
        header('Location: ' . $allowedReturnUrl);
        exit;
    } elseif ($notificationAction === 'mark_all_read') {
        $update = $db->prepare('UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ?');
        $update->execute([$studentId]);
        ob_end_clean();
        header('Location: ' . $allowedReturnUrl);
        exit;
    }
} catch (Throwable $error) {
    $flashMessage = 'Unable to update notifications at this time.';
    $flashType = 'danger';
}

$notifications = [];
try {
    $notifications = fetchStudentNotifications($db, $studentId, 50);
} catch (Throwable $error) {
    $flashMessage = 'Unable to load notifications.';
    $flashType = 'danger';
}
?>
        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--student-sidebar);">Notifications</h1>
                    <p class="text-muted mb-0">Review updates from lecturers and project status changes.</p>
                </div>
                <div>
                    <a href="../student/student_dashboard.php" class="btn btn-sm btn-outline-secondary">Back to dashboard</a>
                    <a href="?action=mark_all_read&return=<?= rawurlencode($allowedReturnUrl) ?>" class="btn btn-sm btn-outline-primary">Mark all read</a>
                </div>
            </section>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <?php if (count($notifications) === 0): ?>
                        <div class="p-4 text-center text-muted">
                            No notifications available.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                $projectTitle = decryptValue($notification['project_title'] ?? '');
                                $senderName = decryptValue($notification['sender_name'] ?? '');
                                ?>
                                <a href="?action=mark_read&id=<?= e($notification['notification_id']) ?>&return=<?= rawurlencode($returnUrl) ?>" class="list-group-item list-group-item-action <?= $notification['is_read'] ? 'bg-light text-muted' : 'bg-light fw-semibold' ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="mb-1"><?= e($notification['message']) ?></p>
                                            <?php if ($projectTitle !== ''): ?>
                                                <small class="text-muted">Project: <?= e($projectTitle) ?></small><br>
                                            <?php endif; ?>
                                            <?php if ($senderName !== ''): ?>
                                                <small class="text-muted">From: <?= e($senderName) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= e(date('d M Y H:i', strtotime($notification['created_at'] ?? ''))) ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </div>
</body>
</html>
