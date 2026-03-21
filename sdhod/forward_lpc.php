<?php
/**
 * forward_lpc.php
 * SDHOD forwards LPC to Finance by updating the lpc_workflow JSON.
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
    // Update ALL lpc_master rows for this month/year:
    // Set SDHOD step to approved, advance current_step to FINANCE
    $stmt = $pdo->prepare("
        UPDATE lpc_master
        SET lpc_workflow = JSON_SET(
            lpc_workflow,
            '$.current_step',        'FINANCE',
            '$.current_step_id',     2,
            '$.steps[0].status',     'approved',
            '$.steps[0].comment',    ?,
            '$.steps[0].acted_by',   ?,
            '$.steps[0].acted_at',   ?
        )
        WHERE lpc_month = ?
          AND lpc_year  = ?
          AND JSON_UNQUOTE(JSON_EXTRACT(lpc_workflow, '$.current_step')) = 'SDHOD'
    ");
    $stmt->execute([$comment, $userId, $actedAt, $month, $year]);

    $rowsUpdated = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "LPC forwarded to Finance successfully ($rowsUpdated records updated)",
        'rows_updated' => $rowsUpdated,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
