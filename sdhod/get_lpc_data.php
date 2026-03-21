<?php
/**
 * get_lpc_data.php
 * Returns LPC summary and workflow data for a given month/year.
 * Used by monthlylpp.php (SDHOD) and Finance module (AJAX).
 */
require "../config.php";

header('Content-Type: application/json');

$month = (int)($_GET['month'] ?? 0);
$year  = (int)($_GET['year']  ?? 0);

if (!$month || !$year) {
    echo json_encode(["sites" => [], "workflow" => null]);
    exit;
}

// Fetch all LPC records for the given month/year
$stmt = $pdo->prepare("SELECT sec_no, dak_no, lpc_summary, lpc_workflow FROM lpc_master WHERE lpc_month = ? AND lpc_year = ?");
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
    // Use the first row's workflow as representative (all should be the same)
    if (!$workflow && !empty($row['lpc_workflow'])) {
        $workflow = json_decode($row['lpc_workflow'], true);
    }
}

echo json_encode([
    "sites"    => $allSites,
    "workflow" => $workflow,
]);