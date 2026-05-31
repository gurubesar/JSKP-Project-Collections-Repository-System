<?php
// JSKP Report Export Handler
// Exports system statistics to Excel format

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("=== EXPORT REPORT START ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session ID: " . session_id());
error_log("Admin logged in: " . (isset($_SESSION['admin_logged_in']) ? $_SESSION['admin_logged_in'] : 'NO'));

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
error_log("Input: " . json_encode($input));

if (!isset($input['action']) || $input['action'] !== 'export') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request. action parameter required.']);
    exit;
}

// Load dependencies
try {
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        throw new Exception('Vendor autoloader not found');
    }
    
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../database/db.php';
    
    error_log("Dependencies loaded successfully");
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load dependencies: ' . $e->getMessage()]);
    error_log("Dependency load error: " . $e->getMessage());
    exit;
}

// Import PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    error_log("PhpSpreadsheet classes imported and ready");
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to import PhpSpreadsheet: ' . $e->getMessage()]);
    error_log("Import error: " . $e->getMessage());
    exit;
}

// Temporary: Allow export without auth for testing
// TODO: Re-enable authentication after testing
// if (!(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)) {
//     http_response_code(403);
//     header('Content-Type: application/json');
//     echo json_encode(['error' => 'Admin authorization required']);
//     exit;
// }

try {
    error_log("Starting Excel generation...");
    
    // Query statistics from database
    $stats = [
        'total_students' => 0,
        'total_lecturers' => 0,
        'total_repositories' => 0,
    ];
    
    // Get student count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM students");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_students'] = intval($row['total'] ?? 0);
    
    // Get lecturer count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM lecturers");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_lecturers'] = intval($row['total'] ?? 0);
    
    // Get project count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM projects");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_repositories'] = intval($row['total'] ?? 0);
    
    error_log("Stats retrieved: " . json_encode($stats));
    
    // Create spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Report');
    
    error_log("Spreadsheet created");
    
    // Add title
    $sheet->setCellValue('A1', 'JSKP Project Repository System Report');
    $sheet->mergeCells('A1:B1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('1')->setRowHeight(25);
    
    // Add generated timestamp
    $sheet->setCellValue('A2', 'Generated: ' . date('Y-m-d H:i:s'));
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
    $sheet->getRowDimension('2')->setRowHeight(18);
    
    // Empty row
    $sheet->getRowDimension('3')->setRowHeight(5);
    
    // Headers
    $sheet->setCellValue('A4', 'Metric');
    $sheet->setCellValue('B4', 'Value');
    $sheet->getStyle('A4:B4')->getFont()->setBold(true);
    $sheet->getStyle('A4:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('4')->setRowHeight(20);
    
    // Data rows
    $sheet->setCellValue('A5', 'Total Students');
    $sheet->setCellValue('B5', $stats['total_students']);
    
    $sheet->setCellValue('A6', 'Total Lecturers');
    $sheet->setCellValue('B6', $stats['total_lecturers']);
    
    $sheet->setCellValue('A7', 'Total Repositories');
    $sheet->setCellValue('B7', $stats['total_repositories']);
    
    // Auto-size columns
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(15);
    
    // Set number format for values
    $sheet->getStyle('B5:B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    error_log("Excel content added");
    
    // Prepare download
    $filename = 'jskp_summary_report_' . date('Ymd') . '.xlsx';
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Set headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    error_log("Headers sent, writing file...");
    
    // Write file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    error_log("=== EXPORT REPORT SUCCESS ===");
    exit;

} catch (Throwable $e) {
    error_log("=== EXPORT REPORT ERROR ===");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    error_log("Trace: " . $e->getTraceAsString());
    
    // Try to send error response
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    
    echo json_encode([
        'error' => 'Export failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    exit;
}
