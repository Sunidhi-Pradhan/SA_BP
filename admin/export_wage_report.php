<?php
/**
 * export_wage_report.php
 * Generates a CSV file with wage data for the selected month.
 * URL: admin/export_wage_report.php?month=2026-02
 */
session_start();
if (!isset($_SESSION["user"])) { header("Location: ../login.php"); exit; }
require "../config.php";

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    die("Invalid month format.");
}

[$year, $mon] = explode('-', $month);
$monthName = date('F_Y', mktime(0, 0, 0, (int)$mon, 1, (int)$year));

// ── Pull attendance + employee + pay data for the selected month ──
$stmt = $pdo->prepare("
    SELECT
        e.esic_no,
        e.employee_name,
        e.designation,
        e.site_code,
        e.rank,
        a.attendance_json,
        COALESCE(g.basic_vda, 0)  AS basic_vda,
        COALESCE(g.designation, e.designation) AS grade_desig
    FROM employee_master e
    LEFT JOIN attendance a
        ON  a.esic_no          = e.esic_no
        AND a.attendance_year  = :yr
        AND a.attendance_month = :mo
    LEFT JOIN emp_grade g
        ON  g.designation = e.designation
    ORDER BY e.site_code, e.employee_name
");
$stmt->execute([':yr' => (int)$year, ':mo' => (int)$mon]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Calculate working days (weekdays only) for the month ──
$dim      = cal_days_in_month(CAL_GREGORIAN, (int)$mon, (int)$year);
$weekdays = 0;
for ($d = 1; $d <= $dim; $d++) {
    if (date('N', mktime(0, 0, 0, (int)$mon, $d, (int)$year)) < 6) $weekdays++;
}

// ── Stream CSV ──
$filename = "Wage_Report_{$monthName}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// CSV Header Row
fputcsv($out, [
    'ESIC No',
    'Employee Name',
    'Designation',
    'Site Code',
    'Rank',
    'Working Days (Month)',
    'Days Present (P)',
    'Extra Duty (PP)',
    'Absents (A)',
    'Basic VDA',
    'Per Day Rate',
    'Earned Wages',
    'Extra Duty Amt',
    'Gross Wages',
]);

foreach ($rows as $r) {
    $json    = json_decode($r['attendance_json'] ?? '[]', true) ?: [];
    $present = 0; $extra = 0; $absent = 0;
    foreach ($json as $entry) {
        $s = $entry['status'] ?? '';
        if ($s === 'P')  $present++;
        if ($s === 'PP') $extra++;
        if ($s === 'A')  $absent++;
    }

    $basicVda     = (float)$r['basic_vda'];
    $perDay       = $weekdays > 0 ? round($basicVda / $weekdays, 2) : 0;
    $earnedWages  = round($perDay * $present, 2);
    $extraAmt     = round($perDay * $extra, 2);
    $grossWages   = round($earnedWages + $extraAmt, 2);

    fputcsv($out, [
        $r['esic_no'],
        $r['employee_name'],
        $r['designation'],
        $r['site_code'],
        $r['rank'],
        $weekdays,
        $present,
        $extra,
        $absent,
        number_format($basicVda, 2),
        number_format($perDay, 2),
        number_format($earnedWages, 2),
        number_format($extraAmt, 2),
        number_format($grossWages, 2),
    ]);
}

fclose($out);
exit;
