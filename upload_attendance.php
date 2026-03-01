<?php
session_start();
require "config.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . "/employee_import/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['user'])) {
    die("Login required");
}
$createdBy = $_SESSION['user'];

/* ================= FILE CHECK ================= */
if (!isset($_FILES['attendance_file']) || $_FILES['attendance_file']['error'] !== UPLOAD_ERR_OK) {
    die("No file uploaded");
}

$fileTmp = $_FILES['attendance_file']['tmp_name'];
$fileExt = strtolower(pathinfo($_FILES['attendance_file']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, ['xlsx', 'xls', 'csv'])) {
    die("Invalid file type");
}

/* ================= LOAD FILE ================= */
$spreadsheet = ($fileExt === 'csv')
    ? IOFactory::createReader('Csv')->load($fileTmp)
    : IOFactory::load($fileTmp);

$rows = $spreadsheet->getActiveSheet()->toArray();
unset($rows[0]); // remove header

$processed = 0;
$skipped   = 0;
$errorLog  = [];

/* ================= SQL ================= */
$sql = "
INSERT INTO attendance (
    esic_no,
    attendance_year,
    attendance_month,
    attendance_json,
    backup_attendance_json
)
VALUES (
    :esic_no,
    :attendance_year,
    :attendance_month,
    :attendance_json,
    :backup_attendance_json
)
ON DUPLICATE KEY UPDATE
attendance_year  = VALUES(attendance_year),
attendance_month = VALUES(attendance_month),
attendance_json  = JSON_MERGE_PATCH(
    IFNULL(attendance_json,'{}'),
    VALUES(attendance_json)
)
";
$stmt = $pdo->prepare($sql);

/* ================= BUFFER (IMPORTANT FIX) ================= */
/* Stores all dates per ESIC+MONTH before DB save */
$monthlyBuffer = [];

/* ================= PROCESS EXCEL ================= */
foreach ($rows as $rowIndex => $row) {

    $actualRow = $rowIndex + 1;

    $esic       = trim($row[0] ?? '');
    $excelSite = trim($row[2] ?? '');
    $dateX     = trim($row[3] ?? '');
    $statusRaw = strtolower(trim($row[4] ?? ''));

    if (!$esic || !$dateX || !$statusRaw) {
        continue;
    }

    /* ----- DATE PARSE ----- */
    if (is_numeric($dateX)) {
        $attendance_date = Date::excelToDateTimeObject($dateX)->format('Y-m-d');
    } else {
        $ts = strtotime($dateX);
        if (!$ts) {
            $errorLog[] = "Row $actualRow: Invalid date ($dateX)";
            $skipped++; continue;
        }
        $attendance_date = date('Y-m-d', $ts);
    }

    if ($attendance_date > date('Y-m-d')) {
        $errorLog[] = "Row $actualRow: Future date ($attendance_date)";
        $skipped++; continue;
    }

    $attendance_year  = (int)date('Y', strtotime($attendance_date));
    $attendance_month = (int)date('n', strtotime($attendance_date));

    /* ----- STATUS ----- */
    $statusRaw = preg_replace('/[^a-z]/', '', $statusRaw);
    $statusMap = ['p'=>'P','a'=>'A','pp'=>'PP','l'=>'L'];
    if (!isset($statusMap[$statusRaw])) {
        $errorLog[] = "Row $actualRow: Invalid status ({$row[4]})";
        $skipped++; continue;
    }
    $status = $statusMap[$statusRaw];

    /* ----- EMP CHECK ----- */
    $empStmt = $pdo->prepare("SELECT 1 FROM employee_master WHERE esic_no=?");
    $empStmt->execute([$esic]);
    if (!$empStmt->fetchColumn()) {
        $errorLog[] = "Row $actualRow: ESIC not found ($esic)";
        $skipped++; continue;
    }

    /* ----- SITE CODE ----- */
    $siteStmt = $pdo->prepare("
        SELECT SiteCode FROM site_master
        WHERE UPPER(TRIM(SiteName)) LIKE ?
        LIMIT 1
    ");
    $siteStmt->execute(['%'.strtoupper($excelSite).'%']);
    $siteCode = $siteStmt->fetchColumn();

    if (!$siteCode) {
        $errorLog[] = "Row $actualRow: Site not found ($excelSite)";
        $skipped++; continue;
    }

    /* ----- BUFFER KEY (ESIC + YEAR + MONTH) ----- */
    $bufferKey = $esic . '|' . $attendance_year . '|' . $attendance_month;

    /* ----- LOAD DB JSON ONLY ONCE ----- */
    if (!isset($monthlyBuffer[$bufferKey])) {

        $checkStmt = $pdo->prepare("
            SELECT attendance_json
            FROM attendance
            WHERE esic_no=? AND attendance_year=? AND attendance_month=?
        ");
        $checkStmt->execute([$esic, $attendance_year, $attendance_month]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $monthlyBuffer[$bufferKey] = [];

        if ($existing && $existing['attendance_json']) {
            $decoded = json_decode($existing['attendance_json'], true);
            if (is_array($decoded)) {
                $monthlyBuffer[$bufferKey] = $decoded;
            }
        }
    }

    /* ----- MERGE DATE (CORE LOGIC) ----- */
    $monthlyBuffer[$bufferKey][$attendance_date] = [
        "status" => $status,
        "site_code" => $siteCode,
        "approve_status" => 0,
        "locked" => 1,
        "created_by" => $createdBy,
        "created_at" => date("Y-m-d H:i:s"),
        "updated_by" => null,
        "updated_at" => null
    ];
}

/* ================= SAVE TO DB (ONCE PER MONTH) ================= */
foreach ($monthlyBuffer as $key => $attendanceJson) {

    [$esic, $year, $month] = explode('|', $key);

    $jsonFinal = json_encode($attendanceJson, JSON_UNESCAPED_SLASHES);

    try {
        $stmt->execute([
    ":esic_no" => $esic,
    ":attendance_year" => $year,
    ":attendance_month" => $month,
    ":attendance_json" => $jsonFinal,
    ":backup_attendance_json" => null
]);
        $processed++;
    } catch (Exception $e) {
        $errorLog[] = "DB error for ESIC $esic ($month-$year): ".$e->getMessage();
        $skipped++;
    }
}

/* ================= RESULT ================= */
echo "<h3 style='color:green'>✓ Upload Completed</h3>";
echo "Processed: <b>$processed</b><br>";
echo "Skipped: <b>$skipped</b><br>";

if ($errorLog) {
    echo "<hr><pre>";
    echo implode("\n", array_slice($errorLog, 0, 50));
    echo "</pre>";
}
