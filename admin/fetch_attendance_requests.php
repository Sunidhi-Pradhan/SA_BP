<?php
session_start();
require "../config.php";

header('Content-Type: application/json');

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

/* ================= ENSURE ID COLUMN EXISTS ================= */
$checkCol = $pdo->query("SHOW COLUMNS FROM attendance_edit_request LIKE 'id'");
if ($checkCol->rowCount() === 0) {
    try { $pdo->exec("ALTER TABLE attendance_edit_request DROP PRIMARY KEY"); } catch (Exception $e) {}
    $pdo->exec("ALTER TABLE attendance_edit_request ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");
}

$tab = $_GET['tab'] ?? 'pending';

/* ================= FETCH REQUESTS ================= */
if ($tab === 'pending') {
    $stmt = $pdo->query("
        SELECT * FROM attendance_edit_request
        WHERE status = 'pending'
        ORDER BY request_time DESC
    ");
} else {
    $stmt = $pdo->query("
        SELECT * FROM attendance_edit_request
        WHERE status IN ('approved', 'rejected')
        ORDER BY approved_time DESC
    ");
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= COUNTS ================= */
$pendingCount   = $pdo->query("SELECT COUNT(*) FROM attendance_edit_request WHERE status = 'pending'")->fetchColumn();
$processedCount = $pdo->query("SELECT COUNT(*) FROM attendance_edit_request WHERE status IN ('approved', 'rejected')")->fetchColumn();

/* ================= LOOKUP CURRENT STATUS ================= */
$statusNames = ['P' => 'Present', 'A' => 'Absent', 'PP' => 'Present With Extra', 'L' => 'Leave'];

foreach ($rows as &$row) {
    $row['current_status'] = '';
    $attDate = $row['attendance_date'];
    $year  = (int)date('Y', strtotime($attDate));
    $month = (int)date('n', strtotime($attDate));

    $attStmt = $pdo->prepare("
        SELECT attendance_json FROM attendance
        WHERE esic_no = ? AND attendance_year = ? AND attendance_month = ?
    ");
    $attStmt->execute([$row['empcode'], $year, $month]);
    $attRow = $attStmt->fetch(PDO::FETCH_ASSOC);

    if ($attRow && $attRow['attendance_json']) {
        $json = json_decode($attRow['attendance_json'], true);
        if (isset($json[$attDate]['status'])) {
            $code = $json[$attDate]['status'];
            $row['current_status_name'] = $statusNames[$code] ?? $code;
        } else {
            $row['current_status_name'] = 'N/A';
        }
    } else {
        $row['current_status_name'] = 'N/A';
    }

    // Map new_status to readable name
    $row['new_status_name'] = $statusNames[$row['new_status']] ?? $row['new_status'];
}

echo json_encode([
    'success'         => true,
    'data'            => $rows,
    'pending_count'   => (int)$pendingCount,
    'processed_count' => (int)$processedCount
]);
