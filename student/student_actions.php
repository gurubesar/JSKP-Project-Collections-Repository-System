<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../public/login.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';
$projectId = (int) ($_REQUEST['project_id'] ?? 0);
$returnTo = (string) ($_REQUEST['return_to'] ?? '');

// Simple flash helper
function set_flash(string $msg, string $type = 'success'): void
{
    $_SESSION['student_flash'] = $msg;
    $_SESSION['student_flash_type'] = $type;
}

function ensureCommentVisibilityTableExists(PDO $db): void
{
    try {
        $db->exec('CREATE TABLE IF NOT EXISTS comment_visibility (
            comment_id INTEGER NOT NULL REFERENCES comments(comment_id),
            user_id INTEGER NOT NULL REFERENCES users(user_id),
            hidden_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (comment_id, user_id)
        )');
    } catch (Throwable $e) {
        // Ignore if the table cannot be created on this database.
    }
}

function createNotification(PDO $db, int $recipientId, ?int $senderId, int $projectId, string $message, string $type = 'project_update'): void
{
    try {
        $stmt = $db->prepare('INSERT INTO notifications (recipient_user_id, sender_user_id, project_id, notification_type, message) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$recipientId, $senderId, $projectId, $type, $message]);
    } catch (PDOException $e) {
        // Notifications are optional; ignore failures if the notifications table is missing or unavailable.
    }
}

function notifyProjectLecturer(PDO $db, int $projectId, int $studentId, string $message): void
{
    $stmt = $db->prepare('SELECT lecturer_id FROM projects WHERE project_id = ? LIMIT 1');
    $stmt->execute([$projectId]);
    $lecturerId = (int) $stmt->fetchColumn();
    if ($lecturerId > 0 && $lecturerId !== $studentId) {
        createNotification($db, $lecturerId, $studentId, $projectId, $message);
    }
}

function ensureStudentOwnsProject(PDO $db, int $projectId, int $studentId): void
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ? AND role = 'student'");
    $stmt->execute([$projectId, $studentId]);

    if ((int) $stmt->fetchColumn() === 0) {
        throw new RuntimeException('You are not assigned to this project.');
    }
}

function redirectAfterStudentProjectAction(int $projectId, string $returnTo, string $anchor = ''): void
{
    if ($returnTo === 'posters') {
        header('Location: ../student/student_posters.php?project_id=' . $projectId);
        exit;
    }

    header('Location: ../student/student_project.php?project_id=' . $projectId . $anchor);
    exit;
}

