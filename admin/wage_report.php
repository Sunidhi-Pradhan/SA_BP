<?php
session_start();
if (!isset($_SESSION["user"])) { header("Location: ../login.php"); exit; }
require "../config.php";

// ── AJAX: fetch employee count for selected month ──
if (isset($_GET['fetch']) && $_GET['fetch'] === '1') {
    $month = $_GET['month'] ?? date('Y-m');
    [$year, $mon] = explode('-', $month);
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM employee_master");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['count' => (int)($row['total'] ?? 0)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Wage Report – Security Billing Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ===== RESET ===== */
* { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }

/* ===== THEME VARIABLES ===== */
:root {
    --bg:#f4f6f9; --card:#ffffff; --text:#111827;
    --subtext:#6b7280; --border:#e5e7eb;
}
body.dark {
    --bg:#0b1120; --card:#111827; --text:#e5e7eb;
    --subtext:#9ca3af; --border:#1f2937;
}

/* ===== DARK — SIDEBAR ===== */
body.dark .sidebar { background:#0d1526; box-shadow:2px 0 12px rgba(0,0,0,0.5); }
body.dark .sidebar .menu:hover { background:rgba(255,255,255,0.06); }
body.dark .sidebar .menu.active { background:rgba(255,255,255,0.10); }
body.dark .theme-btn { background:#1e293b; color:#fbbf24; border-color:#334155; }
body.dark .theme-btn:hover { background:#293548; }

body { background:var(--bg); color:var(--text); transition:background 0.3s,color 0.3s; overflow-x:hidden; }

/* ===== LAYOUT ===== */
.dashboard { display:flex; min-height:100vh; }

/* ===== OVERLAY ===== */
.sidebar-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.5); z-index:998;
    backdrop-filter:blur(2px);
}
.sidebar-overlay.active { display:block; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:240px; min-width:240px;
    background:#0f766e; color:#ffffff;
    padding:0; display:flex; flex-direction:column;
    box-shadow:2px 0 8px rgba(0,0,0,0.12);
    flex-shrink:0; z-index:999; overflow-y:auto;
    position:relative; transition:transform 0.3s ease;
    animation:sidebarSlideIn 0.4s ease both;
}
@keyframes sidebarSlideIn {
    from { transform:translateX(-100%); opacity:0; }
    to   { transform:translateX(0);     opacity:1; }
}

.logo { padding:20px 15px; margin-bottom:10px; }
.logo img {
    max-width:160px; height:auto; display:block;
    margin:0 auto; background:#ffffff; border-radius:12px;
    padding:10px 16px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
    animation:logoPulse 3s ease-in-out infinite;
}
@keyframes logoPulse {
    0%,100% { box-shadow:0 2px 8px rgba(0,0,0,0.15); }
    50%      { box-shadow:0 4px 18px rgba(0,0,0,0.28); }
}

nav { display:flex; flex-direction:column; gap:0; padding:0 15px; flex:1; }
.menu {
    display:flex; align-items:center; gap:12px;
    padding:12px 15px; border-radius:6px;
    color:rgba(255,255,255,0.9); text-decoration:none;
    font-size:14px; font-weight:400;
    transition:all 0.25s ease; position:relative;
    margin-bottom:2px; white-space:nowrap;
    opacity:0; animation:menuFadeIn 0.35s ease forwards;
}
@keyframes menuFadeIn {
    from { opacity:0; transform:translateX(-12px); }
    to   { opacity:1; transform:translateX(0); }
}
nav .menu:nth-child(1)  { animation-delay:0.10s; }
nav .menu:nth-child(2)  { animation-delay:0.16s; }
nav .menu:nth-child(3)  { animation-delay:0.22s; }
nav .menu:nth-child(4)  { animation-delay:0.28s; }
nav .menu:nth-child(5)  { animation-delay:0.34s; }
nav .menu:nth-child(6)  { animation-delay:0.40s; }
nav .menu:nth-child(7)  { animation-delay:0.46s; }
nav .menu:nth-child(8)  { animation-delay:0.52s; }
nav .menu:nth-child(9)  { animation-delay:0.58s; }
nav .menu:nth-child(10) { animation-delay:0.64s; }
nav .menu:nth-child(11) { animation-delay:0.70s; }

.menu .icon {
    font-size:16px; width:20px;
    display:flex; align-items:center; justify-content:center;
    opacity:0.95; flex-shrink:0; transition:transform 0.2s ease;
}
.menu:hover .icon { transform:scale(1.2); }
.menu:hover { background:rgba(255,255,255,0.1); color:#ffffff; }
.menu.active { background:rgba(255,255,255,0.15); color:#ffffff; font-weight:500; }
.menu.active::before {
    content:""; position:absolute; left:-15px; top:50%;
    transform:translateY(-50%); width:4px; height:70%;
    background:#ffffff; border-radius:0 4px 4px 0;
}
.menu.logout {
    margin-top:auto; margin-bottom:15px;
    border-top:1px solid rgba(255,255,255,0.15);
    padding-top:15px;
}

/* ===== MAIN ===== */
.main { flex:1; display:flex; flex-direction:column; min-width:0; overflow-x:hidden; }

/* ===== HEADER ===== */
header {
    display:flex; align-items:center; gap:14px;
    padding:0 25px; height:62px;
    background:var(--card);
    box-shadow:0 1px 4px rgba(0,0,0,0.08);
    position:sticky; top:0; z-index:50;
    border-bottom:1px solid var(--border);
    flex-shrink:0;
    animation:headerDrop 0.4s ease both;
}
@keyframes headerDrop {
    from { transform:translateY(-100%); opacity:0; }
    to   { transform:translateY(0);     opacity:1; }
}
header h1 { font-size:1.5rem; font-weight:700; color:var(--text); flex:1; text-align:center; }

/* ===== HAMBURGER ===== */
.menu-btn {
    background:none; border:none; font-size:22px; cursor:pointer;
    color:var(--text); padding:6px 8px; border-radius:6px;
    display:none; align-items:center; justify-content:center;
    flex-shrink:0; transition:background 0.2s,transform 0.2s;
}
.menu-btn:hover { background:rgba(0,0,0,0.06); transform:rotate(90deg); }

/* ===== THEME BUTTON ===== */
.theme-btn {
    width:44px; height:44px; border-radius:12px;
    border:1px solid var(--border); background:var(--card);
    color:var(--subtext); font-size:16px; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; transition:background 0.2s,color 0.2s,border-color 0.2s,transform 0.2s;
    box-shadow:0 1px 4px rgba(0,0,0,0.07);
}
.theme-btn:hover { background:#f3f4f6; color:var(--text); transform:scale(1.08); }
.theme-btn.active { background:#1e293b; color:#a5b4fc; border-color:#334155; }

/* ===== PAGE CONTENT ===== */
.page-content {
    padding:32px 28px 40px;
    display:flex; flex-direction:column; gap:24px;
    width:100%; min-width:0; box-sizing:border-box;
    animation:contentFadeUp 0.5s 0.2s ease both;
}
@keyframes contentFadeUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

/* ===== WAGE CARD ===== */
.wage-card {
    background:var(--card); border-radius:16px;
    border:1px solid rgba(15,118,110,0.15);
    box-shadow:0 4px 20px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
    overflow:hidden;
    transition:box-shadow 0.2s,border-color 0.2s;
    animation:cardPop 0.45s 0.3s ease both; opacity:0;
}
@keyframes cardPop {
    from { opacity:0; transform:translateY(18px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
body.dark .wage-card {
    box-shadow:0 4px 20px rgba(15,118,110,0.28), 0 1px 4px rgba(16,185,129,0.14);
    border-color:rgba(15,118,110,0.28);
}
.wage-card:hover {
    box-shadow:0 8px 32px rgba(15,118,110,0.22), 0 2px 10px rgba(16,185,129,0.15);
    border-color:rgba(16,185,129,0.38);
}

.wage-card-header {
    padding:1.1rem 1.4rem;
    border-bottom:1px solid var(--border);
    display:flex; align-items:center; gap:.5rem;
}
.wage-card-header .title { font-size:.95rem; font-weight:700; color:var(--text); }
.wage-card-header .subtitle {
    font-size:.78rem; color:var(--subtext); margin-top:1px;
}
.wage-card-header i { color:#0f766e; font-size:1rem; }
.wage-card-header-text { display:flex; flex-direction:column; }

/* ===== SELECTOR SECTION ===== */
.selector-section {
    padding:28px 32px;
    display:flex; flex-direction:column; gap:20px;
}
.selector-label {
    font-size:.75rem; font-weight:700; color:var(--subtext);
    text-transform:uppercase; letter-spacing:.7px;
}
.selector-row {
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
}
.month-input {
    padding:.65rem 1rem;
    border:1px solid var(--border); border-radius:10px;
    font-size:.92rem; color:var(--text);
    background:var(--bg); font-family:inherit; outline:none;
    transition:border-color .2s, box-shadow .2s, background .2s;
    cursor:pointer; min-width:200px;
}
.month-input:focus {
    border-color:#0f766e;
    box-shadow:0 0 0 3px rgba(15,118,110,.12);
    background:var(--card);
}

/* Fetch Data button — matches screenshot green style */
.btn-fetch {
    display:flex; align-items:center; gap:.5rem;
    padding:.65rem 1.6rem;
    background:#0f766e; color:#fff;
    border:none; border-radius:10px;
    font-size:.9rem; font-weight:700;
    cursor:pointer; font-family:inherit;
    box-shadow:0 3px 12px rgba(15,118,110,.28);
    transition:background .2s, transform .15s, box-shadow .2s;
}
.btn-fetch:hover { background:#0d5f58; transform:translateY(-2px); box-shadow:0 6px 20px rgba(15,118,110,.38); }
.btn-fetch:active { transform:translateY(0); }
.btn-fetch.loading { opacity:.75; pointer-events:none; }

/* ===== RESULT AREA ===== */
.result-area {
    display:none;
    border-top:1px solid var(--border);
}
.result-area.visible { display:block; animation:fadeUp 0.4s ease both; }
@keyframes fadeUp {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}

.result-inner {
    padding:36px 32px 40px;
    display:flex; flex-direction:column; align-items:center; gap:24px;
}

/* ===== EXCEL ICON ===== */
.excel-icon-wrap {
    display:flex; flex-direction:column; align-items:center; gap:16px;
    animation:iconBounce 0.5s 0.1s ease both;
}
@keyframes iconBounce {
    0%   { opacity:0; transform:scale(.6) translateY(20px); }
    70%  { transform:scale(1.08) translateY(-4px); }
    100% { opacity:1; transform:scale(1) translateY(0); }
}
.excel-icon {
    width:72px; height:72px; background:#1d6f42; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 6px 22px rgba(29,111,66,.35);
    position:relative;
}
.excel-icon::before {
    content:"X"; font-size:32px; font-weight:900; color:#ffffff;
    font-style:italic; letter-spacing:-2px;
}
.excel-badge {
    position:absolute; top:-8px; right:-8px;
    background:#fff; border:2px solid #1d6f42;
    border-radius:50%; width:24px; height:24px;
    display:flex; align-items:center; justify-content:center;
}
.excel-badge i { font-size:.6rem; color:#1d6f42; }

.report-title { font-size:1.4rem; font-weight:800; color:var(--text); text-align:center; }
.report-sub   { font-size:.88rem; color:var(--subtext); text-align:center; }
.emp-count    { font-size:1.05rem; font-weight:600; color:#0f766e; }

/* Download CSV button */
.btn-download {
    display:flex; align-items:center; gap:.55rem;
    padding:.75rem 2.2rem;
    background:linear-gradient(135deg,#1d6f42 0%,#27a35e 100%);
    color:#fff; border:none; border-radius:12px;
    font-size:.95rem; font-weight:700;
    cursor:pointer; font-family:inherit;
    box-shadow:0 4px 16px rgba(29,111,66,.35);
    transition:transform .2s, box-shadow .2s, filter .2s;
    text-decoration:none;
    animation:fadeUp 0.4s 0.25s ease both; opacity:0;
}
.btn-download:hover {
    transform:translateY(-3px);
    box-shadow:0 8px 26px rgba(29,111,66,.45);
    filter:brightness(1.06);
}
.btn-download:active { transform:translateY(-1px); }

.download-note {
    display:flex; align-items:center; gap:.4rem;
    font-size:.78rem; color:var(--subtext);
    animation:fadeUp 0.4s 0.4s ease both; opacity:0;
}
.download-note i { color:#059669; font-size:.7rem; }

/* ===== SPINNER ===== */
.spinner {
    display:inline-block; width:18px; height:18px;
    border:2px solid rgba(255,255,255,.4); border-top-color:#fff;
    border-radius:50%; animation:spin .7s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg); } }

/* ===== RESPONSIVE ===== */
@media (max-width:992px) {
    .sidebar { width:210px; min-width:210px; }
    .menu { font-size:13px; padding:11px 12px; }
    header h1 { font-size:1.3rem; }
    .page-content { padding:24px 20px 32px; }
}
@media (max-width:768px) {
    .menu-btn { display:flex; }
    .sidebar {
        position:fixed; top:0; left:0; height:100vh;
        transform:translateX(-100%); animation:none;
        width:260px; min-width:260px;
        box-shadow:4px 0 20px rgba(0,0,0,0.25);
    }
    .sidebar.open { transform:translateX(0); }
    header { padding:0 14px; height:56px; }
    header h1 { font-size:1.1rem; }
    .theme-btn { width:38px; height:38px; font-size:14px; }
    .page-content { padding:16px 14px 28px; gap:14px; }
    .selector-section { padding:20px 18px; gap:16px; }
    .selector-row { flex-direction:column; align-items:stretch; gap:10px; }
    .month-input { min-width:100%; font-size:.88rem; }
    .btn-fetch { width:100%; justify-content:center; }
    .result-inner { padding:28px 18px 32px; gap:18px; }
    .report-title { font-size:1.2rem; }
    .btn-download { width:100%; justify-content:center; }
}
@media (max-width:480px) {
    header { padding:0 12px; height:52px; }
    header h1 { font-size:.95rem; }
    .page-content { padding:12px 10px 24px; gap:12px; }
    .selector-section { padding:16px 14px; }
    .result-inner { padding:22px 14px 26px; gap:14px; }
    .excel-icon { width:56px; height:56px; border-radius:12px; }
    .excel-icon::before { font-size:24px; }
    .report-title { font-size:1.05rem; }
    .btn-download { padding:.65rem 1.4rem; font-size:.83rem; }
}
</style>
</head>
<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ========== SIDEBAR ========== -->
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
            <a href="basic_pay_update.php" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>
            <a href="add_extra_manpower.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>
            <a href="../unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                <span>Unlock Attendance</span>
            </a>
            <a href="attendance_request.php" class="menu">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                <span>Attendance Request</span>
            </a>
            <a href="../download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-download"></i></span>
                <span>Download Attendance</span>
            </a>
            <a href="wage_report.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Wage Report</span>
            </a>
            <a href="monthly_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                <span>Monthly Attendance</span>
            </a>
            <a href="../logout.php" class="menu logout">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- ========== MAIN ========== -->
    <main class="main">

        <!-- HEADER -->
        <header>
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1>Security Billing Management Portal</h1>
            <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <!-- PAGE CONTENT -->
        <div class="page-content">

            <div class="wage-card">
                <!-- Card Header -->
                <div class="wage-card-header">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <div class="wage-card-header-text">
                        <span class="title">Wage Report Download</span>
                        <span class="subtitle">Select month and year to fetch wage records</span>
                    </div>
                </div>

                <!-- Selector -->
                <div class="selector-section">
                    <div class="selector-label">Select Month &amp; Year</div>
                    <div class="selector-row">
                        <input
                            class="month-input"
                            type="month"
                            id="monthPicker"
                            value="<?= date('Y-m') ?>"
                        >
                        <button class="btn-fetch" id="fetchBtn" onclick="fetchData()">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Fetch Data
                        </button>
                    </div>
                </div>

                <!-- Result Area -->
                <div class="result-area" id="resultArea">
                    <div class="result-inner" id="resultInner">
                        <!-- Populated by JS -->
                    </div>
                </div>

            </div>

        </div><!-- /.page-content -->
    </main>
</div>

<script>
/* ── Sidebar ── */
const menuBtn = document.getElementById('menuBtn');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');

function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; }

menuBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar .menu').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); });
});
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; }
});

/* ── Theme ── */
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
themeToggle.addEventListener('click', function () {
    const isDark = document.body.classList.contains('dark');
    applyTheme(!isDark);
    localStorage.setItem('theme', !isDark ? 'dark' : 'light');
});

/* ── Fetch & Render ── */
async function fetchData() {
    const month       = document.getElementById('monthPicker').value;
    const fetchBtn    = document.getElementById('fetchBtn');
    const resultArea  = document.getElementById('resultArea');
    const resultInner = document.getElementById('resultInner');

    if (!month) return;

    fetchBtn.classList.add('loading');
    fetchBtn.innerHTML = '<span class="spinner"></span> Fetching…';
    resultArea.classList.remove('visible');

    try {
        const res   = await fetch(`wage_report.php?fetch=1&month=${encodeURIComponent(month)}`);
        const data  = await res.json();
        const count = data.count ?? 0;

        const [yr, mo] = month.split('-');
        const label = new Date(yr, mo - 1, 1).toLocaleString('en-US', { month:'long', year:'numeric' });
        const downloadUrl = `export_wage_report.php?month=${encodeURIComponent(month)}`;

        resultInner.innerHTML = `
            <div class="excel-icon-wrap">
                <div class="excel-icon">
                    <span class="excel-badge"><i class="fa-solid fa-check"></i></span>
                </div>
            </div>
            <div class="report-title">Report Ready: ${label}</div>
            <div class="report-sub">
                Calculations completed for <span class="emp-count">${Number(count).toLocaleString()} employees</span>.
            </div>
            <a class="btn-download" href="${downloadUrl}" download>
                <i class="fa-solid fa-download"></i>
                Download CSV File
            </a>
            <div class="download-note">
                <i class="fa-solid fa-circle-info"></i>
                Taxes, Service Charges, and GST included
            </div>
        `;

        resultArea.classList.add('visible');
    } catch (err) {
        resultInner.innerHTML = `
            <div style="padding:48px 24px;text-align:center;color:var(--subtext);">
                <i class="fa-solid fa-circle-exclamation" style="font-size:2.8rem;display:block;margin-bottom:12px;opacity:.4;"></i>
                <p style="font-size:.9rem;">Failed to fetch data. Please try again.</p>
            </div>
        `;
        resultArea.classList.add('visible');
    } finally {
        fetchBtn.classList.remove('loading');
        fetchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Fetch Data';
    }
}
</script>
</body>
</html>
