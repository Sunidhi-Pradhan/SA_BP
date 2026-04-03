<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Details Monthly LPP – Security Billing Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root { --primary:#0f766e; --primary-dark:#0d5f58; --sidebar-width:270px; }
        html { scroll-behavior:smooth; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f1f5f4; color:#333; line-height:1.5; }
        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }

        /* ── SIDEBAR (identical to monthlylpp) ── */
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

        /* ── MAIN ── */
        .main-content { overflow-y:auto; display:flex; flex-direction:column; min-width:0; }

        /* ── TOPBAR ── */
        .topbar { display:flex; justify-content:space-between; align-items:center; background:white; padding:0.75rem 1.5rem; box-shadow:0 1px 6px rgba(0,0,0,0.06); border-bottom:1px solid #e5e7eb; gap:1rem; position:sticky; top:0; z-index:50; }
        .hamburger-btn { display:none; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px; width:34px; height:34px; align-items:center; justify-content:center; cursor:pointer; color:#0f766e; font-size:0.9rem; flex-shrink:0; }
        .topbar-left { display:flex; align-items:center; gap:0.75rem; }
        .topbar h2 { font-size:1.1rem; font-weight:700; color:#1f2937; }
        .topbar-right { display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .header-icon { width:34px; height:34px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; color:#6b7280; font-size:0.88rem; border:1px solid #e5e7eb; }
        .header-icon .badge { position:absolute; top:-3px; right:-3px; background:#ef4444; color:white; font-size:0.55rem; width:15px; height:15px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-icon { width:34px; height:34px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
        .user-icon svg { width:16px; height:16px; stroke:white; }

        /* ── PAGE BODY ── */
        .page-body { padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:1rem; }

        /* ── FILTER PANEL ── */
        .filter-card { background:white; border-radius:10px; border:1px solid #e5e7eb; box-shadow:0 1px 6px rgba(0,0,0,0.05); padding:1rem 1.25rem; }
        .filter-row { display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; align-items:end; }
        .filter-field { display:flex; flex-direction:column; gap:0.3rem; }
        .filter-label { font-size:0.7rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.6px; }
        .filter-select { padding:0.6rem 2rem 0.6rem 0.85rem; border:1.5px solid #e5e7eb; border-radius:8px; font-size:0.85rem; color:#1f2937; background:#fff; outline:none; transition:border-color 0.2s; width:100%; appearance:none; -webkit-appearance:none; cursor:pointer; background-image:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="%236b7280" viewBox="0 0 16 16"><path d="M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>'); background-repeat:no-repeat; background-position:right 0.8rem center; }
        .filter-select:focus { border-color:#0f766e; }
        .month-picker-wrap { position:relative; }
        .month-disp { width:100%; padding:0.6rem 0.85rem; border:1.5px solid #e5e7eb; border-radius:8px; font-size:0.85rem; color:#1f2937; background:white; outline:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; transition:border-color 0.2s; }
        .month-disp:hover, .month-disp.open { border-color:#0f766e; }
        .month-popup { display:none; position:absolute; top:calc(100% + 6px); left:0; background:white; border:1.5px solid #e5e7eb; border-radius:12px; box-shadow:0 8px 28px rgba(0,0,0,0.12); padding:1rem; z-index:300; min-width:270px; }
        .month-popup.open { display:block; animation:fadeUp 0.2s ease; }
        @keyframes fadeUp { from{transform:translateY(8px);opacity:0} to{transform:translateY(0);opacity:1} }
        .picker-year { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem; }
        .picker-year-label { font-size:0.95rem; font-weight:700; color:#1f2937; }
        .picker-year-btn { background:none; border:1.5px solid #e5e7eb; border-radius:7px; width:28px; height:28px; cursor:pointer; color:#374151; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .picker-year-btn:hover { border-color:#0f766e; color:#0f766e; }
        .picker-months { display:grid; grid-template-columns:repeat(4,1fr); gap:0.4rem; }
        .picker-month-btn { padding:0.42rem; border-radius:7px; border:1.5px solid #e5e7eb; background:white; font-size:0.78rem; font-weight:600; color:#374151; cursor:pointer; transition:all 0.2s; text-align:center; }
        .picker-month-btn:hover { border-color:#0f766e; color:#0f766e; background:#f0fdf4; }
        .picker-month-btn.active { background:#0f766e; color:white; border-color:#0f766e; }
        .picker-footer { display:flex; justify-content:space-between; margin-top:0.5rem; padding-top:0.5rem; border-top:1px solid #f0f0f0; font-size:0.78rem; }
        .picker-footer a { color:#6b7280; cursor:pointer; }
        .picker-footer a:hover { color:#0f766e; }
        .btn-filter { display:inline-flex; align-items:center; gap:0.45rem; padding:0.62rem 1.4rem; border-radius:8px; background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; border:none; font-size:0.85rem; font-weight:700; cursor:pointer; white-space:nowrap; transition:all 0.2s; box-shadow:0 2px 8px rgba(15,118,110,0.3); }
        .btn-filter:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(15,118,110,0.38); }

        /* ── SUMMARY HEADER ── */
        .summary-bar { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; }
        .summary-title h3 { font-size:1rem; font-weight:800; color:#1f2937; }
        .summary-title p { font-size:0.75rem; color:#6b7280; margin-top:0.1rem; }
        .summary-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
        .month-chip { display:inline-flex; align-items:center; gap:0.35rem; background:#f0fdf4; color:#0f766e; border:1.5px solid #86efac; border-radius:20px; padding:0.25rem 0.8rem; font-size:0.75rem; font-weight:700; }
        .month-chip-dot { width:7px; height:7px; border-radius:50%; background:#0f766e; display:inline-block; }
        .btn-excel { display:inline-flex; align-items:center; gap:0.35rem; padding:0.38rem 0.85rem; border-radius:6px; background:#16a34a; color:white; border:none; font-size:0.76rem; font-weight:700; cursor:pointer; transition:all 0.2s; }
        .btn-excel:hover { background:#15803d; }
        .btn-pdf { display:inline-flex; align-items:center; gap:0.35rem; padding:0.38rem 0.85rem; border-radius:6px; background:#dc2626; color:white; border:none; font-size:0.76rem; font-weight:700; cursor:pointer; transition:all 0.2s; }
        .btn-pdf:hover { background:#b91c1c; }

        /* ── SEARCH BAR ── */
        .search-bar { padding:0.45rem 0.85rem 0.45rem 2rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.8rem; outline:none; width:190px; background:white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.6rem center; background-size:12px; }
        .search-bar:focus { border-color:#0f766e; }

        /* ── TABLE CARD ── */
        .table-card { background:white; border-radius:10px; border:1px solid #e5e7eb; box-shadow:0 1px 6px rgba(0,0,0,0.05); overflow:hidden; }
        .table-toolbar { display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1rem; border-bottom:1px solid #f0f0f0; flex-wrap:wrap; gap:0.6rem; }
        .search-label { font-size:0.78rem; color:#6b7280; font-weight:500; }

        /* ── MAIN TABLE ── */
        .lpp-table-wrap { overflow-x:auto; scrollbar-width:thin; scrollbar-color:#0f766e #f0f0f0; }
        .lpp-table-wrap::-webkit-scrollbar { height:4px; }
        .lpp-table-wrap::-webkit-scrollbar-thumb { background:#0f766e; border-radius:3px; }
        .lpp-table { width:100%; border-collapse:collapse; font-size:0.78rem; min-width:860px; }

        .lpp-table thead tr th { background:#f8fafc; color:#374151; font-weight:700; font-size:0.72rem; padding:0.65rem 0.75rem; text-align:center; border-bottom:2px solid #e5e7eb; border-right:1px solid #e5e7eb; white-space:nowrap; position:relative; }
        .lpp-table thead tr th:first-child { text-align:left; padding-left:1rem; min-width:280px; border-right:1px solid #e5e7eb; }
        .lpp-table thead tr th:last-child { border-right:none; background:#f0fdf4; color:#065f46; }
        .sort-icon { font-size:0.6rem; color:#9ca3af; margin-left:4px; cursor:pointer; }

        .site-row { cursor:pointer; transition:background 0.12s; border-bottom:1px solid #f0f0f0; }
        .site-row:hover { background:#f9fafb; }
        .site-row.expanded { background:#f0fdf9; border-bottom:none; }
        .site-row td { padding:0.65rem 0.75rem; vertical-align:middle; color:#1f2937; white-space:nowrap; text-align:center; border-right:1px solid #f0f0f0; }
        .site-row td:first-child { text-align:left; padding-left:0.75rem; border-right:1px solid #f0f0f0; }
        .site-row td:last-child { border-right:none; color:#065f46; font-weight:700; background:#f0fdf4; }

        .site-name-wrap { display:flex; align-items:flex-start; gap:0.5rem; }
        .expand-btn { width:20px; height:20px; border-radius:5px; border:1.5px solid #d1d5db; background:white; color:#6b7280; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.58rem; flex-shrink:0; margin-top:1px; transition:all 0.2s; }
        .expand-btn.open { background:#0f766e; color:white; border-color:#0f766e; }
        .site-icon { width:20px; height:20px; border-radius:4px; background:#dbeafe; display:flex; align-items:center; justify-content:center; font-size:0.58rem; color:#1d4ed8; font-weight:900; flex-shrink:0; margin-top:1px; }
        .site-info { display:flex; flex-direction:column; }
        .site-name { font-weight:700; font-size:0.8rem; color:#1f2937; }
        .site-emp-count { font-size:0.65rem; color:#9ca3af; }

        .pill { display:inline-flex; align-items:center; justify-content:center; min-width:26px; height:22px; border-radius:20px; font-size:0.7rem; font-weight:700; padding:0 0.45rem; }
        .pill-green  { background:#dcfce7; color:#15803d; }
        .pill-red    { background:#fee2e2; color:#b91c1c; }
        .pill-orange { background:#fff7ed; color:#c2410c; }
        .pill-blue   { background:#dbeafe; color:#1d4ed8; }
        .pill-yellow { background:#fef9c3; color:#a16207; }
        .pill-gray   { background:#f3f4f6; color:#6b7280; }

        .breakdown-link-row td { padding:0.25rem 0.75rem 0.5rem 2.7rem; background:#f0fdf9; border-bottom:1px solid #e0f2f1; }
        .breakdown-link-row.hidden { display:none; }
        .breakdown-link { display:inline-flex; align-items:center; gap:0.3rem; font-size:0.72rem; font-weight:600; color:#0f766e; cursor:pointer; border:none; background:none; padding:0; text-decoration:underline; text-underline-offset:2px; }
        .breakdown-link i { font-size:0.65rem; }

        .desig-section-row { display:none; }
        .desig-section-row.open { display:table-row; }
        .desig-outer-td { padding:0; background:#f8fafc; border-bottom:2px solid #d1fae5; }

        .desig-sub-wrap { overflow-x:auto; }
        .desig-sub-table { width:100%; border-collapse:collapse; font-size:0.73rem; min-width:860px; }
        .desig-sub-table thead th { background:linear-gradient(135deg,#1e3a5f 0%,#1a3354 100%); color:rgba(255,255,255,0.92); font-weight:700; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.5rem 0.65rem; text-align:center; border-right:1px solid rgba(255,255,255,0.08); white-space:nowrap; }
        .desig-sub-table thead th:first-child { text-align:left; padding-left:2.5rem; min-width:160px; }
        .desig-sub-table thead th:last-child { background:linear-gradient(135deg,#059669,#047857) !important; border-right:none; }
        .desig-sub-table tbody tr { border-bottom:1px solid #edf0f5; }
        .desig-sub-table tbody tr:nth-child(even) { background:#f0f4f8; }
        .desig-sub-table tbody tr:last-child { border-bottom:none; }
        .desig-sub-table tbody td { padding:0.5rem 0.65rem; text-align:center; color:#374151; white-space:nowrap; border-right:1px solid #edf0f5; vertical-align:middle; }
        .desig-sub-table tbody td:first-child { text-align:left; padding-left:2.5rem; font-weight:600; color:#1f2937; }
        .desig-sub-table tbody td:last-child { border-right:none; background:#d1fae5; color:#065f46; font-weight:700; }
        .desig-sub-table tbody tr:nth-child(even) td:last-child { background:#a7f3d0; }

        .grand-row td { background:linear-gradient(90deg,#f0fdf4,#dcfce7); font-weight:800; color:#065f46; padding:0.7rem 0.75rem; text-align:center; border-top:2px solid #6ee7b7; white-space:nowrap; font-size:0.78rem; }
        .grand-row td:first-child { text-align:left; padding-left:1rem; }
        .grand-row td:last-child { background:linear-gradient(135deg,#059669,#047857); color:white; border-right:none; }

        /* ── SIDEBAR OVERLAY ── */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }

        /* ── RESPONSIVE ── */
        @media (max-width:900px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); transition:transform 0.3s; z-index:200; }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .filter-row { grid-template-columns:1fr; }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">

    <!-- ── SIDEBAR (identical to monthlylpp) ── -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <div class="mcl-logo-box">
                <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img"
                     onerror="this.parentElement.innerHTML='<span style=&quot;font-size:1.4rem;font-weight:900;color:#0f766e;letter-spacing:2px;&quot;>&#9679; MCL</span>'">
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthlyatt.php" class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="monthlylpp.php" class="nav-link"><i class="fa-solid fa-calendar-check"></i><span>Monthly LPP</span></a></li>
            <li><a href="details_monthly_lpp.php" class="nav-link active"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a href="vvstatement.php" class="nav-link"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- ── MAIN ── -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
                <h2>Security Billing Management Portal</h2>
            </div>
            <div class="topbar-right">
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <div class="user-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
                    </svg>
                </div>
            </div>
        </header>

        <div class="page-body">

            <!-- Filter Panel -->
            <div class="filter-card">
                <div class="filter-row">
                    <div class="filter-field">
                        <label class="filter-label">Site Filter</label>
                        <select class="filter-select" id="siteFilter">
                            <option value="all">All Sites</option>
                            <option>MAHANADI COAL FIELD(001)</option>
                            <option>MAHANADI COAL FIELD BHUBANESWAR(002)</option>
                            <option>MAHANADI COAL FIELD SAMBALPUR(003)</option>
                            <option>MAHANADI COAL FIELDS LIMITED(004)</option>
                            <option>MCL HQ BURLA(016)</option>
                            <option>MCL TALCHER COALFIELD(018)</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label">Select Billing Month &amp; Year</label>
                        <div class="month-picker-wrap">
                            <div class="month-disp" id="monthDisp" onclick="toggleMonthPicker()">
                                <span id="monthDispText">February, 2026</span>
                                <i class="fa-solid fa-chevron-down" style="font-size:0.6rem;color:#9ca3af;"></i>
                            </div>
                            <div class="month-popup" id="monthPopup">
                                <div class="picker-year">
                                    <button type="button" class="picker-year-btn" onclick="changePickerYear(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                                    <span class="picker-year-label" id="pickerYearLabel">2026</span>
                                    <button type="button" class="picker-year-btn" onclick="changePickerYear(1)"><i class="fa-solid fa-chevron-right"></i></button>
                                </div>
                                <div class="picker-months" id="pickerMonthsGrid"></div>
                                <div class="picker-footer">
                                    <a onclick="clearMonthPicker()">Clear</a>
                                    <a style="color:#0f766e;font-weight:600;" onclick="setThisMonth()">This month</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" style="visibility:hidden">.</label>
                        <button class="btn-filter" onclick="applyFilter()">
                            <i class="fa-solid fa-magnifying-glass"></i> Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Bar -->
            <div class="summary-bar">
                <div class="summary-title">
                    <h3 id="summaryTitle">LPP Summary (February 2026)</h3>
                    <p id="summarySubtitle">35 sites &nbsp;|&nbsp; 9,555 total employees</p>
                </div>
                <div class="summary-right">
                    <div class="month-chip"><span class="month-chip-dot"></span> <span id="summaryMonthChip">February 2026</span></div>
                    <button class="btn-excel"><i class="fa-solid fa-file-excel"></i> Excel</button>
                    <button class="btn-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="table-card">
                <div class="table-toolbar">
                    <div></div>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="search-label">Search sites:</span>
                        <input type="text" class="search-bar" id="searchBar" placeholder="" oninput="filterSites()">
                    </div>
                </div>
                <div class="lpp-table-wrap">
                    <table class="lpp-table" id="lppTable">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding-left:1rem;">Site Name <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Present <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Absent <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Leave <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Extra<br>Duty <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Total Working<br>Days <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Total<br>Employees <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Net Pay <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>GST (18%) <i class="fa-solid fa-sort sort-icon"></i></th>
                                <th>Gross Total <i class="fa-solid fa-sort sort-icon"></i></th>
                            </tr>
                        </thead>
                        <tbody id="lppBody"></tbody>
                        <tfoot>
                            <tr class="grand-row">
                                <td><i class="fa-solid fa-sigma" style="margin-right:0.35rem;"></i>GRAND TOTAL</td>
                                <td id="gt-present">0</td>
                                <td id="gt-absent">0</td>
                                <td id="gt-leave">0</td>
                                <td id="gt-extra">0</td>
                                <td id="gt-twd">0</td>
                                <td id="gt-emp">0</td>
                                <td id="gt-net">&#8377;0.00</td>
                                <td id="gt-gst">&#8377;0.00</td>
                                <td id="gt-gross">&#8377;0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div><!-- /page-body -->
    </main>
</div>

<script>
/* ── DATA ── */
const siteData = [
    { id:1,  name:'MAHANADI COAL FIELD(001)',                          emp:277  },
    { id:2,  name:'MAHANADI COAL FIELD BHUBANESWAR(002)',              emp:53   },
    { id:3,  name:'MAHANADI COAL FIELD SAMBALPUR(003)',                emp:148  },
    { id:4,  name:'MAHANADI COAL FIELDS LIMITED(004)',                 emp:109  },
    { id:5,  name:'MAHANADI COALFIELD LIMITED (NSCH COLLEGE)(007)',    emp:31   },
    { id:6,  name:'MAHANADI COALFIELD LIMITED (NSCH HOSPITAL)(008)',   emp:44   },
    { id:7,  name:'MAHANADI COALFIELD LIMITED (HINGULA GM)(010)',      emp:62   },
    { id:8,  name:'MAHANADI COALFIELD LIMITED (BALANDA OCP)(011)',     emp:88   },
    { id:9,  name:'MCL HQ BURLA(016)',                                 emp:312  },
    { id:10, name:'MCL REGIONAL OFFICE SAMBALPUR(017)',                emp:120  },
    { id:11, name:'MCL TALCHER COALFIELD(018)',                        emp:198  },
    { id:12, name:'MCL IB VALLEY COALFIELD(019)',                      emp:155  },
    { id:13, name:'MAHANADI COALFIELD ANANTA OCP(020)',                emp:76   },
    { id:14, name:'MAHANADI COALFIELD BHARATPUR OCP(021)',             emp:93   },
    { id:15, name:'MCL HOSPITAL BURLA(026)',                           emp:210  },
];

const designations = [
    { title:'GUN MAN',             empPct:0.05, basic:893,  cmpf:107.16, cmps:62.51, bonus:null,  esi:null,  pfAdmin:6.43, otherAllow:1069.10, sc:133.64, perDay:1202.74 },
    { title:'SECURITY GUARD',      empPct:0.88, basic:760,  cmpf:91.20,  cmps:53.20, bonus:63.31, esi:30.40, pfAdmin:5.47, otherAllow:1003.58, sc:125.45, perDay:1129.03 },
    { title:'SECURITY SUPERVISOR', empPct:0.07, basic:893,  cmpf:107.16, cmps:62.51, bonus:null,  esi:null,  pfAdmin:6.43, otherAllow:1069.10, sc:133.64, perDay:1202.74 },
];

const INR = v => v === 0 ? '&#8377;0.00' : '&#8377;' + v.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
const shortMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const fullMonths  = ['January','February','March','April','May','June','July','August','September','October','November','December'];

let pickerYear = 2026, pickerMonth = 2;

function toggleMonthPicker() {
    const popup = document.getElementById('monthPopup');
    const disp  = document.getElementById('monthDisp');
    const open  = popup.classList.toggle('open');
    disp.classList.toggle('open', open);
    if (open) renderPickerMonths();
}
function renderPickerMonths() {
    const grid = document.getElementById('pickerMonthsGrid');
    grid.innerHTML = '';
    shortMonths.forEach((m,i) => {
        const b = document.createElement('button');
        b.type='button'; b.className='picker-month-btn'+(i+1===pickerMonth?' active':'');
        b.textContent=m; b.onclick=()=>selectPickerMonth(i+1);
        grid.appendChild(b);
    });
    document.getElementById('pickerYearLabel').textContent = pickerYear;
}
function selectPickerMonth(m) {
    pickerMonth = m;
    document.getElementById('monthDispText').textContent = fullMonths[m-1]+', '+pickerYear;
    document.getElementById('monthPopup').classList.remove('open');
    document.getElementById('monthDisp').classList.remove('open');
}
function changePickerYear(d) { pickerYear+=d; renderPickerMonths(); }
function clearMonthPicker() { pickerMonth=2; pickerYear=2026; selectPickerMonth(2); }
function setThisMonth() { const n=new Date(); pickerYear=n.getFullYear(); pickerMonth=n.getMonth()+1; selectPickerMonth(pickerMonth); }
document.addEventListener('click', e => {
    const w=document.querySelector('.month-picker-wrap');
    if(w&&!w.contains(e.target)){
        document.getElementById('monthPopup').classList.remove('open');
        document.getElementById('monthDisp').classList.remove('open');
    }
});

/* ── BUILD TABLE ── */
function applyFilter() { buildTable(); }

function buildTable() {
    const body = document.getElementById('lppBody');
    body.innerHTML = '';
    let gTotals = { emp:0, net:0, gst:0, gross:0 };

    const siteFilter = document.getElementById('siteFilter').value;
    const filtered = siteFilter === 'all' ? siteData : siteData.filter(s => s.name === siteFilter);

    filtered.forEach(site => {
        const desigRows = designations.map(d => {
            const empCount = Math.max(1, Math.round(site.emp * d.empPct));
            const netPay = d.perDay * 26 * empCount;
            const gst    = netPay * 0.18;
            const gross  = netPay + gst;
            return { ...d, empCount, netPay, gst, gross };
        });
        const siteNet   = desigRows.reduce((s,r)=>s+r.netPay, 0);
        const siteGst   = desigRows.reduce((s,r)=>s+r.gst, 0);
        const siteGross = desigRows.reduce((s,r)=>s+r.gross, 0);

        gTotals.emp   += site.emp;
        gTotals.net   += siteNet;
        gTotals.gst   += siteGst;
        gTotals.gross += siteGross;

        const sid = site.id;

        /* Site Row */
        const tr = document.createElement('tr');
        tr.className = 'site-row';
        tr.dataset.siteName = site.name.toLowerCase();
        tr.dataset.siteId = sid;
        tr.innerHTML = `
            <td>
                <div class="site-name-wrap">
                    <button class="expand-btn" id="expbtn-${sid}" onclick="event.stopPropagation();toggleDesig(${sid})"><i class="fa-solid fa-chevron-right" style="font-size:0.55rem;transition:transform 0.2s;"></i></button>
                    <div class="site-icon">R</div>
                    <div class="site-info">
                        <span class="site-name">${site.name}</span>
                        <span class="site-emp-count">${site.emp} employees</span>
                    </div>
                </div>
            </td>
            <td><span class="pill pill-green">0</span></td>
            <td><span class="pill pill-red">0</span></td>
            <td><span class="pill pill-orange">0</span></td>
            <td><span class="pill pill-blue">0</span></td>
            <td><span class="pill pill-yellow">0</span></td>
            <td><strong>${site.emp}</strong></td>
            <td>${INR(siteNet)}</td>
            <td>${INR(siteGst)}</td>
            <td>${INR(siteGross)}</td>
        `;
        tr.onclick = () => toggleDesig(sid);
        body.appendChild(tr);

        /* Breakdown link row */
        const blr = document.createElement('tr');
        blr.className = 'breakdown-link-row hidden';
        blr.id = `blr-${sid}`;
        blr.innerHTML = `
            <td colspan="10">
                <button class="breakdown-link" onclick="event.stopPropagation();toggleDesig(${sid})">
                    <i class="fa-solid fa-layer-group"></i> Designation-wise Breakdown
                </button>
            </td>
        `;
        body.appendChild(blr);

        /* Designation sub-table row */
        const dsr = document.createElement('tr');
        dsr.className = 'desig-section-row';
        dsr.id = `dsr-${sid}`;

        const subRows = desigRows.map(d => `
            <tr>
                <td>${d.title}</td>
                <td>${d.empCount}</td>
                <td><span class="pill pill-green">0</span></td>
                <td><span class="pill pill-red">0</span></td>
                <td><span class="pill pill-orange">0</span></td>
                <td>${INR(d.basic)}</td>
                <td>${INR(d.cmpf)}</td>
                <td>${INR(d.cmps)}</td>
                <td>${d.bonus ? INR(d.bonus) : '&mdash;'}</td>
                <td>${d.esi ? INR(d.esi) : '&mdash;'}</td>
                <td>${INR(d.pfAdmin)}</td>
                <td>${INR(d.otherAllow)}</td>
                <td>${INR(d.sc)}</td>
                <td>${INR(d.perDay)}</td>
                <td>${INR(d.netPay)}</td>
                <td>${INR(d.gst)}</td>
                <td>${INR(d.gross)}</td>
            </tr>
        `).join('');

        dsr.innerHTML = `
            <td colspan="10" class="desig-outer-td">
                <div class="desig-sub-wrap">
                    <table class="desig-sub-table">
                        <thead>
                            <tr>
                                <th>Designation</th>
                                <th>Emp</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Leave</th>
                                <th>Basic+VDA</th>
                                <th>CMPF</th>
                                <th>CMPS</th>
                                <th>Bonus</th>
                                <th>ESI</th>
                                <th>PF Admin</th>
                                <th>Other Allowance</th>
                                <th>Service charge 12.5%</th>
                                <th>Per Day</th>
                                <th>Net Pay</th>
                                <th>GST 18%</th>
                                <th>Gross Total</th>
                            </tr>
                        </thead>
                        <tbody>${subRows}</tbody>
                    </table>
                </div>
            </td>
        `;
        body.appendChild(dsr);
    });

    /* Grand totals */
    document.getElementById('gt-emp').textContent   = gTotals.emp.toLocaleString('en-IN');
    document.getElementById('gt-net').innerHTML     = INR(gTotals.net);
    document.getElementById('gt-gst').innerHTML     = INR(gTotals.gst);
    document.getElementById('gt-gross').innerHTML   = INR(gTotals.gross);

    const mLabel = fullMonths[pickerMonth-1] + ' ' + pickerYear;
    document.getElementById('summaryTitle').textContent     = `LPP Summary (${mLabel})`;
    document.getElementById('summaryMonthChip').textContent = mLabel;
    document.getElementById('summarySubtitle').textContent  = `${filtered.length} sites\u00a0|\u00a0${gTotals.emp.toLocaleString('en-IN')} total employees`;

    if (filtered.length > 0) toggleDesig(filtered[0].id);
}

/* ── TOGGLE ── */
function toggleDesig(sid) {
    const siteRow = document.querySelector(`[data-site-id="${sid}"]`);
    const blr  = document.getElementById(`blr-${sid}`);
    const dsr  = document.getElementById(`dsr-${sid}`);
    const btn  = document.getElementById(`expbtn-${sid}`);
    if (!siteRow) return;
    const isOpen = dsr.classList.contains('open');
    if (isOpen) {
        dsr.classList.remove('open');
        blr.classList.add('hidden');
        btn.classList.remove('open');
        siteRow.classList.remove('expanded');
        btn.querySelector('i').style.transform = '';
    } else {
        dsr.classList.add('open');
        blr.classList.remove('hidden');
        btn.classList.add('open');
        siteRow.classList.add('expanded');
        btn.querySelector('i').style.transform = 'rotate(90deg)';
    }
}

/* ── SEARCH ── */
function filterSites() {
    const q = document.getElementById('searchBar').value.toLowerCase();
    document.querySelectorAll('.site-row').forEach(row => {
        const sid = row.dataset.siteId;
        const match = row.dataset.siteName.includes(q);
        row.style.display = match ? '' : 'none';
        const blr = document.getElementById(`blr-${sid}`);
        const dsr = document.getElementById(`dsr-${sid}`);
        if (blr) blr.style.display = match ? '' : 'none';
        if (dsr) dsr.style.display = match ? '' : 'none';
    });
}

/* ── SIDEBAR ── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('hamburgerBtn').addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('active'); });
document.getElementById('sidebarClose').addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

/* ── INIT ── */
buildTable();
</script>
</body>
</html>