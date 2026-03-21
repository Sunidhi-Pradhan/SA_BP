<?php
/**
 * approve_lpc.php (Finance)
 * Finance final approval of LPC records.
 * Called via AJAX POST with: lpc_month, lpc_year, comment
 */
session_start();
require "../config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId  = $_SESSION['user'];
$month   = (int)($_POST['lpc_month'] ?? 0);
$year    = (int)($_POST['lpc_year']  ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!$month || !$year) {
    echo json_encode(['success' => false, 'message' => 'Month and year are required']);
    exit;
}

$actedAt = date('Y-m-d H:i:s');

try {
    // Update all lpc_master rows for this month/year:
    // Set FINANCE step to approved, mark workflow COMPLETE
    $stmt = $pdo->prepare("
        UPDATE lpc_master
        SET lpc_workflow = JSON_SET(
            lpc_workflow,
            '$.current_step',        'COMPLETE',
            '$.current_step_id',     3,
            '$.steps[1].status',     'approved',
            '$.steps[1].comment',    ?,
            '$.steps[1].acted_by',   ?,
            '$.steps[1].acted_at',   ?
        )
        WHERE lpc_month = ?
          AND lpc_year  = ?
          AND JSON_UNQUOTE(JSON_EXTRACT(lpc_workflow, '$.current_step')) = 'FINANCE'
    ");
    $stmt->execute([$comment, $userId, $actedAt, $month, $year]);

    $rowsUpdated = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "LPC finally approved ($rowsUpdated records)",
        'rows_updated' => $rowsUpdated,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
