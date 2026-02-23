<?php
session_start();
require "../config.php";

if (!isset($_SESSION['user'])) { header("Location: ../login.php"); exit; }

$stmtUser = $pdo->prepare("SELECT role FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'SDHOD') { die("Access denied"); }

$siteCode = $_GET['site_code'] ?? '';
$year     = (int)($_GET['year']  ?? date('Y'));
$month    = (int)($_GET['month'] ?? date('n'));
if (!$siteCode) { die("Missing site_code"); }

// ── Try mPDF first (if installed) ────────────────────────
$autoload = __DIR__ . '/../employee_import/vendor/autoload.php';
if (file_exists($autoload)) { require_once $autoload; }

// ── Site name ────────────────────────────────────────────
$stmtSite = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
$stmtSite->execute([$siteCode]);
$siteName = $stmtSite->fetchColumn() ?: $siteCode;

// ── Attendance rows ──────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.*, e.employee_name, e.rank
    FROM attendance a
    LEFT JOIN employee_master e ON a.esic_no = e.esic_no
    WHERE a.attendance_year  = :year
      AND a.attendance_month = :month
      AND e.site_code        = :siteCode
");
$stmt->execute([':year' => $year, ':month' => $month, ':siteCode' => $siteCode]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Workflow ─────────────────────────────────────────────
$stmtWf = $pdo->prepare("
    SELECT attendance_workflow FROM attendance_approval
    WHERE area_code = ? AND attendance_month = ? AND attendance_year = ?
");
$stmtWf->execute([$siteCode, $month, $year]);
$wfRow    = $stmtWf->fetch(PDO::FETCH_ASSOC);
$workflow = $wfRow ? json_decode($wfRow['attendance_workflow'], true) : null;

// ── Weekdays ─────────────────────────────────────────────
function getWeekdays($year, $month) {
    $days = [];
    $total = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($d = 1; $d <= $total; $d++) {
        if (date('N', mktime(0,0,0,$month,$d,$year)) < 6) $days[] = $d;
    }
    return $days;
}
$dayColumns = getWeekdays($year, $month);
$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
               7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
$monthName = $monthNames[$month] ?? '';

// ── Step statuses ─────────────────────────────────────────
$stepStatuses = ['ASO'=>'pending','APM'=>'pending','GM'=>'pending','HQSO'=>'pending','SDHOD'=>'pending'];
if ($workflow) {
    foreach ($workflow['steps'] as $step) {
        $stepStatuses[$step['Code']] = $step['status'];
    }
}
$allDone = !in_array('pending', array_values($stepStatuses));

// ── Totals ────────────────────────────────────────────────
$grandW = 0; $grandE = 0;
$rowData = [];
foreach ($rows as $row) {
    $ad = json_decode($row['attendance_json'], true) ?? [];
    $w  = 0; $e = 0;
    foreach ($ad as $entry) {
        if (($entry['status']??'') === 'P')  $w++;
        if (($entry['status']??'') === 'PP') $e++;
    }
    $grandW += $w; $grandE += $e;
    $rowData[] = ['row'=>$row, 'ad'=>$ad, 'w'=>$w, 'e'=>$e];
}

// ════════════════════════════════════════════════════════
//  Build HTML (shared between mPDF and browser print)
// ════════════════════════════════════════════════════════
$filename = "Attendance_{$siteCode}_{$monthName}_{$year}.pdf";

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($filename) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 8px; color: #1f2937; background:#fff; }

/* ── Print/PDF config ── */
@page {
    size: A3 landscape;
    margin: 10mm 8mm;
}
@media print {
    .no-print { display:none !important; }
    .page-break { page-break-before: always; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

/* ── Title bar ── */
.title-bar { background:#0f766e !important; color:#fff !important; padding:8px 12px; text-align:center; border-radius:4px 4px 0 0; -webkit-print-color-adjust:exact; }
.title-bar h1 { font-size:13px; font-weight:800; margin-bottom:2px; }
.title-bar p  { font-size:8px; opacity:0.92; }

/* ── Attendance table ── */
table.att { width:100%; border-collapse:collapse; margin-top:7px; font-size:7.5px; }
table.att th {
    background:#0f766e !important; color:#fff !important;
    padding:4px 2px; text-align:center; font-size:7px; font-weight:bold;
    border:1px solid #0a5c55; white-space:nowrap;
    -webkit-print-color-adjust:exact;
}
table.att th.left { text-align:left; padding-left:4px; }
table.att th.th-w { background:#0ea5e9 !important; }
table.att th.th-e { background:#f59e0b !important; }
table.att th.th-t { background:#10b981 !important; }

table.att td { padding:2.5px 2px; text-align:center; border:1px solid #e5e7eb; vertical-align:middle; }
table.att td.left { text-align:left; padding-left:4px; white-space:nowrap; }
table.att tr.even td { background:#f9fafb !important; }
table.att tr.odd  td { background:#ffffff; }
table.att td.col-w { background:#dbeafe !important; color:#0369a1; font-weight:bold; -webkit-print-color-adjust:exact; }
table.att td.col-e { background:#fef3c7 !important; color:#b45309; font-weight:bold; -webkit-print-color-adjust:exact; }
table.att td.col-t { background:#d1fae5 !important; color:#059669; font-weight:bold; -webkit-print-color-adjust:exact; }

/* ── Status chips ── */
.chip { border-radius:3px; padding:1px 3px; font-weight:bold; display:inline-block; -webkit-print-color-adjust:exact; }
.p  { background:#d1fae5 !important; color:#059669 !important; }
.pp { background:#bfdbfe !important; color:#1d4ed8 !important; font-size:6.5px; }
.l  { background:#fef3c7 !important; color:#d97706 !important; }
.a  { background:#fee2e2 !important; color:#dc2626 !important; }
.dash { color:#d1d5db; }

/* ── Footer row ── */
tr.foot td { font-weight:bold; font-size:8.5px; }
tr.foot td.lbl { background:#0f766e !important; color:#fff !important; text-align:center; -webkit-print-color-adjust:exact; }

/* ── Legend ── */
.legend { display:flex; gap:12px; justify-content:center; margin:6px 0; font-size:7.5px; flex-wrap:wrap; }

/* ── Flowchart page ── */
.page-break { page-break-before: always; }
.fc-title { background:#0f766e !important; color:#fff !important; text-align:center; padding:10px; font-size:14px; font-weight:800; border-radius:4px; -webkit-print-color-adjust:exact; }
.fc-sub   { text-align:center; font-size:8.5px; color:#6b7280; margin:6px 0 22px; }

/* ── Step circles ── */
table.fc-table { width:100%; border-collapse:collapse; margin:0 auto; }
table.fc-table td { text-align:center; vertical-align:top; padding:0; }
.fc-circle {
    width:70px; height:70px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:24px; font-weight:900; border:3px solid;
    -webkit-print-color-adjust:exact;
}
.fc-circle.approved { background:#f0fdf4 !important; border-color:#22c55e; color:#16a34a; }
.fc-circle.pending  { background:#f9fafb !important; border-color:#d1d5db; color:#9ca3af; }
.fc-code { font-size:10px; font-weight:800; margin-top:5px; }
.fc-code.approved { color:#15803d; }
.fc-code.pending  { color:#9ca3af; }
.fc-desc { font-size:7px; color:#6b7280; margin-top:2px; }
.fc-badge { font-size:7.5px; font-weight:bold; margin-top:3px; }
.fc-badge.approved { color:#16a34a; }
.fc-badge.pending  { color:#9ca3af; }
.fc-arrow-td { vertical-align:middle; font-size:22px; color:#9ca3af; padding:0 4px; padding-bottom:20px; }

/* ── Sign-off box ── */
.signoff {
    border:2px solid #22c55e; border-radius:10px;
    background:#f0fdf4 !important; padding:16px 20px;
    text-align:center; margin:22px auto; max-width:520px;
    -webkit-print-color-adjust:exact;
}
.signoff.warn { border-color:#f59e0b; background:#fffbeb !important; }
.signoff-icon  { font-size:28px; margin-bottom:6px; }
.signoff-title { font-size:14px; font-weight:800; color:#15803d; margin-bottom:5px; }
.signoff-title.warn { color:#b45309; }
.signoff-desc  { font-size:8.5px; color:#4b5563; line-height:1.7; }

/* ── Sig block ── */
table.sig { width:100%; border-collapse:collapse; margin-top:28px; }
table.sig td { text-align:center; padding:6px 4px; border:1px solid #e5e7eb; }
.sig-line  { height:26px; border-bottom:1px solid #374151; margin-bottom:4px; }
.sig-name  { font-size:8.5px; font-weight:bold; color:#374151; }
.sig-sub   { font-size:7px; color:#9ca3af; }

.footer { text-align:center; font-size:7px; color:#9ca3af; margin-top:14px; border-top:1px solid #e5e7eb; padding-top:5px; }

/* ── Print button (hidden in PDF) ── */
.print-btn {
    display:block; margin:14px auto 0; padding:10px 28px;
    background:#0f766e; color:white; border:none; border-radius:8px;
    font-size:14px; font-weight:700; cursor:pointer; letter-spacing:0.5px;
}
.print-btn:hover { background:#0d5f58; }
</style>
</head>
<body>

<!-- ══ PRINT BUTTON (hidden when printing) ══════════════ -->
<div class="no-print" style="text-align:center;padding:10px 0 4px;">
    <button class="print-btn" onclick="triggerPrint()">
        &#128438; Download / Print as PDF
    </button>
    <p style="font-size:11px;color:#6b7280;margin-top:6px;">
        When the print dialog opens &rarr; change <strong>Destination</strong> to <strong>"Save as PDF"</strong> &rarr; click Save
    </p>
</div>

<!-- ══ PAGE 1 : ATTENDANCE TABLE ════════════════════════ -->
<div class="title-bar">
    <h1>Monthly Attendance Report &ndash; MCL (<?= htmlspecialchars($siteName) ?>)</h1>
    <p>Period: <?= $monthName ?> <?= $year ?> &nbsp;|&nbsp; Site: <?= htmlspecialchars($siteCode) ?> &nbsp;|&nbsp; Working Days: <?= count($dayColumns) ?> &nbsp;|&nbsp; Generated: <?= date('d-m-Y H:i') ?></p>
</div>

<table class="att">
    <thead>
        <tr>
            <th style="width:18px">S.N.</th>
            <th style="width:50px">EMP CODE</th>
            <th class="left" style="min-width:90px">NAME</th>
            <th style="width:55px">RANK</th>
            <?php foreach ($dayColumns as $d): ?>
                <th style="width:16px"><?= $d ?></th>
            <?php endforeach; ?>
            <th class="th-w" style="width:34px">WORK</th>
            <th class="th-e" style="width:28px">EXTRA</th>
            <th class="th-t" style="width:28px">TOTAL</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $sn = 1;
    foreach ($rowData as $rd):
        $row = $rd['row']; $ad = $rd['ad'];
        $w   = $rd['w'];   $e  = $rd['e'];
        $rc  = ($sn % 2 === 0) ? 'even' : 'odd';
    ?>
        <tr class="<?= $rc ?>">
            <td><?= $sn ?></td>
            <td><?= htmlspecialchars($row['esic_no']) ?></td>
            <td class="left"><?= htmlspecialchars($row['employee_name']) ?></td>
            <td><?= htmlspecialchars($row['rank']) ?></td>
            <?php foreach ($dayColumns as $day):
                $dk  = $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT).'-'.str_pad($day,2,'0',STR_PAD_LEFT);
                if (isset($ad[$dk])):
                    $st  = $ad[$dk]['status'];
                    $cls = match($st) { 'P'=>'p','PP'=>'pp','L'=>'l','A'=>'a', default=>'' };
            ?><td><span class="chip <?= $cls ?>"><?= $st ?></span></td>
            <?php else: ?><td><span class="dash">-</span></td><?php endif; endforeach; ?>
            <td class="col-w"><?= $w ?></td>
            <td class="col-e"><?= $e ?></td>
            <td class="col-t"><?= $w + $e ?></td>
        </tr>
    <?php $sn++; endforeach; ?>
    <tr class="foot">
        <td colspan="4" class="lbl">TOTALS</td>
        <?php foreach ($dayColumns as $d): ?><td style="background:#f3f4f6"></td><?php endforeach; ?>
        <td class="col-w"><?= $grandW ?></td>
        <td class="col-e"><?= $grandE ?></td>
        <td class="col-t"><?= $grandW + $grandE ?></td>
    </tr>
    </tbody>
</table>

<div class="legend">
    <div><span class="chip p">P</span>&nbsp;Present</div>
    <div><span class="chip pp">PP</span>&nbsp;Double Duty</div>
    <div><span class="chip l">L</span>&nbsp;Leave</div>
    <div><span class="chip a">A</span>&nbsp;Absent</div>
    <div><span class="dash">-</span>&nbsp;Weekend / Holiday</div>
</div>

<!-- ══ PAGE 2 : APPROVAL FLOWCHART ══════════════════════ -->
<div class="page-break"></div>

<div class="fc-title">Approval Flowchart &ndash; <?= htmlspecialchars($siteName) ?></div>
<div class="fc-sub">Period: <?= $monthName ?> <?= $year ?> &nbsp;|&nbsp; Site Code: <?= htmlspecialchars($siteCode) ?></div>

<table class="fc-table">
    <tr>
    <?php
    $flowSteps = [
        'ASO'   => 'Area Security Officer',
        'APM'   => 'Asst. Project Manager',
        'GM'    => 'General Manager',
        'HQSO'  => 'HQ Security Officer',
        'SDHOD' => 'SD Head of Dept',
    ];
    $codes = array_keys($flowSteps);
    foreach ($flowSteps as $code => $label):
        $approved = ($stepStatuses[$code] === 'approved');
    ?>
        <td style="width:18%">
            <div class="fc-circle <?= $approved ? 'approved' : 'pending' ?>">
                <?= $approved ? '&#10003;' : '&ndash;' ?>
            </div>
            <div class="fc-code <?= $approved ? 'approved' : 'pending' ?>"><?= $code ?></div>
            <div class="fc-desc"><?= $label ?></div>
            <div class="fc-badge <?= $approved ? 'approved' : 'pending' ?>"><?= $approved ? '&#10003; APPROVED' : 'PENDING' ?></div>
        </td>
        <?php if ($code !== 'SDHOD'): ?>
        <td class="fc-arrow-td" style="width:2.5%">&#8594;</td>
        <?php endif; ?>
    <?php endforeach; ?>
    </tr>
</table>

<?php if ($allDone): ?>
<div class="signoff">
    <div class="signoff-icon">&#127937;</div>
    <div class="signoff-title">Workflow Complete &ndash; Finally Approved</div>
    <div class="signoff-desc">
        This monthly attendance report has been reviewed and approved by all five authorities.<br>
        <strong>ASO &rarr; APM &rarr; GM &rarr; HQSO &rarr; SDHOD</strong> &mdash; All steps cleared.
    </div>
</div>
<?php else: ?>
<div class="signoff warn">
    <div class="signoff-title warn">&#9888; Approval In Progress</div>
    <div class="signoff-desc">Some approval steps are still pending completion.</div>
</div>
<?php endif; ?>

<table class="sig">
    <tr>
        <?php foreach ($flowSteps as $code => $label): ?>
        <td>
            <div class="sig-line"></div>
            <div class="sig-name"><?= $code ?></div>
            <div class="sig-sub"><?= $label ?></div>
            <div class="sig-sub">Signature &amp; Date</div>
        </td>
        <?php endforeach; ?>
    </tr>
</table>

<div class="footer">
    Security Billing Management Portal &ndash; MCL &nbsp;|&nbsp;
    <?= htmlspecialchars($siteName) ?> &nbsp;|&nbsp;
    <?= $monthName ?> <?= $year ?> &nbsp;|&nbsp;
    System generated &nbsp;|&nbsp; <?= date('d-m-Y H:i:s') ?>
</div>

<script>
function triggerPrint() {
    window.print();
}
// Auto-trigger print dialog on page load (comment out if you don't want auto-print)
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 800);
});
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// ════════════════════════════════════════════════════════
//  Try mPDF → TCPDF → wkhtmltopdf → browser print
// ════════════════════════════════════════════════════════

// 1. mPDF
if (class_exists('\Mpdf\Mpdf')) {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A3-L',
        'margin_left'   => 8,
        'margin_right'  => 8,
        'margin_top'    => 8,
        'margin_bottom' => 8,
    ]);
    $mpdf->SetTitle($filename);
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, 'D');  // D = force download
    exit;
}

// 2. TCPDF
if (class_exists('TCPDF')) {
    $pdf = new TCPDF('L', PDF_UNIT, 'A3', true, 'UTF-8', false);
    $pdf->SetTitle($filename);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filename, 'D');
    exit;
}

// 3. wkhtmltopdf
$wk = trim(shell_exec('which wkhtmltopdf 2>/dev/null'));
if ($wk) {
    $tmpHtml = sys_get_temp_dir().'/att_'.uniqid().'.html';
    $tmpPdf  = sys_get_temp_dir().'/att_'.uniqid().'.pdf';
    file_put_contents($tmpHtml, $html);
    shell_exec("$wk --orientation Landscape --page-size A3 --margin-top 10 --margin-bottom 10 --margin-left 8 --margin-right 8 --quiet ".escapeshellarg($tmpHtml)." ".escapeshellarg($tmpPdf));
    if (file_exists($tmpPdf) && filesize($tmpPdf) > 0) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($tmpPdf));
        readfile($tmpPdf);
        @unlink($tmpHtml);
        @unlink($tmpPdf);
        exit;
    }
}

// 4. Browser print-to-PDF (always works, opens print dialog automatically)
header('Content-Type: text/html; charset=UTF-8');
echo $html;