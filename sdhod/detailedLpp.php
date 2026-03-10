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
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f1f5f4; color:#333; line-height:1.6; }
        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }

        /* ── SIDEBAR ── */
        .sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; display:flex; flex-direction:column; }
        .sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,0.12); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:1rem; align-items:center; justify-content:center; z-index:2; }
        .sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .mcl-logo-box { background:white; padding:10px 20px; border-radius:10px; font-size:1.5rem; font-weight:900; color:#0f766e; letter-spacing:2px; }
        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li { margin:0.25rem 1rem; }
        .nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; cursor:pointer; }
        .nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }

        /* ── MAIN ── */
        .main-content { padding:1.5rem 2rem; overflow-y:auto; display:flex; flex-direction:column; gap:1.25rem; min-width:0; }

        /* ── TOPBAR ── */
        .topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.07); border:1px solid #e5e7eb; gap:1rem; }
        .hamburger-btn { display:none; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px; width:38px; height:38px; align-items:center; justify-content:center; cursor:pointer; color:#0f766e; font-size:1rem; flex-shrink:0; }
        .topbar h2 { font-size:1.3rem; font-weight:700; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .topbar-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
        .header-icon { width:38px; height:38px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; color:#6b7280; font-size:0.95rem; border:1px solid #e5e7eb; }
        .header-icon .badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:0.6rem; width:17px; height:17px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-icon { width:38px; height:38px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
        .user-icon svg { width:18px; height:18px; stroke:white; }
        .role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:#f0fdf4; color:#15803d; border:1.5px solid #86efac; border-radius:20px; padding:0.28rem 0.85rem; font-size:0.8rem; font-weight:700; }

        /* ── FILTER PANEL ── */
        .filter-card { background:white; border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 2px 10px rgba(0,0,0,0.06); padding:1.25rem 1.5rem; }
        .filter-row { display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; align-items:end; }
        .filter-field { display:flex; flex-direction:column; gap:0.4rem; }
        .filter-label { font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
        .filter-select, .filter-input { padding:0.72rem 1rem; border:1.5px solid #e5e7eb; border-radius:9px; font-size:0.88rem; color:#1f2937; background:#f9fafb; outline:none; transition:border-color 0.2s; width:100%; appearance:none; -webkit-appearance:none; cursor:pointer; }
        .filter-select { background-image:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="%236b7280" viewBox="0 0 16 16"><path d="M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>'); background-repeat:no-repeat; background-position:right 0.9rem center; padding-right:2.2rem; }
        .filter-select:focus, .filter-input:focus { border-color:#0f766e; background:white; }
        .month-picker-wrap { position:relative; }
        .month-disp { width:100%; padding:0.72rem 1rem; border:1.5px solid #e5e7eb; border-radius:9px; font-size:0.88rem; color:#1f2937; background:#f9fafb; outline:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; transition:border-color 0.2s; }
        .month-disp:hover, .month-disp.open { border-color:#0f766e; background:white; }
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
        .btn-filter { display:inline-flex; align-items:center; gap:0.5rem; padding:0.72rem 1.5rem; border-radius:9px; background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; border:none; font-size:0.88rem; font-weight:700; cursor:pointer; white-space:nowrap; transition:all 0.2s; box-shadow:0 3px 10px rgba(15,118,110,0.28); }
        .btn-filter:hover { transform:translateY(-1px); box-shadow:0 5px 16px rgba(15,118,110,0.35); }

        /* ── SUMMARY HEADER ── */
        .summary-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; }
        .summary-title h3 { font-size:1.1rem; font-weight:800; color:#1f2937; }
        .summary-title p { font-size:0.78rem; color:#6b7280; margin-top:0.1rem; }
        .summary-meta { display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; }
        .month-chip { display:inline-flex; align-items:center; gap:0.35rem; background:#f0fdf4; color:#0f766e; border:1.5px solid #86efac; border-radius:20px; padding:0.28rem 0.85rem; font-size:0.8rem; font-weight:700; }
        .month-chip span { width:8px; height:8px; border-radius:50%; background:#0f766e; display:inline-block; }
        .btn-excel { display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.9rem; border-radius:7px; background:#16a34a; color:white; border:none; font-size:0.8rem; font-weight:700; cursor:pointer; transition:all 0.2s; }
        .btn-excel:hover { background:#15803d; }
        .btn-pdf { display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.9rem; border-radius:7px; background:#dc2626; color:white; border:none; font-size:0.8rem; font-weight:700; cursor:pointer; transition:all 0.2s; }
        .btn-pdf:hover { background:#b91c1c; }

        /* ── SEARCH BAR ── */
        .search-bar-wrap { position:relative; }
        .search-bar { padding:0.5rem 0.9rem 0.5rem 2.2rem; border:1.5px solid #e5e7eb; border-radius:8px; font-size:0.84rem; outline:none; width:200px; background:white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.65rem center; background-size:13px; }
        .search-bar:focus { border-color:#0f766e; }

        /* ── MAIN TABLE CARD ── */
        .table-card { background:white; border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow:hidden; }
        .table-card-toolbar { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f0f0f0; flex-wrap:wrap; gap:0.75rem; }
        .table-card-title { font-size:0.88rem; font-weight:700; color:#0f766e; display:flex; align-items:center; gap:0.45rem; }

        /* ── LPP TABLE ── */
        .lpp-table-wrap { overflow-x:auto; scrollbar-width:thin; scrollbar-color:#0f766e #f0f0f0; }
        .lpp-table-wrap::-webkit-scrollbar { height:5px; }
        .lpp-table-wrap::-webkit-scrollbar-thumb { background:#0f766e; border-radius:3px; }
        .lpp-table { width:100%; border-collapse:collapse; font-size:0.8rem; min-width:900px; }

        /* Header */
        .lpp-table thead th { background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; font-weight:700; font-size:0.71rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.75rem 0.75rem; text-align:center; white-space:nowrap; border-right:1px solid rgba(255,255,255,0.1); }
        .lpp-table thead th:first-child { text-align:left; padding-left:1rem; min-width:260px; }
        .lpp-table thead th:last-child { border-right:none; background:linear-gradient(135deg,#059669,#047857) !important; }

        /* Site rows */
        .lpp-table tbody tr.site-row { cursor:pointer; transition:background 0.15s; border-bottom:1px solid #e5e7eb; }
        .lpp-table tbody tr.site-row:hover { background:#f0fdf4; }
        .lpp-table tbody tr.site-row td { padding:0.75rem 0.75rem; vertical-align:middle; color:#1f2937; white-space:nowrap; text-align:center; border-right:1px solid #f0f0f0; }
        .lpp-table tbody tr.site-row td:first-child { text-align:left; padding-left:0.85rem; }
        .lpp-table tbody tr.site-row td:last-child { border-right:none; background:#d1fae5; color:#065f46; font-weight:700; }
        .lpp-table tbody tr.site-row.expanded { background:#f0fdf4; }
        .lpp-table tbody tr.site-row.expanded td { border-bottom:none; }

        /* Site name cell */
        .site-name-wrap { display:flex; align-items:center; gap:0.5rem; }
        .expand-btn { width:22px; height:22px; border-radius:6px; border:1.5px solid #0f766e; background:white; color:#0f766e; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.65rem; flex-shrink:0; transition:all 0.2s; }
        .expand-btn.open { background:#0f766e; color:white; }
        .site-icon { width:22px; height:22px; border-radius:5px; background:#e0f2fe; display:flex; align-items:center; justify-content:center; font-size:0.6rem; color:#0369a1; font-weight:900; flex-shrink:0; }
        .site-info { display:flex; flex-direction:column; }
        .site-name { font-weight:700; font-size:0.82rem; color:#1f2937; }
        .site-emp-count { font-size:0.68rem; color:#6b7280; }

        /* Badge pills for attendance */
        .pill { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:24px; border-radius:20px; font-size:0.72rem; font-weight:700; padding:0 0.5rem; }
        .pill-green  { background:#dcfce7; color:#15803d; }
        .pill-red    { background:#fee2e2; color:#b91c1c; }
        .pill-orange { background:#fff7ed; color:#c2410c; }
        .pill-blue   { background:#dbeafe; color:#1d4ed8; }
        .pill-gray   { background:#f3f4f6; color:#6b7280; }

        /* Designation breakdown rows */
        .desig-section { display:none; }
        .desig-section.open { display:table-row-group; animation:fadeUp 0.25s ease; }

        /* Breakdown header */
        .desig-header-row td { background:#f8fafc; padding:0.5rem 0.75rem 0.4rem; border-bottom:1px solid #e5e7eb; }
        .desig-header-inner { display:flex; align-items:center; gap:0.4rem; font-size:0.72rem; font-weight:700; color:#0f766e; }
        .desig-header-inner i { font-size:0.72rem; }

        /* Designation sub-table */
        .desig-sub-wrap { padding:0 0 0.75rem; background:#f8fafc; }
        .desig-sub-table { width:100%; border-collapse:collapse; font-size:0.74rem; min-width:900px; }
        .desig-sub-table thead th { background:linear-gradient(135deg,#1e3a5f,#1a3354); color:rgba(255,255,255,0.9); font-weight:700; font-size:0.67rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.55rem 0.65rem; text-align:center; white-space:nowrap; border-right:1px solid rgba(255,255,255,0.08); }
        .desig-sub-table thead th:first-child { text-align:left; padding-left:2rem; min-width:160px; }
        .desig-sub-table thead th:last-child { border-right:none; background:linear-gradient(135deg,#059669,#047857) !important; }
        .desig-sub-table tbody td { padding:0.55rem 0.65rem; border-bottom:1px solid #edf0f5; text-align:center; color:#374151; white-space:nowrap; border-right:1px solid #f0f0f0; vertical-align:middle; }
        .desig-sub-table tbody td:first-child { text-align:left; padding-left:2rem; font-weight:600; color:#1f2937; }
        .desig-sub-table tbody td:last-child { border-right:none; background:#d1fae5; color:#065f46; font-weight:700; }
        .desig-sub-table tbody tr:last-child td { border-bottom:none; }
        .desig-sub-table tbody tr:nth-child(even) { background:#f0f4f8; }
        .desig-sub-table tbody tr:nth-child(even) td:last-child { background:#a7f3d0; }

        /* Breakdown link */
        .breakdown-link { display:inline-flex; align-items:center; gap:0.3rem; font-size:0.72rem; font-weight:600; color:#0f766e; cursor:pointer; border:none; background:none; padding:0; transition:color 0.2s; text-decoration:underline; text-underline-offset:2px; }
        .breakdown-link:hover { color:#0d5f58; }

        /* Grand total row */
        .grand-row td { background:linear-gradient(135deg,#f0fdf4,#dcfce7); font-weight:800; color:#065f46; padding:0.75rem 0.75rem; text-align:center; border-top:2px solid #6ee7b7; white-space:nowrap; font-size:0.8rem; }
        .grand-row td:first-child { text-align:left; padding-left:1rem; }
        .grand-row td:last-child { background:linear-gradient(135deg,#059669,#047857); color:white; }

        /* Empty state */
        .empty-state { text-align:center; padding:3rem 1rem; color:#9ca3af; }
        .empty-state i { font-size:2.5rem; margin-bottom:0.75rem; opacity:0.4; display:block; }

        /* Sidebar overlay */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }

        /* ── RESPONSIVE ── */
        @media (max-width:1024px) {
            :root { --sidebar-width:240px; }
            .main-content { padding:1.25rem 1.5rem; }
            .filter-row { grid-template-columns:1fr 1fr; }
            .btn-filter { grid-column:1/-1; }
        }
        @media (max-width:900px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); transition:transform 0.3s; z-index:200; }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .main-content { padding:1rem; }
            .filter-row { grid-template-columns:1fr; }
            .summary-header { flex-direction:column; align-items:flex-start; }
            .role-badge { display:none; }
            .search-bar { width:100%; }
        }
        @media (max-width:480px) {
            .main-content { padding:0.75rem; gap:0.75rem; }
            .topbar { padding:0.75rem 1rem; }
            .topbar h2 { font-size:0.9rem; }
            .filter-card { padding:1rem; }
            .table-card-toolbar { flex-direction:column; align-items:flex-start; }
            .summary-meta { flex-wrap:wrap; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <div class="mcl-logo-box">● MCL</div>
        </div>
        <ul class="sidebar-nav">
            <li><a class="nav-link" onclick="alert('Dashboard')"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a class="nav-link" onclick="alert('Monthly Attendance')"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a class="nav-link" onclick="window.location='lpp_billing_portal.html'"><i class="fa-solid fa-calendar-check"></i><span>Monthly LPP</span></a></li>
            <li><a class="nav-link active"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a class="nav-link" onclick="alert('VV Statement')"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a class="nav-link logout-link" onclick="alert('Logout')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- ── MAIN ── -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
            <h2>Security Billing Management Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-tie"></i> ADMIN</span>
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <div class="user-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
                    </svg>
                </div>
            </div>
        </header>

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
                            <i class="fa-solid fa-chevron-down" style="font-size:0.68rem;color:#9ca3af;"></i>
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

        <!-- Summary Header -->
        <div class="summary-header">
            <div class="summary-title">
                <h3 id="summaryTitle">LPP Summary (February 2026)</h3>
                <p id="summarySubtitle">35 sites &nbsp;|&nbsp; 9,555 total employees</p>
            </div>
            <div class="summary-meta">
                <div class="month-chip"><span></span> <span id="summaryMonthChip">February 2026</span></div>
                <button class="btn-excel" onclick="alert('Excel export')"><i class="fa-solid fa-file-excel"></i> Excel</button>
                <button class="btn-pdf" onclick="alert('PDF export')"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-card-toolbar">
                <div class="table-card-title">
                    <i class="fa-solid fa-table-list"></i>
                    Details Monthly LPP — Designation-wise Breakdown
                </div>
                <input type="text" class="search-bar" id="searchBar" placeholder="Search sites..." oninput="filterSites()">
            </div>
            <div class="lpp-table-wrap">
                <table class="lpp-table" id="lppTable">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding-left:1rem;">Site Name</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Extra Duty</th>
                            <th>Total Working Days</th>
                            <th>Total Employees</th>
                            <th>Net Pay</th>
                            <th>GST (18%)</th>
                            <th style="background:linear-gradient(135deg,#059669,#047857)!important;">Gross Total</th>
                        </tr>
                    </thead>
                    <tbody id="lppBody"></tbody>
                    <tfoot>
                        <tr class="grand-row" id="grandRow">
                            <td><i class="fa-solid fa-sigma" style="margin-right:0.4rem;"></i>GRAND TOTAL</td>
                            <td id="gt-present"></td>
                            <td id="gt-absent"></td>
                            <td id="gt-leave"></td>
                            <td id="gt-extra"></td>
                            <td id="gt-twd"></td>
                            <td id="gt-emp"></td>
                            <td id="gt-net"></td>
                            <td id="gt-gst"></td>
                            <td id="gt-gross"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
/* ── DATA ── */
const siteData = [
    { id:1,  name:'MAHANADI COAL FIELD(001)',                    emp:277,  present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:2,  name:'MAHANADI COAL FIELD BHUBANESWAR(002)',        emp:53,   present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:3,  name:'MAHANADI COAL FIELD SAMBALPUR(003)',          emp:148,  present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:4,  name:'MAHANADI COAL FIELDS LIMITED(004)',           emp:109,  present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:5,  name:'MAHANADI COALFIELD LIMITED (NSCH COLLEGE)(007)', emp:31,present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:6,  name:'MAHANADI COALFIELD LIMITED (NSCH HOSPITAL)(008)', emp:44,present:0,absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:7,  name:'MAHANADI COALFIELD LIMITED (HINGULA GM)(010)', emp:62, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:8,  name:'MAHANADI COALFIELD LIMITED (BALANDA OCP)(011)',emp:88, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:9,  name:'MCL HQ BURLA(016)',                           emp:312, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:10, name:'MCL REGIONAL OFFICE SAMBALPUR(017)',          emp:120, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:11, name:'MCL TALCHER COALFIELD(018)',                  emp:198, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:12, name:'MCL IB VALLEY COALFIELD(019)',                emp:155, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:13, name:'MAHANADI COALFIELD ANANTA OCP(020)',          emp:76,  present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:14, name:'MAHANADI COALFIELD BHARATPUR OCP(021)',       emp:93,  present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
    { id:15, name:'MCL HOSPITAL BURLA(026)',                     emp:210, present:0, absent:0, leave:0, extra:0, twd:0,  netpay:0,       gst:0,      gross:0 },
];

/* Designations per site */
const designations = [
    { title:'GUN MAN',           empPct:0.05, basic:893,  cmpf:107.16, cmps:62.51, bonus:null, esi:null,  pfAdmin:6.43,  otherAllow:1069.10, sc:133.64, perDay:1202.74 },
    { title:'SECURITY GUARD',    empPct:0.88, basic:760,  cmpf:91.20,  cmps:53.20, bonus:63.31,esi:30.40, pfAdmin:5.47,  otherAllow:1003.58, sc:125.45, perDay:1129.03 },
    { title:'SECURITY SUPERVISOR',empPct:0.07,basic:893,  cmpf:107.16, cmps:62.51, bonus:null, esi:null,  pfAdmin:6.43,  otherAllow:1069.10, sc:133.64, perDay:1202.74 },
];

/* ── HELPERS ── */
const INR = v => v === 0 ? '₹0.00' : '₹' + v.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
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
    if(w&&!w.contains(e.target)){ document.getElementById('monthPopup').classList.remove('open'); document.getElementById('monthDisp').classList.remove('open'); }
});

/* ── BUILD TABLE ── */
function applyFilter() { buildTable(); }

function buildTable() {
    const body = document.getElementById('lppBody');
    body.innerHTML = '';
    let gTotals = { present:0,absent:0,leave:0,extra:0,twd:0,emp:0,net:0,gst:0,gross:0 };

    const siteFilter = document.getElementById('siteFilter').value;
    const filtered = siteFilter === 'all' ? siteData : siteData.filter(s => s.name === siteFilter);

    filtered.forEach(site => {
        // Compute designation rows
        const desigRows = designations.map(d => {
            const empCount = Math.max(1, Math.round(site.emp * d.empPct));
            const netPay = d.perDay * 26 * empCount;
            const gst    = netPay * 0.18;
            const gross  = netPay + gst;
            return { ...d, empCount, netPay, gst, gross };
        });
        const siteNet   = desigRows.reduce((s,r)=>s+r.netPay,0);
        const siteGst   = desigRows.reduce((s,r)=>s+r.gst,0);
        const siteGross = desigRows.reduce((s,r)=>s+r.gross,0);

        gTotals.emp   += site.emp;
        gTotals.net   += siteNet;
        gTotals.gst   += siteGst;
        gTotals.gross += siteGross;

        const sectionId = 'desig-' + site.id;

        /* ── Site Row ── */
        const tr = document.createElement('tr');
        tr.className = 'site-row';
        tr.dataset.siteName = site.name.toLowerCase();
        tr.onclick = () => toggleDesig(site.id);
        tr.innerHTML = `
            <td>
                <div class="site-name-wrap">
                    <button class="expand-btn" id="expbtn-${site.id}"><i class="fa-solid fa-chevron-right" style="font-size:0.6rem;"></i></button>
                    <div class="site-icon">M</div>
                    <div class="site-info">
                        <span class="site-name">${site.name}</span>
                        <span class="site-emp-count">${site.emp} employees</span>
                    </div>
                </div>
            </td>
            <td><span class="pill pill-green">${site.present}</span></td>
            <td><span class="pill pill-red">${site.absent}</span></td>
            <td><span class="pill pill-orange">${site.leave}</span></td>
            <td><span class="pill pill-blue">${site.extra}</span></td>
            <td><span class="pill pill-gray">${site.twd}</span></td>
            <td><strong>${site.emp}</strong></td>
            <td>${INR(siteNet)}</td>
            <td>${INR(siteGst)}</td>
            <td>${INR(siteGross)}</td>
        `;
        body.appendChild(tr);

        /* ── Breakdown header row ── */
        const hdrTr = document.createElement('tr');
        hdrTr.className = 'desig-section';
        hdrTr.id = sectionId + '-hdr';
        hdrTr.innerHTML = `
            <td colspan="10" class="desig-header-row">
                <div class="desig-header-inner">
                    <i class="fa-solid fa-layer-group"></i>
                    Designation-wise Breakdown — ${site.name}
                </div>
            </td>
        `;
        body.appendChild(hdrTr);

        /* ── Designation sub-table row ── */
        const subTr = document.createElement('tr');
        subTr.className = 'desig-section';
        subTr.id = sectionId + '-body';
        const subRows = desigRows.map((d,i) => `
            <tr>
                <td>${d.title}</td>
                <td>${d.empCount}</td>
                <td><span class="pill pill-green">0</span></td>
                <td><span class="pill pill-red">0</span></td>
                <td><span class="pill pill-orange">0</span></td>
                <td>${INR(d.basic)}</td>
                <td>${INR(d.cmpf)}</td>
                <td>${INR(d.cmps)}</td>
                <td>${d.bonus ? INR(d.bonus) : '—'}</td>
                <td>${d.esi ? INR(d.esi) : '—'}</td>
                <td>${INR(d.pfAdmin)}</td>
                <td>${INR(d.otherAllow)}</td>
                <td>${INR(d.sc)}</td>
                <td>${INR(d.perDay)}</td>
                <td>${INR(d.netPay)}</td>
                <td>${INR(d.gst)}</td>
                <td>${INR(d.gross)}</td>
            </tr>
        `).join('');
        subTr.innerHTML = `
            <td colspan="10" style="padding:0;background:#f8fafc;">
                <div class="desig-sub-wrap">
                    <table class="desig-sub-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding-left:2rem;">Designation</th>
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
                                <th>Other Allow</th>
                                <th>Svc Chg 12.5%</th>
                                <th>Per Day</th>
                                <th>Net Pay</th>
                                <th>GST 18%</th>
                                <th style="background:linear-gradient(135deg,#059669,#047857)!important;">Gross Total</th>
                            </tr>
                        </thead>
                        <tbody>${subRows}</tbody>
                    </table>
                </div>
            </td>
        `;
        body.appendChild(subTr);
    });

    /* Grand totals */
    document.getElementById('gt-present').textContent = '0';
    document.getElementById('gt-absent').textContent  = '0';
    document.getElementById('gt-leave').textContent   = '0';
    document.getElementById('gt-extra').textContent   = '0';
    document.getElementById('gt-twd').textContent     = '0';
    document.getElementById('gt-emp').textContent     = gTotals.emp.toLocaleString('en-IN');
    document.getElementById('gt-net').textContent     = INR(gTotals.net);
    document.getElementById('gt-gst').textContent     = INR(gTotals.gst);
    document.getElementById('gt-gross').textContent   = INR(gTotals.gross);

    /* Update summary */
    const mLabel = fullMonths[pickerMonth-1] + ' ' + pickerYear;
    document.getElementById('summaryTitle').textContent    = `LPP Summary (${mLabel})`;
    document.getElementById('summaryMonthChip').textContent = mLabel;
    document.getElementById('summarySubtitle').textContent  = `${filtered.length} sites\u00a0|\u00a0${gTotals.emp.toLocaleString('en-IN')} total employees`;
}

/* ── TOGGLE DESIGNATION ── */
function toggleDesig(siteId) {
    const hdr  = document.getElementById('desig-' + siteId + '-hdr');
    const body = document.getElementById('desig-' + siteId + '-body');
    const btn  = document.getElementById('expbtn-' + siteId);
    const siteRow = btn.closest('tr');
    const isOpen = hdr.classList.contains('open');

    hdr.classList.toggle('open', !isOpen);
    body.classList.toggle('open', !isOpen);
    btn.classList.toggle('open', !isOpen);
    siteRow.classList.toggle('expanded', !isOpen);

    const icon = btn.querySelector('i');
    icon.style.transform = isOpen ? '' : 'rotate(90deg)';
    icon.style.transition = 'transform 0.2s';
}

/* ── SEARCH ── */
function filterSites() {
    const q = document.getElementById('searchBar').value.toLowerCase();
    document.querySelectorAll('.site-row').forEach(row => {
        const match = row.dataset.siteName.includes(q);
        row.style.display = match ? '' : 'none';
        const id = row.querySelector('[id^="expbtn-"]').id.replace('expbtn-','');
        const hdr  = document.getElementById('desig-'+id+'-hdr');
        const body = document.getElementById('desig-'+id+'-body');
        if (hdr)  hdr.style.display  = match ? '' : 'none';
        if (body) body.style.display = match ? '' : 'none';
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