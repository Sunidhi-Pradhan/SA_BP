<?php
/**
 * export_vv_statement.php
 * Generates an Excel-style CSV for VV Statement download.
 * Called via: sdhod/export_vv_statement.php?fy=2026-2027&site=ALL&category=all&month=April 2026
 */
session_start();
if (!isset($_SESSION['user'])) { header("Location: ../login.php"); exit; }
require "../config.php";

/* ── PARAMETERS ── */
$fy       = $_GET['fy']       ?? '';
$site     = $_GET['site']     ?? 'ALL';
$category = $_GET['category'] ?? 'all';
$month    = $_GET['month']    ?? '';

if (empty($fy) || empty($month)) {
    die("Missing parameters: fy and month are required.");
}

/* ── PARSE FINANCIAL YEAR & MONTH ── */
// month comes as "April 2026" or "2026-04" etc.
// Try to parse month name + year, else use direct date
if (preg_match('/^(\w+)\s+(\d{4})$/i', $month, $m)) {
    $monthNum = date('n', strtotime("1 {$m[1]} {$m[2]}"));
    $yearNum  = (int)$m[2];
} elseif (preg_match('/^(\d{4})-(\d{2})$/', $month, $m)) {
    $monthNum = (int)$m[2];
    $yearNum  = (int)$m[1];
} else {
    die("Invalid month format.");
}

$monthName = date('F', mktime(0,0,0,$monthNum,1,$yearNum));

/* ── FETCH EMPLOYEES WITH ATTENDANCE & GRADE ── */
$sql = "
    SELECT
        e.esic_no,
        e.reg_no,
        e.aadhar_no,
        e.employee_name,
        e.father_name,
        e.rank AS designation,
        e.site_code,
        s.SiteName,
        a.attendance_json,
        COALESCE(MAX(g.basic_vda), 0) AS basic_vda
    FROM employee_master e
    LEFT JOIN site_master s ON s.SiteCode = e.site_code
    LEFT JOIN attendance a
        ON a.esic_no = e.esic_no
        AND a.attendance_year  = :yr
        AND a.attendance_month = :mo
    LEFT JOIN emp_grade g
        ON g.designation = e.rank
";

$params = [':yr' => $yearNum, ':mo' => $monthNum];

if ($site !== 'ALL') {
    $sql .= " WHERE e.site_code = :site";
    $params[':site'] = $site;
}

$sql .= " GROUP BY e.esic_no ORDER BY e.site_code, e.employee_name";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage(), 'count' => 0, 'rows' => []]);
        exit;
    }
    die("Database error: " . $e->getMessage());
}

/* ── CALCULATE NOTIONAL WORKING DAYS (26 fixed or weekdays) ── */
$notionalDays = 26;

/* ── PROCESS ROWS ── */
$data = [];
$slNo = 0;

foreach ($rows as $r) {
    $json = json_decode($r['attendance_json'] ?? '[]', true) ?: [];

    // Count actual attendance (P = present, PP = extra/overtime)
    $present = 0;
    $overtime = 0;
    $absent = 0;
    $leave = 0;

    foreach ($json as $dateKey => $entry) {
        $s = strtoupper($entry['status'] ?? '');
        if ($s === 'P')       $present++;
        elseif ($s === 'PP')  { $present++; $overtime++; }
        elseif ($s === 'A')   $absent++;
        elseif ($s === 'L')   $leave++;
    }

    $actualAttendance = $present;

    // Category filter
    if ($category === '>26' && $actualAttendance <= 26) continue;
    if ($category === '=26' && $actualAttendance !== 26) continue;
    if ($category === '<26' && $actualAttendance >= 26) continue;
    if ($category === 'overtime' && $overtime <= 0) continue;
    if ($category === 'leave' && $leave <= 0) continue;

    $slNo++;
    $basicVda   = (float)$r['basic_vda'];
    $grossWage  = ($notionalDays > 0) ? round($basicVda / $notionalDays * $actualAttendance, 2) : 0;
    $notionalWage = $basicVda;

    // PF calculations
    $pfMember12    = round($basicVda * 0.12, 2);
    $pfEmployer12  = round($basicVda * 0.12, 2);
    $pensionMember7  = round($basicVda * 0.07, 2);
    $pensionEmployer7 = round($basicVda * 0.07, 2);
    $totalPF       = round($pfMember12 + $pfEmployer12, 2);
    $totalPension  = round($pensionMember7 + $pensionEmployer7, 2);
    $totalDeduction = round($pfMember12 + $pensionMember7, 2);

    $data[] = [
        'slNo'              => $slNo,
        'regNo'             => $r['reg_no'] ?? '',
        'esicNo'            => $r['esic_no'] ?? '',
        'cmpfAccNo'         => '',  // CMPF Acc No — not in current DB schema
        'aadharNo'          => $r['aadhar_no'] ?? '',
        'employeeName'      => $r['employee_name'] ?? '',
        'fatherName'        => $r['father_name'] ?? '',
        'designation'       => $r['designation'] ?? '',
        'siteName'          => $r['SiteName'] ?? $r['site_code'] ?? '',
        'wages'             => $basicVda,
        'actualAttendance'  => $actualAttendance,
        'notionalDays'      => $notionalDays,
        'grossWage'         => $grossWage,
        'notionalWages'     => $notionalWage,
        'pfMember12'        => $pfMember12,
        'pfEmployer12'      => $pfEmployer12,
        'pensionMember7'    => $pensionMember7,
        'pensionEmployer7'  => $pensionEmployer7,
        'totalPF'           => $totalPF,
        'totalPension'      => $totalPension,
        'totalDeduction'    => $totalDeduction,
    ];
}

/* ── If called as JSON (for SheetJS client-side), return JSON ── */
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'title'  => "Monthly VV Statement for the period: {$monthName} {$yearNum}",
        'area'   => $site === 'ALL' ? 'ALL SITES' : $site,
        'fy'     => $fy,
        'count'  => count($data),
        'rows'   => $data,
    ]);
    exit;
}

/* ── DEFAULT: stream CSV ── */
$filename = "VV_Report_{$fy}_{$monthName}_{$yearNum}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// Title rows
fputcsv($out, ["Monthly VV Statement for the period: {$monthName} {$yearNum}"]);
fputcsv($out, ["Area: MCL {$site}"]);
fputcsv($out, []); // blank row

// Header
fputcsv($out, [
    'SlNo', 'RegNo', 'ESIC No', 'CMPF Acc No', 'Aadhar No',
    'Name of the Employee', "Father's Name", 'Designation', 'Site Name',
    'Wages (Basic+VDA)', 'Actual Attendance', 'National Working Days',
    'Gross Wage Payment', 'National Wages',
    'PF Member (12%)', 'PF Employer (12%)',
    'Pension Member (7%)', 'Pension Employer (7%)',
    'Total PF', 'Total Pension', 'Total Deduction',
]);

foreach ($data as $d) {
    fputcsv($out, [
        $d['slNo'],
        $d['regNo'],
        $d['esicNo'],
        $d['cmpfAccNo'],
        $d['aadharNo'],
        $d['employeeName'],
        $d['fatherName'],
        $d['designation'],
        $d['siteName'],
        $d['wages'],
        $d['actualAttendance'],
        $d['notionalDays'],
        $d['grossWage'],
        $d['notionalWages'],
        $d['pfMember12'],
        $d['pfEmployer12'],
        $d['pensionMember7'],
        $d['pensionEmployer7'],
        $d['totalPF'],
        $d['totalPension'],
        $d['totalDeduction'],
    ]);
}

fclose($out);
exit;