try {
    if ($action === 'upload_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        if (!isset($_FILES['project_file'])) throw new RuntimeException('No file uploaded');
        ensureStudentOwnsProject($db, $projectId, $studentId);

        $uploadType = (string) ($_POST['upload_type'] ?? 'document');
        $allowedTypes = ['document', 'poster', 'header_photo'];
        if (!in_array($uploadType, $allowedTypes, true)) {
            $uploadType = 'document';
        }

        if ($uploadType === 'poster') {
            $existingPosterStmt = $db->prepare("SELECT COUNT(*) FROM files WHERE project_id = ? AND file_type = 'poster'");
            $existingPosterStmt->execute([$projectId]);
            if ((int) $existingPosterStmt->fetchColumn() > 0) {
                throw new RuntimeException('Please delete the previous poster before uploading a new one.');
            }
        }

        $file = $_FILES['project_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed');

        $maxFileSize = 200 * 1024 * 1024; // 200 MB
        if ($file['size'] > $maxFileSize) {
            throw new RuntimeException('File size exceeds 200 MB limit. Please upload a smaller file.');
        }

        $origName = basename($file['name']);

        $safeName = time() . '_' . $uploadType . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origName);
        $destDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destPath = $destDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Unable to move uploaded file');
        }

        $webPath = '/public/uploads/' . $safeName;
        $stmt = $db->prepare('INSERT INTO files (project_id, file_name_encrypted, file_path_encrypted, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$projectId, encryptData($origName), encryptData($webPath), $uploadType, $studentId]);

        if ($uploadType === 'poster') {
            $uploadLabel = 'poster';
        } elseif ($uploadType === 'header_photo') {
            $uploadLabel = 'header photo';
        } else {
            $uploadLabel = 'file';
        }

        if ($uploadType === 'document') {
            notifyProjectLecturer(
                $db,
                $projectId,
                $studentId,
                sprintf('Student %s uploaded a new file "%s" for project %s.', $_SESSION['user_name'] ?? 'A student', $origName, 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT))
            );

            $stmt2 = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
            $stmt2->execute([$projectId, 'pending']);
        }

        set_flash(ucfirst($uploadLabel) . ' uploaded successfully.');
        redirectAfterStudentProjectAction($projectId, $returnTo, '#uploadBox');
    }

    if ($action === 'generate_proposal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        $title = trim((string) ($_POST['proposal_title'] ?? 'Project Proposal'));
        $proposalFileName = trim((string) ($_POST['proposal_file_name'] ?? ''));
        $projectType = trim((string) ($_POST['proposal_type'] ?? ''));
        $clientName = trim((string) ($_POST['proposal_client'] ?? ''));
        $programmingLanguage = trim((string) ($_POST['proposal_language'] ?? ''));
        $framework = trim((string) ($_POST['proposal_framework'] ?? ''));
        $database = trim((string) ($_POST['proposal_database'] ?? ''));
        $methodology = trim((string) ($_POST['proposal_methodology'] ?? ''));
        $projectAim = trim((string) ($_POST['proposal_aim'] ?? ''));
        $objectives = trim((string) ($_POST['proposal_objectives'] ?? ''));
        $scopes = trim((string) ($_POST['proposal_scopes'] ?? ''));
        $problemDescription = trim((string) ($_POST['proposal_problem_description'] ?? ''));
        $affects = trim((string) ($_POST['proposal_affects'] ?? ''));
        $impact = trim((string) ($_POST['proposal_impact'] ?? ''));
        $successfulSolution = trim((string) ($_POST['proposal_successful_solution'] ?? ''));
        $productFor = trim((string) ($_POST['proposal_product_for'] ?? ''));
        $productWho = trim((string) ($_POST['proposal_product_who'] ?? ''));
        $productName = trim((string) ($_POST['proposal_product_name'] ?? ''));
        $productThat = trim((string) ($_POST['proposal_product_that'] ?? ''));
        $productUnlike = trim((string) ($_POST['proposal_product_unlike'] ?? ''));
        $productOur = trim((string) ($_POST['proposal_product_our'] ?? ''));

        $escape = static fn(string $value): string => nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
        $html .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#111;}table{border-collapse:collapse;width:100%;margin-bottom:20px;}th,td{border:1px solid #333;padding:8px;text-align:left;}th{background:#f0f0f0;}h1,h2,h3{margin:16px 0 8px;}</style></head><body>';
        $html .= '<h1 style="text-align:center;">DSPD 2794 PROJECT</h1>';
        $html .= '<h2 style="text-align:center;">' . htmlspecialchars($title) . '</h2>';
        $html .= '<h3>PROJECT INFORMATION</h3>';
        $html .= '<table><tr><th>Item</th><th>Details</th></tr>';
        $html .= '<tr><td>Project Title</td><td>' . htmlspecialchars($title) . '</td></tr>';
        $html .= '<tr><td>Project Type</td><td>' . htmlspecialchars($projectType) . '</td></tr>';
        $html .= '<tr><td>Client / Organization Name</td><td>' . htmlspecialchars($clientName) . '</td></tr>';
        $html .= '<tr><td>Programming Language</td><td>' . htmlspecialchars($programmingLanguage) . '</td></tr>';
        $html .= '<tr><td>Framework</td><td>' . htmlspecialchars($framework) . '</td></tr>';
        $html .= '<tr><td>Database</td><td>' . htmlspecialchars($database) . '</td></tr>';
        $html .= '<tr><td>Methodology</td><td>' . htmlspecialchars($methodology) . '</td></tr>';
        $html .= '</table>';
        $html .= '<h3>PROJECT AIM / OBJECTIVES / SCOPES</h3>';
        $html .= '<table><tr><th>Item</th><th>Details</th></tr>';
        $html .= '<tr><td>Project Aim</td><td>' . $escape($projectAim) . '</td></tr>';
        $html .= '<tr><td>Objectives</td><td>' . $escape($objectives) . '</td></tr>';
        $html .= '<tr><td>Project Scopes</td><td>' . $escape($scopes) . '</td></tr>';
        $html .= '</table>';
        $html .= '<h3>PROBLEM STATEMENT</h3>';
        $html .= '<table><tr><th>Item</th><th>Description</th></tr>';
        $html .= '<tr><td>The problem of</td><td>' . $escape($problemDescription) . '</td></tr>';
        $html .= '<tr><td>Affects</td><td>' . $escape($affects) . '</td></tr>';
        $html .= '<tr><td>The impact of which</td><td>' . $escape($impact) . '</td></tr>';
        $html .= '<tr><td>A successful solution should be</td><td>' . $escape($successfulSolution) . '</td></tr>';
        $html .= '</table>';
        $html .= '<h3>PRODUCT VISION STATEMENT</h3>';
        $html .= '<table><tr><th>Item</th><th>Description</th></tr>';
        $html .= '<tr><td>For</td><td>' . $escape($productFor) . '</td></tr>';
        $html .= '<tr><td>Who</td><td>' . $escape($productWho) . '</td></tr>';
        $html .= '<tr><td>The ' . htmlspecialchars($productName ?: 'Product') . '</td><td>' . $escape($productName) . '</td></tr>';
        $html .= '<tr><td>That</td><td>' . $escape($productThat) . '</td></tr>';
        $html .= '<tr><td>Unlike</td><td>' . $escape($productUnlike) . '</td></tr>';
        $html .= '<tr><td>Our product</td><td>' . $escape($productOur) . '</td></tr>';
        $html .= '</table>';
        $html .= '<p>Generated by: ' . htmlspecialchars($_SESSION['user_name'] ?? 'Student') . ' on ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '</body></html>';

        $baseFileName = pathinfo($proposalFileName, PATHINFO_FILENAME);
        $baseFileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseFileName);
        $baseFileName = trim($baseFileName, '_');
        if ($baseFileName === '') {
            $baseFileName = 'project_' . $projectId . '_proposal_' . time();
        }
        $safeName = $baseFileName . '.doc';
        $destDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destPath = $destDir . $safeName;
        file_put_contents($destPath, $html);

        $webPath = '/public/uploads/' . $safeName;
        $stmt = $db->prepare('INSERT INTO files (project_id, file_name_encrypted, file_path_encrypted, uploaded_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$projectId, encryptData($safeName), encryptData($webPath), $studentId]);

        notifyProjectLecturer(
            $db,
            $projectId,
            $studentId,
            sprintf('Student %s generated a proposal file "%s" for project %s.', $_SESSION['user_name'] ?? 'A student', $safeName, 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT))
        );

        // mark submission as pending
        $stmt2 = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
        $stmt2->execute([$projectId, 'pending']);

        set_flash('Proposal generated and saved to project files as a Word document.');
        header('Location: ../student/student_project.php?project_id=' . $projectId);
        exit;
    }

    if ($action === 'delete_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $fileId = (int) ($_POST['file_id'] ?? 0);
        if ($fileId <= 0) throw new RuntimeException('Invalid file id');

        ensureStudentOwnsProject($db, $projectId, $studentId);

        $stmt = $db->prepare('SELECT f.file_path_encrypted, f.file_type, f.uploaded_by FROM files f WHERE f.file_id = ? AND f.project_id = ?');
        $stmt->execute([$fileId, $projectId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('File not found');

        $fileType = (string) ($row['file_type'] ?? 'document');
        $isPosterManagerDelete = $returnTo === 'posters' && in_array($fileType, ['poster', 'header_photo'], true);
        if (!$isPosterManagerDelete && (int) $row['uploaded_by'] !== $studentId) {
            throw new RuntimeException('Not allowed to delete this file');
        }

        $path = decryptData($row['file_path_encrypted']);
        $fsPath = __DIR__ . '/../' . ltrim($path, '/');
        if (is_file($fsPath)) @unlink($fsPath);

        $del = $db->prepare('DELETE FROM files WHERE file_id = ?');
        $del->execute([$fileId]);

        notifyProjectLecturer(
            $db,
            $projectId,
            $studentId,
            sprintf('Student %s deleted a file from project %s.', $_SESSION['user_name'] ?? 'A student', 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT))
        );

        set_flash('File deleted.');
        redirectAfterStudentProjectAction($projectId, $returnTo);
    }

    if ($action === 'delete_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        $commentId = (int) ($_POST['comment_id'] ?? 0);
        $deleteMode = trim((string) ($_POST['delete_mode'] ?? 'me'));
        if ($commentId <= 0) throw new RuntimeException('Invalid comment');

        $stmt = $db->prepare('SELECT project_id, user_id FROM comments WHERE comment_id = ? LIMIT 1');
        $stmt->execute([$commentId]);
        $commentRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$commentRow || (int) $commentRow['project_id'] !== $projectId) {
            throw new RuntimeException('Comment not found');
        }

        if ($deleteMode === 'all') {
            if ((int) $commentRow['user_id'] !== $studentId) {
                throw new RuntimeException('Only the message author may delete for all');
            }

            $del = $db->prepare('DELETE FROM comments WHERE comment_id = ?');
            $del->execute([$commentId]);
            $delVis = $db->prepare('DELETE FROM comment_visibility WHERE comment_id = ?');
            $delVis->execute([$commentId]);
            set_flash('Message deleted for all.');
        } else {
            ensureCommentVisibilityTableExists($db);
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $upsert = $db->prepare('INSERT INTO comment_visibility (comment_id, user_id, hidden_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (comment_id, user_id) DO UPDATE SET hidden_at = CURRENT_TIMESTAMP');
            } else {
                $upsert = $db->prepare('INSERT OR REPLACE INTO comment_visibility (comment_id, user_id, hidden_at) VALUES (?, ?, CURRENT_TIMESTAMP)');
            }
            $upsert->execute([$commentId, $studentId]);
            set_flash('Message deleted for you.');
        }

        header('Location: ../student/student_project.php?project_id=' . $projectId . '#feedbackSection');
        exit;
    }

    if ($action === 'post_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        $content = trim((string) ($_POST['comment_content'] ?? ''));
        if ($content === '') throw new RuntimeException('Comment cannot be empty');

        $encrypted = encryptData($content);
        $ins = $db->prepare('INSERT INTO comments (project_id, user_id, content_encrypted) VALUES (?, ?, ?)');
        $ins->execute([$projectId, $studentId, $encrypted]);

        // Notify lecturer about the student reply
        notifyProjectLecturer(
            $db,
            $projectId,
            $studentId,
            sprintf('Student %s replied to a comment on project %s.', $_SESSION['user_name'] ?? 'A student', 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT))
        );

        set_flash('Reply posted. Your lecturer will be notified.');
        header('Location: ../student/student_project.php?project_id=' . $projectId . '#feedbackSection');
        exit;
    }

    // Unknown action
    header('Location: ../student/student_dashboard.php');
    exit;
} catch (Throwable $e) {
    set_flash('Error: ' . $e->getMessage(), 'danger');
    if ($returnTo === 'posters' && $projectId > 0) {
        header('Location: ../student/student_posters.php?project_id=' . $projectId);
        exit;
    }
    header('Location: ../student/student_project.php?project_id=' . $projectId);
    exit;
}
