<?php
// JSKP Report Export Handler
// Generates a multi-sheet Excel-readable workbook without Composer dependencies.

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function exportReportJsonError(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

function exportReportXml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function exportReportDecrypt(?string $value): string
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

function exportReportProjectCode(int $projectId): string
{
    return 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
}

function exportReportSafeFilename(string $filename): string
{
    $filename = trim(pathinfo($filename, PATHINFO_FILENAME));
    $filename = preg_replace('/[^A-Za-z0-9_\- ]/', '', $filename);
    $filename = trim((string) $filename);

    if ($filename === '') {
        $filename = 'jskp_report_' . date('Ymd_His');
    }

    return str_replace(' ', '_', $filename) . '.xls';
}

function exportReportRows(array $rows): string
{
    $xml = '';
    foreach ($rows as $row) {
        $styleId = $row['_style'] ?? '';
        unset($row['_style']);
        $style = $styleId !== '' ? ' ss:StyleID="' . exportReportXml($styleId) . '"' : '';
        $xml .= "<Row{$style}>";
        foreach ($row as $cell) {
            $merge = '';
            $value = $cell;
            if (is_array($cell)) {
                $value = $cell['value'] ?? '';
                if (!empty($cell['mergeAcross'])) {
                    $merge = ' ss:MergeAcross="' . (int) $cell['mergeAcross'] . '"';
                }
            }
            $type = is_numeric($value) && $value !== '' ? 'Number' : 'String';
            $xml .= '<Cell' . $merge . '><Data ss:Type="' . $type . '">' . exportReportXml($value) . '</Data></Cell>';
        }
        $xml .= '</Row>';
    }

    return $xml;
}

function exportReportWorksheet(string $name, array $rows): string
{
    return '<Worksheet ss:Name="' . exportReportXml(substr($name, 0, 31)) . '"><Table>' . exportReportRows($rows) . '</Table></Worksheet>';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exportReportJsonError(405, 'Method not allowed. Use POST.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || ($input['action'] ?? '') !== 'export') {
    exportReportJsonError(400, 'Invalid request. action parameter required.');
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exportReportJsonError(403, 'Admin authorization required.');
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';

try {
    $generatedAt = date('Y-m-d H:i:s');
    $filename = exportReportSafeFilename((string) ($input['filename'] ?? ''));

    $studentRows = $db->query(
        "SELECT s.user_id, s.matric_no, s.course, s.intake, u.name_encrypted, u.email_encrypted, u.created_at
         FROM students s
         INNER JOIN users u ON u.user_id = s.user_id
         ORDER BY u.created_at DESC, s.student_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $lecturerRows = $db->query(
        "SELECT l.user_id, l.staff_id, l.department, u.name_encrypted, u.email_encrypted, u.created_at
         FROM lecturers l
         INNER JOIN users u ON u.user_id = l.user_id
         ORDER BY u.created_at DESC, l.lecturer_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $projectRows = $db->query(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.lecturer_id, p.study_year, p.created_at,
                lu.name_encrypted AS lecturer_name,
                lu.email_encrypted AS lecturer_email,
                COALESCE(s.latest_status, 'pending') AS latest_status
         FROM projects p
         LEFT JOIN users lu ON lu.user_id = p.lecturer_id
         LEFT JOIN (
             SELECT project_id, status AS latest_status
             FROM submissions
             WHERE submission_id IN (
                 SELECT MAX(submission_id)
                 FROM submissions
                 GROUP BY project_id
             )
         ) s ON s.project_id = p.project_id
         ORDER BY p.created_at DESC, p.project_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $memberRows = $db->query(
        "SELECT pm.project_id, pm.role, u.user_id, u.name_encrypted, u.email_encrypted, u.role AS account_role,
                st.matric_no, st.course, l.staff_id
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         LEFT JOIN students st ON st.user_id = u.user_id
         LEFT JOIN lecturers l ON l.user_id = u.user_id
         ORDER BY pm.project_id ASC, CASE pm.role WHEN 'lecturer' THEN 0 ELSE 1 END, u.user_id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $membersByProject = [];
    foreach ($memberRows as $member) {
        $projectId = (int) ($member['project_id'] ?? 0);
        $membersByProject[$projectId][] = [
            'name' => exportReportDecrypt($member['name_encrypted'] ?? ''),
            'email' => exportReportDecrypt($member['email_encrypted'] ?? ''),
            'role' => (string) ($member['role'] ?? $member['account_role'] ?? ''),
            'matric_no' => (string) ($member['matric_no'] ?? ''),
            'course' => (string) ($member['course'] ?? ''),
            'staff_id' => (string) ($member['staff_id'] ?? ''),
        ];
    }

    $stats = [
        ['Total Students', count($studentRows)],
        ['Total Lecturers', count($lecturerRows)],
        ['Total Projects', count($projectRows)],
        ['Total Project Members', count($memberRows)],
    ];

    $summaryRows = [
        ['_style' => 'Title', [['value' => 'JSKP Project Repository System Report', 'mergeAcross' => 7]]],
        [['value' => 'Generated: ' . $generatedAt, 'mergeAcross' => 7]],
        [''],
        ['_style' => 'Section', [['value' => 'Summary', 'mergeAcross' => 1]]],
        ['_style' => 'Header', 'Metric', 'Value'],
    ];
    foreach ($stats as $row) {
        $summaryRows[] = $row;
    }

    $summaryRows[] = [''];
    $summaryRows[] = ['_style' => 'Section', [['value' => 'Students', 'mergeAcross' => 7]]];
    $summaryRows[] = ['_style' => 'Header', 'Name', 'Email', 'Matric No', 'Course', 'Intake', 'Created At'];
    foreach ($studentRows as $student) {
        $summaryRows[] = [
            exportReportDecrypt($student['name_encrypted'] ?? ''),
            exportReportDecrypt($student['email_encrypted'] ?? ''),
            $student['matric_no'] ?? '',
            $student['course'] ?? '',
            $student['intake'] ?? '',
            $student['created_at'] ?? '',
        ];
    }

    $summaryRows[] = [''];
    $summaryRows[] = ['_style' => 'Section', [['value' => 'Lecturers', 'mergeAcross' => 7]]];
    $summaryRows[] = ['_style' => 'Header', 'Name', 'Email', 'Staff ID', 'Department', 'Created At'];
    foreach ($lecturerRows as $lecturer) {
        $summaryRows[] = [
            exportReportDecrypt($lecturer['name_encrypted'] ?? ''),
            exportReportDecrypt($lecturer['email_encrypted'] ?? ''),
            $lecturer['staff_id'] ?? '',
            $lecturer['department'] ?? '',
            $lecturer['created_at'] ?? '',
        ];
    }

    $summaryRows[] = [''];
    $summaryRows[] = ['_style' => 'Section', [['value' => 'Projects and Team Members', 'mergeAcross' => 7]]];
    $summaryRows[] = ['_style' => 'Header', 'Project Code', 'Project Name', 'Supervisor', 'Study Year', 'Status', 'Member Name', 'Member Role', 'Member ID'];

    $projectSheetRows = [
        ['_style' => 'Title', [['value' => 'Projects and Team Members', 'mergeAcross' => 8]]],
        [['value' => 'Generated: ' . $generatedAt, 'mergeAcross' => 8]],
        [''],
        ['_style' => 'Header', 'Project Code', 'Project Name', 'Description', 'Supervisor', 'Supervisor Email', 'Study Year', 'Status', 'Student Count', 'Team Members'],
    ];

    foreach ($projectRows as $project) {
        $projectId = (int) $project['project_id'];
        $projectCode = exportReportProjectCode($projectId);
        $projectName = exportReportDecrypt($project['title_encrypted'] ?? '') ?: $projectCode;
        $lecturerName = exportReportDecrypt($project['lecturer_name'] ?? '') ?: 'Not assigned';
        $lecturerEmail = exportReportDecrypt($project['lecturer_email'] ?? '');
        $members = $membersByProject[$projectId] ?? [];
        $students = array_values(array_filter($members, static fn($member): bool => strtolower((string) $member['role']) !== 'lecturer'));
        $teamNames = implode(', ', array_map(static fn($member): string => $member['name'], $students));

        if (!$members) {
            $summaryRows[] = [$projectCode, $projectName, $lecturerName, $project['study_year'] ?? '', ucfirst((string) ($project['latest_status'] ?? 'pending')), 'No members assigned', '', ''];
        } else {
            foreach ($members as $member) {
                $summaryRows[] = [
                    $projectCode,
                    $projectName,
                    $lecturerName,
                    $project['study_year'] ?? '',
                    ucfirst((string) ($project['latest_status'] ?? 'pending')),
                    $member['name'] ?: 'Unnamed member',
                    ucfirst((string) $member['role']),
                    $member['matric_no'] ?: $member['staff_id'],
                ];
            }
        }

        $projectSheetRows[] = [
            $projectCode,
            $projectName,
            exportReportDecrypt($project['description_encrypted'] ?? ''),
            $lecturerName,
            $lecturerEmail,
            $project['study_year'] ?? '',
            ucfirst((string) ($project['latest_status'] ?? 'pending')),
            count($students),
            $teamNames ?: 'No students assigned',
        ];
    }

    $studentSheetRows = [
        ['_style' => 'Title', [['value' => 'Students', 'mergeAcross' => 5]]],
        [['value' => 'Generated: ' . $generatedAt, 'mergeAcross' => 5]],
        [''],
        ['_style' => 'Header', 'Name', 'Email', 'Matric No', 'Course', 'Intake', 'Created At'],
    ];
    foreach ($studentRows as $student) {
        $studentSheetRows[] = [
            exportReportDecrypt($student['name_encrypted'] ?? ''),
            exportReportDecrypt($student['email_encrypted'] ?? ''),
            $student['matric_no'] ?? '',
            $student['course'] ?? '',
            $student['intake'] ?? '',
            $student['created_at'] ?? '',
        ];
    }

    $lecturerSheetRows = [
        ['_style' => 'Title', [['value' => 'Lecturers', 'mergeAcross' => 4]]],
        [['value' => 'Generated: ' . $generatedAt, 'mergeAcross' => 4]],
        [''],
        ['_style' => 'Header', 'Name', 'Email', 'Staff ID', 'Department', 'Created At'],
    ];
    foreach ($lecturerRows as $lecturer) {
        $lecturerSheetRows[] = [
            exportReportDecrypt($lecturer['name_encrypted'] ?? ''),
            exportReportDecrypt($lecturer['email_encrypted'] ?? ''),
            $lecturer['staff_id'] ?? '',
            $lecturer['department'] ?? '',
            $lecturer['created_at'] ?? '',
        ];
    }

    if (ob_get_level()) {
        ob_clean();
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<?mso-application progid="Excel.Sheet"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
    echo '<Styles>';
    echo '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16" ss:Color="#800020"/></Style>';
    echo '<Style ss:ID="Section"><Interior ss:Color="#F2A900" ss:Pattern="Solid"/><Font ss:Bold="1" ss:Color="#2B1800"/></Style>';
    echo '<Style ss:ID="Header"><Interior ss:Color="#800020" ss:Pattern="Solid"/><Font ss:Bold="1" ss:Color="#FFFFFF"/></Style>';
    echo '</Styles>';
    echo exportReportWorksheet('Summary', $summaryRows);
    echo exportReportWorksheet('Students', $studentSheetRows);
    echo exportReportWorksheet('Lecturers', $lecturerSheetRows);
    echo exportReportWorksheet('Projects', $projectSheetRows);
    echo '</Workbook>';
    exit;
} catch (Throwable $error) {
    exportReportJsonError(500, 'Export failed: ' . $error->getMessage());
}
