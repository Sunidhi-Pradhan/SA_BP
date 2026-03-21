<?php
session_start();
require __DIR__ . '/../config.php';

// ── Session guard ──
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// ── Fetch GM user details & site code ──
$stmtUser = $pdo->prepare("SELECT role, site_code, name FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'GM') {
    die("Access denied");
}

$siteCode = $user['site_code'];

// ── Fetch site name for display ──
$stmtSiteName = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
$stmtSiteName->execute([$siteCode]);
$siteRow = $stmtSiteName->fetch(PDO::FETCH_ASSOC);
$siteName = $siteRow['SiteName'] ?? $siteCode;

// ── Determine FY ──
$now         = new DateTime();
$fyStartYear = ($now->format('n') >= 4) ? (int)$now->format('Y') : (int)$now->format('Y') - 1;
$selFY       = $_GET['fy'] ?? ($fyStartYear . '-' . ($fyStartYear + 1));

// Parse FY
$fyParts = explode('-', $selFY);
$fyStart = (int)($fyParts[0] ?? $fyStartYear);
$fyEnd   = (int)($fyParts[1] ?? ($fyStartYear + 1));

// Build 12 months of this FY (Apr start … Mar end)
$fyMonths = [];
for ($m = 4; $m <= 12; $m++) $fyMonths[] = ['year' => $fyStart, 'month' => $m];
for ($m = 1; $m <= 3;  $m++) $fyMonths[] = ['year' => $fyEnd,   'month' => $m];

// ── Active staff count (GM's site only) ──
$st = $pdo->prepare("SELECT COUNT(*) FROM employee_master WHERE site_code = ?");
$st->execute([$siteCode]);
$activeStaff = (int)$st->fetchColumn();

// ── Monthly rows ──
$dashRows         = [];
$totalWorkingDays = 0;
$totalExtraDuties = 0;
$monthNames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

foreach ($fyMonths as $period) {
    $yr = $period['year'];
    $mo = $period['month'];

    $st = $pdo->prepare("
        SELECT a.attendance_json FROM attendance a
        INNER JOIN employee_master e ON a.esic_no = e.esic_no
        WHERE a.attendance_year = :yr AND a.attendance_month = :mo AND e.site_code = :sc
    ");
    $st->execute([':yr' => $yr, ':mo' => $mo, ':sc' => $siteCode]);
    $attRows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (empty($attRows)) continue;

    $staffApproved = 0; $mWork = 0; $mExtra = 0; $mAbsent = 0;
    foreach ($attRows as $aRow) {
        $json = json_decode($aRow['attendance_json'], true);
        if (!is_array($json) || empty($json)) continue;
        $has = false;
        foreach ($json as $entry) {
            $s = $entry['status'] ?? '';
            if ($s === 'P')  { $mWork++;  $has = true; }
            if ($s === 'PP') { $mExtra++; $has = true; }
            if ($s === 'A')  { $mAbsent++; $has = true; }
        }
        if ($has) $staffApproved++;
    }

    $dim = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);
    $weekdays = 0;
    for ($d = 1; $d <= $dim; $d++) {
        if (date('N', mktime(0,0,0,$mo,$d,$yr)) < 6) $weekdays++;
    }
    $maxP = $staffApproved * $weekdays;
    $pct  = $maxP > 0 ? round(($mWork / $maxP) * 100, 1) : 0;

    $totalWorkingDays += $mWork;
    $totalExtraDuties += $mExtra;

    $dashRows[] = [
        'year'           => $yr,
        'month'          => $mo,
        'month_label'    => $monthNames[$mo] . ' ' . $yr,
        'staff_approved' => $staffApproved,
        'working_days'   => $weekdays,
        'extra_duty'     => $mExtra,
        'absents'        => $mAbsent,
        'attendance_pct' => $pct,
    ];
}

// Sort most recent first
usort($dashRows, function ($a, $b) {
    return ($a['year'] !== $b['year']) ? $b['year'] - $a['year'] : $b['month'] - $a['month'];
});

$grandTotal = $totalWorkingDays + $totalExtraDuties;

