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

/* ================= INPUT ================= */
$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);

// Fallback: try POST if json_decode failed
$id     = (int)($input['id'] ?? ($_POST['id'] ?? 0));
$action = $input['action'] ?? ($_POST['action'] ?? '');

if (!$id || !in_array($action, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters. id='.$id.', action='.$action]);
    exit;
}

/* ================= GET REQUEST ROW ================= */
$stmt = $pdo->prepare("SELECT * FROM attendance_edit_request WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
    exit;
}

try {
    $pdo->beginTransaction();

    /* ---- Update request status ---- */
    $updateStmt = $pdo->prepare("
        UPDATE attendance_edit_request
        SET status = ?, approved_by = ?, approved_time = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$action, $_SESSION['user'], $id]);

    /* ---- If approved → update attendance table ---- */
    if ($action === 'approved') {

        $attDate   = $request['attendance_date'];
        $year      = (int)date('Y', strtotime($attDate));
        $month     = (int)date('n', strtotime($attDate));
        $empcode   = $request['empcode'];
        $newStatus = $request['new_status'];

        // Fetch current attendance JSON
        $attStmt = $pdo->prepare("
            SELECT attendance_json FROM attendance
            WHERE esic_no = ? AND attendance_year = ? AND attendance_month = ?
        ");
        $attStmt->execute([$empcode, $year, $month]);
        $attRow = $attStmt->fetch(PDO::FETCH_ASSOC);

        if ($attRow && $attRow['attendance_json']) {
            $json = json_decode($attRow['attendance_json'], true);
            if (!is_array($json)) $json = [];

            // Update or create the date entry
            if (isset($json[$attDate])) {
                $json[$attDate]['status']     = $newStatus;
                $json[$attDate]['updated_by'] = $_SESSION['user'];
                $json[$attDate]['updated_at'] = date('Y-m-d H:i:s');
            } else {
                $json[$attDate] = [
                    'status'         => $newStatus,
                    'site_code'      => '',
                    'approve_status' => 0,
                    'locked'         => 1,
                    'created_by'     => $_SESSION['user'],
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_by'     => null,
                    'updated_at'     => null
                ];
            }

            $updateAtt = $pdo->prepare("
                UPDATE attendance SET attendance_json = ?
                WHERE esic_no = ? AND attendance_year = ? AND attendance_month = ?
            ");
            $updateAtt->execute([
                json_encode($json, JSON_UNESCAPED_SLASHES),
                $empcode, $year, $month
            ]);
        }
        // If no attendance row exists for this month, the approval still goes through
        // but no attendance row is created (the original attendance must exist first)
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Request ' . $action . ' successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
