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
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

    // Validate input
    if (empty($esic_no) || empty($date) || empty($new_status)) {
        throw new Exception('Missing required parameters');
    }

    // Validate status
    $valid_statuses = ['present', 'absent', 'leave', 'overtime', 'p', 'a', 'l', 'pp'];
    if (!in_array(strtolower($new_status), $valid_statuses)) {
        throw new Exception('Invalid status value');
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

    foreach ($attendanceJson as &$record) {
        if (isset($record['created_at'])) {
            $itemDate = substr($record['created_at'], 0, 10);
            if ($itemDate === $dateKey) {
                // Check if record is unlocked (locked = 0)
                if (isset($record['locked']) && $record['locked'] == 1) {
                    throw new Exception('Cannot change status. Attendance is locked.');
                }
                
                // Update status
                $record['status'] = $new_status;
                $record['updated_at'] = date('Y-m-d H:i:s');
                $record['updated_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'admin';
                $recordFound = true;
                break;
            }
        }
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
        $response = [
            'success' => true,
            'message' => "Status changed to '$new_status' successfully for employee $esic_no on $date",
            'esic_no' => $esic_no,
            'date' => $date,
            'new_status' => $new_status
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