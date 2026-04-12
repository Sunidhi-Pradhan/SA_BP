<?php
session_start();
require "../config.php";

/* ── AUTH ── */
if (!isset($_SESSION['user'])) { header("Location: ../login.php"); exit; }
$stmtU = $pdo->prepare("SELECT role, name FROM user WHERE id = ?");
$stmtU->execute([$_SESSION['user']]);
$user = $stmtU->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'SDHOD') { die("Access denied"); }
$userId = $_SESSION['user'];
$userName = $user['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly LPP – Security Attendance and Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root { --primary:#0f766e; --primary-dark:#0d5f58; --sidebar-width:270px; }
        html { scroll-behavior:smooth; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; line-height:1.6; }
        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }
        .sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; display:flex; flex-direction:column; }
        .sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,0.12); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:1rem; align-items:center; justify-content:center; z-index:2; }
        .sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .mcl-logo-box { background:white; padding:8px 14px; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .mcl-logo-img { max-width:140px; height:auto; display:block; }
        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li { margin:0.25rem 1rem; }
        .nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; cursor:pointer; }
        .nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }
        .main-content { padding:2rem; overflow-y:auto; display:flex; flex-direction:column; gap:1.5rem; min-width:0; }
        .topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .hamburger-btn { display:none; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px; width:38px; height:38px; align-items:center; justify-content:center; cursor:pointer; color:#0f766e; font-size:1rem; }
        .topbar h2 { font-size:1.4rem; font-weight:700; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; border-radius:20px; padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700; letter-spacing:0.5px; }
        .header-icon { width:40px; height:40px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; color:#6b7280; font-size:1rem; border:1px solid #e5e7eb; }
        .header-icon .badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:0.65rem; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-icon { width:40px; height:40px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
        .user-icon svg { width:20px; height:20px; stroke:white; }
        .filter-panel { background:white; border-radius:18px; border:1px solid #e5e7eb; box-shadow:0 4px 24px rgba(0,0,0,0.08); padding:2rem 2.5rem 1.75rem; max-width:780px; width:100%; margin:0 auto; }
        .filter-grid { display:grid; grid-template-columns:1fr auto; gap:1.25rem; align-items:end; }
        .filter-field { display:flex; flex-direction:column; gap:0.45rem; }
        .filter-label { display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; }
        .filter-label i { color:#0f766e; }
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
        .workflow-section { background:white; border-radius:14px; padding:1.5rem 2rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; gap:1.2rem; }
        .workflow-title { display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; font-weight:600; color:#374151; }
        .workflow-title i { color:#0f766e; }
        .workflow-steps { display:flex; align-items:center; justify-content:center; }
        .workflow-step { display:flex; flex-direction:column; align-items:center; gap:0.5rem; }
        .step-card { width:115px; height:115px; border-radius:14px; border:2px solid #e5e7eb; background:#f9fafb; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.35rem; position:relative; padding-top:18px; transition:all 0.2s; }
        .step-avatar { width:54px; height:54px; border-radius:50%; background:#d1d5db; display:flex; align-items:center; justify-content:center; }
        .step-avatar i { font-size:1.55rem; color:white; }
        .step-label { font-size:0.78rem; font-weight:700; color:#6b7280; text-transform:uppercase; }
        .step-sub { font-size:0.65rem; color:#9ca3af; }
        .step-check { position:absolute; top:-11px; left:50%; transform:translateX(-50%); width:22px; height:22px; border-radius:50%; background:#16a34a; color:white; display:flex; align-items:center; justify-content:center; font-size:0.65rem; border:2px solid white; box-shadow:0 1px 4px rgba(0,0,0,0.15); }
        .workflow-step.approved .step-card { border-color:#86efac; background:#f0fdf4; }
        .workflow-step.approved .step-avatar { background:#16a34a; }
        .workflow-step.approved .step-label { color:#15803d; }
        .workflow-step.current .step-card { border-color:#f59e0b; background:linear-gradient(135deg,#fffbeb,#fef3c7); box-shadow:0 0 0 4px rgba(245,158,11,0.2); }
        .workflow-step.current .step-avatar { background:linear-gradient(135deg,#f59e0b,#d97706); }
        .workflow-step.current .step-label { color:#92400e; font-weight:800; }
        .workflow-step.pending .step-avatar { background:#9ca3af; }
        .workflow-step.pending .step-label { color:#9ca3af; }
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
        .upload-compare-section { background:white; border-radius:14px; border:1.5px solid #e5e7eb; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:1.25rem 1.75rem; display:flex; flex-direction:column; gap:0.85rem; }
        .upload-compare-title { display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; font-weight:700; color:#1f2937; }
        .upload-compare-title i { color:#0f766e; font-size:0.95rem; }
        .upload-compare-body { display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; }
        .custom-file-btn { display:inline-flex; align-items:center; gap:0.4rem; padding:0.48rem 1.1rem; border-radius:7px; border:1.5px solid #d1d5db; background:#f9fafb; font-size:0.84rem; font-weight:600; color:#374151; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
        .custom-file-btn:hover { border-color:#0f766e; color:#0f766e; background:#f0fdf4; }
        #fileInput { display:none; }
        .file-name-label { font-size:0.84rem; color:#6b7280; min-width:110px; }
        .upload-hint { display:flex; align-items:center; gap:0.35rem; font-size:0.78rem; color:#6b7280; }
        .upload-hint i { color:#0f766e; font-size:0.78rem; }
        .compare-result-bar { display:none; align-items:center; gap:0.75rem; padding:0.75rem 1.1rem; border-radius:10px; font-size:0.88rem; font-weight:600; animation:fadeUp 0.3s ease; }
        .compare-result-bar.match { background:#f0fdf4; border:2px solid #86efac; color:#15803d; }
        .compare-result-bar.mismatch { background:#fef2f2; border:2px solid #fca5a5; color:#b91c1c; }
        .compare-result-bar.show { display:flex; }
        .mismatch-section { display:none; background:white; border-radius:14px; border:2px solid #fca5a5; box-shadow:0 4px 18px rgba(220,38,38,0.10); overflow:hidden; animation:fadeUp 0.35s ease; }
        .mismatch-section.show { display:block; }
        .mismatch-header { display:flex; align-items:center; justify-content:space-between; padding:0.9rem 1.25rem; background:linear-gradient(135deg,#dc2626,#b91c1c); }
        .mismatch-header-left { display:flex; align-items:center; gap:0.6rem; color:white; font-size:0.9rem; font-weight:700; }
        .mismatch-count-badge { background:rgba(255,255,255,0.25); color:white; border-radius:20px; padding:0.15rem 0.65rem; font-size:0.78rem; font-weight:800; border:1.5px solid rgba(255,255,255,0.4); }
        .mismatch-table-wrap { overflow-x:auto; }
        .mismatch-table { width:100%; border-collapse:collapse; font-size:0.81rem; }
        .mismatch-table thead th { background:#fef2f2; color:#b91c1c; font-weight:700; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.65rem 1rem; text-align:left; border-bottom:2px solid #fca5a5; white-space:nowrap; }
        .mismatch-table thead th.th-right { text-align:right; }
        .mismatch-table tbody td { padding:0.62rem 1rem; border-bottom:1px solid #fef2f2; color:#1f2937; white-space:nowrap; vertical-align:middle; }
        .forward-btn-wrap { display:flex; justify-content:flex-end; align-items:center; gap:1rem; margin-top:0.85rem; flex-wrap:wrap; }
        .btn-forward { display:inline-flex; align-items:center; gap:0.55rem; padding:0.72rem 1.75rem; border-radius:9px; background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; border:none; font-size:0.9rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(15,118,110,0.3); transition:all 0.2s; }
        .btn-forward:hover:not(:disabled) { transform:translateY(-1px); }
        .btn-forward:disabled { background:linear-gradient(135deg,#9ca3af,#6b7280); box-shadow:none; cursor:not-allowed; opacity:0.7; }
        .forward-lock-msg { display:none; align-items:center; gap:0.45rem; font-size:0.82rem; font-weight:600; color:#b91c1c; background:#fef2f2; border:1.5px solid #fca5a5; border-radius:8px; padding:0.45rem 0.9rem; }
        .forward-lock-msg.show { display:flex; }
        .forward-ok-msg { display:none; align-items:center; gap:0.45rem; font-size:0.82rem; font-weight:600; color:#15803d; background:#f0fdf4; border:1.5px solid #86efac; border-radius:8px; padding:0.45rem 0.9rem; }
        .forward-ok-msg.show { display:flex; }
        .card { background:white; border-radius:14px; padding:1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem; flex-wrap:wrap; gap:0.75rem; }
        .card-title { font-size:0.95rem; font-weight:700; color:#0f766e; display:flex; align-items:center; gap:0.5rem; }
        .billing-table-wrapper { overflow-x:auto; border-radius:10px; border:1px solid #e5e7eb; }
        .billing-table { width:max-content; min-width:100%; border-collapse:separate; border-spacing:0; font-size:0.81rem; background:white; }
        .billing-table thead th { background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; font-weight:700; font-size:0.73rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.78rem 0.7rem; text-align:right; white-space:nowrap; }
        .billing-table thead th.th-left { text-align:left; }
        .billing-table thead th.th-center { text-align:center; }
        .billing-table tbody td { padding:0.65rem 0.7rem; border-bottom:1px solid #f0f0f0; text-align:right; color:#1f2937; vertical-align:middle; white-space:nowrap; }
        .billing-table tbody tr:hover { background:#f0fdf4; }
        .billing-table tbody tr:nth-child(even) { background:#fafafa; }
        .billing-table tfoot td { background:linear-gradient(135deg,#f0fdf4,#dcfce7); font-weight:800; color:#065f46; padding:0.78rem 0.7rem; border-top:2px solid #6ee7b7; text-align:right; white-space:nowrap; font-size:0.82rem; }
        .sn-cell { color:#9ca3af !important; font-size:0.77rem; font-weight:600; text-align:center !important; }
        .site-name-cell { font-weight:600; color:#1f2937 !important; font-size:0.82rem; text-align:left !important; padding-left:0.9rem !important; }
        .hidden { display:none !important; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }
        .no-data-msg { text-align:center; padding:3rem; color:#6b7280; font-size:1rem; }
        .no-data-msg i { font-size:2rem; color:#d1d5db; display:block; margin-bottom:1rem; }
        @media (max-width:900px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); transition:transform 0.3s; z-index:200; }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .main-content { padding:1rem; gap:1rem; }
            .filter-panel { padding:1.25rem; border-radius:12px; max-width:100%; }
            .filter-grid { grid-template-columns:1fr; }
            .btn-load-report { width:100%; justify-content:center; }
            .role-badge { display:none; }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <div class="mcl-logo-box">
                <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img" onerror="this.parentElement.innerHTML='<span style=&quot;font-size:1.4rem;font-weight:900;color:#0f766e;letter-spacing:2px;&quot;>&#9679; MCL</span>'">
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthlyatt.php" class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="monthlylpp.php" class="nav-link active"><i class="fa-solid fa-calendar-check"></i><span>Monthly LPP</span></a></li>
            <li><a href="details_monthly_lpp.php" class="nav-link"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a href="vvstatement.php" class="nav-link"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
            <h2>Security Attendance and Billing Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-tie"></i> SDHOD</span>
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <a href="profile.php" style="text-decoration:none;"><div class="user-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/></svg></div></a>
            </div>
        </header>

        <div class="filter-panel">
            <div class="filter-grid">
                <div class="filter-field">
                    <label class="filter-label"><i class="fa-regular fa-calendar"></i> Select Billing Month & Year</label>
                    <div class="month-picker-wrapper">
                        <div class="month-display-input" id="monthDisplayInput" onclick="togglePicker()">
                            <span id="monthDisplayText"></span>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.72rem;color:#6b7280;"></i>
                        </div>
                        <div class="month-picker-popup" id="monthPickerPopup">
                            <div class="picker-year">
                                <button type="button" class="picker-year-btn" onclick="changeYear(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                                <span class="picker-year-label" id="pickerYearLabel"></span>
                                <button type="button" class="picker-year-btn" onclick="changeYear(1)"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="picker-months" id="pickerMonths"></div>
                            <div class="picker-footer">
                                <a onclick="clearPicker()">Clear</a>
                                <a class="picker-this-month" onclick="setThisMonth()">This month</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-field">
                    <label class="filter-label" style="visibility:hidden;">Load</label>
                    <button type="button" class="btn-load-report" onclick="loadReport()">
                        <i class="fa-solid fa-filter"></i> Load Data
                    </button>
                </div>
            </div>
        </div>

        <div id="reportSection" class="hidden">
            <!-- Workflow -->
            <div class="workflow-section" id="workflowSection"></div>

            <!-- Upload Compare -->
            <div class="upload-compare-section" style="margin-top:1.5rem;">
                <div class="upload-compare-title"><i class="fa-solid fa-file-arrow-up"></i> Upload Excel / CSV to Validate & Compare Values</div>
                <div class="upload-compare-body">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <label class="custom-file-btn" for="fileInput"><i class="fa-solid fa-folder-open"></i> Choose File</label>
                        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" onchange="handleFileUpload(this)">
                        <span class="file-name-label" id="fileNameLabel">No file chosen</span>
                    </div>
                    <div class="upload-hint"><i class="fa-solid fa-circle-info"></i> Upload the Excel file — all values must match to enable Forward Report</div>
                </div>
                <div class="compare-result-bar" id="compareResultBar">
                    <i id="compareResultIcon"></i>
                    <span id="compareResultText"></span>
                </div>
            </div>

            <!-- Mismatch -->
            <div class="mismatch-section" id="mismatchSection">
                <div class="mismatch-header">
                    <div class="mismatch-header-left"><i class="fa-solid fa-triangle-exclamation"></i> Mismatch Details <span class="mismatch-count-badge" id="mismatchCountBadge">0</span></div>
                </div>
                <div class="mismatch-table-wrap">
                    <table class="mismatch-table"><thead><tr><th>#</th><th>Site Name</th><th>Column</th><th class="th-right">System</th><th class="th-right">Uploaded</th></tr></thead><tbody id="mismatchBody"></tbody></table>
                </div>
            </div>

            <!-- Billing Table -->
            <div class="card" style="margin-top:1.5rem;">
                <div class="card-header">
                    <div class="card-title"><i class="fa-solid fa-table-list"></i> System Generated LPP Billing Summary</div>
                </div>
                <div class="billing-table-wrapper">
                    <table class="billing-table" id="billingTable">
                        <thead><tr>
                            <th class="th-center">SL NO</th><th class="th-left">SITE NAME</th>
                            <th>SEC NO</th><th>DAK NO</th>
                            <th>EMPLOYEES</th><th>PRESENT</th><th>LEAVE</th><th>OVERTIME</th>
                            <th>NET PAY</th><th>GST (18%)</th><th>GROSS TOTAL</th>
                        </tr></thead>
                        <tbody id="billingBody"></tbody>
                        <tfoot><tr id="grandTotalRow">
                            <td colspan="4" style="text-align:right;font-weight:800;"><i class="fa-solid fa-sigma" style="margin-right:0.3rem;"></i>GRAND TOTAL</td>
                            <td id="gt-emp"></td><td id="gt-present"></td><td id="gt-leave"></td><td id="gt-overtime"></td>
                            <td id="gt-net"></td><td id="gt-gst"></td><td id="gt-gross"></td>
                        </tr></tfoot>
                    </table>
                </div>
            </div>

            <!-- Forward Section -->
            <div style="margin-top:1.5rem;background:white;border-radius:14px;border:2px solid #0f766e;box-shadow:0 2px 12px rgba(15,118,110,0.10);padding:1.5rem 1.75rem;" id="forwardSection">
                <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.3rem;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#0f766e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid fa-paper-plane" style="color:white;font-size:0.95rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1rem;font-weight:800;color:#1f2937;">Forward Report to Finance</div>
                        <div style="font-size:0.78rem;color:#6b7280;" id="forwardSubtitle">Upload and validate the Excel file before forwarding.</div>
                    </div>
                </div>
                <div style="margin-top:1.1rem;">
                    <label style="font-size:0.82rem;font-weight:700;color:#374151;display:block;margin-bottom:0.45rem;">Add Comments / Remarks (Optional)</label>
                    <textarea id="forwardRemarks" rows="3" placeholder="Enter your comments or remarks here..." style="width:100%;padding:0.75rem 1rem;border:1.5px solid #93c5fd;border-radius:9px;font-size:0.88rem;color:#1f2937;font-family:inherit;resize:vertical;outline:none;background:#f9fafb;"></textarea>
                </div>
                <div class="forward-btn-wrap">
                    <div class="forward-lock-msg" id="forwardLockMsg"><i class="fa-solid fa-lock"></i> Fix mismatches to enable forwarding</div>
                    <div class="forward-ok-msg" id="forwardOkMsg"><i class="fa-solid fa-circle-check"></i> All values verified — ready to forward</div>
                    <button class="btn-forward" id="forwardBtn" onclick="handleForwardReport()" disabled>
                        <i class="fa-solid fa-lock" id="forwardBtnIcon"></i>
                        <span id="forwardBtnText">Forward Report</span>
                    </button>
                </div>
            </div>

            <!-- Already forwarded message -->
            <div id="alreadyForwardedMsg" class="hidden" style="margin-top:1.5rem;background:#f0fdf4;border:2px solid #86efac;border-radius:14px;padding:1.5rem;text-align:center;">
                <i class="fa-solid fa-circle-check" style="font-size:2rem;color:#16a34a;margin-bottom:0.5rem;display:block;"></i>
                <div style="font-size:1.1rem;font-weight:800;color:#15803d;">Report Already Forwarded to Finance</div>
                <div style="font-size:0.85rem;color:#6b7280;margin-top:0.3rem;">The LPC has been forwarded. Finance will review and give final approval.</div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ── Sidebar ── */
const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay');
document.getElementById('hamburgerBtn').addEventListener('click', ()=>{ sidebar.classList.add('open'); overlay.classList.add('active'); });
document.getElementById('sidebarClose').addEventListener('click', ()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
overlay.addEventListener('click', ()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });

/* ── Month Picker ── */
const shortM=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const fullM=['January','February','March','April','May','June','July','August','September','October','November','December'];
let pYear=new Date().getFullYear(), pMonth=new Date().getMonth()+1;

function togglePicker(){ const p=document.getElementById('monthPickerPopup'); p.classList.toggle('open'); document.getElementById('monthDisplayInput').classList.toggle('open'); if(p.classList.contains('open')) renderMonths(); }
function renderMonths(){ const g=document.getElementById('pickerMonths'); g.innerHTML=''; shortM.forEach((m,i)=>{ const b=document.createElement('button'); b.type='button'; b.className='picker-month-btn'+(i+1===pMonth?' active':''); b.textContent=m; b.onclick=()=>selectMonth(i+1); g.appendChild(b); }); document.getElementById('pickerYearLabel').textContent=pYear; }
function selectMonth(m){ pMonth=m; document.getElementById('monthDisplayText').textContent=fullM[m-1]+', '+pYear; document.getElementById('monthPickerPopup').classList.remove('open'); document.getElementById('monthDisplayInput').classList.remove('open'); }
function changeYear(d){ pYear+=d; renderMonths(); }
function clearPicker(){ pMonth=new Date().getMonth()+1; pYear=new Date().getFullYear(); selectMonth(pMonth); }
function setThisMonth(){ clearPicker(); }
selectMonth(pMonth);

document.addEventListener('click', e=>{ const w=document.querySelector('.month-picker-wrapper'); if(w&&!w.contains(e.target)){ document.getElementById('monthPickerPopup').classList.remove('open'); document.getElementById('monthDisplayInput').classList.remove('open'); }});

/* ── Data ── */
let tableData = [];
let lpcWorkflow = null;
let fileVerified = false;
const fmt = v => v===0?'₹0.00':'₹'+v.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});

function loadReport(){
    const month=pMonth, year=pYear;
    fetch('get_lpc_data.php?month='+month+'&year='+year)
        .then(r=>r.json())
        .then(data=>{
            if(!data || !data.sites || data.sites.length===0){
                document.getElementById('reportSection').classList.remove('hidden');
                document.getElementById('billingBody').innerHTML='<tr><td colspan="9" class="no-data-msg"><i class="fa-solid fa-folder-open"></i>No LPC data found for this period.<br>LPC is auto-generated after SDHOD approves monthly attendance.</td></tr>';
                document.getElementById('workflowSection').innerHTML='';
                document.getElementById('forwardSection').style.display='none';
                document.getElementById('alreadyForwardedMsg').classList.add('hidden');
                return;
            }

            tableData = data.sites;
            lpcWorkflow = data.workflow;
            buildTable();
            buildWorkflow();
            handleForwardVisibility();
            document.getElementById('reportSection').classList.remove('hidden');
        })
        .catch(err=>{ Swal.fire('Error', 'Error loading data: '+err.message, 'error'); });
}

function buildTable(){
    const tbody=document.getElementById('billingBody'); tbody.innerHTML='';
    let t={emp:0,present:0,leave:0,overtime:0,net:0,gst:0,gross:0};
    tableData.forEach((r,i)=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`
            <td class="sn-cell">${i+1}</td>
            <td class="site-name-cell">${r.siteName}</td>
            <td style="text-align:center;">${r.sec_no||'-'}</td>
            <td style="text-align:center;">${r.dak_no||'-'}</td>
            <td style="text-align:center;">${r.employees}</td>
            <td style="text-align:center;">${r.present}</td>
            <td style="text-align:center;">${r.leave}</td>
            <td style="text-align:center;">${r.overtime}</td>
            <td>${fmt(r.total_netpay)}</td>
            <td>${fmt(r.gst_amount)}</td>
            <td>${fmt(r.grand_total)}</td>`;
        tbody.appendChild(tr);
        t.emp+=r.employees; t.present+=r.present; t.leave+=r.leave; t.overtime+=r.overtime;
        t.net+=r.total_netpay; t.gst+=r.gst_amount; t.gross+=r.grand_total;
    });
    document.getElementById('gt-emp').textContent=t.emp;
    document.getElementById('gt-present').textContent=t.present;
    document.getElementById('gt-leave').textContent=t.leave;
    document.getElementById('gt-overtime').textContent=t.overtime;
    document.getElementById('gt-net').innerHTML=fmt(t.net);
    document.getElementById('gt-gst').innerHTML=fmt(t.gst);
    document.getElementById('gt-gross').innerHTML=fmt(t.gross);
}

function buildWorkflow(){
    if(!lpcWorkflow) return;
    const ws=document.getElementById('workflowSection');
    const steps=lpcWorkflow.steps||[];
    let html=`<div class="workflow-title"><i class="fa-solid fa-sitemap"></i> LPC Report Approval Workflow</div><div class="workflow-steps">`;
    steps.forEach((s,i)=>{
        let cls='pending', sub='Pending', icon=s.Code==='FINANCE'?'fa-landmark':'fa-user-tie';
        if(s.status==='approved'){ cls='approved'; sub='Approved'; }
        else if(lpcWorkflow.current_step===s.Code){ cls='current'; sub='Active'; }
        html+=`<div class="workflow-step ${cls}"><div class="step-card">`;
        if(s.status==='approved') html+=`<span class="step-check"><i class="fa-solid fa-check"></i></span>`;
        html+=`<div class="step-avatar"><i class="fa-solid ${icon}"></i></div><span class="step-label">${s.Code}</span><span class="step-sub">${sub}</span></div></div>`;
        if(i<steps.length-1) html+=`<div class="workflow-arrow"><i class="fa-solid fa-arrow-right"></i></div>`;
    });
    html+=`</div>`;
    // Comments
    const approved=steps.filter(s=>s.status==='approved'&&s.comment);
    if(approved.length>0){
        html+=`<button class="btn-approval" onclick="document.getElementById('lpcComments').classList.toggle('open')"><i class="fa-solid fa-comments"></i> View Comments</button>`;
        html+=`<div id="lpcComments" class="approval-comments">`;
        approved.forEach(s=>{
            html+=`<div class="comment-item"><div class="comment-header"><div class="comment-role">${s.Code}</div><div class="comment-time"><i class="fa-regular fa-clock" style="font-size:0.68rem;"></i> ${s.acted_at||''}</div></div><div class="comment-text">"${s.comment}"</div></div>`;
        });
        html+=`</div>`;
    }
    ws.innerHTML=html;
}

function handleForwardVisibility(){
    const fs=document.getElementById('forwardSection');
    const afm=document.getElementById('alreadyForwardedMsg');
    if(!lpcWorkflow){ fs.style.display='none'; afm.classList.add('hidden'); return; }
    if(lpcWorkflow.current_step==='SDHOD'){
        fs.style.display='block'; afm.classList.add('hidden');
    } else {
        fs.style.display='none'; afm.classList.remove('hidden');
    }
}

/* ── File Upload Compare ── */
function handleFileUpload(input){
    const file=input.files[0]; if(!file) return;
    document.getElementById('fileNameLabel').textContent=file.name;
    const reader=new FileReader();
    reader.onload=function(e){
        const data=new Uint8Array(e.target.result);
        const wb=XLSX.read(data,{type:'array'});
        const ws=wb.Sheets[wb.SheetNames[0]];
        const rows=XLSX.utils.sheet_to_json(ws,{header:1});
        compareWithSystem(rows);
    };
    reader.readAsArrayBuffer(file);
}

function compareWithSystem(rows){
    let mismatches=[];
    const bar=document.getElementById('compareResultBar');
    const mm=document.getElementById('mismatchSection');

    if(rows.length<2){ bar.className='compare-result-bar mismatch show'; document.getElementById('compareResultText').textContent='File appears empty'; return; }

    const header = (rows[0]||[]).map(h=>String(h).toUpperCase());
    const siteIdx = header.findIndex(h=>h.includes('SITE'));
    const secIdx = header.findIndex(h=>h.includes('SEC NO'));
    const dakIdx = header.findIndex(h=>h.includes('DAK NO'));
    const grossIdx = header.length - 1; 

    const sIdx = siteIdx>-1 ? siteIdx : 0;

    for(let i=1;i<rows.length;i++){
        const row=rows[i]; if(!row||!row[sIdx]) continue;
        const siteName=String(row[sIdx]).trim();
        const sysRow=tableData.find(s=>s.siteName.toUpperCase().includes(siteName.toUpperCase()));
        
        if(sysRow){
            const uploadGross=parseFloat(row[grossIdx])||0;
            const sysGross=sysRow.grand_total;
            if(Math.abs(uploadGross-sysGross)>1){
                mismatches.push({site:sysRow.siteName,col:'Gross Total',sys:sysGross,up:uploadGross});
            }
            if(secIdx>-1){
                const uploadSec = String(row[secIdx]||'').trim();
                if(uploadSec !== String(sysRow.sec_no)){
                    mismatches.push({site:sysRow.siteName,col:'SEC NO',sys:sysRow.sec_no,up:uploadSec});
                }
            }
            if(dakIdx>-1){
                const uploadDak = String(row[dakIdx]||'').trim();
                if(uploadDak !== String(sysRow.dak_no)){
                    mismatches.push({site:sysRow.siteName,col:'DAK NO',sys:sysRow.dak_no,up:uploadDak});
                }
            }
        }
    }

    if(mismatches.length===0){
        bar.className='compare-result-bar match show';
        document.getElementById('compareResultIcon').className='fa-solid fa-circle-check';
        document.getElementById('compareResultText').textContent='All values match! Ready to forward.';
        mm.classList.remove('show');
        fileVerified=true;
        enableForwardBtn(true);
    } else {
        bar.className='compare-result-bar mismatch show';
        document.getElementById('compareResultIcon').className='fa-solid fa-triangle-exclamation';
        document.getElementById('compareResultText').textContent=mismatches.length+' mismatches found';
        document.getElementById('mismatchCountBadge').textContent=mismatches.length+' mismatches';
        const mb=document.getElementById('mismatchBody'); mb.innerHTML='';
        mismatches.forEach((m,i)=>{ mb.innerHTML+=`<tr><td style="text-align:center;">${i+1}</td><td>${m.site}</td><td>${m.col}</td><td style="text-align:right;color:#15803d;font-weight:700;">${fmt(m.sys)}</td><td style="text-align:right;color:#b91c1c;font-weight:700;">${fmt(m.up)}</td></tr>`; });
        mm.classList.add('show');
        fileVerified=false;
        enableForwardBtn(false);
    }
}

function enableForwardBtn(ok){
    const btn=document.getElementById('forwardBtn');
    const lock=document.getElementById('forwardLockMsg');
    const okm=document.getElementById('forwardOkMsg');
    if(ok){
        btn.disabled=false; document.getElementById('forwardBtnIcon').className='fa-solid fa-paper-plane';
        lock.classList.remove('show'); okm.classList.add('show');
    } else {
        btn.disabled=true; document.getElementById('forwardBtnIcon').className='fa-solid fa-lock';
        lock.classList.add('show'); okm.classList.remove('show');
    }
}

function handleForwardReport(){
    if(!fileVerified){ Swal.fire('Oops...', 'Please upload and verify the Excel file first.', 'warning'); return; }
    const comment=document.getElementById('forwardRemarks').value.trim();
    const btn=document.getElementById('forwardBtn');
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Forwarding...';

    const fd=new FormData();
    fd.append('lpc_month',pMonth);
    fd.append('lpc_year',pYear);
    fd.append('comment',comment);

    fetch('forward_lpc.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                Swal.fire({ icon: 'success', title: 'Success!', text: 'LPC forwarded to Finance successfully!', confirmButtonColor: '#0f766e' }).then(() => { loadReport(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#0f766e' });
                btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Forward Report';
            }
        })
        .catch(err=>{ Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not connect to the server.', confirmButtonColor: '#0f766e' }); btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Forward Report'; });
}
</script>
</body>
</html>