// Generate FY options
$fyOptions = [];
for ($y = $fyStartYear; $y >= 2021; $y--) {
    $fyOptions[] = $y . '-' . ($y + 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Security Billing Management Portal – GM Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
:root {
  --primary: #0f766e;
  --primary-dark: #0d5f58;
  --primary-darker: #0a4f49;
  --sidebar-width: 270px;
  --topbar-h: 64px;
  --bg: #f5f5f5;
  --white: #ffffff;
  --border: #e5e7eb;
  --text: #1f2937;
  --muted: #6b7280;
  --role: #2563eb;
  --role-light: #dbeafe;
  --role-border: #93c5fd;
}
html { scroll-behavior: smooth; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

/* ─── LAYOUT ─── */
.layout { display: grid; grid-template-columns: var(--sidebar-width) 1fr; min-height: 100vh; }

/* ─── SIDEBAR ─── */
.sidebar {
  background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%);
  color: white;
  display: flex; flex-direction: column;
  position: sticky; top: 0; height: 100vh;
  overflow-y: auto; overflow-x: hidden;
  box-shadow: 4px 0 24px rgba(13,95,88,0.35);
  z-index: 100;
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) transparent;
}
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 2px; }
.sidebar-close {
  display: none; position: absolute; top: 1rem; right: 1rem;
  background: rgba(255,255,255,0.12); border: none; color: white;
  width: 32px; height: 32px; border-radius: 8px; cursor: pointer;
  font-size: 1rem; align-items: center; justify-content: center; z-index: 2;
}
.sidebar-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,0.5); z-index: 99; backdrop-filter: blur(2px);
}
.sidebar-overlay.active { display: block; }

.sidebar-logo {
  padding: 1.4rem 1.5rem 1.2rem;
  border-bottom: 1px solid rgba(255,255,255,0.15);
  display: flex; align-items: center; justify-content: center;
}
.mcl-logo-img {
  max-width: 155px; height: auto; display: block;
  background: white; padding: 10px 14px; border-radius: 10px;
}

.sidebar-nav { list-style: none; padding: 1rem 0; flex: 1; }
.sidebar-nav li { margin: 0.2rem 1rem; }
.nav-link {
  display: flex; align-items: center; gap: 0.9rem;
  padding: 0.85rem 1.1rem;
  color: rgba(255,255,255,0.88);
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.2s;
  font-weight: 500; font-size: 0.93rem;
  cursor: pointer;
}
.nav-link:hover { background: rgba(255,255,255,0.15); color: #fff; }
.nav-link.active { background: rgba(255,255,255,0.22); color: #fff; font-weight: 600; }
.nav-link i { font-size: 1rem; width: 22px; text-align: center; opacity: 0.9; }
.nav-link.logout-link { color: rgba(255,255,255,0.75); }
.nav-link.logout-link:hover { background: rgba(239,68,68,0.18); color: #fca5a5; }

/* ─── MAIN ─── */
.main-content { padding: 2.25rem 2.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 2rem; min-width: 0; }

/* ─── TOPBAR ─── */
.topbar {
  display: flex; justify-content: space-between; align-items: center;
  background: white; border-radius: 14px; padding: 1rem 1.5rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid var(--border);
}
.topbar-left { display: flex; align-items: center; gap: 1rem; }
.hamburger-btn {
  display: none; background: #f3f4f6; border: 1.5px solid var(--border);
  border-radius: 8px; width: 38px; height: 38px;
  align-items: center; justify-content: center;
  cursor: pointer; color: var(--primary); font-size: 1rem;
}
.topbar h2 { font-size: 1.4rem; font-weight: 700; color: var(--text); }
.topbar-right { display: flex; align-items: center; gap: 12px; }

.role-badge {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--role-light); color: #1d4ed8;
  border: 1.5px solid var(--role-border); border-radius: 20px;
  padding: 0.3rem 0.9rem; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.5px;
}
.header-icon {
  width: 40px; height: 40px; border-radius: 50%;
  background: #f3f4f6; display: flex; align-items: center; justify-content: center;
  cursor: pointer; position: relative; color: #6b7280;
  font-size: 1rem; border: 1px solid var(--border);
}
.header-icon .badge {
  position: absolute; top: -4px; right: -4px;
  background: #ef4444; color: white; font-size: 0.65rem;
  width: 18px; height: 18px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-weight: 700;
}
.user-icon {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--primary); display: flex; align-items: center; justify-content: center; cursor: pointer;
}
.user-icon svg { width: 20px; height: 20px; stroke: white; }

/* ─── PAGE HEADING ─── */
.page-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.page-heading-left { display: flex; align-items: center; gap: 0.75rem; }
.page-heading-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(15,118,110,0.1); color: var(--primary);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
}
.page-heading h1 { font-size: 1.5rem; font-weight: 800; color: var(--text); }
.page-heading-sub { font-size: 0.84rem; color: var(--muted); margin-top: 1px; }
.page-heading-sub span { color: var(--primary); font-weight: 600; }

