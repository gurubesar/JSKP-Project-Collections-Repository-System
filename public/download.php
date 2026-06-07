<?php

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/file_storage.php';

require_auth();

$fileId = (int) ($_GET['file_id'] ?? 0);
if ($fileId <= 0) {
    http_response_code(404);
    exit('File not found.');
}

$stmt = $db->prepare(
    'SELECT f.file_id, f.project_id, f.file_name_encrypted, f.file_path_encrypted
     FROM files f
     WHERE f.file_id = ?
     LIMIT 1'
);
$stmt->execute([$fileId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file || !can_access_project($db, (int) $_SESSION['user_id'], (string) $_SESSION['user_role'], (int) $file['project_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$downloadName = decryptData($file['file_name_encrypted']);
$storedPath = decryptData($file['file_path_encrypted']);
$absolutePath = resolve_secure_file_path($storedPath);

audit_log($db, (int) $_SESSION['user_id'], 'Repository Download', 'Downloaded file ' . $fileId . ' from project ' . (int) $file['project_id']);

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($downloadName)) . '"');
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;

