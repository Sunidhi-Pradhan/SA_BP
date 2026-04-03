<?php
session_start();
require 'config.php';
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// ── Filter params ────────────────────────────────────────
$today        = date('Y-m-d');
$todayDisp    = date('d.m.Y');
$filterDate   = $_GET['att_date']   ?? $today;           // selected attendance date
$filterSite   = $_GET['site_code']  ?? '';               // '' = All Sites
$filterIsSet  = isset($_GET['att_date']) || isset($_GET['site_code']);

// Parse filter date
$filterDT   = new DateTime($filterDate);
$todayYear  = (int)$filterDT->format('Y');
$todayMon   = (int)$filterDT->format('n');
$dayKey     = (int)$filterDT->format('j');
$todayDisp  = $filterDT->format('d.m.Y');

// ── Fetch all sites for dropdown ─────────────────────────
$sitesStmt = $pdo->query("SELECT SiteCode, SiteName FROM site_master ORDER BY SiteName");
$allSites  = $sitesStmt ? $sitesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// ── 1. Total employees (optionally filtered by site) ────
$empSiteWhere = $filterSite ? 'WHERE site_code = ?' : '';
$empStmt = $pdo->prepare("SELECT COUNT(*) FROM employee_master $empSiteWhere");
$empStmt->execute($filterSite ? [$filterSite] : []);
$totalEmp = (int)$empStmt->fetchColumn();

// ── 2. Attendance stats for selected date ────────────────
$todayPresent = 0; $todayAbsent = 0; $todayLeave = 0; $todayOT = 0;
$attJoinSite  = $filterSite ? ' INNER JOIN employee_master e ON a.esic_no = e.esic_no AND e.site_code = :sc' : '';
$attSql = "SELECT a.attendance_json FROM attendance a
    {$attJoinSite}
    WHERE a.attendance_year = :yr AND a.attendance_month = :mo";
$stmtAtt = $pdo->prepare($attSql);
$attParams = [':yr' => $todayYear, ':mo' => $todayMon];
if ($filterSite) $attParams[':sc'] = $filterSite;
$stmtAtt->execute($attParams);
$attRows = $stmtAtt->fetchAll(PDO::FETCH_COLUMN);
foreach ($attRows as $aj) {
    $json = json_decode($aj, true);
    if (!is_array($json)) continue;
    foreach ($json as $entry) {
        $day = (int)($entry['day'] ?? 0);
        if ($day !== $dayKey) continue;
        $s = $entry['status'] ?? '';
        if ($s === 'P')  $todayPresent++;
        elseif ($s === 'PP') { $todayPresent++; $todayOT++; }
        elseif ($s === 'A')  $todayAbsent++;
        elseif ($s === 'L')  $todayLeave++;
    }
}
$attRate    = $totalEmp > 0 ? round(($todayPresent / $totalEmp) * 100) : 0;
$pendingAtt = max(0, $totalEmp - ($todayPresent + $todayAbsent + $todayLeave));
$pendingPct = $totalEmp > 0 ? round(($pendingAtt / $totalEmp) * 100) : 0;

// ── 3. Total active users ──────────────────────────────
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();

// ── 4. Total attendance uploads (rows in attendance table for selected month) ──
$uplSiteJoin  = $filterSite ? ' INNER JOIN employee_master em ON a.esic_no = em.esic_no AND em.site_code = :sc' : '';
$uplSql = "SELECT COUNT(*) FROM attendance a {$uplSiteJoin} WHERE a.attendance_year = :yr AND a.attendance_month = :mo";
$stmtUploads = $pdo->prepare($uplSql);
$uplParams = [':yr' => $todayYear, ':mo' => $todayMon];
if ($filterSite) $uplParams[':sc'] = $filterSite;
$stmtUploads->execute($uplParams);
$totalUploads = (int)$stmtUploads->fetchColumn();
$uploadPct    = $totalEmp > 0 ? round(($totalUploads / $totalEmp) * 100) : 0;

// ── 5. Approved monthly reports (from attendance_approval) ──────────────────
$stmtApproved = $pdo->prepare("SELECT COUNT(*) FROM attendance_approval WHERE attendance_year = ? AND attendance_month = ?");
$stmtApproved->execute([$todayYear, $todayMon]);
$approvedReports = (int)$stmtApproved->fetchColumn();

