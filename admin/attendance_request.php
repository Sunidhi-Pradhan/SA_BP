<?php
session_start();
require "../config.php";

// Role check
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Request – Security Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }

        :root {
            --bg:#f4f6f9; --card:#ffffff; --text:#111827;
            --subtext:#6b7280; --border:#e5e7eb;
            --teal:#0f766e; --teal-dark:#0d5f58;
        }
        body.dark {
            --bg:#0b1120; --card:#111827; --text:#e5e7eb;
            --subtext:#9ca3af; --border:#1f2937;
        }
        body.dark .sidebar { background:#0d1526; box-shadow:2px 0 12px rgba(0,0,0,.5); }
        body.dark .sidebar .menu:hover  { background:rgba(255,255,255,.06); }
        body.dark .sidebar .menu.active { background:rgba(255,255,255,.10); }
        body.dark .theme-btn { background:#1e293b; color:#fbbf24; border-color:#334155; }
        body.dark .main-card { box-shadow:0 4px 20px rgba(15,118,110,.28); border-color:rgba(15,118,110,.28); }
        body.dark thead tr { background:#0a1628 !important; }
        body.dark tbody tr:hover { background:rgba(15,118,110,.07); }
        body.dark .btn-tab-white { background:#1e293b; border-color:#334155; color:#9ca3af; }
        body.dark .search-box, body.dark .entries-select { background:#1e293b; border-color:#334155; color:#e5e7eb; }
        body.dark .pager-btn { background:#1e293b; border-color:#334155; color:#9ca3af; }
        body.dark .pager-btn:hover:not(:disabled) { background:var(--teal); color:#fff; }
        body.dark .pager-btn.pg-active { background:var(--teal); color:#fff; border-color:var(--teal); }

        body { background:var(--bg); color:var(--text); transition:background .3s,color .3s; overflow-x:hidden; }
        .dashboard { display:flex; min-height:100vh; }

        /* OVERLAY */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:998; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width:240px; min-width:240px;
            background:var(--teal); color:#fff;
            padding:0; display:flex; flex-direction:column;
            box-shadow:2px 0 8px rgba(0,0,0,.12);
            flex-shrink:0; z-index:999; overflow-y:auto;
            position:relative; transition:transform .3s ease;
        }
        @media (max-width:768px) { .sidebar { -webkit-overflow-scrolling:touch; } }

        .logo { padding:20px 15px; margin-bottom:10px; }
        .logo img {
            max-width:160px; height:auto; display:block; margin:0 auto;
            background:#fff; border-radius:12px; padding:10px 16px;
            box-shadow:0 2px 8px rgba(0,0,0,.15);
        }

        nav { display:flex; flex-direction:column; padding:0 15px; flex:1; }
        .menu {
            display:flex; align-items:center; gap:12px;
            padding:12px 15px; border-radius:6px;
            color:rgba(255,255,255,.9); text-decoration:none;
            font-size:14px; font-weight:400;
            transition:all .25s ease; position:relative;
            margin-bottom:2px; white-space:nowrap;
        }
        .menu .icon {
            font-size:16px; width:20px; display:flex;
            align-items:center; justify-content:center;
            opacity:.95; flex-shrink:0; transition:transform .2s ease;
        }
        .menu:hover .icon { transform:scale(1.2); }
        .menu:hover  { background:rgba(255,255,255,.1); color:#fff; }
        .menu.active { background:rgba(255,255,255,.15); color:#fff; font-weight:500; }
        .menu.active::before {
            content:""; position:absolute; left:-15px; top:50%;
            transform:translateY(-50%); width:4px; height:70%;
            background:#fff; border-radius:0 4px 4px 0;
        }
        .menu.logout {
            margin-top:auto; margin-bottom:15px;
            border-top:1px solid rgba(255,255,255,.15); padding-top:15px;
        }



        /* MAIN */
        .main { flex:1; display:flex; flex-direction:column; min-width:0; }

        /* HEADER */
        header {
            display:flex; align-items:center; gap:14px; padding:0 25px; height:62px;
            background:var(--card); box-shadow:0 1px 4px rgba(0,0,0,.08);
            position:sticky; top:0; z-index:50; border-bottom:1px solid var(--border);
            animation:headerDrop .4s ease both;
        }
        @keyframes headerDrop { from{transform:translateY(-100%);opacity:0} to{transform:translateY(0);opacity:1} }
        header h1 { font-size:1.4rem; font-weight:700; color:var(--text); flex:1; text-align:center; }

        .menu-btn {
            background:none; border:none; font-size:21px; cursor:pointer; color:var(--text);
            padding:6px 8px; border-radius:6px; display:none; align-items:center;
            justify-content:center; flex-shrink:0; transition:background .2s,transform .2s;
            -webkit-tap-highlight-color:transparent;
        }
        .menu-btn:hover { background:rgba(0,0,0,.06); transform:rotate(90deg); }

        .theme-btn {
            width:42px; height:42px; border-radius:11px; border:1px solid var(--border);
            background:var(--card); color:var(--subtext); font-size:15px; cursor:pointer;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
            transition:all .2s; box-shadow:0 1px 4px rgba(0,0,0,.07);
            -webkit-tap-highlight-color:transparent;
        }
        .theme-btn:hover { background:#f3f4f6; color:var(--text); transform:scale(1.08); }
        .theme-btn.active { background:#1e293b; color:#a5b4fc; border-color:#334155; }

        /* PAGE CONTENT */
        .page-content {
            padding:26px 26px 48px; display:flex; flex-direction:column; gap:18px;
            animation:fadeUp .5s .1s ease both;
        }
        @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

        /* PAGE TITLE */
        .page-title {
            display:flex; align-items:center; gap:12px;
            animation:fadeUp .4s .18s ease both; opacity:0;
        }
        .pt-icon {
            width:44px; height:44px; border-radius:11px;
            background:rgba(15,118,110,.1); border:1px solid rgba(15,118,110,.2);
            display:flex; align-items:center; justify-content:center;
            color:var(--teal); font-size:1.05rem; flex-shrink:0;
        }
        body.dark .pt-icon { background:rgba(15,118,110,.18); }
        .pt-text h2 { font-size:1.2rem; font-weight:800; color:var(--text); }
        .pt-text p  { font-size:.8rem; color:var(--subtext); margin-top:1px; }

        /* MAIN CARD */
        .main-card {
            background:var(--card); border-radius:16px;
            border:1px solid rgba(15,118,110,.15);
            box-shadow:0 4px 20px rgba(15,118,110,.12),0 1px 4px rgba(16,185,129,.08);
            overflow:hidden; transition:box-shadow .2s,border-color .2s;
            animation:fadeUp .45s .28s ease both; opacity:0;
        }
        .main-card:hover {
            box-shadow:0 8px 32px rgba(15,118,110,.2),0 2px 10px rgba(16,185,129,.12);
            border-color:rgba(16,185,129,.3);
        }

        /* TAB ROW */
        .tab-row { display:flex; align-items:center; gap:8px; padding:18px 20px 0; flex-wrap:wrap; }

        .btn-tab {
            display:inline-flex; align-items:center; gap:.4rem;
            padding:.5rem 1.1rem; min-height:36px;
            border:none; border-radius:8px;
            font-size:.83rem; font-weight:700; cursor:pointer; font-family:inherit;
            transition:all .2s; -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .btn-tab-teal  { background:#0e7490; color:#fff; box-shadow:0 2px 8px rgba(14,116,144,.28); }
        .btn-tab-teal:hover { background:#0c6580; transform:translateY(-1px); }
        .btn-tab-white { background:var(--bg); color:var(--subtext); border:1px solid var(--border); }
        .btn-tab-white:hover { background:var(--border); transform:translateY(-1px); }
        .btn-tab-white.active { background:var(--teal); color:#fff; border-color:var(--teal); box-shadow:0 3px 12px rgba(15,118,110,.3); }
        .tab-badge {
            background:rgba(255,255,255,.25); color:#fff;
            font-size:.65rem; font-weight:800; padding:1px 6px; border-radius:20px;
        }
        .btn-tab-white .tab-badge { background:var(--border); color:var(--subtext); }
        .btn-tab-white.active .tab-badge { background:rgba(255,255,255,.25); color:#fff; }

        /* TAB PANELS */
        .tab-panel { display:none; }
        .tab-panel.active { display:block; animation:fadeUp .3s ease both; }

        /* TOOLBAR */
        .tbl-toolbar {
            display:flex; align-items:center; justify-content:space-between;
            gap:10px; padding:14px 20px 8px; flex-wrap:wrap;
        }
        .entries-wrap { display:flex; align-items:center; gap:7px; font-size:.81rem; color:var(--subtext); }
        .entries-select {
            padding:.28rem .5rem; min-height:30px; border:1px solid var(--border); border-radius:7px;
            font-size:.81rem; color:var(--text); background:var(--bg); font-family:inherit; outline:none; cursor:pointer;
            transition:border-color .2s;
        }
        .entries-select:focus { border-color:var(--teal); }
        .search-wrap { display:flex; align-items:center; gap:7px; font-size:.81rem; color:var(--subtext); }
        .search-box {
            padding:.3rem .75rem; min-height:32px; min-width:170px;
            border:1px solid var(--border); border-radius:8px;
            font-size:.83rem; color:var(--text); background:var(--bg); font-family:inherit; outline:none;
            transition:border-color .2s,box-shadow .2s;
            -webkit-tap-highlight-color:transparent;
        }
        .search-box:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(15,118,110,.1); }

        /* TABLE */
        .tbl-wrapper { overflow-x:auto; -webkit-overflow-scrolling:touch; }
        .data-table { width:100%; border-collapse:collapse; min-width:780px; }
        .data-table thead tr { background:var(--bg); }
        .data-table th {
            padding:.7rem 1rem; font-size:.68rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.6px; color:var(--subtext);
            text-align:left; border-bottom:1px solid var(--border); white-space:nowrap; user-select:none;
        }
        .data-table th.sortable { cursor:pointer; }
        .data-table th.sortable:hover { color:var(--teal); }
        .data-table th .si { margin-left:3px; opacity:.35; font-size:.6rem; }
        .data-table th.s-asc .si, .data-table th.s-desc .si { opacity:1; color:var(--teal); }

        .data-table td {
            padding:.82rem 1rem; font-size:.855rem; color:var(--text);
            border-bottom:1px solid var(--border); vertical-align:middle;
        }
        .data-table tbody tr:last-child td { border-bottom:none; }
        .data-table tbody tr { transition:background .15s; }
        .data-table tbody tr:hover { background:rgba(15,118,110,.04); }
        .data-table th:first-child, .data-table td:first-child { width:36px; text-align:center; padding-right:4px; }

        /* Employee cell */
        .emp-name { font-weight:700; font-size:.875rem; color:var(--text); }
        .emp-esic { font-size:.74rem; color:var(--subtext); margin-top:1px; }

        /* Status change arrows */
        .change-flow { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .att-tag {
            font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:5px;
            display:inline-block; white-space:nowrap;
        }
        .att-present  { background:#d1fae5; color:#065f46; }
        .att-leave    { background:#fef3c7; color:#92400e; }
        .att-absent   { background:#fee2e2; color:#991b1b; }
        .att-extra    { background:#dbeafe; color:#1d4ed8; }
        .att-halfday  { background:#f3e8ff; color:#6d28d9; }
        .att-arrow    { color:var(--subtext); font-size:.8rem; }
        body.dark .att-present { background:#064e3b; color:#6ee7b7; }
        body.dark .att-leave   { background:#451a03; color:#fbbf24; }
        body.dark .att-absent  { background:#450a0a; color:#f87171; }
        body.dark .att-extra   { background:#1e3a5f; color:#60a5fa; }
        body.dark .att-halfday { background:#2e1065; color:#a78bfa; }

        /* Result badge */
        .result-badge {
            font-size:.72rem; font-weight:800; letter-spacing:.4px;
            padding:4px 12px; border-radius:6px; display:inline-block; text-transform:uppercase;
        }
        .res-approved { background:#d1fae5; color:#065f46; }
        .res-rejected { background:#fee2e2; color:#991b1b; }
        .res-pending  { background:#fef3c7; color:#92400e; }
        body.dark .res-approved { background:#064e3b; color:#6ee7b7; }
        body.dark .res-rejected { background:#450a0a; color:#f87171; }
        body.dark .res-pending  { background:#451a03; color:#fbbf24; }

        /* Action buttons */
        .action-btns { display:flex; gap:.3rem; }
        .btn-act {
            width:29px; height:29px; border:none; border-radius:7px; cursor:pointer;
            display:flex; align-items:center; justify-content:center; font-size:.73rem;
            transition:transform .15s,filter .15s; -webkit-tap-highlight-color:transparent;
        }
        .btn-approve { background:#d1fae5; color:#059669; }
        .btn-reject  { background:#fee2e2; color:#ef4444; }
        .btn-view    { background:#dbeafe; color:#2563eb; }
        .btn-act:hover { transform:scale(1.12); filter:brightness(.9); }

        /* Empty state */
        .empty-state { padding:3.5rem 1rem; text-align:center; color:var(--subtext); }
        .empty-state i { font-size:2.2rem; display:block; margin-bottom:.7rem; opacity:.28; }
        .empty-state p { font-size:.86rem; }

        /* FOOTER / PAGINATION */
        .tbl-footer {
            display:flex; align-items:center; justify-content:space-between;
            padding:12px 20px 18px; flex-wrap:wrap; gap:8px;
        }
        .tbl-info { font-size:.8rem; color:var(--subtext); }
        .pager { display:flex; gap:.35rem; flex-wrap:wrap; }
        .pager-btn {
            padding:.38rem .85rem; min-height:32px; background:var(--card); color:var(--subtext);
            border:1px solid var(--border); border-radius:7px; font-size:.8rem; font-weight:600;
            cursor:pointer; font-family:inherit; transition:all .18s;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .pager-btn:hover:not(:disabled) { background:var(--teal); color:#fff; border-color:var(--teal); transform:translateY(-1px); }
        .pager-btn:disabled { opacity:.38; cursor:not-allowed; }
        .pager-btn.pg-active { background:var(--teal); color:#fff; border-color:var(--teal); box-shadow:0 2px 8px rgba(15,118,110,.3); }

        /* ===== RESPONSIVE ===== */
        @media (max-width:992px) {
            .sidebar { width:210px; min-width:210px; }
            .menu { font-size:13px; padding:11px 12px; }
            header h1 { font-size:1.3rem; }
            .page-content { padding:20px 18px 40px; }
        }
        @media (max-width:768px) {
            .menu-btn { display:flex; }
            .sidebar {
                position:fixed; top:0; left:0; height:100vh;
                transform:translateX(-100%); animation:none;
                width:260px; min-width:260px;
                box-shadow:4px 0 20px rgba(0,0,0,.25);
            }
            .sidebar.open { transform:translateX(0); }
            header { padding:0 14px; height:56px; }
            header h1 { font-size:1.1rem; }
            .theme-btn { width:38px; height:38px; font-size:14px; }
            .page-content { padding:16px 12px 40px; gap:14px; }
            .tab-row { padding:14px 14px 0; gap:8px; }
            .tbl-toolbar { flex-direction:column; align-items:stretch; padding:12px 14px 6px; }
            .search-wrap { justify-content:flex-end; }
            .tbl-footer { padding:10px 14px 16px; }
            .search-box { min-width:130px; }
        }
        @media (max-width:480px) {
            header { padding:0 10px; height:52px; }
            header h1 { font-size:.92rem; }
            .menu-btn { font-size:19px; }
            .theme-btn { width:34px; height:34px; font-size:13px; border-radius:8px; }
            .page-content { padding:12px 10px 36px; gap:12px; }
            .pt-text h2 { font-size:1rem; }
            .main-card { border-radius:12px; }
            .btn-tab { font-size:.78rem; padding:.45rem .85rem; min-height:34px; }
            .pager-btn { padding:.32rem .7rem; font-size:.75rem; }
        }
        @media (max-width:360px) {
            header h1 { font-size:.82rem; }
            .btn-tab { font-size:.73rem; padding:.4rem .7rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../assets/logo/images.png" alt="MCL Logo">
        </div>
        <nav>
             <a href="../dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="../user.php" class="menu">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <span>Add Users</span>
            </a>
            <a href="../employees.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-plus"></i></span>
                <span>Add Employee</span>
            </a>
            <a href="../admin/basic_pay_update.php" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>
            <a href="../admin/add_extra_manpower.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>
            <a href="../unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                <span>Unlock Attendance</span>
            </a>
            <a href="../admin/attendance_request.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                <span>Attendance Request</span>
            </a>
            <a href="../download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-download"></i></span>
                <span>Download Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Wage Report</span>
            </a>
            <a href="../admin/monthly_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                <span>Monthly Attendance</span>
            </a>
            <a href="../download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                <span>Download Salary</span>
            </a>
            <a href="logout.php" class="menu logout">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- ===== MAIN ===== -->
    <main class="main">
        <header>
            <button class="menu-btn" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
            <h1>Security Billing Portal</h1>
            <button class="theme-btn" id="themeToggle"><i class="fa-solid fa-moon"></i></button>
        </header>

        <div class="page-content">

            <!-- Title -->
            <div class="page-title">
                <div class="pt-icon"><i class="fa-solid fa-file-signature"></i></div>
                <div class="pt-text">
                    <h2>Edit Attendance Request</h2>
                    <p>Review and process employee attendance requests</p>
                </div>
            </div>

            <!-- Card -->
            <div class="main-card">

                <!-- Tab buttons -->
                <div class="tab-row">
                    <button class="btn-tab btn-tab-teal" id="tabPending" onclick="switchTab('pending')">
                        <i class="fa-solid fa-clock"></i> Pending
                        <span class="tab-badge" id="badgePending">3</span>
                    </button>
                    <button class="btn-tab btn-tab-white active" id="tabProcessed" onclick="switchTab('processed')">
                        <i class="fa-solid fa-rotate-left"></i> Processed History
                        <span class="tab-badge" id="badgeProcessed">6</span>
                    </button>
                </div>

                <!-- ── PENDING PANEL ── -->
                <div class="tab-panel" id="panel-pending">
                    <div class="tbl-toolbar">
                        <div class="entries-wrap">
                            Show
                            <select class="entries-select" id="pendingSize" onchange="render('pending')">
                                <option>10</option><option>25</option><option>50</option>
                            </select>
                            entries
                        </div>
                        <div class="search-wrap">
                            Search: <input class="search-box" id="pendingSearch" oninput="render('pending')" placeholder="Search…">
                        </div>
                    </div>
                    <div class="tbl-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="chkAllPending" onclick="checkAll('pending')"></th>
                                    <th class="sortable" onclick="sort('pending',1)">Req. Date <i class="fa-solid fa-sort si"></i></th>
                                    <th class="sortable" onclick="sort('pending',2)">Employee <i class="fa-solid fa-sort si"></i></th>
                                    <th class="sortable" onclick="sort('pending',3)">Attendance Update Date <i class="fa-solid fa-sort si"></i></th>
                                    <th>Final Status Change</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pending"></tbody>
                        </table>
                    </div>
                    <div id="empty-pending" class="empty-state" style="display:none;">
                        <i class="fa-solid fa-inbox"></i><p>No pending requests found.</p>
                    </div>
                    <div class="tbl-footer">
                        <div class="tbl-info" id="info-pending"></div>
                        <div class="pager" id="pager-pending"></div>
                    </div>
                </div>

                <!-- ── PROCESSED PANEL ── -->
                <div class="tab-panel active" id="panel-processed">
                    <div class="tbl-toolbar">
                        <div class="entries-wrap">
                            Show
                            <select class="entries-select" id="processedSize" onchange="render('processed')">
                                <option>10</option><option>25</option><option>50</option>
                            </select>
                            entries
                        </div>
                        <div class="search-wrap">
                            Search: <input class="search-box" id="processedSearch" oninput="render('processed')" placeholder="Search…">
                        </div>
                    </div>
                    <div class="tbl-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="chkAllProcessed" onclick="checkAll('processed')"></th>
                                    <th class="sortable" onclick="sort('processed',1)">Processed On <i class="fa-solid fa-sort si"></i></th>
                                    <th class="sortable" onclick="sort('processed',2)">Employee <i class="fa-solid fa-sort si"></i></th>
                                    <th class="sortable" onclick="sort('processed',3)">Attendance Update Date <i class="fa-solid fa-sort si"></i></th>
                                    <th>Final Status Change</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-processed"></tbody>
                        </table>
                    </div>
                    <div id="empty-processed" class="empty-state" style="display:none;">
                        <i class="fa-solid fa-clock-rotate-left"></i><p>No processed requests found.</p>
                    </div>
                    <div class="tbl-footer">
                        <div class="tbl-info" id="info-processed"></div>
                        <div class="pager" id="pager-processed"></div>
                    </div>
                </div>

            </div><!-- /.main-card -->
        </div><!-- /.page-content -->
    </main>
</div>

<script>
/* ═══════════════════════════════════════
   DATA (loaded from API)
═══════════════════════════════════════ */
let PENDING_DATA = [];
let PROCESSED_DATA = [];

/* ═══════════════════════════════════════
   STATE
═══════════════════════════════════════ */
const ST = {
    pending:   { page:1, col:1, dir:'asc', data:[] },
    processed: { page:1, col:1, dir:'asc', data:[] }
};

/* ═══════════════════════════════════════
   TAG HELPER
═══════════════════════════════════════ */
function attTag(label) {
    const map = {
        'Present':'att-present', 'Leave':'att-leave', 'Absent':'att-absent',
        'Present With Extra':'att-extra', 'Half Day':'att-halfday', 'N/A':''
    };
    if (!label || label === 'N/A') return '<span style="color:#9ca3af;font-size:.78rem;">N/A</span>';
    const cls = map[label] || 'att-present';
    return `<span class="att-tag ${cls}">${label}</span>`;
}
function changeFlow(from, to) {
    return `<div class="change-flow">${attTag(from)}<span class="att-arrow"><i class="fa-solid fa-arrow-right"></i></span>${attTag(to)}</div>`;
}

/* ═══════════════════════════════════════
   LOAD DATA FROM API
═══════════════════════════════════════ */
function loadData() {
    // Load pending
    fetch('fetch_attendance_requests.php?tab=pending')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                PENDING_DATA = data.data.map(r => ({
                    id:       r.id,
                    req_date: r.request_time || '',
                    employee: r.empname || '',
                    esic:     r.empcode || '',
                    att_date: r.attendance_date || '',
                    from:     r.current_status_name || 'N/A',
                    to:       r.new_status_name || r.new_status || '',
                    reason:   r.reason_for_update || '',
                    req_by_name: r.request_by_name || '',
                    req_by_role: r.request_by_role || ''
                }));
                document.getElementById('badgePending').textContent = data.pending_count;
                document.getElementById('badgeProcessed').textContent = data.processed_count;
                render('pending');
            }
        })
        .catch(err => console.error('Error loading pending:', err));

    // Load processed
    fetch('fetch_attendance_requests.php?tab=processed')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                PROCESSED_DATA = data.data.map(r => ({
                    id:        r.id,
                    proc_date: r.approved_time || '',
                    employee:  r.empname || '',
                    esic:      r.empcode || '',
                    att_date:  r.attendance_date || '',
                    from:      r.current_status_name || 'N/A',
                    to:        r.new_status_name || r.new_status || '',
                    result:    r.status ? r.status.toUpperCase() : '',
                    reason:    r.reason_for_update || '',
                    req_by_name:  r.request_by_name || '',
                    req_by_role:  r.request_by_role || '',
                    appr_by_name: r.approved_by_name || '',
                    appr_by_role: r.approved_by_role || ''
                }));
                render('processed');
            }
        })
        .catch(err => console.error('Error loading processed:', err));
}

/* ═══════════════════════════════════════
   RENDER TABLE
═══════════════════════════════════════ */
function render(type) {
    const raw    = type === 'pending' ? PENDING_DATA : PROCESSED_DATA;
    const search = (document.getElementById(type+'Search')?.value||'').toLowerCase();
    const pgSize = parseInt(document.getElementById(type+'Size')?.value||10);
    const s      = ST[type];

    // Filter
    let filtered = raw.filter(r => JSON.stringify(r).toLowerCase().includes(search));

    // Sort
    const colMap = {
        pending:   [null,'req_date','employee','att_date'],
        processed: [null,'proc_date','employee','att_date']
    };
    const key = colMap[type][s.col];
    if (key) {
        filtered.sort((a,b) => {
            const av = (a[key]||'').toLowerCase(), bv = (b[key]||'').toLowerCase();
            return s.dir==='asc' ? av.localeCompare(bv) : bv.localeCompare(av);
        });
    }
    s.data = filtered;

    // Paginate
    const total = filtered.length;
    const pages = Math.max(1, Math.ceil(total/pgSize));
    if (s.page > pages) s.page = pages;
    const start = (s.page-1)*pgSize;
    const slice = filtered.slice(start, start+pgSize);

    // Render tbody
    const tbody = document.getElementById('tbody-'+type);
    const emptyEl = document.getElementById('empty-'+type);

    if (slice.length === 0) {
        tbody.innerHTML = '';
        emptyEl.style.display = 'block';
    } else {
        emptyEl.style.display = 'none';
        tbody.innerHTML = slice.map((r, i) => {
            const empCell = `<td><div class="emp-name">${r.employee}</div><div class="emp-esic">ESIC: ${r.esic}</div></td>`;
            if (type === 'pending') {
                return `<tr style="opacity:0;animation:fadeUp .3s ${i*.05}s ease forwards">
                    <td><input type="checkbox" class="rc-pending"></td>
                    <td><div>${r.req_date}</div><div style="font-size:.72rem;color:#6b7280;margin-top:2px;">By: ${r.req_by_name} <span style="background:#e0f2fe;color:#0369a1;padding:1px 6px;border-radius:4px;font-size:.68rem;font-weight:600;">${r.req_by_role}</span></div></td>
                    ${empCell}
                    <td><strong>${r.att_date}</strong></td>
                    <td>${changeFlow(r.from, r.to)}</td>
                    <td><div class="action-btns">
                        <button class="btn-act btn-approve" title="Approve" onclick="processRow(${r.id},'approved')"><i class="fa-solid fa-check"></i></button>
                        <button class="btn-act btn-reject"  title="Reject"  onclick="processRow(${r.id},'rejected')"><i class="fa-solid fa-xmark"></i></button>
                        <button class="btn-act btn-view"    title="Reason: ${(r.reason||'').replace(/'/g, '&#39;')}"><i class="fa-solid fa-eye"></i></button>
                    </div></td>
                </tr>`;
            } else {
                const resCls = r.result==='APPROVED' ? 'res-approved' : r.result==='REJECTED' ? 'res-rejected' : 'res-pending';
                return `<tr style="opacity:0;animation:fadeUp .3s ${i*.05}s ease forwards">
                    <td><input type="checkbox" class="rc-processed"></td>
                    <td>${r.proc_date}</td>
                    ${empCell}
                    <td><strong>${r.att_date}</strong></td>
                    <td>${changeFlow(r.from, r.to)}</td>
                    <td><span class="result-badge ${resCls}">${r.result}</span></td>
                </tr>`;
            }
        }).join('');
    }

    // Info
    const from = total===0?0:start+1, to=Math.min(start+pgSize,total);
    document.getElementById('info-'+type).textContent =
        `Showing ${from} to ${to} of ${total} entries${search?' (filtered from '+raw.length+' total)':''}`;

    renderPager(type, pages);
}

function renderPager(type, pages) {
    const s = ST[type];
    let html = `<button class="pager-btn" onclick="goPage('${type}',${s.page-1})" ${s.page===1?'disabled':''}>Previous</button>`;
    for (let p=1; p<=pages; p++) {
        const range=2;
        if (p===1||p===pages||(p>=s.page-range&&p<=s.page+range)) {
            html+=`<button class="pager-btn ${p===s.page?'pg-active':''}" onclick="goPage('${type}',${p})">${p}</button>`;
        } else if (p===s.page-range-1||p===s.page+range+1) {
            html+=`<button class="pager-btn" disabled>…</button>`;
        }
    }
    html+=`<button class="pager-btn" onclick="goPage('${type}',${s.page+1})" ${s.page===pages?'disabled':''}>Next</button>`;
    document.getElementById('pager-'+type).innerHTML = html;
}

function goPage(type, p) {
    const pgSize = parseInt(document.getElementById(type+'Size')?.value||10);
    const pages  = Math.max(1, Math.ceil(ST[type].data.length/pgSize));
    ST[type].page = Math.max(1, Math.min(p, pages));
    render(type);
}

/* ═══════════════════════════════════════
   SORT
═══════════════════════════════════════ */
function sort(type, col) {
    const s = ST[type];
    if (s.col===col) s.dir = s.dir==='asc'?'desc':'asc';
    else { s.col=col; s.dir='asc'; }
    s.page=1;
    // Update icons
    document.querySelectorAll(`#panel-${type} .data-table th`).forEach((th,i) => {
        th.classList.remove('s-asc','s-desc');
        const ic=th.querySelector('.si'); if(!ic)return;
        if(i===col){
            th.classList.add(s.dir==='asc'?'s-asc':'s-desc');
            ic.className=`fa-solid fa-sort-${s.dir==='asc'?'up':'down'} si`;
        } else { ic.className='fa-solid fa-sort si'; }
    });
    render(type);
}

/* ═══════════════════════════════════════
   SELECT ALL
═══════════════════════════════════════ */
function checkAll(type) {
    const master = document.getElementById('chkAll'+type.charAt(0).toUpperCase()+type.slice(1));
    document.querySelectorAll('.rc-'+type).forEach(cb=>cb.checked=master.checked);
}

/* ═══════════════════════════════════════
   PROCESS (approve/reject via API)
═══════════════════════════════════════ */
function processRow(id, action) {
    const row = PENDING_DATA.find(r=>r.id==id);
    if (!row) return;

    const isApprove = action === 'approved';
    Swal.fire({
        title: isApprove ? 'Approve Request?' : 'Reject Request?',
        html: `<div style="text-align:left;font-size:.9rem;">
            <p><strong>Employee:</strong> ${row.employee}</p>
            <p><strong>ESIC:</strong> ${row.esic}</p>
            <p><strong>Date:</strong> ${row.att_date}</p>
            <p><strong>Reason:</strong> ${row.reason || 'N/A'}</p>
        </div>`,
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#059669' : '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: isApprove ? '<i class="fa-solid fa-check"></i> Yes, Approve' : '<i class="fa-solid fa-xmark"></i> Yes, Reject',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('process_attendance_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, action: action })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: isApprove ? 'Approved!' : 'Rejected!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                loadData();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Network Error', text: err.message }));
    });
}

/* ═══════════════════════════════════════
   TAB SWITCH
═══════════════════════════════════════ */
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('panel-'+tab).classList.add('active');
    document.getElementById('tabPending').classList.remove('active');
    document.getElementById('tabProcessed').classList.remove('active');
    // Style active
    if(tab==='pending'){
        document.getElementById('tabPending').classList.remove('btn-tab-white');
        document.getElementById('tabPending').classList.add('btn-tab-teal');
        document.getElementById('tabProcessed').classList.remove('btn-tab-teal');
        document.getElementById('tabProcessed').classList.add('btn-tab-white');
    } else {
        document.getElementById('tabProcessed').classList.add('active');
        document.getElementById('tabPending').classList.remove('btn-tab-teal');
        document.getElementById('tabPending').classList.add('btn-tab-white');
        document.getElementById('tabProcessed').classList.remove('btn-tab-white');
        document.getElementById('tabProcessed').classList.add('btn-tab-teal');
    }
}

/* ═══════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════ */
const menuBtn=document.getElementById('menuBtn'), sidebar=document.getElementById('sidebar'), overlay=document.getElementById('sidebarOverlay');
const openSB=()=>{sidebar.classList.add('open');overlay.classList.add('active');document.body.style.overflow='hidden'};
const closeSB=()=>{sidebar.classList.remove('open');overlay.classList.remove('active');document.body.style.overflow=''};
menuBtn.addEventListener('click',()=>sidebar.classList.contains('open')?closeSB():openSB());
overlay.addEventListener('click',closeSB);
document.querySelectorAll('.sidebar .menu').forEach(l=>l.addEventListener('click',()=>{if(window.innerWidth<=768)closeSB()}));
window.addEventListener('resize',()=>{if(window.innerWidth>768){sidebar.classList.remove('open');overlay.classList.remove('active');document.body.style.overflow=''}});

/* ═══════════════════════════════════════
   THEME
═══════════════════════════════════════ */
const themeToggle=document.getElementById('themeToggle'), themeIcon=themeToggle.querySelector('i');
function applyTheme(d){
    document.body.classList.toggle('dark',d);
    themeToggle.classList.toggle('active',d);
    themeIcon.className=d?'fa-solid fa-sun':'fa-solid fa-moon';
}
applyTheme(localStorage.getItem('theme')==='dark');
themeToggle.addEventListener('click',()=>{const d=!document.body.classList.contains('dark');applyTheme(d);localStorage.setItem('theme',d?'dark':'light')});

/* ═══════════════════════════════════════
   INIT
═══════════════════════════════════════ */
loadData();
</script>
</body>
</html>