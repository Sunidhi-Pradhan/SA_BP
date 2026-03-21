<?php
/**
 * get_lpc_data.php (Finance)
 * Returns LPC data that has been forwarded to Finance (or completed).
 */
require "../config.php";

header('Content-Type: application/json');

$month = (int)($_GET['month'] ?? 0);
$year  = (int)($_GET['year']  ?? 0);

if (!$month || !$year) {
    echo json_encode(["sites" => [], "workflow" => null]);
    exit;
}

// Fetch LPC records for the given month/year where workflow is at FINANCE or COMPLETE
$stmt = $pdo->prepare("
    SELECT sec_no, dak_no, lpc_summary, lpc_workflow 
    FROM lpc_master 
    WHERE lpc_month = ? 
      AND lpc_year = ?
      AND JSON_UNQUOTE(JSON_EXTRACT(lpc_workflow, '$.current_step')) IN ('FINANCE', 'COMPLETE')
");
$stmt->execute([$month, $year]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo json_encode(["sites" => [], "workflow" => null]);
    exit;
}

$allSites = [];
$workflow = null;

foreach ($rows as $row) {
    $summary = json_decode($row['lpc_summary'], true);
    if (is_array($summary)) {
        foreach ($summary as $site) {
            $site['sec_no'] = $row['sec_no'];
            $site['dak_no'] = $row['dak_no'];
            $allSites[] = $site;
        }
    }
    if (!$workflow && !empty($row['lpc_workflow'])) {
        $workflow = json_decode($row['lpc_workflow'], true);
    }
}

echo json_encode([
    "sites"    => $allSites,
    "workflow" => $workflow,
]);