$stmtAllReports = $pdo->prepare("SELECT COUNT(*) FROM attendance_approval WHERE attendance_year = ? AND attendance_month = ?");
$stmtAllReports->execute([$todayYear, $todayMon]);
$monthlyReports = (int)$stmtAllReports->fetchColumn();

// ── 6. LPP data (stub — table may not exist yet) ───────
$lppGenerated = 0; $lppPaidAmt = 0.0;
try {
    $lppGenerated = (int)$pdo->query("SELECT COUNT(*) FROM lpp_records")->fetchColumn();
    $lppPaidAmt   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM lpp_records WHERE status='paid'")->fetchColumn();
} catch (Exception $e) { /* table may not exist */ }

// ── 7. Last 12 months for charts ───────────────────────
$now = new DateTime();
$months12 = [];
for ($i = 11; $i >= 0; $i--) {
    $d = (clone $now)->modify("-{$i} month");
    $months12[] = ['y' => (int)$d->format('Y'), 'm' => (int)$d->format('n'), 'label' => $d->format('M y')];
}

$chartLabels    = [];
$chartPresent   = [];
$chartOT        = [];
$chartLPP       = [];

foreach ($months12 as $p) {
    $chartLabels[] = $p['label'];

    $s2 = $pdo->prepare("SELECT attendance_json FROM attendance WHERE attendance_year = :y AND attendance_month = :m");
    $s2->execute([':y' => $p['y'], ':m' => $p['m']]);
    $rows2 = $s2->fetchAll(PDO::FETCH_COLUMN);

    $pCnt = 0; $ppCnt = 0;
    foreach ($rows2 as $aj) {
        $j = json_decode($aj, true);
        if (!is_array($j)) continue;
        foreach ($j as $e) {
            if (($e['status'] ?? '') === 'P')  $pCnt++;
            if (($e['status'] ?? '') === 'PP') $ppCnt++;
        }
    }
    $chartPresent[] = $pCnt;
    $chartOT[]      = $ppCnt;

    // LPP per month (stub)
    $chartLPP[] = 0;
}
try {
    foreach ($months12 as $i => $p) {
        $s3 = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM lpp_records WHERE YEAR(created_at)=? AND MONTH(created_at)=?");
        $s3->execute([$p['y'], $p['m']]);
        $chartLPP[$i] = (float)$s3->fetchColumn();
    }
} catch (Exception $e) {}

// Donut: present vs absent vs leave for today
$donutPresent = $todayPresent;
$donutAbsent  = $todayAbsent;
$donutLeave   = $todayLeave;
$donutTotal   = $donutPresent + $donutAbsent + $donutLeave;
if ($donutTotal === 0) { $donutPresent = 1; $donutTotal = 1; } // show filled circle when no data
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Security Portal – Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/desh.css">
<style>
/* ── Extra page-level styles (sidebar unchanged via desh.css) ── */
:root {
  --accent: #0f766e;
  --accent2: #10b981;
  --card-r: 14px;
}

