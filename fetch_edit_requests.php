<?php
session_start();
require "config.php";

header('Content-Type: application/json');

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$requestBy = $_SESSION['user'];
$tab = $_GET['tab'] ?? 'pending';

/* ================= FETCH REQUESTS ================= */
if ($tab === 'pending') {
    $stmt = $pdo->prepare("
        SELECT * FROM attendance_edit_request
        WHERE request_by = ? AND status = 'pending'
        ORDER BY request_time DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM attendance_edit_request
        WHERE request_by = ? AND status IN ('approved', 'rejected')
        ORDER BY approved_time DESC
    ");
}
$stmt->execute([$requestBy]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= PENDING COUNT ================= */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_edit_request WHERE request_by = ? AND status = 'pending'");
$countStmt->execute([$requestBy]);
$pendingCount = $countStmt->fetchColumn();

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
            $row['current_status'] = $statusNames[$code] ?? $code;
        }
    }

    // Map new_status code to readable name
    $row['new_status_name'] = $statusNames[$row['new_status']] ?? $row['new_status'];
}

echo json_encode([
    'success'       => true,
    'data'          => $rows,
    'pending_count' => (int)$pendingCount
]);
