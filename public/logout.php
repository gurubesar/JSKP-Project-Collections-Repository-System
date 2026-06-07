<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../database/db.php';

secure_session_start();
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
if ($userId) {
    audit_log($db, $userId, 'Logout', 'User logged out');
}
destroy_secure_session();
header('Location: login.php');
exit;
