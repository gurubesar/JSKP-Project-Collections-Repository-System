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

// Simple flash helper
function set_flash(string $msg, string $type = 'success'): void
{
    $_SESSION['student_flash'] = $msg;
    $_SESSION['student_flash_type'] = $type;
}

function upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected file is too large to upload.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a file before uploading.',
        UPLOAD_ERR_NO_TMP_DIR => 'Upload failed because the server temporary folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'Upload failed because the server could not save the file.',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
        default => 'Upload failed. Please try again.',
    };
}

function ini_size_to_bytes(string $size): int
{
    $size = trim($size);
    if ($size === '') {
        return 0;
    }

    $unit = strtolower($size[strlen($size) - 1]);
    $value = (float) $size;

    return (int) match ($unit) {
        'g' => $value * 1024 * 1024 * 1024,
        'm' => $value * 1024 * 1024,
        'k' => $value * 1024,
        default => $value,
    };
}

function student_is_project_member(PDO $db, int $studentId, int $projectId): bool
{
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ? AND pm.user_id = ? AND u.role = 'student'"
    );
    $stmt->execute([$projectId, $studentId]);

    return (int) $stmt->fetchColumn() > 0;
}

try {
    if ($action === 'rename_project' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        if (!student_is_project_member($db, $studentId, $projectId)) {
            throw new RuntimeException('You are not allowed to edit this project.');
        }

        $newTitle = trim((string) ($_POST['project_title'] ?? ''));
        if ($newTitle === '') {
            throw new RuntimeException('Project name cannot be empty.');
        }

        if (strlen($newTitle) > 160) {
            throw new RuntimeException('Project name is too long.');
        }

        $stmt = $db->prepare('UPDATE projects SET title_encrypted = ? WHERE project_id = ?');
        $stmt->execute([encryptData($newTitle), $projectId]);

        set_flash('Project name updated.');
        header('Location: ../student/student_project.php?project_id=' . $projectId);
        exit;
    }

    if ($action === 'save_project_details' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        if (!student_is_project_member($db, $studentId, $projectId)) {
            throw new RuntimeException('You are not allowed to edit this project.');
        }

        $newTitle = trim((string) ($_POST['project_title'] ?? ''));
        if ($newTitle === '') {
            throw new RuntimeException('Project name cannot be empty.');
        }

        if (strlen($newTitle) > 160) {
            throw new RuntimeException('Project name is too long.');
        }

        $description = trim((string) ($_POST['project_description'] ?? ''));
        $category = trim((string) ($_POST['project_category'] ?? ''));

        if (strlen($category) > 80) {
            throw new RuntimeException('Project category is too long.');
        }

        if (strlen($description) > 4000) {
            throw new RuntimeException('Project description is too long.');
        }

        $stmt = $db->prepare(
            'UPDATE projects
             SET title_encrypted = ?, description_encrypted = ?, category_encrypted = ?
             WHERE project_id = ?'
        );
        $stmt->execute([
            encryptData($newTitle),
            encryptData($description),
            $category !== '' ? encryptData($category) : null,
            $projectId,
        ]);

        set_flash('Project details saved.');
        header('Location: ../student/student_project.php?project_id=' . $projectId);
        exit;
    }

    if ($action === 'upload_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        if (!student_is_project_member($db, $studentId, $projectId)) {
            throw new RuntimeException('You are not allowed to upload files to this project.');
        }

        if (!isset($_FILES['project_file'])) {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMaxSize = ini_size_to_bytes((string) ini_get('post_max_size'));
            if ($postMaxSize > 0 && $contentLength > $postMaxSize) {
                throw new RuntimeException('The selected file is too large to upload. Maximum size: ' . ini_get('post_max_size') . '.');
            }

            throw new RuntimeException('Please choose a file before uploading.');
        }

        $file = $_FILES['project_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(upload_error_message((int) $file['error']));
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'];
        if (!in_array($ext, $allowedExtensions, true)) {
            throw new RuntimeException('Allowed formats: PDF, Word, PowerPoint, or ZIP.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload failed. Please choose the file again and retry.');
        }

        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origName);
        $destDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destPath = $destDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Unable to move uploaded file');
        }

        $webPath = '/public/uploads/' . $safeName;
        $stmt = $db->prepare('INSERT INTO files (project_id, file_name_encrypted, file_path_encrypted, uploaded_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$projectId, encryptData($origName), encryptData($webPath), $studentId]);

        // mark submission as pending
        $stmt2 = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
        $stmt2->execute([$projectId, 'pending']);

        set_flash('File uploaded successfully.');
        header('Location: ../student/student_project.php?project_id=' . $projectId);
        exit;
    }

    if ($action === 'generate_proposal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($projectId <= 0) throw new RuntimeException('Invalid project');
        if (!student_is_project_member($db, $studentId, $projectId)) {
            throw new RuntimeException('You are not allowed to generate a proposal for this project.');
        }

        $title = trim((string) ($_POST['proposal_title'] ?? 'Project Proposal'));
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

        $safeName = 'project_' . $projectId . '_proposal_' . time() . '.doc';
        $destDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destPath = $destDir . $safeName;
        file_put_contents($destPath, $html);

        $webPath = '/public/uploads/' . $safeName;
        $stmt = $db->prepare('INSERT INTO files (project_id, file_name_encrypted, file_path_encrypted, uploaded_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$projectId, encryptData($safeName), encryptData($webPath), $studentId]);

        // mark submission as pending
        $stmt2 = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
        $stmt2->execute([$projectId, 'pending']);

        set_flash('Proposal generated and saved to project files as a Word document.');
        header('Location: ../student/student_project.php?project_id=' . $projectId);
        exit;
    }

    if ($action === 'delete_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!student_is_project_member($db, $studentId, $projectId)) {
            throw new RuntimeException('You are not allowed to delete files from this project.');
        }

        $fileId = (int) ($_POST['file_id'] ?? 0);
        if ($fileId <= 0) throw new RuntimeException('Invalid file id');

        $stmt = $db->prepare('SELECT f.file_path_encrypted, f.uploaded_by FROM files f WHERE f.file_id = ? AND f.project_id = ?');
        $stmt->execute([$fileId, $projectId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('File not found');
        if ((int) $row['uploaded_by'] !== $studentId) throw new RuntimeException('Not allowed to delete this file');

        $path = decryptData($row['file_path_encrypted']);
        $fsPath = __DIR__ . '/../' . ltrim($path, '/');
        if (is_file($fsPath)) @unlink($fsPath);

        $del = $db->prepare('DELETE FROM files WHERE file_id = ?');
        $del->execute([$fileId]);

        set_flash('File deleted.');
        header('Location: ../student/student_project.php?project_id=' . $projectId);
        exit;
    }

    // Unknown action
    header('Location: ../student/student_dashboard.php');
    exit;
} catch (Throwable $e) {
    set_flash('Error: ' . $e->getMessage(), 'danger');
    header('Location: ../student/student_project.php?project_id=' . $projectId);
    exit;
}
