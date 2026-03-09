<?php
session_start();
require "../config.php";

/* ---------------------------
   LOGIN CHECK
----------------------------*/
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

/* ---------------------------
   GET USER ROLE
----------------------------*/
$stmtUser = $pdo->prepare("SELECT role, site_code, name FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Access denied");
}

$userId   = $_SESSION['user'];
$userRole = $user['role'];

/* ---------------------------
   FILTER PARAMS
----------------------------*/
$selectedYear  = (int)($_POST['year']  ?? $_GET['year']  ?? date('Y'));
$selectedMonth = (int)($_POST['month'] ?? $_GET['month'] ?? date('n'));
$reportLoaded  = isset($_POST['load_report']) || isset($_GET['load_report']);

/* ---------------------------
   HELPER: Month Names
----------------------------*/
$monthNames = [
    1=>'January',2=>'February',3=>'March',4=>'April',
    5=>'May',6=>'June',7=>'July',8=>'August',
    9=>'September',10=>'October',11=>'November',12=>'December'
];
$monthName = $monthNames[$selectedMonth] ?? '';

/* ---------------------------
   FETCH LPP DATA & TOTALS
----------------------------*/
$lppRows         = [];
$comments        = [];
$workflowRow     = null;
$workflow        = null;
$alreadyApproved = false;
$stepStatuses    = ['SDHOD' => 'pending', 'FINANCE' => 'pending'];
$lastApprovedCode = '—';

$grandAmount     = 0;
$grandGst        = 0;
$grandGrossTotal = 0;
$grandItTds      = 0;
$grandSgst       = 0;
$grandCgst       = 0;
$grandRetention  = 0;
$grandBonus      = 0;
$grandNetPayment = 0;