/* ── TOPBAR STRIP ── */
.dash-topbar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px;
  background: white; border-radius: var(--card-r);
  padding: .65rem 1.2rem;
  border: 1px solid var(--border);
  box-shadow: 0 1px 6px rgba(0,0,0,.06);
  animation: contentFadeUp .45s .1s ease both;
}
.topbar-left { display: flex; align-items: center; gap: 10px; }
.date-chip {
  background: #d1fae5; color: #065f46;
  border: 1px solid #6ee7b7; border-radius: 20px;
  padding: .28rem .9rem; font-size: .8rem; font-weight: 700;
}
.btn-show-filters {
  display: flex; align-items: center; gap: .4rem;
  background: #f9fafb; border: 1.5px solid var(--border);
  border-radius: 8px; padding: .38rem .9rem;
  font-size: .82rem; font-weight: 600; color: #374151;
  cursor: pointer; transition: all .2s;
}
.btn-show-filters:hover { border-color: var(--accent); color: var(--accent); }
.btn-post-dash {
  display: flex; align-items: center; gap: .4rem;
  background: var(--accent); color: white;
  border: none; border-radius: 8px; padding: .38rem 1rem;
  font-size: .82rem; font-weight: 700;
  cursor: pointer; transition: background .2s, transform .15s;
}
.btn-post-dash:hover { background: #0d5f58; transform: translateY(-1px); }

/* ── FILTER PANEL ── */
.filter-bar-wrap { display: flex; flex-direction: column; gap: 0; }
.filter-panel {
  background: white;
  border: 1px solid var(--border);
  border-top: none;
  border-radius: 0 0 var(--card-r) var(--card-r);
  padding: 1rem 1.2rem 1.2rem;
  box-shadow: 0 4px 12px rgba(0,0,0,.06);
  animation: slideDown .25s ease both;
  overflow: hidden;
}
.dash-topbar { border-radius: var(--card-r); }
.filter-bar-wrap .dash-topbar { border-radius: var(--card-r) var(--card-r) 0 0; }
/* When filter is hidden, restore full radius */
.filter-bar-wrap .dash-topbar:has(+ .filter-panel[style*="display:none"]),
.filter-bar-wrap .dash-topbar:only-child { border-radius: var(--card-r); }

@keyframes slideDown {
  from { opacity:0; transform:translateY(-10px); }
  to   { opacity:1; transform:translateY(0); }
}
.filter-row {
  display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;
}
.filter-field { display: flex; flex-direction: column; gap: 5px; min-width: 160px; }
.filter-label {
  font-size: .72rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .6px; color: #6b7280;
}
.filter-input, .filter-select {
  height: 36px; padding: 0 .75rem;
  border: 1.5px solid var(--border); border-radius: 8px;
  font-size: .87rem; color: #111827;
  background: white; outline: none; font-family: inherit;
  transition: border-color .2s, box-shadow .2s;
  cursor: pointer;
}
.filter-input:focus, .filter-select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(15,118,110,.1);
}
.filter-select { min-width: 180px; }
.filter-actions { flex-shrink: 0; }
.btn-apply-filter {
  display: flex; align-items: center; gap: .4rem;
  background: #2563eb; color: white;
  border: none; border-radius: 8px;
  height: 36px; padding: 0 1.2rem;
  font-size: .85rem; font-weight: 700;
  cursor: pointer; font-family: inherit;
  transition: background .2s, transform .15s;
  white-space: nowrap;
}
.btn-apply-filter:hover { background: #1d4ed8; transform: translateY(-1px); }
.btn-reset-filter {
  display: flex; align-items: center; gap: .4rem;
  background: #f9fafb; color: #374151;
  border: 1.5px solid var(--border); border-radius: 8px;
  height: 36px; padding: 0 1rem;
  font-size: .85rem; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: border-color .2s, color .2s, transform .15s;
  white-space: nowrap;
}
.btn-reset-filter:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-1px); }

/* Active filter badge on the show-filters button */
.btn-show-filters.active {
  background: #ecfdf5; border-color: #34d399; color: #065f46;
}

/* ── KPI GRID ── */
.kpi-grid-8 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
.kpi-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
.kpi-card {
  background: white;
  border: 1px solid var(--border);
  border-radius: var(--card-r);
  padding: 1.1rem 1.25rem;
  display: flex; align-items: flex-start; gap: .9rem;
  box-shadow: 0 2px 8px rgba(15,118,110,.07);
  transition: transform .2s, box-shadow .2s;
  position: relative; overflow: hidden;
  opacity: 0; animation: cardPop .4s ease forwards;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 22px rgba(15,118,110,.16); }
