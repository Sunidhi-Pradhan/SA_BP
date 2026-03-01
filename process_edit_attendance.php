<?php
session_start();
require "config.php";

error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . "/employee_import/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}
$requestBy = $_SESSION['user'];

/* ================= FILE CHECK ================= */
if (!isset($_FILES['edit_file']) || $_FILES['edit_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$fileTmp = $_FILES['edit_file']['tmp_name'];
$fileExt = strtolower(pathinfo($_FILES['edit_file']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload .xlsx, .xls, or .csv']);
    exit;
}

/* ================= ENSURE ID COLUMN EXISTS ================= */
$checkCol = $pdo->query("SHOW COLUMNS FROM attendance_edit_request LIKE 'id'");
if ($checkCol->rowCount() === 0) {
    // Drop any existing primary key first
    try { $pdo->exec("ALTER TABLE attendance_edit_request DROP PRIMARY KEY"); } catch (Exception $e) {}
    // Add id column as auto-increment primary key
    $pdo->exec("ALTER TABLE attendance_edit_request ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");
}

/* ================= LOAD FILE ================= */
$spreadsheet = ($fileExt === 'csv')
    ? IOFactory::createReader('Csv')->load($fileTmp)
    : IOFactory::load($fileTmp);

$rows = $spreadsheet->getActiveSheet()->toArray();
unset($rows[0]); // Remove header row

$inserted = 0;
$skipped  = 0;
$errorLog = [];

/* ================= PREPARE INSERT ================= */
$insertStmt = $pdo->prepare("
    INSERT INTO attendance_edit_request
    (empcode, empname, attendance_date, new_status, reason_for_update, request_by, request_time, status)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')
");

/* ================= PROCESS ROWS ================= */
foreach ($rows as $rowIndex => $row) {

    $actualRow = $rowIndex + 1;

    $empcode   = trim($row[0] ?? '');
    $empname   = trim($row[1] ?? '');
    $dateX     = trim($row[2] ?? '');
    $statusRaw = strtolower(trim($row[3] ?? ''));
    $reason    = trim($row[4] ?? '');

    // Skip completely empty rows
    if (!$empcode && !$empname && !$dateX) continue;

    /* ----- Required fields ----- */
    if (!$empcode || !$empname || !$dateX || !$statusRaw) {
        $errorLog[] = "Row $actualRow: All fields are mandatory (empcode, empname, date, status)";
        $skipped++;
        continue;
    }

    if (!$reason) {
        $errorLog[] = "Row $actualRow: Reason for update is mandatory";
        $skipped++;
        continue;
    }

    /* ----- Validate empcode exists in employee_master ----- */
    $empStmt = $pdo->prepare("SELECT employee_name FROM employee_master WHERE esic_no = ?");
    $empStmt->execute([$empcode]);
    $empRecord = $empStmt->fetch(PDO::FETCH_ASSOC);

    if (!$empRecord) {
        $errorLog[] = "Row $actualRow: Employee code '$empcode' not found in Employee Master";
        $skipped++;
        continue;
    }

    /* ----- Validate empname matches employee_master ----- */
    $dbName = trim($empRecord['employee_name']);
    if (strtolower($dbName) !== strtolower($empname)) {
        $errorLog[] = "Row $actualRow: Employee name '$empname' does not match Employee Master for empcode $empcode (expected: '$dbName')";
        $skipped++;
        continue;
    }

    /* ----- Parse date ----- */
    if (is_numeric($dateX)) {
        $attendance_date = Date::excelToDateTimeObject($dateX)->format('Y-m-d');
    } else {
        $ts = strtotime($dateX);
        if (!$ts) {
            $errorLog[] = "Row $actualRow: Invalid date format ($dateX)";
            $skipped++;
            continue;
        }
        $attendance_date = date('Y-m-d', $ts);
    }

    /* ----- Validate status ----- */
    $statusClean = preg_replace('/[^a-z]/', '', $statusRaw);
    $statusMap   = ['p' => 'P', 'a' => 'A', 'pp' => 'PP', 'l' => 'L'];
    if (!isset($statusMap[$statusClean])) {
        $errorLog[] = "Row $actualRow: Invalid status '{$row[3]}'. Use P (Present), A (Absent), PP (Present with Extra), L (Leave)";
        $skipped++;
        continue;
    }
    $newStatus = $statusMap[$statusClean];

    /* ----- Insert into attendance_edit_request ----- */
    try {
        $insertStmt->execute([$empcode, $empname, $attendance_date, $newStatus, $reason, $requestBy]);
        $inserted++;
    } catch (Exception $e) {
        $errorLog[] = "Row $actualRow: Database error - " . $e->getMessage();
        $skipped++;
    }
}

/* ================= RESULT ================= */
if ($inserted > 0 && empty($errorLog)) {
    echo json_encode([
        'success'  => true,
        'message'  => "$inserted request(s) submitted for admin approval.",
        'inserted' => $inserted,
        'skipped'  => $skipped
    ]);
} elseif ($inserted > 0) {
    echo json_encode([
        'success'  => true,
        'message'  => "$inserted request(s) submitted. $skipped row(s) skipped with errors.",
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'errors'   => array_slice($errorLog, 0, 20)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "No requests submitted. $skipped row(s) had errors.",
        'errors'  => array_slice($errorLog, 0, 20)
    ]);
}
