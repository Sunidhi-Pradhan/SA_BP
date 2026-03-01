<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized');
}
require '../config.php';
require __DIR__ . '/../employee_import/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

/* ───── Input ───── */
$monthParam = $_GET['month'] ?? date('Y-m');
[$yearStr, $monStr] = explode('-', $monthParam . '-01');
$year  = (int)$yearStr;
$month = (int)$monStr;

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$monthLabel  = date('F Y', mktime(0, 0, 0, $month, 1, $year));

/* ───── Status map ───── */
$statusMap = [
    'P'  => 'Present',
    'A'  => 'Absent',
    'PP' => 'Present With Extra',
    'L'  => 'Leave',
];

/* ───── Fetch all employees + their attendance data ───── */
$stmt = $pdo->query("
    SELECT
        em.esic_no,
        em.employee_name,
        em.rank AS designation,
        em.site_code,
        a.attendance_json
    FROM employee_master em
    LEFT JOIN attendance a
        ON em.esic_no = a.esic_no
    ORDER BY em.employee_name ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ───── Build spreadsheet ───── */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Attendance $monthLabel");

/* ── Column map: A=Sr, B=ESIC, C=Name, D=Designation, E=Site, F...(F+days-1)=days, then P, A, PP, L totals ── */
$dayOffset  = 6;  // Column index (1-based) where day 1 starts → F=6
$totalCols  = ['P' => 0, 'A' => 0, 'PP' => 0, 'L' => 0];

/* ── Header row 1: Title ── */
$lastDayCol = $dayOffset + $daysInMonth - 1;
$totalEndCol = $lastDayCol + 4; // P, A, PP, L
$titleColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalEndCol);

$sheet->mergeCells("A1:{$titleColLetter}1");
$sheet->setCellValue('A1', "Monthly Attendance Report — $monthLabel");
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f766e']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(32);

/* ── Header row 2: Column headings ── */
$headers = ['#', 'ESIC No.', 'Employee Name', 'Designation', 'Site'];
foreach ($headers as $ci => $hdr) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
    $sheet->setCellValue("{$col}2", $hdr);
}
// Day headers
for ($d = 1; $d <= $daysInMonth; $d++) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dayOffset + $d - 1);
    $sheet->setCellValue("{$col}2", $d);
    $sheet->getColumnDimension($col)->setWidth(4);
}
// Totals headers
$totColLabels = ['P', 'A', 'PP', 'L'];
foreach ($totColLabels as $ti => $lbl) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDayCol + $ti + 1);
    $sheet->setCellValue("{$col}2", $lbl);
    $sheet->getColumnDimension($col)->setWidth(6);
}

// Style header row 2
$headerRange = "A2:{$titleColLetter}2";
$sheet->getStyle($headerRange)->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f766e']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0d5f58']]],
]);
$sheet->getRowDimension(2)->setRowHeight(22);

/* ── Fixed column widths ── */
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(14);
$sheet->getColumnDimension('C')->setWidth(26);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(12);

/* ── Data rows ── */
$rowNum = 3;
foreach ($rows as $i => $row) {
    $json = json_decode($row['attendance_json'] ?? '', true);
    $dayStatus = [];

    if (is_array($json)) {
        // Build daily status — only include dates matching selected month/year
        foreach ($json as $dateKey => $entry) {
            $ts = strtotime($dateKey);
            if (!$ts) continue;
            $dYear  = (int)date('Y', $ts);
            $dMonth = (int)date('n', $ts);
            if ($dYear === $year && $dMonth === $month) {
                $day = (int)date('j', $ts);
                $dayStatus[$day] = strtoupper(trim($entry['status'] ?? ''));
            }
        }
    }

    // Fixed columns
    $sheet->setCellValue("A{$rowNum}", $i + 1);
    $sheet->setCellValue("B{$rowNum}", $row['esic_no']);
    $sheet->setCellValue("C{$rowNum}", $row['employee_name']);
    $sheet->setCellValue("D{$rowNum}", $row['designation'] ?? '');
    $sheet->setCellValue("E{$rowNum}", $row['site_code'] ?? '');

    // Totals for this employee
    $emp_totals = ['P' => 0, 'A' => 0, 'PP' => 0, 'L' => 0];

    // Day columns
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $colIdx = $dayOffset + $d - 1;
        $col    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $st     = $dayStatus[$d] ?? '';
        $sheet->setCellValue("{$col}{$rowNum}", $st);

        // Color code the cell
        if ($st === 'P') {
            $sheet->getStyle("{$col}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
            $emp_totals['P']++;
        } elseif ($st === 'A') {
            $sheet->getStyle("{$col}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
            $emp_totals['A']++;
        } elseif ($st === 'PP') {
            $sheet->getStyle("{$col}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDBEAFE');
            $emp_totals['PP']++;
        } elseif ($st === 'L') {
            $sheet->getStyle("{$col}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
            $emp_totals['L']++;
        }

        $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$col}{$rowNum}")->getFont()->setSize(9);
    }

    // Totals columns
    foreach (['P', 'A', 'PP', 'L'] as $ti => $lbl) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDayCol + $ti + 1);
        $sheet->setCellValue("{$col}{$rowNum}", $emp_totals[$lbl]);
        $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$col}{$rowNum}")->getFont()->setBold(true);
        $totalCols[$lbl] += $emp_totals[$lbl];
    }

    // Row style
    $bg = ($i % 2 === 0) ? 'FFFFFFFF' : 'FFF9FAFB';
    $sheet->getStyle("A{$rowNum}:E{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
    $sheet->getStyle("A{$rowNum}:E{$rowNum}")->getFont()->setSize(9);
    $sheet->getStyle("A{$rowNum}:E{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($rowNum)->setRowHeight(16);

    // Full row border
    $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalEndCol);
    $sheet->getStyle("A{$rowNum}:{$endCol}{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');

    $rowNum++;
}

/* ── Grand totals row ── */
$sheet->setCellValue("A{$rowNum}", 'TOTAL');
$sheet->mergeCells("A{$rowNum}:E{$rowNum}");
$sheet->getStyle("A{$rowNum}")->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => '111827']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

foreach (['P', 'A', 'PP', 'L'] as $ti => $lbl) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDayCol + $ti + 1);
    $sheet->setCellValue("{$col}{$rowNum}", $totalCols[$lbl]);
    $sheet->getStyle("{$col}{$rowNum}")->applyFromArray([
        'font'      => ['bold' => true],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
}

// Freeze top 2 rows and left 5 columns
$sheet->freezePane('F3');

/* ───── Stream download ───── */
$filename = "Attendance_{$year}_{$month}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
