<?php
session_start();
require "../config.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT role FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'SDHOD') {
    die("Access denied");
}

// ── Params ──────────────────────────────────────────────
$siteCode = $_GET['site_code'] ?? '';
$year     = (int)($_GET['year']  ?? date('Y'));
$month    = (int)($_GET['month'] ?? date('n'));

if (!$siteCode) { die("Missing site_code"); }

// ── Autoload PhpSpreadsheet from employee_import vendor ─
require_once __DIR__ . '/../employee_import/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// ── Fetch site name ──────────────────────────────────────
$stmtSite = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
$stmtSite->execute([$siteCode]);
$siteName = $stmtSite->fetchColumn() ?: $siteCode;

// ── Fetch attendance ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.*, e.employee_name, e.rank, e.site_code
    FROM attendance a
    LEFT JOIN employee_master e ON a.esic_no = e.esic_no
    WHERE a.attendance_year  = :year
      AND a.attendance_month = :month
      AND e.site_code        = :siteCode
");
$stmt->execute([':year' => $year, ':month' => $month, ':siteCode' => $siteCode]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Working weekdays ─────────────────────────────────────
function getWeekdays($year, $month) {
    $days  = [];
    $total = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($d = 1; $d <= $total; $d++) {
        if (date('N', mktime(0,0,0,$month,$d,$year)) < 6) $days[] = $d;
    }
    return $days;
}
$dayColumns = getWeekdays($year, $month);

$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',
    5=>'May',6=>'June',7=>'July',8=>'August',
    9=>'September',10=>'October',11=>'November',12=>'December'];
$monthName = $monthNames[$month] ?? '';

// ── Build Spreadsheet ─────────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance');

// ── Title row ─────────────────────────────────────────────
$totalCols = 4 + count($dayColumns) + 3; // SN, EMP, NAME, RANK + days + WORKING, EXTRA, TOTAL
$lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

$sheet->mergeCells("A1:{$lastColLetter}1");
$sheet->setCellValue('A1', "Monthly Attendance Report – MCL ({$siteName})");
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f766e']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(28);

$sheet->mergeCells("A2:{$lastColLetter}2");
$sheet->setCellValue('A2', "Period: {$monthName} {$year}   |   Site: {$siteCode}   |   Working Days (Weekdays): " . count($dayColumns));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['size' => 10, 'color' => ['rgb' => '374151']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'e5e7eb']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(2)->setRowHeight(20);

// ── Header row ────────────────────────────────────────────
$headers = ['S.N.', 'EMP CODE', 'NAME', 'RANK'];
foreach ($dayColumns as $d) { $headers[] = $d; }
$headers[] = 'WORKING';
$headers[] = 'EXTRA';
$headers[] = 'TOTAL';

$col = 1;
foreach ($headers as $h) {
    $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '3';

    // Determine color
    if ($col <= 4) {
        $bgColor = '0f766e'; // teal
    } elseif ($col === count($headers) - 2) {
        $bgColor = '0ea5e9'; // blue – WORKING
    } elseif ($col === count($headers) - 1) {
        $bgColor = 'f59e0b'; // amber – EXTRA
    } elseif ($col === count($headers)) {
        $bgColor = '10b981'; // green – TOTAL
    } else {
        $bgColor = '134e4a'; // dark teal for day columns
    }

    $sheet->setCellValue($cellRef, $h);
    $sheet->getStyle($cellRef)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
    ]);
    $col++;
}
$sheet->getRowDimension(3)->setRowHeight(22);

// ── Data rows ─────────────────────────────────────────────
$statusColors = [
    'P'  => ['bg' => 'd1fae5', 'fg' => '059669'], // green present
    'PP' => ['bg' => 'bfdbfe', 'fg' => '1d4ed8'], // blue extra
    'L'  => ['bg' => 'fef3c7', 'fg' => 'd97706'], // amber leave
    'A'  => ['bg' => 'fee2e2', 'fg' => 'dc2626'], // red absent
];

$rowNum = 4;
$sn     = 1;
$totalWorking = 0;
$totalExtra   = 0;

