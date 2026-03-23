<?php
/**
 * export_wage_report.php
 * Generates an Excel (.xlsx) file with wage data for the selected month.
 * URL: admin/export_wage_report.php?month=2026-03
 *
 * Columns: RegNo, Employee Name, Site Code, Rank, ESIC No, A/C No, IFSC Code,
 *          Month, Year, Salary Days, OT Hours, Total Days, Basic, Extra Duty,
 *          Rewards, PF, ESI, PT, Other Deduct, SMPF, Sewa, Gross, Total Earned,
 *          Total Deduct, Net Amount, Bonus
 *
 * Formulas:
 *   Salary Days  = Present (P) + Leave (L)
 *   OT Hours     = Extra Duty days (PP)
 *   Total Days   = Salary Days + OT Hours
 *   Basic        = Salary Days × Basic VDA Rate
 *   Extra Duty   = OT × Basic VDA Rate
 *   Rewards      = 0
 *   Gross        = Basic + Extra Duty
 *   PF           = Gross × 12%
 *   ESI          = Gross × 4%
 *   PT           = ₹125
 *   Other Deduct = ₹0
 *   SMPF         = ₹1383
 *   Sewa         = ₹50
 *   Total Earned = Gross
 *   Total Deduct = PF + ESI + PT + Other + SMPF + Sewa
 *   Net Amount   = Gross − Total Deductions
 *   Bonus        = Total Days × Basic VDA Rate × 8.33%
 */
session_start();
if (!isset($_SESSION["user"])) { header("Location: ../login.php"); exit; }
require "../config.php";

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    die("Invalid month format.");
}

[$year, $mon] = explode('-', $month);
$yearNum  = (int)$year;
$monthNum = (int)$mon;
$monthLabel = date('F', mktime(0, 0, 0, $monthNum, 1, $yearNum));

// ── Pull attendance + employee + pay data ──
$stmt = $pdo->prepare("
    SELECT
        e.reg_no,
        e.employee_name,
        e.site_code,
        e.rank,
        e.esic_no,
        e.ac_no,
        e.ifsc_code,
        a.attendance_json,
        COALESCE(MAX(g.basic_vda), 0) AS basic_vda
    FROM employee_master e
    LEFT JOIN attendance a
        ON  a.esic_no          = e.esic_no
        AND a.attendance_year  = :yr
        AND a.attendance_month = :mo
    LEFT JOIN emp_grade g
        ON  g.designation = e.rank
    GROUP BY e.esic_no
    ORDER BY e.site_code, e.employee_name
");
$stmt->execute([':yr' => $yearNum, ':mo' => $monthNum]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Stream CSV (Excel-compatible) ──
$filename = "Wage_Report_{$monthLabel}_{$yearNum}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM for Excel UTF-8 detection
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Row
fputcsv($out, [
    'Reg No',
    'Employee Name',
    'Site Code',
    'Rank',
    'ESIC No',
    'A/C No',
    'IFSC Code',
    'Month',
    'Year',
    'Salary Days',
    'OT Hours',
    'Total Days',
    'Basic',
    'Extra Duty',
    'Rewards',
    'PF',
    'ESI',
    'PT',
    'Other Deduct',
    'SMPF',
    'Sewa',
    'Gross',
    'Total Earned',
    'Total Deduct',
    'Net Amount',
    'Bonus',
]);

foreach ($rows as $r) {
    $json = json_decode($r['attendance_json'] ?? '[]', true) ?: [];

    $present  = 0;
    $overtime = 0;
    $leave    = 0;

    foreach ($json as $entry) {
        $s = strtoupper($entry['status'] ?? '');
        if ($s === 'P')       $present++;
        elseif ($s === 'PP')  $overtime++;
        elseif ($s === 'L')   $leave++;
    }

    $basicVdaRate = (float)$r['basic_vda'];

    // Salary Days = Present + Leave
    $salaryDays = $present + $leave;
    // OT Hours = Overtime (PP) days
    $otHours    = $overtime;
    // Total Days = Salary Days + OT
    $totalDays  = $salaryDays + $otHours;

    // Basic = Salary Days × Basic VDA Rate
    $basic     = round($salaryDays * $basicVdaRate, 2);
    // Extra Duty = OT × Basic VDA Rate
    $extraDuty = round($otHours * $basicVdaRate, 2);
    // Rewards = 0
    $rewards   = 0;

    // Gross = Basic + Extra Duty
    $gross     = round($basic + $extraDuty, 2);

    // Deductions
    $pf          = round($gross * 0.12, 2);      // PF = Gross × 12%
    $esi         = round($gross * 0.04, 2);      // ESI = Gross × 4%
    $pt          = 125;                           // PT = ₹125
    $otherDeduct = 0;                             // Other Deduct = ₹0
    $smpf        = 1383;                          // SMPF = ₹1383
    $sewa        = 50;                            // Sewa = ₹50

    // Total Earned = Gross
    $totalEarned = $gross;
    // Total Deduct = PF + ESI + PT + Other + SMPF + Sewa
    $totalDeduct = round($pf + $esi + $pt + $otherDeduct + $smpf + $sewa, 2);
    // Net Amount = Gross − Total Deductions
    $netAmount   = round($gross - $totalDeduct, 2);
    // Bonus = Total Days × Basic VDA Rate × 8.33%
    $bonus       = round($totalDays * $basicVdaRate * 0.0833, 2);

    fputcsv($out, [
        $r['reg_no']        ?? '',
        $r['employee_name'] ?? '',
        $r['site_code']     ?? '',
        $r['rank']          ?? '',
        $r['esic_no']       ?? '',
        $r['ac_no']         ?? '',
        $r['ifsc_code']     ?? '',
        $monthLabel,
        $yearNum,
        $salaryDays,
        $otHours,
        $totalDays,
        $basic,
        $extraDuty,
        $rewards,
        $pf,
        $esi,
        $pt,
        $otherDeduct,
        $smpf,
        $sewa,
        $gross,
        $totalEarned,
        $totalDeduct,
        $netAmount,
        $bonus,
    ]);
}

fclose($out);
exit;
