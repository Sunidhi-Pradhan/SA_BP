<?php
// Ultra-strict output control
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

try {
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('Configuration file not found');
    }

    ob_start();
    require_once __DIR__ . '/config.php';
    ob_end_clean();

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Check if POST data is received
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get POST parameters
    $esic_no = isset($_POST['esic_no']) ? trim($_POST['esic_no']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    // Validate input
    if (empty($esic_no) || empty($date) || empty($action)) {
        throw new Exception('Missing required parameters');
    }

    // Validate action
    if ($action !== 'lock' && $action !== 'unlock') {
        throw new Exception('Invalid action');
    }

    // Validate reason for unlock
    if ($action === 'unlock' && empty($reason)) {
        throw new Exception('Reason is required for unlocking attendance');
    }

    // Get month and year from date
    try {
        $dateObj = new DateTime($date);
        $month = (int)$dateObj->format('n');
        $year = (int)$dateObj->format('Y');
    } catch (Exception $e) {
        throw new Exception('Invalid date format');
    }

    // Fetch the attendance record
    $query = "SELECT attendance_json FROM attendance 
              WHERE esic_no = ? AND attendance_year = ? AND attendance_month = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed');
    }

    $stmt->bind_param("sii", $esic_no, $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Attendance record not found');
    }

    $row = $result->fetch_assoc();
    $attendanceJson = json_decode($row['attendance_json'], true);

    if (!is_array($attendanceJson)) {
        throw new Exception('Invalid attendance data');
    }

    // Find and update the specific date record
    $dateKey = $dateObj->format('Y-m-d');
    $recordFound = false;
    $newLockValue = ($action === 'lock') ? 1 : 0;

    foreach ($attendanceJson as &$record) {

    if (!isset($record['created_at'])) {
        continue;
    }

    // Safer date comparison
    $itemDate = date('Y-m-d', strtotime($record['created_at']));

    if ($itemDate !== $dateKey) {
        continue;
    }

    // ===== UNLOCK =====
    if ($action === 'unlock') {

        $record['locked'] = 0;
        $record['unlock_reason'] = $reason;
        $record['unlocked_at'] = date('Y-m-d H:i:s');
        $record['unlocked_by'] = $_SESSION['user_id'] ?? 'admin';

        // Remove old lock data
        unset($record['locked_at']);
        unset($record['locked_by']);
    }

    // ===== LOCK =====
    if ($action === 'lock') {

        $record['locked'] = 1;
        $record['locked_at'] = date('Y-m-d H:i:s');
        $record['locked_by'] = $_SESSION['user_id'] ?? 'admin';

        // Remove old unlock data
        unset($record['unlock_reason']);
        unset($record['unlocked_at']);
        unset($record['unlocked_by']);
    }

    $record['updated_at'] = date('Y-m-d H:i:s');
    $record['updated_by'] = $_SESSION['user_id'] ?? 'admin';

    $recordFound = true;
    break;
}

    if (!$recordFound) {
        throw new Exception('Attendance record for the specified date not found');
    }

    // Update the database
    $updatedJson = json_encode($attendanceJson, JSON_UNESCAPED_UNICODE);
    $updateQuery = "UPDATE attendance SET attendance_json = ? 
                    WHERE esic_no = ? AND attendance_year = ? AND attendance_month = ?";

    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception('Update query preparation failed');
    }

    $updateStmt->bind_param("ssii", $updatedJson, $esic_no, $year, $month);

    if ($updateStmt->execute()) {
        $message = ($action === 'lock') 
            ? "Attendance locked successfully for employee $esic_no on $date"
            : "Attendance unlocked successfully for employee $esic_no on $date. Reason: $reason";
        
        $response = [
            'success' => true,
            'message' => $message,
            'action' => $action,
            'esic_no' => $esic_no,
            'date' => $date,
            'reason' => $reason
        ];
    } else {
        throw new Exception('Failed to update attendance');
    }

    $stmt->close();
    $updateStmt->close();
    $conn->close();

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
ob_end_flush();
exit(0);
?>