<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Attendance</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        /* ===== RESET ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }

        /* ===== THEME VARIABLES ===== */
        :root {
            --bg: #f4f6f9;
            --card: #ffffff;
            --text: #111827;
            --subtext: #6b7280;
            --border: #e5e7eb;
        }
        body.dark {
            --bg: #0b1120;
            --card: #111827;
            --text: #e5e7eb;
            --subtext: #9ca3af;
            --border: #1f2937;
        }

        /* ===== DARK MODE — SIDEBAR ===== */
        body.dark .sidebar { background: #0d1526; box-shadow: 2px 0 12px rgba(0,0,0,0.5); }
        body.dark .sidebar .menu:hover  { background: rgba(255,255,255,0.06); }
        body.dark .sidebar .menu.active { background: rgba(255,255,255,0.10); }

        /* ===== DARK MODE — THEME BUTTON ===== */
        body.dark .theme-btn { background: #1e293b; color: #fbbf24; border-color: #334155; }
        body.dark .theme-btn:hover { background: #293548; }

        body {
            background: var(--bg);
            color: var(--text);
            transition: background 0.3s, color 0.3s;
            overflow-x: hidden;
        }

        /* ===== LAYOUT ===== */
        .dashboard { display: flex; min-height: 100vh; }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { display: block; }

        /* ===== SIDEBAR — no animations ===== */
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: #0f766e;
            color: #ffffff;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 8px rgba(0,0,0,0.12);
            flex-shrink: 0;
            z-index: 999;
            overflow-y: auto;
            position: relative;
            transition: transform 0.3s ease;
            /* sidebarSlideIn animation intentionally removed */
        }

        /* ===== LOGO — no pulse animation ===== */
        .logo {
            padding: 20px 15px;
            margin-bottom: 10px;
            font-size: inherit;
            font-weight: inherit;
        }
        .logo img {
            max-width: 160px;
            height: auto;
            display: block;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 10px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            /* logoPulse animation intentionally removed */
        }

        /* ===== NAV — no staggered menuFadeIn ===== */
        nav {
            display: flex;
            flex-direction: column;
            gap: 0;
            padding: 0 15px;
            flex: 1;
        }
        .menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 6px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: all 0.25s ease;
            position: relative;
            margin-bottom: 2px;
            letter-spacing: 0.1px;
            white-space: nowrap;
            /* menuFadeIn animation intentionally removed */
        }
        .menu .icon {
            font-size: 16px;
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.95;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .menu:hover .icon { transform: scale(1.2); }
        .menu:hover  { background: rgba(255,255,255,0.1); color: #ffffff; }
        .menu.active { background: rgba(255,255,255,0.15); color: #ffffff; font-weight: 500; }
        .menu.active::before {
            content: "";
            position: absolute;
            left: -15px; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 70%;
            background: #ffffff;
            border-radius: 0 4px 4px 0;
        }
        .menu.logout {
            margin-top: auto;
            margin-bottom: 15px;
            border-top: 1px solid rgba(255,255,255,0.15);
            padding-top: 15px;
        }

        /* ===== MAIN ===== */
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow-x: hidden; }

        /* ===== HEADER / TOPBAR ===== */
        .topbar {
            display: flex; align-items: center; gap: 14px;
            padding: 0 25px; height: 62px;
            background: var(--card);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            position: sticky; top: 0; z-index: 50;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            animation: headerDrop 0.4s ease both;
        }
        @keyframes headerDrop {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }
        .topbar h2 { font-size: 1.5rem; font-weight: 700; color: var(--text); flex: 1; text-align: center; margin: 0; }

        /* ===== HAMBURGER ===== */
        .menu-btn {
            background: none; border: none;
            font-size: 22px; cursor: pointer;
            color: var(--text); padding: 6px 8px;
            border-radius: 6px; display: none;
            align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s, transform 0.2s;
        }
        .menu-btn:hover { background: rgba(0,0,0,0.06); transform: rotate(90deg); }

        /* ===== THEME BUTTON ===== */
        .theme-btn {
            width: 44px; height: 44px; border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--card); color: var(--subtext);
            font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s, color 0.2s, border-color 0.2s, transform 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }
        .theme-btn:hover  { background: #f3f4f6; color: var(--text); transform: scale(1.08); }
        .theme-btn.active { background: #1e293b; color: #a5b4fc; border-color: #334155; }

        /* ===== RESPONSIVE BASE ===== */
        @media (max-width: 768px) {
            .menu-btn { display: flex; }
            .sidebar { position: fixed; top: 0; left: 0; height: 100vh; transform: translateX(-100%); animation: none; }
            .sidebar.open { transform: translateX(0); }
            .topbar { padding: 0 16px; }
            .topbar h2 { font-size: 1.1rem; }
        }
        @media (max-width: 480px) { .topbar h2 { font-size: 0.95rem; } }

        /* ══════════════════════════════════════
           DOWNLOAD ATTENDANCE PAGE — SPECIFIC
        ══════════════════════════════════════ */

        /* ===== KEYFRAMES ===== */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes rowSlideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes statPop {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            padding: 2rem; overflow-y: auto;
            animation: fadeIn 0.4s 0.15s ease both;
        }

        /* ===== FILTER SECTION ===== */
        .filter-section {
            background: white; padding: 24px 32px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            max-width: 1400px;
            animation: fadeUp 0.4s 0.2s ease both;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .filter-section:hover { box-shadow: 0 8px 28px rgba(15,118,110,0.2); border-color: rgba(16,185,129,0.3); }
        .filter-title { display: flex; align-items: center; margin-bottom: 24px; color: #1f2937; font-size: 17px; font-weight: 600; }
        .filter-icon  { margin-right: 10px; font-size: 20px; }
        .filter-row   { display: flex; gap: 16px; margin-bottom: 24px; justify-content: flex-start; }
        .filter-group { display: flex; flex-direction: column; max-width: 220px; }
        .filter-label { font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-input, .filter-select {
            padding: 11px 14px; border: 1.5px solid #d1d5db;
            border-radius: 6px; font-size: 14px; color: #374151;
            transition: all 0.2s; background: white; font-family: "Segoe UI", sans-serif;
        }
        .filter-input::placeholder { color: #9ca3af; }
        .filter-input:focus, .filter-select:focus { outline: none; border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }
        .filter-input[type="date"] { color: #374151; cursor: pointer; }

        .action-buttons { display: flex; gap: 12px; justify-content: flex-end; padding-top: 8px; margin-top: auto; }
        .filter-content-wrapper { display: flex; justify-content: space-between; align-items: flex-end; }

        .btn {
            padding: 11px 28px; border: none; border-radius: 6px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-family: "Segoe UI", sans-serif;
        }
        .btn-primary { background: #0f766e; color: white; }
        .btn-primary:hover { background: #0d5f57; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15,118,110,0.3); }
        .btn-secondary { background: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #d1d5db; border-color: #9ca3af; transform: translateY(-1px); }

        .status-badge { display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 16px; font-size: 12px; font-weight: 600; }
        .status-badge.success { background: #d1fae5; color: #065f46; }

        /* ===== PREVIEW SECTION ===== */
        .preview-section {
            background: white; padding: 30px; border-radius: 8px;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            margin-top: 30px; min-height: 300px; display: none;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .preview-section.active { display: block; animation: fadeUp 0.4s ease both; }
        .preview-section:hover { box-shadow: 0 8px 28px rgba(15,118,110,0.2); border-color: rgba(16,185,129,0.3); }
        .preview-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .preview-info { flex: 1; }
        .preview-title { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 12px; }
        .employee-details { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .employee-detail-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #555; }
        .employee-detail-row i { color: #0f766e; width: 16px; }
        .detail-label { font-weight: 600; color: #374151; }
        .detail-value { color: #6b7280; }
        .period-info  { font-size: 13px; color: #6b7280; margin-top: 8px; }
        .total-records { font-weight: 600; color: #0f766e; }
        .action-buttons-preview { display: flex; gap: 10px; }
        .preview-content-wrapper { display: flex; gap: 25px; }

        /* ===== TABLE ===== */
        .table-container { flex: 1; overflow-x: auto; background: white; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #2d3e50; color: white; }
        th { padding: 14px 12px; text-align: center; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.3px; }
        th:first-child { text-align: left; padding-left: 20px; }
        td { padding: 14px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; color: #374151; text-align: center; }
        td:first-child { text-align: left; padding-left: 20px; font-weight: 500; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f9fafb; }

        /* staggered table row animation */
        tbody tr { opacity: 0; animation: rowSlideIn 0.3s ease forwards; }
        tbody tr:nth-child(1)    { animation-delay: 0.05s; }
        tbody tr:nth-child(2)    { animation-delay: 0.10s; }
        tbody tr:nth-child(3)    { animation-delay: 0.15s; }
        tbody tr:nth-child(4)    { animation-delay: 0.20s; }
        tbody tr:nth-child(5)    { animation-delay: 0.25s; }
        tbody tr:nth-child(6)    { animation-delay: 0.30s; }
        tbody tr:nth-child(7)    { animation-delay: 0.35s; }
        tbody tr:nth-child(8)    { animation-delay: 0.40s; }
        tbody tr:nth-child(9)    { animation-delay: 0.45s; }
        tbody tr:nth-child(10)   { animation-delay: 0.50s; }
        tbody tr:nth-child(n+11) { animation-delay: 0.55s; }

        /* ===== STATUS BADGES ===== */
        .status-present          { background-color: #d1fae5; color: #059669; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; }
        .status-absent           { background-color: #fee2e2; color: #dc2626; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; }
        .status-overtime         { background-color: #dbeafe; color: #2563eb; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; }
        .status-present-overtime { background-color: #fef3c7; color: #d97706; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; }
        .status-leave            { background-color: #ede9fe; color: #7c3aed; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; }

        /* ===== PAGINATION ===== */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .pagination-text { font-size: 13px; color: #6b7280; }
        .pagination button { padding: 8px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .pagination button:hover:not(:disabled) { background: #f3f4f6; border-color: #9ca3af; transform: translateY(-1px); }
        .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination button.active { background: #0f766e; color: white; border-color: #0f766e; }

        /* ===== STATS CONTAINER ===== */
        .stats-container { width: 280px; display: flex; flex-direction: column; gap: 15px; }
        .stat-card {
            background: #f9fafb; border: 1px solid #e5e7eb;
            border-radius: 8px; padding: 20px; text-align: center;
            opacity: 0;
            animation: statPop 0.35s ease forwards;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:nth-child(1) { animation-delay: 0.30s; }
        .stat-card:nth-child(2) { animation-delay: 0.38s; }
        .stat-card:nth-child(3) { animation-delay: 0.46s; }
        .stat-card:nth-child(4) { animation-delay: 0.54s; }
        .stat-card:nth-child(5) { animation-delay: 0.62s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,118,110,0.15); }
        .stat-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; }
        .stat-subtext { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        .stat-default  { background: #f9fafb; border: 1px solid #e5e7eb; }
        .stat-present  { background: #ecfdf5; border: 1px solid #10b981; }
        .stat-present  .stat-label, .stat-present  .stat-value { color: #059669; }
        .stat-overtime { background: #fffbeb; border: 1px solid #f59e0b; }
        .stat-overtime .stat-label, .stat-overtime .stat-value { color: #d97706; }
        .stat-absent   { background: #fef2f2; border: 1px solid #dc2626; }
        .stat-absent   .stat-label, .stat-absent   .stat-value { color: #dc2626; }

        /* dark mode */
        body.dark .filter-section,
        body.dark .preview-section { background: var(--card); box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12); border-color: rgba(15,118,110,0.25); }
        body.dark .topbar { background: var(--card); }

        /* ===== PAGE RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content { padding: 1rem; }
            .filter-row { flex-direction: column; }
            .filter-group { max-width: 100%; }
            .filter-content-wrapper { flex-direction: column; gap: 12px; }
            .preview-content-wrapper { flex-direction: column; }
            .stats-container { width: 100%; }
        }
    </style>
</head>

<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <h2 class="logo">
            <img src="../assets/logo/images.png" alt="MCL Logo">
        </h2>
        <nav>
            <a href="../dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span><span>Dashboard</span>
            </a>
            <a href="../user.php" class="menu">
                <span class="icon"><i class="fa-solid fa-users"></i></span><span>Add Users</span>
            </a>
            <a href="../employees.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-plus"></i></span><span>Add Employee</span>
            </a>
            <a href="../admin/basic_pay_update.php" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span><span>Basic Pay Update</span>
            </a>
            <a href="../admin/add_extra_manpower.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span><span>Add Extra Manpower</span>
            </a>
            <a href="../unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span><span>Unlock Attendance</span>
            </a>
            <a href="../admin/attendance_request.php" class="menu">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span><span>Attendance Request</span>
            </a>
            <a href="../download_attendance/download_attendance.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-download"></i></span><span>Download Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span><span>Wage Report</span>
            </a>
            <a href="../admin/monthly_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-calendar-days"></i></span><span>Monthly Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-pdf"></i></span><span>Download Salary</span>
            </a>
            <a href="../logout.php" class="menu logout">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2>Security Billing Portal</h2>
            <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <div class="main-content">

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title">
                    <span class="filter-icon">📋</span>
                    Employee Attendance Report
                    <span class="status-badge success" style="margin-left: auto;">Period: Current period</span>
                </div>
                <div class="filter-content-wrapper">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label">EMPLOYEE ID</label>
                            <input type="text" class="filter-input" id="employeeId" placeholder="Enter Employee ID">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">FROM DATE</label>
                            <input type="date" class="filter-input" id="fromDate" value="2026-01-31">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">TO DATE</label>
                            <input type="date" class="filter-input" id="toDate" value="2026-02-12">
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-secondary" onclick="resetFilters()">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                        <button class="btn btn-primary" onclick="previewReport()">
                            <i class="fa-solid fa-eye"></i> Preview
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="preview-section" id="previewSection">
                <div class="preview-header">
                    <div class="preview-info">
                        <h3 class="preview-title">📋 Employee Attendance Report</h3>
                        <div class="employee-details">
                            <div class="employee-detail-row">
                                <i class="fa-solid fa-user"></i>
                                <span class="detail-label">Employee Name:</span>
                                <span class="detail-value" id="displayEmployeeName"></span>
                            </div>
                            <div class="employee-detail-row">
                                <i class="fa-solid fa-id-card"></i>
                                <span class="detail-label">Employee ID:</span>
                                <span class="detail-value" id="displayEmployeeId"></span>
                            </div>
                            <div class="employee-detail-row">
                                <i class="fa-solid fa-location-dot"></i>
                                <span class="detail-label">Site Location:</span>
                                <span class="detail-value" id="displaySiteLocation"></span>
                            </div>
                            <div class="employee-detail-row">
                                <i class="fa-solid fa-calendar"></i>
                                <span class="detail-label">Period:</span>
                                <span class="detail-value" id="displayPeriod"></span>
                            </div>
                        </div>
                        <p class="period-info">
                            <span class="total-records" id="totalRecordsText"></span>
                        </p>
                    </div>
                    <div class="action-buttons-preview">
                        <button class="btn" style="background:#10b981;color:white;" onclick="downloadExcel()">
                            <i class="fa-solid fa-file-excel"></i> Excel
                        </button>
                        <button class="btn" style="background:#dc2626;color:white;" onclick="downloadPDF()">
                            <i class="fa-solid fa-file-pdf"></i> PDF Report
                        </button>
                    </div>
                </div>

                <div class="preview-content-wrapper">
                    <div class="table-container">
                        <table id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>S.NO</th>
                                    <th>DATE</th>
                                    <th>DAY</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                        <div class="pagination">
                            <span class="pagination-text">Showing 1 to 6 of 6 entries</span>
                            <button disabled>Previous</button>
                            <button class="active">1</button>
                            <button disabled>Next</button>
                        </div>
                    </div>

                    <div class="stats-container">
                        <div class="stat-card stat-default">
                            <div class="stat-label"><i class="fa-solid fa-calendar-check"></i> TOTAL DAYS IN PERIOD</div>
                            <div class="stat-value" id="totalDaysValue"></div>
                            <div class="stat-subtext">Calendar days</div>
                        </div>
                        <div class="stat-card stat-default">
                            <div class="stat-label"><i class="fa-solid fa-database"></i> DAYS RECORDED</div>
                            <div class="stat-value" id="daysRecordedValue"></div>
                            <div class="stat-subtext">Attendance marked</div>
                        </div>
                        <div class="stat-card stat-present">
                            <div class="stat-label"><i class="fa-solid fa-check-circle"></i> PRESENT</div>
                            <div class="stat-value" id="presentValue"></div>
                            <div class="stat-subtext">Days present</div>
                        </div>
                        <div class="stat-card stat-overtime">
                            <div class="stat-label"><i class="fa-solid fa-clock"></i> OVERTIME DAYS</div>
                            <div class="stat-value" id="overtimeValue"></div>
                            <div class="stat-subtext">Extra hours</div>
                        </div>
                        <div class="stat-card stat-absent">
                            <div class="stat-label"><i class="fa-solid fa-times-circle"></i> ABSENT</div>
                            <div class="stat-value" id="absentValue"></div>
                            <div class="stat-subtext">Days absent</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    /* ── Sidebar toggle ── */
    const menuBtn = document.getElementById('menuBtn');
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');

    function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

    menuBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay.addEventListener('click', closeSidebar);
    document.querySelectorAll('.sidebar .menu').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); }));
    window.addEventListener('resize', () => { if (window.innerWidth > 768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; } });

    /* ── Theme toggle (moon/sun) ── */
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = themeToggle.querySelector('i');
    function applyTheme(dark) {
        if (dark) { document.body.classList.add('dark'); themeToggle.classList.add('active'); themeIcon.className = 'fa-solid fa-sun'; }
        else       { document.body.classList.remove('dark'); themeToggle.classList.remove('active'); themeIcon.className = 'fa-solid fa-moon'; }
    }
    applyTheme(localStorage.getItem('theme') === 'dark');
    themeToggle.addEventListener('click', () => { const d = document.body.classList.contains('dark'); applyTheme(!d); localStorage.setItem('theme', !d ? 'dark' : 'light'); });

    /* ── All original JS unchanged ── */
    let currentReportData = {};

    function resetFilters() {
        document.getElementById('employeeId').value = '';
        document.getElementById('fromDate').value = '';
        document.getElementById('toDate').value = '';
        document.getElementById('previewSection').classList.remove('active');
        document.getElementById('tableBody').innerHTML = '';
        currentReportData = {};
    }

    async function previewReport() {
        const employeeId = document.getElementById('employeeId').value;
        const fromDate   = document.getElementById('fromDate').value;
        const toDate     = document.getElementById('toDate').value;
        if (!employeeId || !fromDate || !toDate) { alert('Please fill all fields'); return; }
        try {
            const response = await fetch(`fetch_report.php?employee_id=${employeeId}&from_date=${fromDate}&to_date=${toDate}`);
            const result = await response.json();
            if (!result.success) { alert(result.error); return; }
            document.getElementById('previewSection').classList.add('active');
            document.getElementById('displayEmployeeName').textContent = result.employeeName;
            document.getElementById('displayEmployeeId').textContent   = result.employeeId;
            document.getElementById('displaySiteLocation').textContent = result.siteLocation;
            document.getElementById('displayPeriod').textContent       = result.period;
            currentReportData = result;
            document.getElementById('totalDaysValue').textContent    = result.totalDays;
            document.getElementById('daysRecordedValue').textContent = result.daysRecorded;
            document.getElementById('presentValue').textContent      = result.present;
            document.getElementById('overtimeValue').textContent     = result.overtime;
            document.getElementById('absentValue').textContent       = result.absent;
            document.getElementById('totalRecordsText').textContent  = `TOTAL RECORDS FOUND: ${result.daysRecorded} out of ${result.totalDays} days`;
            let tableHTML = '';
            result.attendanceData.forEach(row => {
                let statusClass = '';
                if      (row.status === 'Present')            statusClass = 'status-present';
                else if (row.status === 'Absent')             statusClass = 'status-absent';
                else if (row.status === 'Present + Overtime') statusClass = 'status-present-overtime';
                else if (row.status === 'Overtime')           statusClass = 'status-overtime';
                else if (row.status === 'Leave')              statusClass = 'status-leave';
                tableHTML += `<tr><td>${row.sno}</td><td>${row.date}</td><td>${row.day}</td><td><span class="${statusClass}">${row.status}</span></td></tr>`;
            });
            document.getElementById('tableBody').innerHTML = tableHTML;
        } catch (error) { alert("Error loading report"); console.error(error); }
    }

    function downloadPDF() {
        if (!currentReportData || !currentReportData.attendanceData.length) { alert("Please preview report first."); return; }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("p","mm","a4");
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        doc.setDrawColor(0,128,96); doc.setLineWidth(1); doc.rect(8,8,pageWidth-16,pageHeight-16);
        doc.setFillColor(16,121,96); doc.roundedRect(20,20,25,20,3,3,"F");
        doc.setTextColor(255,255,255); doc.setFontSize(14); doc.setFont("helvetica","bold"); doc.text("MCL",32.5,33,{align:"center"});
        doc.setTextColor(0); doc.setFontSize(14); doc.text("Mahanadi Coalfields Limited",55,27);
        doc.setFontSize(8); doc.setTextColor(100); doc.text("A Miniratna Subsidiary Company of Coal India Limited",55,32); doc.text("Jagruti Vihar, Burla, Sambalpur-768020, Odisha",55,36);
        doc.setDrawColor(0,128,96); doc.line(20,42,pageWidth-20,42);
        doc.setFontSize(12); doc.setTextColor(0); doc.setFont("helvetica","bold"); doc.text("EMPLOYEE ATTENDANCE SHEET",pageWidth/2,50,{align:"center"});
        doc.line(pageWidth/2-35,53,pageWidth/2+35,53);
        let y=60;
        doc.autoTable({ startY:y, body:[["Employee Name:",currentReportData.employeeName,"Employee ID:",currentReportData.employeeId],["Site Location:",currentReportData.siteLocation,"Period:",currentReportData.period]], theme:"grid", styles:{fontSize:8}, columnStyles:{0:{fontStyle:"bold",fillColor:[230,240,240]},2:{fontStyle:"bold",fillColor:[230,240,240]}}, margin:{left:20,right:20} });
        y=doc.lastAutoTable.finalY+8;
        doc.setFontSize(9); doc.setFont("helvetica","bold"); doc.text("ATTENDANCE SUMMARY",20,y); y+=4;
        doc.autoTable({ startY:y, head:[["Total Days","Present","Overtime","Absent","Leave"]], body:[[currentReportData.totalDays,currentReportData.present,currentReportData.overtime,currentReportData.absent,currentReportData.leave]], theme:"grid", headStyles:{textColor:255,halign:"center"}, didParseCell:function(data){ if(data.section==="head"){ if(data.column.index===0)data.cell.styles.fillColor=[55,65,81]; if(data.column.index===1)data.cell.styles.fillColor=[16,185,129]; if(data.column.index===2)data.cell.styles.fillColor=[59,130,246]; if(data.column.index===3)data.cell.styles.fillColor=[239,68,68]; if(data.column.index===4)data.cell.styles.fillColor=[245,158,11]; }}, styles:{halign:"center",fontSize:8}, margin:{left:20,right:20} });
        y=doc.lastAutoTable.finalY+8;
        const tableRows=currentReportData.attendanceData.map(row=>[row.sno,row.date,row.day,row.status]);
        doc.autoTable({ startY:y, head:[["S.NO","DATE","DAY","STATUS"]], body:tableRows, theme:"grid", headStyles:{fillColor:[45,62,80],textColor:255}, styles:{fontSize:8,halign:"center"}, didParseCell:function(data){ if(data.section==="body"&&data.column.index===3){ if(data.cell.raw==="Present"){data.cell.styles.fillColor=[209,250,229];data.cell.styles.textColor=[5,150,105];} else if(data.cell.raw==="Absent"){data.cell.styles.fillColor=[254,226,226];data.cell.styles.textColor=[220,38,38];} else if(data.cell.raw==="Overtime"){data.cell.styles.fillColor=[219,234,254];data.cell.styles.textColor=[37,99,235];} else if(data.cell.raw==="Present + Overtime"){data.cell.styles.fillColor=[254,243,199];data.cell.styles.textColor=[217,119,6];} else if(data.cell.raw==="Leave"){data.cell.styles.fillColor=[237,233,254];data.cell.styles.textColor=[124,58,237];} }}, margin:{left:20,right:20} });
        doc.save(`MCL_Attendance_${currentReportData.employeeId}.pdf`);
    }

    function downloadExcel() {
        const d = currentReportData;
        if (!d || !d.attendanceData) { alert("No data to export"); return; }
        const wb=XLSX.utils.book_new(); const ws={};
        const enc=XLSX.utils.encode_cell;
        function setCell(r,c,v){ws[enc({r,c})]={v:v};}
        function merge(r1,c1,r2,c2){if(!ws['!merges'])ws['!merges']=[];ws['!merges'].push({s:{r:r1,c:c1},e:{r:r2,c:c2}});}
        let row=0;
        setCell(row,0,"Mahanadi Coalfields Limited"); merge(row,0,row,4); row++;
        setCell(row,0,"A Miniratna Subsidiary Company of Coal India Limited"); merge(row,0,row,4); row++;
        setCell(row,0,"Jagruti Vihar, Burla, Sambalpur-768020, Odisha"); merge(row,0,row,4); row+=2;
        setCell(row,0,"EMPLOYEE ATTENDANCE SHEET"); merge(row,0,row,4); row+=2;
        setCell(row,0,"Employee Name:"); setCell(row,1,d.employeeName); setCell(row,3,"Employee ID:"); setCell(row,4,d.employeeId); row++;
        setCell(row,0,"Site Location:"); setCell(row,1,d.siteLocation); setCell(row,3,"Period:"); setCell(row,4,d.period); row+=2;
        setCell(row,0,"ATTENDANCE SUMMARY"); merge(row,0,row,4); row++;
        ["Total Days","Present","Overtime","Absent","Leave"].forEach((h,i)=>setCell(row,i,h)); row++;
        [d.totalDays,d.present,d.overtime,d.absent,d.leave].forEach((v,i)=>setCell(row,i,v)); row+=2;
        ["S.NO","DATE","DAY","STATUS"].forEach((h,i)=>setCell(row,i,h)); row++;
        d.attendanceData.forEach(rec=>{setCell(row,0,rec.sno);setCell(row,1,rec.date);setCell(row,2,rec.day);setCell(row,3,rec.status);row++;});
        ws['!ref']=XLSX.utils.encode_range({s:{r:0,c:0},e:{r:row,c:4}});
        ws['!cols']=[{wch:10},{wch:18},{wch:18},{wch:20},{wch:20}];
        XLSX.utils.book_append_sheet(wb,ws,"Attendance Report");
        XLSX.writeFile(wb,`MCL_Attendance_${d.employeeId}.xlsx`);
    }
</script>

</body>
</html>