/* Site Badge */
.site-badge {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: #f0fdf4; color: #15803d;
  border: 1.5px solid #86efac; border-radius: 20px;
  padding: 0.35rem 1rem; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.3px;
}

/* Filters */
.filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-wrap { position: relative; }
.filter-select {
  appearance: none; -webkit-appearance: none;
  background: var(--white)
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E")
    no-repeat right 10px center;
  border: 1.5px solid var(--border); border-radius: 8px;
  padding: 7px 30px 7px 12px;
  font-size: 0.84rem; color: var(--text);
  cursor: pointer; outline: none;
  font-family: inherit;
  transition: border-color 0.2s;
}
.filter-select:focus { border-color: var(--primary); }

/* ─── KPI CARDS ─── */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.75rem; }
.kpi-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  display: flex; align-items: center; gap: 1rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  transition: transform 0.2s, box-shadow 0.2s;
  animation: fadeUp 0.4s ease both;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.kpi-card:nth-child(1) { animation-delay: 0.05s; }
.kpi-card:nth-child(2) { animation-delay: 0.1s;  }
.kpi-card:nth-child(3) { animation-delay: 0.15s; }
.kpi-card:nth-child(4) { animation-delay: 0.2s;  }

.kpi-icon-wrap {
  width: 44px; height: 44px; flex-shrink: 0;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem;
}
.kpi-icon-wrap.blue   { background: #dbeafe; color: #2563eb; }
.kpi-icon-wrap.teal   { background: #ccfbf1; color: #0f766e; }
.kpi-icon-wrap.amber  { background: #fef3c7; color: #d97706; }
.kpi-icon-wrap.green  { background: #d1fae5; color: #059669; }

.kpi-body { flex: 1; }
.kpi-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: var(--muted); margin-bottom: 4px; }
.kpi-value { font-size: 1.7rem; font-weight: 800; line-height: 1; color: var(--text); }

/* ─── TABLE SECTION ─── */
.section {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  overflow: hidden;
}

/* Table */
table { width: 100%; border-collapse: collapse; }
thead th {
  background: linear-gradient(135deg, #0f766e, #0d5f58);
  color: white; font-size: 0.76rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
  padding: 14px 20px; text-align: left;
  white-space: nowrap;
}
thead th:last-child { text-align: center; }
tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f9fafb; }
tbody tr:nth-child(even) { background: #fafafa; }
tbody tr:nth-child(even):hover { background: #f3f4f6; }
td { padding: 16px 20px; font-size: 0.86rem; }

.month-chip {
  display: inline-flex; align-items: center; gap: 5px;
  background: #eff6ff; color: #2563eb;
  border: 1px solid #bfdbfe;
  border-radius: 20px; padding: 3px 12px;
  font-size: 0.8rem; font-weight: 700;
}
.extra-chip {
  display: inline-flex; align-items: center; justify-content: center;
  background: #f5f3ff; color: #7c3aed;
  border: 1px solid #ddd6fe;
  border-radius: 20px; padding: 3px 14px;
  font-size: 0.8rem; font-weight: 700;
}
.absent-val { color: #dc2626; font-weight: 700; font-size: 0.9rem; }
.td-center { text-align: center; }

/* Progress */
.progress-cell { display: flex; align-items: center; gap: 10px; min-width: 160px; }
.progress-track { flex: 1; height: 7px; background: #f0f0f0; border-radius: 99px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #10b981, #34d399); }
.progress-pct { font-size: 0.82rem; font-weight: 700; color: #059669; min-width: 38px; text-align: right; }

/* Expand btn */
.expand-btn {
  width: 24px; height: 24px; border-radius: 6px;
  background: #f3f4f6; border: 1px solid var(--border);
  cursor: pointer; font-size: 0.65rem; color: var(--muted);
  display: inline-flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.expand-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }

/* Pagination */
.pagination {
  padding: 1.25rem 1.75rem;
  border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: flex-end;
  font-size: 0.82rem; color: var(--muted);
  background: #fafafa; gap: 6px;
}
.pag-btn {
  min-width: 34px; height: 34px; border-radius: 8px;
  border: 1.5px solid var(--border); background: var(--white);
  cursor: pointer; font-size: 0.82rem; font-weight: 600; color: var(--muted);
  display: flex; align-items: center; justify-content: center; padding: 0 10px;
  transition: all 0.2s; font-family: inherit;
}
.pag-btn:hover { border-color: var(--role); color: var(--role); }
.pag-btn.active { background: var(--role); color: white; border-color: var(--role); }
.pag-btn:disabled { opacity: 0.4; cursor: default; }

/* No data row */
.no-data { text-align: center; padding: 2rem; color: var(--muted); font-size: 0.9rem; }

/* ─── ANIMATIONS ─── */
@keyframes fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
.section { animation: fadeUp 0.4s 0.25s ease both; }

/* ─── RESPONSIVE ─── */
@media (max-width: 1100px) { .kpi-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 768px) {
  .layout { grid-template-columns: 1fr; }
  .sidebar { position: fixed; left: 0; top: 0; height: 100vh; transform: translateX(-100%); z-index: 200; transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
  .sidebar.open { transform: translateX(0); box-shadow: 8px 0 32px rgba(0,0,0,0.3); }
  .sidebar-close { display: flex; }
  .hamburger-btn { display: flex; }
  .kpi-grid { grid-template-columns: 1fr; }
  .main-content { padding: 1.25rem; }
  .page-heading { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="layout">

  <!-- ═══════════════ SIDEBAR ═══════════════ -->
  <aside class="sidebar" id="sidebar">

    <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>

    <div class="sidebar-logo">
      <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
    </div>

    <ul class="sidebar-nav">
      <li>
        <a class="nav-link active" href="dashboard.php">
          <i class="fa-solid fa-gauge-high"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li>
        <a class="nav-link" href="monthly.php">
          <i class="fa-solid fa-calendar-days"></i>
          <span>Monthly Attendance</span>
        </a>
      </li>
      <li>
        <a class="nav-link logout-link" href="../logout.php">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Logout</span>
        </a>
      </li>
    </ul>

  </aside>

  <!-- ═══════════════ MAIN ═══════════════ -->
  <main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h2>Security Billing Management Portal</h2>
      </div>
      <div class="topbar-right">
        <span class="role-badge"><i class="fa-solid fa-user-gear"></i> GM</span>
        <div class="header-icon">
          <i class="fa-regular fa-bell"></i>
          <span class="badge">3</span>
        </div>
        <a href="profile.php" title="My Profile" style="text-decoration:none;">
          <div class="user-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
            </svg>
          </div>
        </a>
      </div>
    </header>

    <!-- CONTENT -->
    <div>

      <!-- Page Heading -->
      <div class="page-heading">
        <div class="page-heading-left">
          <div class="page-heading-icon">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div>
            <h1>Attendance Dashboard</h1>
            <div class="page-heading-sub"><i class="fa-solid fa-circle" style="font-size:0.5rem;color:var(--primary);vertical-align:middle;margin-right:4px;"></i> Showing <span>Only Approved</span> monthly data</div>
          </div>
        </div>
        <div class="filters">
          <!-- Site badge (no dropdown – GM sees only their site) -->
          <span class="site-badge">
            <i class="fa-solid fa-location-dot"></i>
            <?= htmlspecialchars($siteCode) ?> – <?= htmlspecialchars($siteName) ?>
          </span>
          <!-- FY dropdown -->
          <div class="filter-wrap" style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:0.85rem;font-weight:600;color:var(--muted);white-space:nowrap;">FY:</span>
            <select class="filter-select" id="fySelect" onchange="reloadDashboard()">
              <?php foreach ($fyOptions as $fy): ?>
                <option value="<?= $fy ?>" <?= $selFY === $fy ? 'selected' : '' ?>><?= $fy ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid" style="margin-top: 1.75rem;">
        <div class="kpi-card">
          <div class="kpi-icon-wrap blue"><i class="fa-solid fa-users"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Active Staff</div>
            <div class="kpi-value"><?= number_format($activeStaff) ?></div>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon-wrap teal"><i class="fa-solid fa-calendar-check"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Working Days</div>
            <div class="kpi-value"><?= number_format($totalWorkingDays) ?></div>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon-wrap amber"><i class="fa-solid fa-bolt"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Extra Duties</div>
            <div class="kpi-value"><?= number_format($totalExtraDuties) ?></div>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon-wrap green"><i class="fa-solid fa-chart-bar"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Grand Total</div>
            <div class="kpi-value"><?= number_format($grandTotal) ?></div>
          </div>
        </div>
      </div>

      <!-- Monthly Table -->
      <div class="section" style="margin-top: 1.75rem;">
        <table>
          <thead>
            <tr>
              <th></th>
              <th>Month / Year</th>
              <th>Staff (Approved)</th>
              <th>Working Days</th>
              <th>Extra Duty</th>
              <th>Absents</th>
              <th>Attendance %</th>
            </tr>
          </thead>
          <tbody id="attendanceTableBody">
          </tbody>
        </table>

        <div class="pagination" id="paginationWrap">
          <button class="pag-btn" id="prevBtn" disabled onclick="changePage(-1)">Previous</button>
          <span id="pageIndicator"></span>
          <button class="pag-btn" id="nextBtn" disabled onclick="changePage(1)">Next</button>
        </div>
      </div>

    </div><!-- /content -->
  </main>

</div><!-- /layout -->

<script>
  /* ── Sidebar toggle ── */
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  document.getElementById('hamburgerBtn').addEventListener('click', () => {
    sidebar.classList.add('open'); overlay.classList.add('active');
  });
  document.getElementById('sidebarClose').addEventListener('click', () => {
    sidebar.classList.remove('open'); overlay.classList.remove('active');
  });
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open'); overlay.classList.remove('active');
  });

  /* ── Reload page with selected FY ── */
  function reloadDashboard() {
    const fy = document.getElementById('fySelect').value;
    window.location.href = `dashboard.php?fy=${encodeURIComponent(fy)}`;
  }

  /* ══════════════════════════════════════════════════════════════
     TABLE DATA — embedded from PHP, client-side pagination only
  ══════════════════════════════════════════════════════════════ */
  const allRows     = <?= json_encode($dashRows) ?>;
  let   currentPage = 1;
  const perPage     = 5;

  function renderTable() {
    const tbody = document.getElementById('attendanceTableBody');

    if (!allRows.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="no-data">No attendance data found for the selected financial year.</td></tr>';
      document.getElementById('prevBtn').disabled = true;
      document.getElementById('nextBtn').disabled = true;
      document.getElementById('pageIndicator').innerHTML = '';
      return;
    }

    const totalPages = Math.ceil(allRows.length / perPage);
    const start = (currentPage - 1) * perPage;
    const pageRows = allRows.slice(start, start + perPage);

    tbody.innerHTML = '';
    pageRows.forEach(row => {
      const pct = row.attendance_pct != null ? parseFloat(row.attendance_pct).toFixed(1) : '0.0';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="td-center"><button class="expand-btn">▶</button></td>
        <td><span class="month-chip"><i class="fa-regular fa-calendar" style="font-size:0.72rem;"></i> ${row.month_label}</span></td>
        <td>${row.staff_approved || 0}</td>
        <td>${row.working_days || 0}</td>
        <td class="td-center"><span class="extra-chip">${row.extra_duty || 0}</span></td>
        <td class="td-center"><span class="absent-val">${row.absents || 0}</span></td>
        <td>
          <div class="progress-cell">
            <div class="progress-track"><div class="progress-fill" style="width:${pct}%"></div></div>
            <span class="progress-pct">${pct}%</span>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;

    let pagHTML = '';
    for (let i = 1; i <= totalPages; i++) {
      pagHTML += `<button class="pag-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }
    document.getElementById('pageIndicator').innerHTML = pagHTML;
  }

  function changePage(delta) { currentPage += delta; renderTable(); }
  function goToPage(p) { currentPage = p; renderTable(); }

  // Render on page load
  renderTable();
</script>
</body>
</html>
