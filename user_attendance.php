<?php
session_start();
require "config.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// login check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// role check
$stmt = $pdo->prepare("SELECT role FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if ($u['role'] !== 'user') {
    die("Access denied");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/useratt.css">
    <style>

    /* ============================================================
       KEYFRAME ANIMATIONS
    ============================================================ */
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-44px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes logoFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes navReveal {
        from { opacity: 0; transform: translateX(-18px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes fadeDown {
        from { opacity: 0; transform: translateY(-14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    /* ============================================================
       LAYOUT
    ============================================================ */
    .container {
        display: flex !important;
        min-height: 100vh;
    }

    /* ============================================================
       SIDEBAR  — identical to user_dashboard
    ============================================================ */
    .sidebar {
        width: 240px !important;
        background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%) !important;
        color: #fff !important;
        padding: 25px 20px !important;
        min-height: 100vh;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 22px rgba(13,95,88,0.32);
        transition: transform 0.3s ease;
        z-index: 100;
    }

    .sidebar-close {
        display: none;
        position: absolute;
        top: 14px; right: 14px;
        background: rgba(255,255,255,0.14);
        border: none; color: #fff;
        width: 30px; height: 30px;
        border-radius: 7px; cursor: pointer; font-size: 15px;
        align-items: center; justify-content: center;
        transition: background 0.2s, transform 0.25s;
        z-index: 2;
    }
    .sidebar-close:hover {
        background: rgba(255,255,255,0.28);
        transform: rotate(90deg);
    }

    /* Logo */
    .sidebar .logo {
        text-align: center !important;
        margin-bottom: 30px !important;
        font-size: unset !important;
        font-weight: unset !important;
        padding-bottom: 22px;
        border-bottom: 1px solid rgba(255,255,255,0.15);
    }
    .sidebar .logo img {
        max-width: 140px;
        height: auto;
        display: block;
        margin: 0 auto;
        background: #fff;
        padding: 10px 14px;
        border-radius: 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .sidebar .logo img:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }

    /* Nav list */
    .sidebar ul {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
        flex: 1;
    }

    /* Nav item */
    .sidebar ul li {
        padding: 0 !important;
        margin-bottom: 10px !important;
        border-radius: 10px !important;
        background: transparent;
        transition: background 0.22s ease, box-shadow 0.22s ease, transform 0.2s ease;
    }
    .sidebar ul li:nth-child(1) { }
    .sidebar ul li:nth-child(2) { }
    .sidebar ul li:nth-child(3) { }
    .sidebar ul li:nth-child(4) { }

    .sidebar ul li:hover {
        background: rgba(255,255,255,0.15) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.13);
        transform: translateX(4px);
    }
    .sidebar ul li.active {
        background: rgba(255,255,255,0.22) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        transform: translateX(4px);
    }

    /* Anchor inside LI */
    .sidebar ul li a {
        display: flex !important;
        align-items: center !important;
        gap: 13px !important;
        padding: 13px 16px !important;
        text-decoration: none !important;
        color: rgba(255,255,255,0.85) !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        background: transparent !important;
        border-radius: 10px !important;
        transition: color 0.2s ease !important;
        white-space: nowrap;
    }
    .sidebar ul li:hover a,
    .sidebar ul li.active a {
        color: #fff !important;
        font-weight: 600 !important;
    }

    /* Icon */
    .sidebar ul li a i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.80;
        transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s ease;
    }
    .sidebar ul li:hover  a i { transform: scale(1.28) rotate(-6deg); opacity: 1; }
    .sidebar ul li.active a i { transform: scale(1.18); opacity: 1; }



    /* ============================================================
       MOBILE OVERLAY
    ============================================================ */
    .sidebar-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 99;
        backdrop-filter: blur(2px);
    }
    .sidebar-overlay.active { display: block; }

    /* ============================================================
       TOPBAR
    ============================================================ */
    .topbar {
        position: relative;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        animation: fadeDown 0.4s 0.3s ease both;
    }
    .topbar h3 {
        position: absolute;
        left: 50%; transform: translateX(-50%);
        margin: 0; font-size: 1.1rem; font-weight: 700;
        color: #1f2937; white-space: nowrap;
    }
    .topbar-left { display: flex; align-items: center; gap: 10px; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }

    .hamburger-btn {
        display: none;
        background: #f3f4f6; border: 1.5px solid #e5e7eb;
        border-radius: 8px; width: 38px; height: 38px;
        align-items: center; justify-content: center;
        cursor: pointer; color: #0f766e; font-size: 1rem;
        transition: background 0.2s, transform 0.2s;
    }
    .hamburger-btn:hover { background: #e5e7eb; transform: scale(1.07); }

    .user-icon {
        width: 36px; height: 36px; border-radius: 50%;
        background: #0f766e;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .user-icon:hover { transform: scale(1.1); box-shadow: 0 2px 10px rgba(15,118,110,0.4); }
    .user-icon svg { width: 18px; height: 18px; stroke: white; }

    /* main content */
    .main { animation: fadeIn 0.4s 0.35s ease both; }

    /* ============================================================
       NOTIFICATION
    ============================================================ */
    .notification {
        display: none;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
        animation: slideDown 0.3s ease-out;
    }
    .notification.success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    .notification.error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    .notification h3 { margin: 0 0 10px 0; font-size: 1.2rem; }
    .notification .stats { font-size: 1rem; margin: 10px 0; }
    .notification .stats b { font-weight: 600; }
    .notification .error-log {
        margin-top: 15px; padding: 10px;
        background: rgba(0,0,0,0.05); border-radius: 4px;
        max-height: 200px; overflow-y: auto;
    }
    .notification .error-log pre { margin: 0; white-space: pre-wrap; font-size: 0.9rem; }
    .notification .close-btn {
        float: right; background: none; border: none;
        font-size: 1.5rem; cursor: pointer; color: inherit; opacity: 0.7;
    }
    .notification .close-btn:hover { opacity: 1; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Loading Spinner */
    .loading-spinner { display: none; text-align: center; padding: 20px; }
    .loading-spinner.active { display: block; }
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #0f766e;
        border-radius: 50%;
        width: 40px; height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        0%   { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .btn.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    /* ============================================================
       RESPONSIVE
    ============================================================ */
    @media (max-width: 768px) {
        .sidebar {
            position: fixed !important;
            left: 0; top: 0;
            transform: translateX(-100%);
            animation: none !important;
        }
        .sidebar.open { transform: translateX(0) !important; box-shadow: 8px 0 32px rgba(0,0,0,0.3) !important; }
        .sidebar-close { display: flex !important; }
        .hamburger-btn { display: flex !important; }
        .upload-box    { max-width: 100%; height: 200px; }
        .upload-buttons { flex-direction: column; }
    }

    </style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container">

    <!-- ===================================================
         SIDEBAR  (identical to user_dashboard.php)
    =================================================== -->
    <aside class="sidebar" id="sidebar">

        <button class="sidebar-close" id="sidebarClose">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Logo -->
        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>

        <!-- Nav -->
        <ul>
            <li>
                <a href="user_dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Dashboard
                </a>
            </li>
            <li class="active">
                <a href="user_attendance.php">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Upload Attendance
                </a>
            </li>
            <li>
                <a href="user_update_attendance.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    Update Attendance
                </a>
            </li>

        </ul>

    </aside>

    <!-- ===================================================
         MAIN
    =================================================== -->
    <main class="main">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" id="hamburgerBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <h3>Security Attendance and Billing Portal</h3>
            <div class="topbar-right">
                <div class="user-icon">
                    <a href="user_profile.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="8" r="4"/>
                        </svg>
                    </a>
                </div>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </header>

        <!-- Notification Area -->
        <div id="notification" class="notification"></div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="loading-spinner">
            <div class="spinner"></div>
            <p>Uploading attendance data...</p>
        </div>

        <!-- Upload Card -->
        <section class="card upload-card">
            <form method="POST" action="upload_attendance.php"
                  enctype="multipart/form-data" id="attendanceForm">

                <h3 class="upload-title">
                    Bulk Attendance Upload
                    <span>Excel / CSV File</span>
                </h3>

                <div class="upload-box">
                    <input type="file" id="fileUpload" name="attendance_file"
                           accept=".csv,.xls,.xlsx" required>
                    <label for="fileUpload" id="uploadLabel">
                        <strong>Drag & Drop</strong>
                        <span>or click to upload file</span>
                    </label>
                </div>

                <div class="upload-buttons">
                    <button type="button" class="btn green">
                        <a href="Attendance.xlsx" target="_blank" class="excel-link">Excel List</a>
                    </button>
                    <button type="button" class="btn yellow">
                        <a href="demoAttendance.xlsx" target="_blank" class="excel-link">Excel Demo List</a>
                    </button>
                </div>

            </form>
        </section>

        <!-- Preview Section -->
        <section class="card preview-card" id="previewCard" style="display:none;">
            <div id="previewContainer"></div>
            <div id="uploadButtonContainer" style="display:none; text-align:center; margin-top:30px;">
                <button type="button" id="submitBtn" class="btn submit-btn">
                    Upload Attendance
                </button>
            </div>
        </section>

        <footer>© 2026 MCL — All Rights Reserved</footer>

    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>

    /* Mobile sidebar */
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarClose');

    hamburger && hamburger.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('active');
    });
    closeBtn && closeBtn.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });

    /* File upload & preview */
    const fileInput             = document.getElementById("fileUpload");
    const uploadLabel           = document.getElementById("uploadLabel");
    const previewContainer      = document.getElementById("previewContainer");
    const uploadButtonContainer = document.getElementById("uploadButtonContainer");
    const previewCard           = document.getElementById("previewCard");
    const submitBtn             = document.getElementById("submitBtn");
    const notification          = document.getElementById("notification");
    const loadingSpinner        = document.getElementById("loadingSpinner");
    const attendanceForm        = document.getElementById("attendanceForm");

    function excelSerialToDate(serial) {
        const utcDays    = Math.floor(serial - 25569);
        const date       = new Date(utcDays * 86400 * 1000);
        const year       = date.getUTCFullYear();
        const month      = String(date.getUTCMonth() + 1).padStart(2, "0");
        const day        = String(date.getUTCDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    fileInput.addEventListener("change", function () {
        if (!this.files.length) return;
        const file = this.files[0];
        uploadLabel.innerHTML = `<strong>Selected File</strong><div class="upload-filename">${file.name}</div>`;
        previewContainer.innerHTML = "";
        uploadButtonContainer.style.display = "none";
        previewCard.style.display = "none";
        hideNotification();

        const reader = new FileReader();
        reader.onload = function (e) {
            const data     = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: "array" });
            const sheet    = workbook.Sheets[workbook.SheetNames[0]];
            const rows     = XLSX.utils.sheet_to_json(sheet, { header: 1 });
            renderPreview(rows);
            previewCard.style.display           = "block";
            uploadButtonContainer.style.display = "block";
        };
        reader.readAsArrayBuffer(file);
    });

    function renderPreview(rows) {
        if (!rows.length) return;
        let html = `<div class="preview-section">
            <h4 class="preview-title">Preview (First 10 Rows)</h4>
            <div class="preview-table-wrapper">
                <table class="preview-table"><thead>`;
        if (rows[0]) {
            html += "<tr>";
            rows[0].forEach(cell => { html += `<th>${cell ?? ""}</th>`; });
            html += "</tr>";
        }
        html += "</thead><tbody>";
        rows.slice(1, 10).forEach(row => {
            html += "<tr>";
            row.forEach((cell, colIndex) => {
                let value = cell ?? "";
                if (colIndex === 3 && typeof value === "number") value = excelSerialToDate(value);
                html += `<td>${value}</td>`;
            });
            html += "</tr>";
        });
        html += "</tbody></table></div></div>";
        previewContainer.innerHTML = html;
    }

    function showNotification(type, title, message) {
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <button class="close-btn" onclick="hideNotification()">&times;</button>
            <h3>${title}</h3>
            <div class="message">${message}</div>`;
        notification.style.display = "block";
        notification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideNotification() { notification.style.display = "none"; }

    submitBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (!fileInput.files.length) {
            showNotification('error', '❌ Error', 'Please select a file first.');
            return;
        }
        submitBtn.disabled     = true;
        submitBtn.textContent  = "Uploading...";
        loadingSpinner.classList.add('active');
        hideNotification();

        fetch('upload_attendance.php', { method: 'POST', body: new FormData(attendanceForm) })
            .then(r => r.text())
            .then(data => {
                loadingSpinner.classList.remove('active');
                submitBtn.disabled    = false;
                submitBtn.textContent = "Upload Attendance";
                parseAndDisplayResponse(data);
                attendanceForm.reset();
                uploadLabel.innerHTML = `<strong>Drag & Drop</strong><span>or click to upload file</span>`;
                previewCard.style.display = "none";
            })
            .catch(error => {
                loadingSpinner.classList.remove('active');
                submitBtn.disabled    = false;
                submitBtn.textContent = "Upload Attendance";
                showNotification('error', '❌ Upload Failed', `Error: ${error.message}`);
            });
    });

    function parseAndDisplayResponse(htmlResponse) {
        const tempDiv        = document.createElement('div');
        tempDiv.innerHTML    = htmlResponse;
        const h3             = tempDiv.querySelector('h3');
        const title          = h3 ? h3.textContent : '✓ Upload Completed';
        const processedMatch = htmlResponse.match(/Processed:\s*<b>(\d+)<\/b>/);
        const skippedMatch   = htmlResponse.match(/Skipped:\s*<b>(\d+)<\/b>/);
        const processed      = processedMatch ? processedMatch[1] : '0';
        const skipped        = skippedMatch   ? skippedMatch[1]   : '0';
        const preElement     = tempDiv.querySelector('pre');
        const errorLog       = preElement ? preElement.textContent : '';

        let message = `<div class="stats">Processed: <b>${processed}</b><br>Skipped: <b>${skipped}</b></div>`;
        if (errorLog) {
            message += `<div class="error-log"><strong>Error Details:</strong><pre>${errorLog}</pre></div>`;
        }
        showNotification(parseInt(processed) > 0 ? 'success' : 'error', title, message);
    }
</script>
</body>
</html>