foreach ($rows as $row) {
    $attendanceData = json_decode($row['attendance_json'], true) ?? [];
    $working = 0; $extra = 0;

    $isEven = ($sn % 2 === 0);
    $baseBg = $isEven ? 'fafafa' : 'FFFFFF';

    // Fixed columns
    $sheet->setCellValue("A{$rowNum}", $sn);
    $sheet->setCellValue("B{$rowNum}", $row['esic_no']);
    $sheet->setCellValue("C{$rowNum}", $row['employee_name']);
    $sheet->setCellValue("D{$rowNum}", $row['rank']);

    foreach (['A','B','C','D'] as $fc) {
        $sheet->getStyle("{$fc}{$rowNum}")->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $baseBg]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
            'font'      => ['size' => 9],
        ]);
    }
    $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Day columns
    $colIdx = 5;
    foreach ($dayColumns as $day) {
        $dateKey  = $year . "-" . str_pad($month,2,'0',STR_PAD_LEFT) . "-" . str_pad($day,2,'0',STR_PAD_LEFT);
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $cellRef   = "{$colLetter}{$rowNum}";

        if (isset($attendanceData[$dateKey])) {
            $status = $attendanceData[$dateKey]['status'];
            if ($status === 'P')  $working++;
            if ($status === 'PP') $extra++;
            $colors = $statusColors[$status] ?? ['bg' => $baseBg, 'fg' => '333333'];
            $sheet->setCellValue($cellRef, $status);
            $sheet->getStyle($cellRef)->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['bg']]],
                'font'      => ['bold' => true, 'color' => ['rgb' => $colors['fg']], 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
            ]);
        } else {
            $sheet->setCellValue($cellRef, '-');
            $sheet->getStyle($cellRef)->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $baseBg]],
                'font'      => ['color' => ['rgb' => 'cccccc'], 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
            ]);
        }
        $colIdx++;
    }

    // Summary cols
    $total = $working + $extra;
    $wColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
    $eColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
    $tColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2);

    $sheet->setCellValue("{$wColLetter}{$rowNum}", $working);
    $sheet->getStyle("{$wColLetter}{$rowNum}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isEven ? 'bfdbfe' : 'dbeafe']],
        'font'      => ['bold' => true, 'color' => ['rgb' => '0369a1'], 'size' => 9],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
    ]);

    $sheet->setCellValue("{$eColLetter}{$rowNum}", $extra);
    $sheet->getStyle("{$eColLetter}{$rowNum}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isEven ? 'fde68a' : 'fef3c7']],
        'font'      => ['bold' => true, 'color' => ['rgb' => 'b45309'], 'size' => 9],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
    ]);

    $sheet->setCellValue("{$tColLetter}{$rowNum}", $total);
    $sheet->getStyle("{$tColLetter}{$rowNum}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isEven ? 'a7f3d0' : 'd1fae5']],
        'font'      => ['bold' => true, 'color' => ['rgb' => '059669'], 'size' => 9],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
    ]);

    $sheet->getRowDimension($rowNum)->setRowHeight(18);

    $totalWorking += $working;
    $totalExtra   += $extra;
    $rowNum++;
    $sn++;
}

// ── Totals footer row ─────────────────────────────────────
$totalRow = $rowNum;
$sheet->mergeCells("A{$totalRow}:D{$totalRow}");
$sheet->setCellValue("A{$totalRow}", "TOTALS");
$sheet->getStyle("A{$totalRow}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f766e']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// blank day cells in total row
$colIdx = 5;
foreach ($dayColumns as $day) {
    $cl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
    $sheet->getStyle("{$cl}{$totalRow}")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f3f4f6']],
    ]);
    $colIdx++;
}

$wColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
$eColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
$tColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2);

foreach ([
    [$wColLetter, $totalWorking, '0369a1', 'dbeafe'],
    [$eColLetter, $totalExtra,   'b45309', 'fef3c7'],
    [$tColLetter, $totalWorking + $totalExtra, '059669', 'd1fae5'],
] as [$cl, $val, $fg, $bg]) {
    $sheet->setCellValue("{$cl}{$totalRow}", $val);
    $sheet->getStyle("{$cl}{$totalRow}")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => $fg], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $fg]]],
    ]);
}
$sheet->getRowDimension($totalRow)->setRowHeight(22);

// ── Legend row ────────────────────────────────────────────
$legendRow = $totalRow + 2;
$sheet->mergeCells("A{$legendRow}:{$lastColLetter}{$legendRow}");
$sheet->setCellValue("A{$legendRow}", "Legend:  P = Present   |   PP = Double Duty (Extra)   |   L = Leave   |   A = Absent   |   - = Weekend / Holiday");
$sheet->getStyle("A{$legendRow}")->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '6b7280']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f9fafb']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// ── Column widths ─────────────────────────────────────────
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(22);
$sheet->getColumnDimension('D')->setWidth(14);

$colIdx = 5;
foreach ($dayColumns as $day) {
    $cl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
    $sheet->getColumnDimension($cl)->setWidth(5);
    $colIdx++;
}
$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx))->setWidth(9);
$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx+1))->setWidth(7);
$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx+2))->setWidth(7);

// ── Freeze panes ──────────────────────────────────────────
$sheet->freezePane('E4');

// ── Page setup for printing ───────────────────────────────
$sheet->getPageSetup()
    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3)
    ->setFitToWidth(1)
    ->setFitToHeight(0);
$sheet->getHeaderFooter()
    ->setOddHeader("&C&B Monthly Attendance – {$siteName} – {$monthName} {$year}");
$sheet->getHeaderFooter()
    ->setOddFooter("&L Generated: " . date('Y-m-d H:i') . "&R Page &P of &N");

// ── Output ────────────────────────────────────────────────
$filename = "Attendance_{$siteCode}_{$monthName}_{$year}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