if ($reportLoaded) {

    $stmtLPP = $pdo->prepare("
        SELECT l.*, sm.SiteName
        FROM monthly_lpp l
        LEFT JOIN site_master sm ON l.site_code = sm.SiteCode
        WHERE l.lpp_year = :year AND l.lpp_month = :month
        ORDER BY sm.SiteName ASC
    ");
    $stmtLPP->execute([':year' => $selectedYear, ':month' => $selectedMonth]);
    $lppRows = $stmtLPP->fetchAll(PDO::FETCH_ASSOC) ?? [];

    foreach ($lppRows as $r) {
        $grandAmount     += (float)($r['amount']      ?? 0);
        $grandGst        += (float)($r['gst']         ?? 0);
        $grandGrossTotal += (float)($r['gross_total'] ?? 0);
        $grandItTds      += (float)($r['it_tds']      ?? 0);
        $grandSgst       += (float)($r['sgst']        ?? 0);
        $grandCgst       += (float)($r['cgst']        ?? 0);
        $grandRetention  += (float)($r['retention']   ?? 0);
        $grandBonus      += (float)($r['bonus']       ?? 0);
        $grandNetPayment += (float)($r['net_payment'] ?? 0);
    }

    $stmtWF = $pdo->prepare("SELECT * FROM lpp_approval WHERE lpp_month = ? AND lpp_year = ?");
    $stmtWF->execute([$selectedMonth, $selectedYear]);
    $workflowRow = $stmtWF->fetch(PDO::FETCH_ASSOC);
    $workflow    = $workflowRow ? json_decode($workflowRow['lpp_workflow'], true) : null;

    if ($workflow) {
        foreach ($workflow['steps'] as $step) {
            if ($step['status'] === 'approved') {
                $stepStatuses[$step['Code']] = 'approved';
            }
        }
        $liveStep = $workflow['current_step'] ?? 'SDHOD';
        if (isset($stepStatuses[$liveStep]) && $stepStatuses[$liveStep] !== 'approved') {
            $stepStatuses[$liveStep] = 'current';
        }
        foreach (array_reverse($workflow['steps']) as $s) {
            if ($s['status'] === 'approved') { $lastApprovedCode = $s['Code']; break; }
        }
        foreach ($workflow['steps'] as $step) {
            if ($step['Code'] === 'FINANCE' && $step['status'] === 'approved') { $alreadyApproved = true; break; }
        }
        foreach ($workflow['steps'] as $step) {
            if (!empty($step['comment']) && $step['status'] === 'approved') {
                $actorName = 'Unknown';
                if (!empty($step['acted_by'])) {
                    $stmtA = $pdo->prepare("SELECT name FROM user WHERE id = ?");
                    $stmtA->execute([$step['acted_by']]);
                    $actor = $stmtA->fetch(PDO::FETCH_ASSOC);
                    $actorName = $actor['name'] ?? 'Unknown';
                }
                $comments[] = ['role'=>$step['Code'],'approved_by'=>$actorName,'comment'=>$step['comment'],'created_at'=>$step['acted_at']??''];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly LPP – Security Billing Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root { --primary:#0f766e; --primary-dark:#0d5f58; --sidebar-width:270px; }
        html { scroll-behavior:smooth; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; line-height:1.6; }
        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }

        /* ── SIDEBAR ── */
        .sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; transition:transform 0.3s cubic-bezier(0.4,0,0.2,1); display:flex; flex-direction:column; }
        .sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,0.12); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:1rem; align-items:center; justify-content:center; z-index:2; }
        .sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .mcl-logo-img { max-width:155px; height:auto; display:block; background:white; padding:10px 14px; border-radius:10px; }
        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li { margin:0.25rem 1rem; }
        .nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; }
        .nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }

        /* ── MAIN ── */
        .main-content { padding:2rem; overflow-y:auto; display:flex; flex-direction:column; gap:1.5rem; min-width:0; }
        .topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .hamburger-btn { display:none; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px; width:38px; height:38px; align-items:center; justify-content:center; cursor:pointer; color:#0f766e; font-size:1rem; }
        .topbar h2 { font-size:1.4rem; font-weight:700; color:#1f2937; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .header-icon { width:40px; height:40px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; color:#6b7280; font-size:1rem; border:1px solid #e5e7eb; }
        .header-icon .badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:0.65rem; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-icon { width:40px; height:40px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
        .user-icon svg { width:20px; height:20px; stroke:white; }
        .role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:#f0fdf4; color:#15803d; border:1.5px solid #86efac; border-radius:20px; padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700; letter-spacing:0.5px; }

        /* ── FILTER ── */
        .filter-panel { background:white; border-radius:18px; border:1px solid #e5e7eb; box-shadow:0 4px 24px rgba(0,0,0,0.08); padding:2rem 2.5rem 1.75rem; max-width:780px; width:100%; margin:0 auto; }
        .filter-grid { display:grid; grid-template-columns:1fr auto; gap:1.25rem; align-items:end; }
        .filter-field { display:flex; flex-direction:column; gap:0.45rem; }
        .filter-label { display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; }
        .filter-label i { color:#0f766e; }

        /* Month picker */
        .month-picker-wrapper { position:relative; }
        .month-display-input { width:100%; padding:0.8rem 1rem; border:1.5px solid #e5e7eb; border-radius:10px; font-size:0.95rem; color:#1f2937; background:#f9fafb; outline:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; transition:border-color 0.2s; }
        .month-display-input:hover,.month-display-input.open { border-color:#0f766e; background:white; }
        .month-picker-popup { display:none; position:absolute; top:calc(100% + 8px); left:0; background:white; border:1.5px solid #e5e7eb; border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,0.12); padding:1.2rem; z-index:200; min-width:290px; }
        .month-picker-popup.open { display:block; animation:fadeUp 0.2s ease; }
        @keyframes fadeUp { 0%{transform:translateY(8px);opacity:0} 100%{transform:translateY(0);opacity:1} }
        .picker-year { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem; }
        .picker-year-label { font-size:1rem; font-weight:700; color:#1f2937; }
        .picker-year-btn { background:none; border:1.5px solid #e5e7eb; border-radius:8px; width:30px; height:30px; cursor:pointer; color:#374151; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .picker-year-btn:hover { border-color:#0f766e; color:#0f766e; }
        .picker-months { display:grid; grid-template-columns:repeat(4,1fr); gap:0.5rem; }
        .picker-month-btn { padding:0.5rem; border-radius:8px; border:1.5px solid #e5e7eb; background:white; font-size:0.82rem; font-weight:600; color:#374151; cursor:pointer; transition:all 0.2s; text-align:center; }
        .picker-month-btn:hover { border-color:#0f766e; color:#0f766e; background:#f0fdf4; }
        .picker-month-btn.active { background:#0f766e; color:white; border-color:#0f766e; }
        .picker-footer { display:flex; justify-content:space-between; margin-top:0.6rem; padding-top:0.6rem; border-top:1px solid #f0f0f0; font-size:0.8rem; }
        .picker-footer a { color:#6b7280; cursor:pointer; }
        .picker-footer a:hover { color:#0f766e; }
        .picker-this-month { color:#0f766e !important; font-weight:600; }
        .btn-load-report { display:inline-flex; align-items:center; gap:0.6rem; padding:0.85rem 2rem; border-radius:10px; background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; border:none; font-size:0.95rem; font-weight:700; cursor:pointer; white-space:nowrap; transition:all 0.2s; box-shadow:0 4px 14px rgba(15,118,110,0.3); letter-spacing:0.3px; text-transform:uppercase; }
        .btn-load-report:hover { background:linear-gradient(135deg,#0d5f58,#0a4f49); transform:translateY(-1px); }

        /* ── WORKFLOW ── */
        .workflow-section { background:white; border-radius:14px; padding:1.5rem 2rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; gap:1.2rem; }
        .workflow-title { display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; font-weight:600; color:#374151; }
        .workflow-title i { color:#0f766e; }
        .workflow-steps { display:flex; align-items:center; justify-content:center; }
        .workflow-step { display:flex; flex-direction:column; align-items:center; gap:0.5rem; }
        .step-card { width:115px; height:115px; border-radius:14px; border:2px solid #e5e7eb; background:#f9fafb; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.35rem; position:relative; padding-top:18px; transition:all 0.2s; }
        .step-avatar { width:54px; height:54px; border-radius:50%; background:#d1d5db; display:flex; align-items:center; justify-content:center; }
        .step-avatar i { font-size:1.55rem; color:white; }
        .step-label { font-size:0.78rem; font-weight:700; color:#6b7280; text-transform:uppercase; }
        .step-sub   { font-size:0.65rem; color:#9ca3af; }
        .step-check { position:absolute; top:-11px; left:50%; transform:translateX(-50%); width:22px; height:22px; border-radius:50%; background:#16a34a; color:white; display:flex; align-items:center; justify-content:center; font-size:0.65rem; border:2px solid white; box-shadow:0 1px 4px rgba(0,0,0,0.15); }
        .workflow-step.approved .step-card  { border-color:#86efac; background:#f0fdf4; }
        .workflow-step.approved .step-avatar { background:#16a34a; }
        .workflow-step.approved .step-label  { color:#15803d; }
        .workflow-step.current  .step-card  { border-color:#fca5a5; background:#fef2f2; box-shadow:0 0 0 3px rgba(220,38,38,0.12); }
        .workflow-step.current  .step-avatar { background:#dc2626; }
        .workflow-step.current  .step-label  { color:#b91c1c; }
        .workflow-step.pending  .step-avatar { background:#9ca3af; }
        .workflow-step.pending  .step-label  { color:#9ca3af; }
        .workflow-arrow { display:flex; align-items:center; padding:0 1.4rem; color:#9ca3af; font-size:1.1rem; margin-bottom:1.5rem; }
        .btn-approval { display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1.4rem; border-radius:8px; background:#fef2f2; color:#b91c1c; border:1.5px solid #fca5a5; font-size:0.84rem; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .btn-approval:hover { background:#fee2e2; }
        .approval-comments { display:none; width:100%; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
        .approval-comments.open { display:block; animation:fadeUp 0.3s ease; }
        .comment-item { padding:0.9rem 1.25rem; border-bottom:1px solid #f0f0f0; background:white; }
        .comment-item:last-child { border-bottom:none; }
        .comment-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem; }
        .comment-role { font-size:0.85rem; font-weight:700; color:#1f2937; }
        .comment-role span { color:#0f766e; margin-left:0.3rem; }
        .comment-time { font-size:0.75rem; color:#9ca3af; display:flex; align-items:center; gap:0.3rem; }
        .comment-text { font-size:0.84rem; color:#4b5563; background:#f9fafb; border-radius:8px; padding:0.5rem 0.85rem; border-left:3px solid #fca5a5; font-style:italic; }

        /* ── EMPTY STATE ── */
        .empty-state { background:white; border-radius:18px; border:1px solid #e5e7eb; padding:5rem 2rem; display:flex; flex-direction:column; align-items:center; gap:1rem; }
        .empty-icon { width:72px; height:72px; background:#f3f4f6; border-radius:50%; display:flex; align-items:center; justify-content:center; }
        .empty-icon i { font-size:2rem; color:#9ca3af; }
        .empty-title { font-size:1.2rem; font-weight:700; color:#1f2937; }
        .empty-sub { font-size:0.9rem; color:#6b7280; text-align:center; max-width:400px; }
        .empty-sub strong { color:#1f2937; }

        /* ── CARD ── */
        .card { background:white; border-radius:14px; padding:1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem; flex-wrap:wrap; gap:0.75rem; }
        .card-title { font-size:0.95rem; font-weight:700; color:#0f766e; display:flex; align-items:center; gap:0.5rem; }
        .table-toolbar { display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap; }
        .show-entries { display:flex; align-items:center; gap:0.4rem; font-size:0.86rem; color:#6b7280; }
        .show-entries select { padding:0.38rem 0.6rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.86rem; outline:none; }
        .export-buttons { display:flex; gap:0.45rem; }
        .btn-export { display:inline-flex; align-items:center; gap:0.35rem; padding:0.42rem 0.9rem; border-radius:7px; font-size:0.82rem; font-weight:700; cursor:pointer; border:none; transition:all 0.2s; }
        .btn-excel { background:#16a34a; color:white; } .btn-excel:hover { background:#15803d; }
        .btn-pdf   { background:#dc2626; color:white; } .btn-pdf:hover   { background:#b91c1c; }
        .search-input { padding:0.46rem 0.9rem 0.46rem 2.35rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.86rem; outline:none; width:190px; background:white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.7rem center; background-size:14px; }
        .search-input:focus { border-color:#0f766e; }

        /* ── BILLING TABLE ── */
        .billing-table-wrapper { overflow-x:auto; border-radius:10px; border:1px solid #e5e7eb; scrollbar-width:thin; scrollbar-color:#0f766e #f0f0f0; }
        .billing-table-wrapper::-webkit-scrollbar { height:5px; }
        .billing-table-wrapper::-webkit-scrollbar-thumb { background:#0f766e; border-radius:3px; }
        .billing-table { width:max-content; min-width:100%; border-collapse:separate; border-spacing:0; font-size:0.81rem; background:white; }

        /* Header */
        .billing-table thead th { background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; font-weight:700; font-size:0.73rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.78rem 0.7rem; text-align:right; white-space:nowrap; }
        .billing-table thead th.th-left   { text-align:left; }
        .billing-table thead th.th-center { text-align:center; }

        /* Sticky left: SL NO + SITE NAME */
        .billing-table thead th:nth-child(1),
        .billing-table tbody td:nth-child(1),
        .billing-table tfoot td:nth-child(1) { position:sticky; left:0; z-index:5; min-width:54px; width:54px; text-align:center; }
        .billing-table thead th:nth-child(2),
        .billing-table tbody td:nth-child(2),
        .billing-table tfoot td:nth-child(2) { position:sticky; left:54px; z-index:5; min-width:240px; width:240px; text-align:left; }
        .billing-table thead th:nth-child(1),
        .billing-table thead th:nth-child(2) { z-index:7; }
        .billing-table tfoot td:nth-child(1),
        .billing-table tfoot td:nth-child(2) { z-index:6; }
        /* white bg for sticky body cells */
        .billing-table tbody td:nth-child(1),
        .billing-table tbody td:nth-child(2) { background:white; }
        .billing-table tbody tr:nth-child(even) td:nth-child(1),
        .billing-table tbody tr:nth-child(even) td:nth-child(2) { background:#fafafa; }
        .billing-table tbody tr:hover td:nth-child(1),
        .billing-table tbody tr:hover td:nth-child(2) { background:#f0fdf4; }
        .billing-table tbody td:nth-child(2) { border-right:2px solid #e5e7eb !important; }

        /* Sticky right: NET PAYMENT */
        .billing-table thead th.col-net,
        .billing-table tbody td.col-net,
        .billing-table tfoot td.col-net { position:sticky; right:0; }
        .billing-table thead th.col-net { z-index:7; background:linear-gradient(135deg,#059669,#047857) !important; }
        .billing-table tfoot td.col-net { z-index:6; background:linear-gradient(135deg,#059669,#047857) !important; color:white !important; border-left:2px solid #047857 !important; }
        .billing-table tbody td.col-net { background:#d1fae5; color:#065f46; font-weight:700; border-left:2px solid #6ee7b7 !important; }
        .billing-table tbody tr:nth-child(even) td.col-net { background:#a7f3d0; }
        .billing-table tbody tr:hover td.col-net { background:#6ee7b7; }

        /* Body cells */
        .billing-table tbody td { padding:0.65rem 0.7rem; border-bottom:1px solid #f0f0f0; text-align:right; color:#1f2937; vertical-align:middle; white-space:nowrap; }
        .billing-table tbody tr:hover { background:#f0fdf4; transition:background 0.15s; }
        .billing-table tbody tr:nth-child(even) { background:#fafafa; }
        .billing-table tbody tr:last-child td { border-bottom:none; }
        .sn-cell { color:#9ca3af !important; font-size:0.77rem; font-weight:600; }
        .site-name-cell { font-weight:600; color:#1f2937 !important; font-size:0.82rem; padding-left:0.9rem !important; }

        /* Grand total row */
        .billing-table tfoot td { background:linear-gradient(135deg,#f0fdf4,#dcfce7); font-weight:800; color:#065f46; padding:0.78rem 0.7rem; border-top:2px solid #6ee7b7; text-align:right; white-space:nowrap; font-size:0.82rem; }
        .billing-table tfoot td:nth-child(1) { background:linear-gradient(135deg,#f0fdf4,#dcfce7) !important; }
        .billing-table tfoot td:nth-child(2) { background:linear-gradient(135deg,#f0fdf4,#dcfce7) !important; border-right:2px solid #6ee7b7 !important; }
        .grand-label { font-size:0.82rem; font-weight:800; color:#065f46; }

        /* Pagination */
        .pagination { display:flex; align-items:center; justify-content:space-between; margin-top:1.1rem; flex-wrap:wrap; gap:0.6rem; }
        .pagination-info { font-size:0.82rem; color:#6b7280; }
        .pagination-btns { display:flex; align-items:center; gap:0.4rem; }
        .page-btn { padding:0.38rem 0.8rem; border-radius:7px; border:1.5px solid #e5e7eb; background:white; cursor:pointer; font-size:0.86rem; font-weight:600; color:#6b7280; transition:all 0.2s; }
        .page-btn:hover { border-color:#0f766e; color:#0f766e; }
        .page-btn.active { background:#0f766e; color:white; border-color:#0f766e; }
        .page-btn:disabled { opacity:0.4; cursor:not-allowed; }

        /* Sidebar overlay */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }

        @media (max-width:900px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .main-content { padding:1rem; gap:1rem; }
            .filter-grid { grid-template-columns:1fr; }
            .btn-load-report { width:100%; justify-content:center; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"        class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthlyatt.php"        class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="monthlylpp.php"        class="nav-link active"><i class="fa-solid fa-calendar-year"></i><span>Monthly LPP</span></a></li>
            <li><a href="detailsmonthlylpp.php" class="nav-link"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a href="vvstatement.php"       class="nav-link"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a href="../logout.php"         class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
            <h2>Security Billing Management Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($userRole) ?></span>
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <a href="profile.php" title="My Profile" style="text-decoration:none;">
                    <div class="user-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
                        </svg>
                    </div>
                </a>
            </div>
        </header>

        <!-- FILTER PANEL -->
        <div class="filter-panel">
            <form method="POST" action="" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-field">
                        <label class="filter-label"><i class="fa-regular fa-calendar"></i> Select Billing Month &amp; Year</label>
                        <div class="month-picker-wrapper">
                            <div class="month-display-input" id="monthDisplayInput" onclick="togglePicker()">
                                <span id="monthDisplayText"><?= $monthName . ', ' . $selectedYear ?></span>
                                <i class="fa-solid fa-chevron-down" style="font-size:0.72rem;color:#6b7280;"></i>
                            </div>
                            <div class="month-picker-popup" id="monthPickerPopup">
                                <div class="picker-year">
                                    <button type="button" class="picker-year-btn" onclick="changeYear(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                                    <span class="picker-year-label" id="pickerYearLabel"><?= $selectedYear ?></span>
                                    <button type="button" class="picker-year-btn" onclick="changeYear(1)"><i class="fa-solid fa-chevron-right"></i></button>
                                </div>
                                <div class="picker-months" id="pickerMonths"></div>
                                <div class="picker-footer">
                                    <a onclick="clearPicker()">Clear</a>
                                    <a class="picker-this-month" onclick="setThisMonth()">This month</a>
                                </div>
                            </div>
                            <input type="hidden" name="month" id="hiddenMonth" value="<?= $selectedMonth ?>">
                            <input type="hidden" name="year"  id="hiddenYear"  value="<?= $selectedYear ?>">
                        </div>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" style="visibility:hidden;">Load</label>
                        <button type="submit" name="load_report" class="btn-load-report">
                            <i class="fa-solid fa-filter"></i> Load Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($reportLoaded): ?>

            <?php if (empty($lppRows)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
                <div class="empty-title">LPP Not Generated Yet</div>
                <div class="empty-sub">It looks like no LPP records have been created for <strong><?= $monthName . ' ' . $selectedYear ?></strong></div>
            </div>

            <?php else: ?>

            <!-- WORKFLOW -->
            <div class="workflow-section">
                <div class="workflow-title"><i class="fa-solid fa-sitemap"></i> LPP Report Approval Workflow</div>
                <div class="workflow-steps">
                    <?php
                    $flowSteps = [
                        ['code'=>'SDHOD',   'sub'=>'Forwarded', 'icon'=>'fa-user-tie'],
                        ['code'=>'FINANCE', 'sub'=>'Approved',  'icon'=>'fa-landmark'],
                    ];
                    foreach ($flowSteps as $i => $fs):
                        $status = $stepStatuses[$fs['code']] ?? 'pending';
                    ?>
                        <div class="workflow-step <?= $status ?>">
                            <div class="step-card">
                                <?php if ($status === 'approved'): ?><span class="step-check"><i class="fa-solid fa-check"></i></span><?php endif; ?>
                                <div class="step-avatar"><i class="fa-solid <?= $fs['icon'] ?>"></i></div>
                                <span class="step-label"><?= $fs['code'] ?></span>
                                <span class="step-sub"><?= $fs['sub'] ?></span>
                            </div>
                        </div>
                        <?php if ($i < count($flowSteps) - 1): ?><div class="workflow-arrow"><i class="fa-solid fa-arrow-right"></i></div><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <button class="btn-approval" id="toggleCommentsBtn" onclick="toggleComments()">
                    <i class="fa-solid fa-comments"></i> View Approval Comments
                    <i class="fa-solid fa-chevron-down" id="commentChevron" style="font-size:0.7rem;"></i>
                </button>
                <div id="approvalComments" class="approval-comments">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $c): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-role"><?= htmlspecialchars($c['role']) ?> :<span><?= htmlspecialchars($c['approved_by']) ?></span></div>
                                <div class="comment-time"><i class="fa-regular fa-clock" style="font-size:0.68rem;"></i> <?= htmlspecialchars($c['created_at']) ?></div>
                            </div>
                            <div class="comment-text">"<?= htmlspecialchars($c['comment']) ?>"</div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center;color:#9ca3af;padding:1.25rem;font-size:0.88rem;">No comments yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BILLING TABLE CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fa-solid fa-table-list"></i>
                        System Generated LPP Billing Summary (Source: Master)
                    </div>
                    <div class="table-toolbar">
                        <div class="show-entries">
                            <label>Show</label>
                            <select id="pageSizeSelect" onchange="changePageSize(this.value)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <label>entries</label>
                        </div>
                        <div class="export-buttons">
                            <button class="btn-export btn-excel" onclick="exportExcel()"><i class="fa-solid fa-file-excel"></i> Excel</button>
                            <button class="btn-export btn-pdf"   onclick="exportPdf()"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                        </div>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search" oninput="filterTable()">
                    </div>
                </div>

                <div class="billing-table-wrapper">
                    <table class="billing-table" id="billingTable">
                        <thead>
                            <tr>
                                <th class="th-center">SL NO</th>
                                <th class="th-left">SITE NAME</th>
                                <th class="th-center">BES NO</th>
                                <th class="th-center">DAR NO</th>
                                <th>AMOUNT</th>
                                <th>GST (18%)</th>
                                <th>GROSS TOTAL</th>
                                <th>IT TDS</th>
                                <th>SGST</th>
                                <th>CGST</th>
                                <th>RETENTION</th>
                                <th>BONUS</th>
                                <th class="col-net">NET PAYMENT</th>
                            </tr>
                        </thead>
                        <tbody id="billingBody">
                        <?php
                        $sn = 1;
                        $fmt = fn($v) => ($v == 0) ? '₹0' : '₹'.number_format($v);
                        foreach ($lppRows as $r):
                        ?>
                        <tr>
                            <td class="sn-cell"><?= $sn++ ?></td>
                            <td class="site-name-cell"><?= htmlspecialchars($r['SiteName'] ?? $r['site_name'] ?? '—') ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['bes_no'] ?? '—') ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['dar_no'] ?? '—') ?></td>
                            <td><?= $fmt((float)($r['amount']      ?? 0)) ?></td>
                            <td><?= $fmt((float)($r['gst']         ?? 0)) ?></td>
                            <td style="font-weight:600;"><?= $fmt((float)($r['gross_total'] ?? 0)) ?></td>
                            <td><?= $fmt((float)($r['it_tds']      ?? 0)) ?></td>
                            <td><?= $fmt((float)($r['sgst']        ?? 0)) ?></td>
                            <td><?= $fmt((float)($r['cgst']        ?? 0)) ?></td>
                            <td><?= $fmt((float)($r['retention']   ?? 0)) ?></td>
                            <td><?= $fmt((float)($r['bonus']       ?? 0)) ?></td>
                            <td class="col-net"><?= $fmt((float)($r['net_payment'] ?? 0)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td class="grand-label"><i class="fa-solid fa-sigma" style="margin-right:0.3rem;"></i>GRAND TOTAL</td>
                                <td></td><td></td>
                                <td>₹<?= number_format($grandAmount) ?></td>
                                <td>₹<?= number_format($grandGst) ?></td>
                                <td>₹<?= number_format($grandGrossTotal) ?></td>
                                <td>₹<?= number_format($grandItTds) ?></td>
                                <td>₹<?= number_format($grandSgst) ?></td>
                                <td>₹<?= number_format($grandCgst) ?></td>
                                <td>₹<?= number_format($grandRetention) ?></td>
                                <td>₹<?= number_format($grandBonus) ?></td>
                                <td class="col-net">₹<?= number_format($grandNetPayment) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="pagination" id="paginationBar">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-btns" id="paginationBtns"></div>
                </div>
            </div>

            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<script>
/* SIDEBAR */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('hamburgerBtn').addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('active'); });
document.getElementById('sidebarClose').addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

/* COMMENTS TOGGLE */
function toggleComments() {
    const panel   = document.getElementById('approvalComments');
    const chevron = document.getElementById('commentChevron');
    const btn     = document.getElementById('toggleCommentsBtn');
    const open    = panel.classList.toggle('open');
    if (chevron) chevron.className = open ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
    btn.childNodes[0].textContent = ' ';
    btn.innerHTML = open
        ? '<i class="fa-solid fa-comments"></i> Hide Approval Comments <i id="commentChevron" class="fa-solid fa-chevron-up" style="font-size:0.7rem;"></i>'
        : '<i class="fa-solid fa-comments"></i> View Approval Comments <i id="commentChevron" class="fa-solid fa-chevron-down" style="font-size:0.7rem;"></i>';
}

/* MONTH PICKER */
const shortMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const fullMonths  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
let pickerYear  = <?= (int)$selectedYear ?>;
let pickerMonth = <?= (int)$selectedMonth ?>;
const initYear  = <?= (int)$selectedYear ?>;
const initMonth = <?= (int)$selectedMonth ?>;

function renderPickerMonths() {
    const grid = document.getElementById('pickerMonths');
    if (!grid) return;
    grid.innerHTML = '';
    shortMonths.forEach((m, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'picker-month-btn' + ((i+1 === pickerMonth && pickerYear === initYear) ? ' active' : '');
        btn.textContent = m;
        btn.onclick = () => selectMonth(i+1);
        grid.appendChild(btn);
    });
    document.getElementById('pickerYearLabel').textContent = pickerYear;
}
function selectMonth(m) {
    pickerMonth = m;
    document.getElementById('hiddenMonth').value = m;
    document.getElementById('hiddenYear').value  = pickerYear;
    document.getElementById('monthDisplayText').textContent = fullMonths[m-1] + ', ' + pickerYear;
    closePicker();
}
function changeYear(d) { pickerYear += d; renderPickerMonths(); }
function togglePicker() {
    const popup = document.getElementById('monthPickerPopup');
    const input = document.getElementById('monthDisplayInput');
    const open  = popup.classList.contains('open');
    popup.classList.toggle('open', !open);
    input.classList.toggle('open', !open);
    if (!open) renderPickerMonths();
}
function closePicker() {
    document.getElementById('monthPickerPopup')?.classList.remove('open');
    document.getElementById('monthDisplayInput')?.classList.remove('open');
}
function clearPicker()    { const n=new Date(); pickerYear=n.getFullYear(); pickerMonth=n.getMonth()+1; selectMonth(pickerMonth); }
function setThisMonth()   { clearPicker(); }
document.addEventListener('click', e => {
    const w = document.querySelector('.month-picker-wrapper');
    if (w && !w.contains(e.target)) closePicker();
});

/* PAGINATION */
let allRows  = [];
let pageSize = 10;
let curPage  = 1;

function initPagination() {
    const tbody = document.getElementById('billingBody');
    if (!tbody) return;
    allRows = Array.from(tbody.querySelectorAll('tr'));
    renderPage();
}
function renderPage() {
    const visible = allRows.filter(r => r.dataset.hidden !== 'true');
    const total   = visible.length;
    const pages   = Math.max(1, Math.ceil(total / pageSize));
    if (curPage > pages) curPage = pages;
    const start = (curPage-1) * pageSize;
    const end   = start + pageSize;
    visible.forEach((r, i) => r.style.display = (i >= start && i < end) ? '' : 'none');
    allRows.filter(r => r.dataset.hidden === 'true').forEach(r => r.style.display = 'none');

    const info = document.getElementById('paginationInfo');
    if (info) {
        const s = total === 0 ? 0 : start+1;
        const e = Math.min(end, total);
        info.textContent = `Showing ${s} to ${e} of ${total} entries`;
    }
    const btns = document.getElementById('paginationBtns');
    if (!btns) return;
    btns.innerHTML = '';
    const addBtn = (label, page, active, disabled) => {
        const b = document.createElement('button');
        b.className = 'page-btn' + (active ? ' active' : '');
        b.textContent = label;
        b.disabled = disabled;
        if (!disabled) b.onclick = () => { curPage = page; renderPage(); };
        btns.appendChild(b);
    };
    addBtn('Previous', curPage-1, false, curPage===1);
    for (let p=1; p<=pages; p++) {
        if (p===1 || p===pages || (p>=curPage-2 && p<=curPage+2)) {
            addBtn(p, p, p===curPage, false);
        } else if (p===curPage-3 || p===curPage+3) {
            const d = document.createElement('span');
            d.textContent='…'; d.style.cssText='padding:0 0.25rem;color:#9ca3af;';
            btns.appendChild(d);
        }
    }
    addBtn('Next', curPage+1, false, curPage===pages);
}
function changePageSize(v) { pageSize = parseInt(v); curPage = 1; renderPage(); }

/* TABLE SEARCH */
function filterTable() {
    const q = document.getElementById('searchInput')?.value.toLowerCase() ?? '';
    allRows.forEach(r => { r.dataset.hidden = r.textContent.toLowerCase().includes(q) ? 'false' : 'true'; });
    curPage = 1;
    renderPage();
}

/* EXPORT */
function buildExportUrl(page) {
    const m = document.getElementById('hiddenMonth').value;
    const y = document.getElementById('hiddenYear').value;
    return page + '?year=' + encodeURIComponent(y) + '&month=' + encodeURIComponent(m);
}
function exportExcel() { window.location.href = buildExportUrl('export_lpp_excel.php'); }
function exportPdf()   { window.open(buildExportUrl('export_lpp_pdf.php'), '_blank'); }

document.addEventListener('DOMContentLoaded', initPagination);
</script>
</body>
</html>