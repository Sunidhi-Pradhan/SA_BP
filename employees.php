<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Master - CSV Import</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        /* ===== RESET ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

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
        body.dark .sidebar .menu:hover { background: rgba(255,255,255,0.06); }
        body.dark .sidebar .menu.active { background: rgba(255,255,255,0.10); }

        /* ===== DARK MODE — STAT CARDS ===== */
        body.dark .stat-card {
            box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12);
            border-color: rgba(15,118,110,0.25);
        }
        body.dark .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(15,118,110,0.4), 0 2px 10px rgba(16,185,129,0.2);
            border-color: rgba(16,185,129,0.4);
        }

        /* ===== DARK MODE — CHART & SUMMARY BOXES ===== */
        body.dark .chart-box, body.dark .summary-box {
            box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12);
            border-color: rgba(15,118,110,0.25);
        }
        body.dark .chart-box:hover, body.dark .summary-box:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.4), 0 2px 10px rgba(16,185,129,0.2);
            border-color: rgba(16,185,129,0.4);
        }

        /* ===== DARK MODE — CARD ICONS ===== */
        body.dark .stat-card:nth-child(1) .card-icon { background: #2e1065; color: #a78bfa; }
        body.dark .stat-card:nth-child(2) .card-icon { background: #064e3b; color: #34d399; }
        body.dark .stat-card:nth-child(3) .card-icon { background: #450a0a; color: #f87171; }
        body.dark .stat-card:nth-child(4) .card-icon { background: #451a03; color: #fbbf24; }
        body.dark .stat-card:nth-child(5) .card-icon { background: #1e3a5f; color: #60a5fa; }
        body.dark .stat-card:nth-child(6) .card-icon { background: #422006; color: #fcd34d; }
        body.dark .stat-card:nth-child(7) .card-icon { background: #064e3b; color: #34d399; }
        body.dark .stat-card:nth-child(8) .card-icon { background: #1e3a5f; color: #60a5fa; }

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
        .logo { padding: 20px 15px; margin-bottom: 10px; }
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
        .menu:hover { background: rgba(255,255,255,0.1); color: #ffffff; }
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

        /* ===== HEADER ===== */
        header {
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
        header h1 { font-size: 1.5rem; font-weight: 700; color: var(--text); flex: 1; text-align: center; }

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
        .theme-btn:hover { background: #f3f4f6; color: var(--text); transform: scale(1.08); }
        .theme-btn.active { background: #1e293b; color: #a5b4fc; border-color: #334155; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) { .graph-section { grid-template-columns: 1fr 260px; } }
        @media (max-width: 900px)  { .stats { grid-template-columns: repeat(2, 1fr); } .graph-section { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .menu-btn { display: flex; }
            .sidebar { position: fixed; top: 0; left: 0; height: 100vh; transform: translateX(-100%); animation: none; }
            .sidebar.open { transform: translateX(0); }
            header { padding: 0 16px; }
            header h1 { font-size: 1.1rem; }
        }
        @media (max-width: 480px) {
            header h1 { font-size: 0.95rem; }
        }

        /* ══════════════════════════════════════
           EMPLOYEE PAGE — SPECIFIC STYLES
        ══════════════════════════════════════ */

        /* ===== KEYFRAMES ===== */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes rowSlideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes cloudFloat {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }

        /* ===== PAGE CONTENT ===== */
        .emp-content {
            padding: 24px 25px 32px;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            animation: fadeUp 0.45s 0.15s ease both;
        }

        /* ===== CARD ===== */
        .card {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            overflow: hidden;
            animation: fadeUp 0.4s 0.2s ease both;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .card:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.22), 0 2px 8px rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.35);
        }
        body.dark .card {
            box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12);
            border-color: rgba(15,118,110,0.25);
        }
        body.dark .card:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.4), 0 2px 10px rgba(16,185,129,0.2);
            border-color: rgba(16,185,129,0.4);
        }

        /* ===== CARD HEADER ===== */
        .card-header {
            background: #0f766e;
            color: white;
            padding: 18px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            animation: fadeIn 0.4s 0.3s ease both;
        }
        .header-left {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-demo {
            background: white;
            color: #0f766e;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
        .btn-demo:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }

        /* ===== CARD BODY ===== */
        .card-body { padding: 28px; }

        /* ===== ALERTS ===== */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            animation: slideDown 0.35s ease both;
        }
        .alert.success { background: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .alert.error   { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }

        /* ===== INSTRUCTIONS ===== */
        .instructions {
            background: #ccfbf1;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #14b8a6;
            animation: fadeUp 0.4s 0.35s ease both;
            opacity: 0;
        }
        .instructions h4 {
            color: #0f766e;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .instructions ul { margin-left: 18px; margin-bottom: 8px; color: #115e59; }
        .instructions li { margin-bottom: 4px; font-size: 12px; line-height: 1.5; }
        .expected {
            background: white;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
            color: #334155;
            border: 2px solid #5eead4;
            line-height: 1.6;
        }
        .expected strong { color: #0f766e; font-size: 13px; }

        /* ===== DROP BOX ===== */
        .upload-area { width: 100%; animation: fadeUp 0.4s 0.45s ease both; opacity: 0; }
        .drop-box {
            border: 3px dashed #99f6e4;
            border-radius: 14px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 180px;
        }
        .drop-box:hover {
            border-color: #14b8a6;
            background: linear-gradient(135deg, #ccfbf1, #99f6e4);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(20,184,166,0.15);
        }
        .dragover {
            border-color: #0f766e !important;
            background: linear-gradient(135deg, #99f6e4, #5eead4) !important;
            transform: scale(1.02);
        }
        .cloud {
            font-size: 50px;
            margin-bottom: 12px;
            display: inline-block;
            animation: cloudFloat 3s ease-in-out infinite;
        }
        #dropText { font-size: 17px; font-weight: 700; color: #0f766e; margin-bottom: 6px; }
        #fileName { font-size: 13px; color: #14b8a6; font-weight: 500; }

        /* ===== UPLOAD BUTTON ===== */
        .btn-upload {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            margin-top: 18px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: fadeUp 0.4s 0.55s ease both;
            opacity: 0;
        }
        .btn-upload:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-upload.active:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,118,110,0.35); }

        /* ===== PREVIEW TABLE ===== */
        #previewSection {
            margin-top: 30px;
            display: none;
            background: #f0fdfa;
            padding: 24px;
            border-radius: 12px;
            border: 2px solid #99f6e4;
            animation: fadeUp 0.4s ease both;
        }
        #previewSection h4 { color: #0f766e; margin-bottom: 16px; font-size: 17px; font-weight: 700; }
        #previewTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        #previewTable th, #previewTable td { border: 1px solid #99f6e4; padding: 12px 14px; text-align: left; }
        #previewTable th {
            background: linear-gradient(135deg, #99f6e4, #5eead4);
            font-weight: 700;
            color: #0f766e;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        #previewTable tr:hover { background: #f0fdfa; }
        #previewTable td { color: #334155; }

        /* staggered preview rows */
        #previewTable tr { opacity: 0; animation: rowSlideIn 0.3s ease forwards; }
        #previewTable tr:nth-child(1)  { animation-delay: 0.05s; }
        #previewTable tr:nth-child(2)  { animation-delay: 0.10s; }
        #previewTable tr:nth-child(3)  { animation-delay: 0.15s; }
        #previewTable tr:nth-child(4)  { animation-delay: 0.20s; }
        #previewTable tr:nth-child(5)  { animation-delay: 0.25s; }
        #previewTable tr:nth-child(6)  { animation-delay: 0.30s; }
        #previewTable tr:nth-child(7)  { animation-delay: 0.35s; }
        #previewTable tr:nth-child(8)  { animation-delay: 0.40s; }
        #previewTable tr:nth-child(9)  { animation-delay: 0.45s; }
        #previewTable tr:nth-child(10) { animation-delay: 0.50s; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .emp-content { padding: 16px; }
            .card-body   { padding: 18px; }
            .drop-box    { padding: 36px 20px; min-height: 160px; }
            .cloud       { font-size: 40px; }
            #dropText    { font-size: 15px; }
        }
        @media (max-width: 480px) {
            .card-header { padding: 14px 16px; }
            .header-left { font-size: 15px; }
            .btn-demo    { padding: 8px 14px; font-size: 12px; }
        }
    </style>
<link rel="stylesheet" href="assets/responsive.css">
</head>

<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>
        <nav>
            <a href="dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="user.php" class="menu">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <span>Add Users</span>
            </a>
            <a href="employees.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-user-plus"></i></span>
                <span>Add Employee</span>
            </a>
            <a href="admin/basic_pay_update.php" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>
            <a href="admin/add_extra_manpower.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>
            <a href="unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                <span>Unlock Attendance</span>
            </a>
            <a href="admin/attendance_request.php" class="menu">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                <span>Attendance Request</span>
            </a>
            <a href="download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-download"></i></span>
                <span>Download Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Wage Report</span>
            </a>
            <a href="admin/monthly_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                <span>Monthly Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                <span>Download Salary</span>
            </a>
            <a href="logout.php" class="menu logout">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- ========== MAIN ========== -->
    <main class="main">

        <!-- TOPBAR -->
        <header>
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1>Security Billing Portal</h1>
            <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <!-- PAGE CONTENT -->
        <div class="emp-content">
            <div class="card">

                <!-- CARD HEADER -->
                <div class="card-header">
                    <div class="header-left">
                        <i class="fa-solid fa-file-csv"></i>
                        Employee Master – CSV Import
                    </div>
                    <a href="demo_employee.csv" class="btn-demo">
                        <i class="fa-solid fa-download"></i> Download Demo CSV
                    </a>
                </div>

                <div class="card-body">

                    <!-- ALERT -->
                    <div id="alertBox"></div>

                    <!-- INSTRUCTIONS -->
                    <div class="instructions">
                        <h4>Import Instructions</h4>
                        <ul>
                            <li>📄 Upload only CSV or Excel file formats (.csv, .xls, .xlsx)</li>
                            <li>📋 The first row must contain column headers</li>
                            <li>🔁 Duplicate ESIC_NO entries will be automatically skipped</li>
                        </ul>
                        <p class="expected">
                            <strong>Expected Columns:</strong><br>
                            ESIC_NO, Site_Name, RegNo, Employee_Name, Rank, Gender,
                            DOB, DOJ, AADHAAR_NO, Father_Name, MOB_NO,
                            AC_NO, IFSC_CODE, Bank_Name, Address
                        </p>
                    </div>

                    <!-- UPLOAD AREA -->
                    <div class="upload-area">
                        <form action="import_employees.php" method="POST" enctype="multipart/form-data">

                            <input type="file"
                                   name="employee_file"
                                   id="fileInput"
                                   accept=".csv,.xls,.xlsx"
                                   hidden required>

                            <div class="drop-box" id="dropBox">
                                <div class="cloud">☁️</div>
                                <p id="dropText">Drag & Drop Your File Here</p>
                                <span id="fileName">or click to browse your computer</span>
                            </div>

                            <button type="submit" class="btn-upload" id="uploadBtn" disabled>
                                ⬆️ Upload & Import Employees
                            </button>
                        </form>

                        <!-- PREVIEW -->
                        <div id="previewSection">
                            <h4>📋 File Preview (First 10 Rows)</h4>
                            <div style="overflow-x:auto; max-height:400px;">
                                <table id="previewTable"></table>
                            </div>
                        </div>
                    </div>

                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div><!-- /.emp-content -->

    </main>
</div>

<script>
/* ================================================
   SIDEBAR TOGGLE
================================================ */
const menuBtn = document.getElementById('menuBtn');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}
menuBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar .menu').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); }));
window.addEventListener('resize', () => { if (window.innerWidth > 768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; } });

/* ================================================
   THEME TOGGLE (moon / sun)
================================================ */
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = themeToggle.querySelector('i');

function applyTheme(dark) {
    if (dark) {
        document.body.classList.add('dark');
        themeToggle.classList.add('active');
        themeIcon.className = 'fa-solid fa-sun';
    } else {
        document.body.classList.remove('dark');
        themeToggle.classList.remove('active');
        themeIcon.className = 'fa-solid fa-moon';
    }
}
applyTheme(localStorage.getItem('theme') === 'dark');
themeToggle.addEventListener('click', () => {
    const isDark = document.body.classList.contains('dark');
    applyTheme(!isDark);
    localStorage.setItem('theme', !isDark ? 'dark' : 'light');
});

/* ================================================
   FILE UPLOAD & PREVIEW
================================================ */
const dropBox        = document.getElementById("dropBox");
const fileInput      = document.getElementById("fileInput");
const fileName       = document.getElementById("fileName");
const dropText       = document.getElementById("dropText");
const uploadBtn      = document.getElementById("uploadBtn");
const previewSection = document.getElementById("previewSection");
const previewTable   = document.getElementById("previewTable");

dropBox.addEventListener("click", () => fileInput.click());
fileInput.addEventListener("change", () => { if (fileInput.files.length) loadFile(fileInput.files[0]); });

dropBox.addEventListener("dragover", e => { e.preventDefault(); dropBox.classList.add("dragover"); });
dropBox.addEventListener("dragleave", () => dropBox.classList.remove("dragover"));
dropBox.addEventListener("drop", e => {
    e.preventDefault();
    dropBox.classList.remove("dragover");
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        loadFile(e.dataTransfer.files[0]);
    }
});

function loadFile(file) {
    fileName.innerText = "✅ Selected: " + file.name;
    dropText.innerText = "File Loaded Successfully!";
    uploadBtn.disabled = false;
    uploadBtn.classList.add("active");
    previewTable.innerHTML = "";
    previewSection.style.display = "none";

    const reader = new FileReader();
    reader.onload = e => {
        const data  = new Uint8Array(e.target.result);
        const wb    = XLSX.read(data, { type: "array" });
        const sheet = wb.Sheets[wb.SheetNames[0]];
        const rows  = XLSX.utils.sheet_to_json(sheet, { header: 1 });
        renderPreview(rows);
    };
    reader.readAsArrayBuffer(file);
}

function renderPreview(rows) {
    if (!rows.length) return;
    previewSection.style.display = "block";
    previewTable.innerHTML = "";
    rows.slice(0, 10).forEach((row, i) => {
        const tr = document.createElement("tr");
        row.forEach(cell => {
            const el = document.createElement(i === 0 ? "th" : "td");
            el.textContent = cell ?? "";
            tr.appendChild(el);
        });
        previewTable.appendChild(tr);
    });
}

/* ================================================
   ALERT HANDLING
================================================ */
const params   = new URLSearchParams(window.location.search);
const alertBox = document.getElementById("alertBox");

if (params.has("inserted") || params.has("skipped")) {
    alertBox.innerHTML = `
        <div class="alert success">
            ✅ Import Completed!&nbsp;
            <strong>Inserted:</strong> ${params.get("inserted") || 0} &nbsp;|&nbsp;
            <strong>Skipped:</strong> ${params.get("skipped") || 0} duplicates
        </div>`;
}
if (params.has("error")) {
    alertBox.innerHTML = `<div class="alert error">❌ Error: ${params.get("error")}</div>`;
}
</script>

</body>
</html>