.kpi-card:nth-child(1){animation-delay:.10s} .kpi-card:nth-child(2){animation-delay:.16s}
.kpi-card:nth-child(3){animation-delay:.22s} .kpi-card:nth-child(4){animation-delay:.28s}
.kpi-card:nth-child(5){animation-delay:.34s} .kpi-card:nth-child(6){animation-delay:.40s}
.kpi-card:nth-child(7){animation-delay:.46s} .kpi-card:nth-child(8){animation-delay:.52s}
@keyframes cardPop {
  from { opacity:0; transform:translateY(16px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

.kpi-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; transition: transform .2s;
}
.kpi-card:hover .kpi-icon { transform: scale(1.12) rotate(-5deg); }
.kpi-icon.purple  { background: #ede9fe; color: #7c3aed; }
.kpi-icon.green   { background: #d1fae5; color: #059669; }
.kpi-icon.red     { background: #fee2e2; color: #dc2626; }
.kpi-icon.amber   { background: #fef3c7; color: #d97706; }
.kpi-icon.blue    { background: #dbeafe; color: #2563eb; }
.kpi-icon.teal    { background: #ccfbf1; color: #0f766e; }
.kpi-icon.orange  { background: #ffedd5; color: #ea580c; }
.kpi-icon.indigo  { background: #e0e7ff; color: #4338ca; }

.kpi-body { flex: 1; min-width: 0; }
.kpi-label  { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #9ca3af; margin-bottom: .18rem; }
.kpi-value  { font-size: 1.55rem; font-weight: 800; color: #111827; line-height: 1; }
.kpi-sub    { font-size: .73rem; color: #6b7280; margin-top: .3rem; }
.kpi-sub.red    { color: #dc2626; }
.kpi-sub.green  { color: #059669; }
.kpi-sub.amber  { color: #d97706; }
.kpi-sub.blue   { color: #2563eb; }

/* accent corner strip */
.kpi-card::before {
  content:''; position:absolute; top:0; left:0; width:3px; height:100%; border-radius:3px 0 0 3px;
  background: var(--kpi-color, #0f766e);
}

/* ── CHARTS + RIGHT PANEL ROW ── */
.charts-row {
  display: grid;
  grid-template-columns: 1fr 260px;
  gap: 16px;
}
.chart-box {
  background: white; border-radius: var(--card-r);
  border: 1px solid var(--border);
  padding: 1.3rem 1.5rem;
  box-shadow: 0 2px 8px rgba(15,118,110,.07);
  opacity: 0; animation: cardPop .45s .55s ease forwards;
}
.chart-box h3 { font-size: .88rem; font-weight: 700; color: #374151; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
.chart-box h3 i { color: var(--accent); }
.chart-box h3 small { font-weight: 400; color: #9ca3af; font-size: .74rem; margin-left: auto; }
.chart-wrapper { position: relative; }

/* Donut + right panel */
.right-col { display: flex; flex-direction: column; gap: 14px; }
.donut-box {
  background: white; border-radius: var(--card-r);
  border: 1px solid var(--border);
  padding: 1.2rem 1rem;
  box-shadow: 0 2px 8px rgba(15,118,110,.07);
  display: flex; flex-direction: column; align-items: center; gap: .8rem;
  opacity: 0; animation: cardPop .45s .6s ease forwards;
}
.donut-box h3 { font-size: .8rem; font-weight: 700; color: #374151; align-self: flex-start; }
.donut-legend { display: flex; flex-wrap: wrap; gap: 8px 14px; justify-content: center; margin-top: .2rem; }
.d-leg { display: flex; align-items: center; gap: 5px; font-size: .72rem; color: #6b7280; }
.d-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

.today-panel {
  background: white; border-radius: var(--card-r);
  border: 1px solid var(--border);
  padding: 1.1rem 1.2rem;
  box-shadow: 0 2px 8px rgba(15,118,110,.07);
  flex: 1;
  opacity: 0; animation: cardPop .45s .65s ease forwards;
}
.today-panel h3 { font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: .85rem; }
.today-row {
  display: flex; justify-content: space-between;
  padding: .45rem 0; border-bottom: 1px solid #f3f4f6;
  font-size: .8rem;
}
.today-row:last-child { border-bottom: none; }
.today-row span { color: #6b7280; }
.today-row b { color: #111827; }
.today-row b.green { color: #059669; }
.today-row b.red   { color: #dc2626; }
.today-row b.amber { color: #d97706; }

/* ── BOTTOM CHARTS ── */
.bottom-charts { display: flex; flex-direction: column; gap: 16px; }

/* no-leave notice inside donut box */
.no-leave-notice {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: 1rem;
  text-align: center;
}
.no-leave-notice i { font-size: 1.8rem; color: #059669; }
.no-leave-notice p { font-size: .78rem; color: #6b7280; }

/* responsive */
@media (max-width: 1100px) { .kpi-grid-8 { grid-template-columns: repeat(2,1fr); } .kpi-grid-4 { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 900px)  { .charts-row { grid-template-columns: 1fr; } .right-col { flex-direction: row; } }
@media (max-width: 768px)  { .kpi-grid-8 { grid-template-columns: 1fr; } .kpi-grid-4 { grid-template-columns: 1fr; } .right-col { flex-direction: column; } }
</style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

  <!-- ========== SIDEBAR (unchanged — from desh.css) ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="logo">
      <img src="assets/logo/images.png" alt="MCL Logo">
    </div>
    <nav>
      <a href="dashboard.php" class="menu active">
        <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
        <span>Dashboard</span>
      </a>
      <a href="user.php" class="menu">
        <span class="icon"><i class="fa-solid fa-users"></i></span>
        <span>Add Users</span>
      </a>
      <a href="employees.php" class="menu">
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
      <a href="admin/wage_report.php" class="menu">
        <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
        <span>Wage Report</span>
      </a>
      <a href="admin/monthly_attendance.php" class="menu">
        <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
        <span>Monthly Attendance</span>
      </a>
      <a href="logout.php" class="menu logout">
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
      <a href="admin_profile.php" title="My Profile" style="text-decoration:none;">
        <div style="width:40px;height:40px;border-radius:50%;background:#0f766e;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s,box-shadow .2s;flex-shrink:0;" onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 4px 14px rgba(15,118,110,0.35)'" onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
          </svg>
        </div>
      </a>
    </header>

    <!-- PAGE CONTENT -->
    <div class="page-content">

      <!-- ── FILTER BAR ── -->
      <div class="filter-bar-wrap">

        <!-- Always-visible row -->
        <div class="dash-topbar">
          <div class="topbar-left">
            <button class="btn-show-filters" id="filterToggleBtn">
              <i class="fa-solid fa-sliders"></i>
              <span id="filterBtnLabel"><?= $filterIsSet ? 'Hide Filters' : 'Show Filters' ?></span>
            </button>
            <span class="date-chip">
              <i class="fa-regular fa-calendar"></i> Today: <?= $todayDisp ?>
            </span>
          </div>
          <button class="btn-post-dash" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Dashboard
          </button>
        </div>

        <!-- Collapsible filter panel -->
        <div class="filter-panel" id="filterPanel" <?= $filterIsSet ? '' : 'style="display:none;"' ?>>
          <form method="GET" action="dashboard.php" id="filterForm">
            <div class="filter-row">

              <!-- Attendance Date -->
              <div class="filter-field">
                <label class="filter-label" for="att_date">Attendance Date</label>
                <input
                  type="date"
                  id="att_date"
                  name="att_date"
                  class="filter-input"
                  value="<?= htmlspecialchars($filterDate) ?>"
                >
              </div>

              <!-- Site -->
              <div class="filter-field" style="flex:1.5;">
                <label class="filter-label" for="site_code">Site</label>
                <select name="site_code" id="site_code" class="filter-select">
                  <option value="">All Sites</option>
                  <?php foreach ($allSites as $site): ?>
                    <option
                      value="<?= htmlspecialchars($site['SiteCode']) ?>"
                      <?= $filterSite === $site['SiteCode'] ? 'selected' : '' ?>
                    >
                      <?= htmlspecialchars($site['SiteCode'] . ' – ' . $site['SiteName']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Apply button -->
              <div class="filter-field filter-actions">
                <label class="filter-label">&nbsp;</label>
                <button type="submit" class="btn-apply-filter">
                  <i class="fa-solid fa-check"></i> Apply Filters
                </button>
              </div>

              <!-- Reset button -->
              <div class="filter-field filter-actions">
                <label class="filter-label">&nbsp;</label>
                <a href="dashboard.php" class="btn-reset-filter">
                  <i class="fa-solid fa-rotate-left"></i> Reset to Today
                </a>
              </div>

            </div>
          </form>
        </div>

      </div><!-- /.filter-bar-wrap -->


      <!-- ── ROW 1: 8 KPI CARDS ── -->
      <div class="kpi-grid-8">

        <!-- Total Employees -->
        <div class="kpi-card" style="--kpi-color:#7c3aed;">
          <div class="kpi-icon purple"><i class="fa-solid fa-users"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Total Employees</div>
            <div class="kpi-value"><?= number_format($totalEmp) ?></div>
            <div class="kpi-sub">All sites</div>
          </div>
        </div>

        <!-- Today Present -->
        <div class="kpi-card" style="--kpi-color:#059669;">
          <div class="kpi-icon green"><i class="fa-solid fa-user-check"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Today Present</div>
            <div class="kpi-value"><?= number_format($todayPresent) ?></div>
            <div class="kpi-sub green"><?= $attRate ?>% Rate</div>
          </div>
        </div>

        <!-- Today Absent -->
        <div class="kpi-card" style="--kpi-color:#dc2626;">
          <div class="kpi-icon red"><i class="fa-solid fa-user-xmark"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Today Absent</div>
            <div class="kpi-value"><?= number_format($todayAbsent) ?></div>
            <div class="kpi-sub red"><?= $todayAbsent > 0 ? 'Not today absent' : 'No absents today' ?></div>
          </div>
        </div>

        <!-- On Leave Today -->
        <div class="kpi-card" style="--kpi-color:#d97706;">
          <div class="kpi-icon amber"><i class="fa-regular fa-calendar-xmark"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">On Leave Today</div>
            <div class="kpi-value"><?= number_format($todayLeave) ?></div>
            <div class="kpi-sub">Leave count</div>
          </div>
        </div>

        <!-- Today Overtime -->
        <div class="kpi-card" style="--kpi-color:#ea580c;">
          <div class="kpi-icon orange"><i class="fa-solid fa-clock"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Today Overtime</div>
            <div class="kpi-value"><?= number_format($todayOT) ?></div>
            <div class="kpi-sub">Overtime</div>
          </div>
        </div>

        <!-- Total Attendance Uploads -->
        <div class="kpi-card" style="--kpi-color:#2563eb;">
          <div class="kpi-icon blue"><i class="fa-solid fa-upload"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Total Att. Uploads</div>
            <div class="kpi-value"><?= number_format($totalUploads) ?></div>
            <div class="kpi-sub blue"><?= $uploadPct ?>% Rate</div>
          </div>
        </div>

        <!-- Pending Attendance -->
        <div class="kpi-card" style="--kpi-color:#dc2626;">
          <div class="kpi-icon red"><i class="fa-regular fa-clock"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Pending Att. Uploads</div>
            <div class="kpi-value"><?= number_format($pendingAtt) ?></div>
            <div class="kpi-sub red"><?= $pendingPct ?>% Pending</div>
          </div>
        </div>

        <!-- Total Active Users -->
        <div class="kpi-card" style="--kpi-color:#4338ca;">
          <div class="kpi-icon indigo"><i class="fa-solid fa-circle-user"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Total Activated Users</div>
            <div class="kpi-value"><?= number_format($totalUsers) ?></div>
            <div class="kpi-sub">Toward quota</div>
          </div>
        </div>

      </div>

      <!-- ── ROW 2: 4 KPI CARDS ── -->
      <div class="kpi-grid-4">

        <!-- Monthly Reports -->
        <div class="kpi-card" style="--kpi-color:#0f766e; animation-delay:.58s">
          <div class="kpi-icon teal"><i class="fa-solid fa-file-lines"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Monthly Reports</div>
            <div class="kpi-value"><?= number_format($monthlyReports) ?></div>
            <div class="kpi-sub">Counted</div>
          </div>
        </div>

        <!-- Approved Monthly Report -->
        <div class="kpi-card" style="--kpi-color:#059669; animation-delay:.64s">
          <div class="kpi-icon green"><i class="fa-solid fa-circle-check"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Approved Monthly Report</div>
            <div class="kpi-value"><?= number_format($approvedReports) ?></div>
            <div class="kpi-sub green">Total Approved</div>
          </div>
        </div>

        <!-- LPP Generated -->
        <div class="kpi-card" style="--kpi-color:#d97706; animation-delay:.70s">
          <div class="kpi-icon amber"><i class="fa-solid fa-file-invoice-dollar"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">LPP Generated</div>
            <div class="kpi-value"><?= number_format($lppGenerated) ?></div>
            <div class="kpi-sub">Total LPPs</div>
          </div>
        </div>

        <!-- Total LPP Paid -->
        <div class="kpi-card" style="--kpi-color:#059669; animation-delay:.76s">
          <div class="kpi-icon green"><i class="fa-solid fa-indian-rupee-sign"></i></div>
          <div class="kpi-body">
            <div class="kpi-label">Total LPP Paid</div>
            <div class="kpi-value">₹<?= number_format($lppPaidAmt, 2) ?></div>
            <div class="kpi-sub">Paid Amount</div>
          </div>
        </div>

      </div>

      <!-- ── CHARTS ROW ── -->
      <div class="charts-row">

        <!-- Attendance Trend (combo bar+line) -->
        <div class="chart-box">
          <h3>
            <i class="fa-solid fa-chart-line"></i> Attendance Trend
            <small>Last 12 months (<?= date('d M Y') ?>)</small>
          </h3>
          <div class="chart-wrapper" style="height:260px;">
            <canvas id="trendChart"></canvas>
          </div>
        </div>

        <!-- Right Column: Donut + Today Panel -->
        <div class="right-col">

          <!-- Donut -->
          <div class="donut-box">
            <h3><i class="fa-solid fa-chart-pie" style="color:var(--accent);margin-right:4px;"></i> Today's Attendance</h3>
            <?php if ($todayAbsent === 0 && $todayPresent === 0 && $todayLeave === 0): ?>
              <div class="no-leave-notice">
                <i class="fa-solid fa-circle-check"></i>
                <p>No employees on leave today</p>
              </div>
            <?php else: ?>
              <div class="chart-wrapper" style="height:150px;width:150px;">
                <canvas id="donutChart"></canvas>
              </div>
              <div class="donut-legend">
                <span class="d-leg"><span class="d-dot" style="background:#10b981;"></span>Present <?= $todayPresent ?></span>
                <span class="d-leg"><span class="d-dot" style="background:#ef4444;"></span>Absent <?= $todayAbsent ?></span>
                <span class="d-leg"><span class="d-dot" style="background:#f59e0b;"></span>Leave <?= $todayLeave ?></span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Today Summary -->
          <div class="today-panel">
            <h3><i class="fa-regular fa-calendar" style="color:var(--accent);margin-right:5px;"></i>Today: <?= $todayDisp ?></h3>
            <div class="today-row"><span>Present</span><b class="green"><?= $todayPresent ?></b></div>
            <div class="today-row"><span>Absent</span><b class="red"><?= $todayAbsent ?></b></div>
            <div class="today-row"><span>On Leave</span><b class="amber"><?= $todayLeave ?></b></div>
            <div class="today-row"><span>Pending Att. Upload</span><b class="red"><?= $pendingAtt ?></b></div>
            <div class="today-row"><span>Attendance Rate</span><b class="green"><?= $attRate ?>%</b></div>
            <div class="today-row"><span>Total Employees</span><b><?= number_format($totalEmp) ?></b></div>
          </div>

        </div>
      </div>

      <!-- ── MONTHLY ATTENDANCE RECORD CHART ── -->
      <div class="chart-box" style="animation-delay:.7s;">
        <h3>
          <i class="fa-solid fa-calendar-days"></i> Monthly Attendance Record
          <small>Last 12 months <?= date('Y') ?></small>
        </h3>
        <div class="chart-wrapper" style="height:240px;">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>

      <!-- ── MONTHLY LPP AMOUNT CHART ── -->
      <div class="chart-box" style="animation-delay:.78s;">
        <h3>
          <i class="fa-solid fa-indian-rupee-sign"></i> Monthly LPP Amount
          <span style="margin-left:auto;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:20px;padding:.15rem .75rem;font-size:.72rem;font-weight:700;">₹ Total Amount</span>
        </h3>
        <div class="chart-wrapper" style="height:220px;">
          <canvas id="lppChart"></canvas>
        </div>
      </div>

    </div><!-- /.page-content -->
  </main>
</div>

<script>
/* ── Sidebar toggle ── */
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

/* ── Theme toggle ── */
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = themeToggle.querySelector('i');
function applyTheme(dark) {
  if (dark) { document.body.classList.add('dark'); themeToggle.classList.add('active'); themeIcon.className='fa-solid fa-sun'; }
  else       { document.body.classList.remove('dark'); themeToggle.classList.remove('active'); themeIcon.className='fa-solid fa-moon'; }
}
applyTheme(localStorage.getItem('theme') === 'dark');
themeToggle.addEventListener('click', () => {
  const d = document.body.classList.contains('dark');
  applyTheme(!d); localStorage.setItem('theme', !d ? 'dark' : 'light');
});

/* ── Filter panel toggle ── */
const filterBtn   = document.getElementById('filterToggleBtn');
const filterPanel = document.getElementById('filterPanel');
const filterLabel = document.getElementById('filterBtnLabel');
const topbarEl    = filterBtn ? filterBtn.closest('.dash-topbar') : null;

function isFilterOpen() {
  return filterPanel && filterPanel.style.display !== 'none';
}
function setFilterOpen(open) {
  if (!filterPanel) return;
  if (open) {
    filterPanel.style.display = '';
    filterLabel.textContent = 'Hide Filters';
    filterBtn.classList.add('active');
    if (topbarEl) { topbarEl.style.borderRadius = 'var(--card-r) var(--card-r) 0 0'; }
  } else {
    filterPanel.style.display = 'none';
    filterLabel.textContent = 'Show Filters';
    filterBtn.classList.remove('active');
    if (topbarEl) { topbarEl.style.borderRadius = 'var(--card-r)'; }
  }
}

if (filterBtn) {
  // Apply correct initial state
  setFilterOpen(isFilterOpen());

  filterBtn.addEventListener('click', () => setFilterOpen(!isFilterOpen()));
}

/* ── Chart data from PHP ── */
const labels    = <?= json_encode($chartLabels) ?>;
const cPresent  = <?= json_encode($chartPresent) ?>;
const cOT       = <?= json_encode($chartOT) ?>;
const cLPP      = <?= json_encode($chartLPP) ?>;

const FONT = { family:"Segoe UI,sans-serif", size:11 };
Chart.defaults.font = FONT;

/* ── 1. Attendance Trend (Bar + Line) ── */
new Chart(document.getElementById('trendChart'), {
  data: {
    labels,
    datasets: [
      {
        type: 'bar',
        label: 'Present Count (Days)',
        data: cPresent,
        backgroundColor: 'rgba(16,185,129,.55)',
        borderColor: '#059669',
        borderWidth: 1,
        borderRadius: 5,
        yAxisID: 'y',
      },
      {
        type: 'line',
        label: 'Total Overtime (Hrs)',
        data: cOT,
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,.12)',
        borderWidth: 2.2,
        tension: 0.4,
        fill: false,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#f59e0b',
        yAxisID: 'y2',
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode:'index', intersect:false },
    plugins: { legend: { labels: { usePointStyle:true, padding:14 } } },
    scales: {
      y:  { beginAtZero:true, grid:{ color:'rgba(0,0,0,.04)' }, ticks:{ font:FONT } },
      y2: { beginAtZero:true, position:'right', grid:{ drawOnChartArea:false }, ticks:{ font:FONT } },
      x:  { grid:{ display:false }, ticks:{ font:FONT, maxRotation:0 } }
    }
  }
});

/* ── 2. Donut ── */
<?php if ($todayPresent > 0 || $todayAbsent > 0 || $todayLeave > 0): ?>
new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: {
    labels: ['Present','Absent','Leave'],
    datasets: [{
      data: [<?= $donutPresent ?>, <?= $donutAbsent ?>, <?= $donutLeave ?>],
      backgroundColor: ['#10b981','#ef4444','#f59e0b'],
      borderWidth: 2, borderColor: '#fff',
      hoverOffset: 6,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '70%',
    plugins: { legend: { display:false } }
  }
});
<?php endif; ?>

/* ── 3. Monthly Attendance Record ── */
new Chart(document.getElementById('monthlyChart'), {
  data: {
    labels,
    datasets: [
      {
        type: 'bar',
        label: 'Present Count (Days)',
        data: cPresent,
        backgroundColor: 'rgba(16,185,129,.55)',
        borderColor: '#059669',
        borderWidth: 1,
        borderRadius: 4,
        yAxisID: 'y',
      },
      {
        type: 'line',
        label: 'Total Overtime (Hrs)',
        data: cOT,
        borderColor: '#f59e0b',
        borderWidth: 2,
        tension: 0.4,
        fill: false,
        pointRadius: 3,
        pointHoverRadius: 5,
        pointBackgroundColor: '#f59e0b',
        yAxisID: 'y2',
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode:'index', intersect:false },
    plugins: { legend: { labels: { usePointStyle:true, padding:14 } } },
    scales: {
      y:  { beginAtZero:true, grid:{ color:'rgba(0,0,0,.04)' }, ticks:{ font:FONT } },
      y2: { beginAtZero:true, position:'right', grid:{ drawOnChartArea:false }, ticks:{ font:FONT } },
      x:  { grid:{ display:false }, ticks:{ font:FONT, maxRotation:45 } }
    }
  }
});

/* ── 4. Monthly LPP Amount ── */
new Chart(document.getElementById('lppChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'Total Amount Paid',
      data: cLPP,
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37,99,235,.07)',
      borderWidth: 2.2,
      tension: 0.4,
      fill: true,
      pointRadius: 4,
      pointHoverRadius: 6,
      pointBackgroundColor: '#2563eb',
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { labels: { usePointStyle:true, padding:14 } },
      tooltip: {
        callbacks: {
          label: ctx => '₹' + Number(ctx.raw).toLocaleString('en-IN', {minimumFractionDigits:2})
        }
      }
    },
    scales: {
      y: {
        beginAtZero:true,
        grid:{ color:'rgba(0,0,0,.04)' },
        ticks: {
          font:FONT,
          callback: v => '₹' + Number(v).toLocaleString('en-IN')
        }
      },
      x: { grid:{ display:false }, ticks:{ font:FONT, maxRotation:45 } }
    }
  }
});
</script>
</body>
</html>