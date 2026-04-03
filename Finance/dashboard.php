<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Security Billing Management Portal – Finance Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
:root {
  --primary: #0f766e;
  --primary-dark: #0d5f58;
  --sidebar-width: 270px;
  --bg: #f5f5f5;
  --white: #ffffff;
  --border: #e5e7eb;
  --text: #1f2937;
  --muted: #6b7280;
  --role: #dc2626;
  --role-light: #fef2f2;
  --role-border: #fca5a5;
}
html { scroll-behavior: smooth; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

/* ─── LAYOUT ─── */
.dashboard-layout { display: grid; grid-template-columns: var(--sidebar-width) 1fr; min-height: 100vh; }

/* ─── SIDEBAR (exact match to PHP file) ─── */
.sidebar {
  background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%);
  color: white; display: flex; flex-direction: column;
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
  /* Fallback when image missing: show styled text */
}
.mcl-logo-fallback {
  background: white; padding: 10px 20px; border-radius: 10px;
  display: flex; align-items: center; gap: 8px;
}
.mcl-logo-fallback .coin {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg,#0f766e,#0a4f49);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 900; color: white; letter-spacing: 0.5px;
}
.mcl-logo-fallback span { font-size: 1.4rem; font-weight: 800; color: #0f766e; letter-spacing: 1.5px; }
.sidebar-nav { list-style: none; padding: 1rem 0; flex: 1; }
.sidebar-nav li { margin: 0.25rem 1rem; }
.nav-link {
  display: flex; align-items: center; gap: 0.9rem;
  padding: 0.85rem 1.1rem; color: rgba(255,255,255,0.88);
  text-decoration: none; border-radius: 12px;
  transition: all 0.2s; font-weight: 500; font-size: 0.95rem; cursor: pointer;
}
.nav-link:hover { background: rgba(255,255,255,0.15); color: #fff; }
.nav-link.active { background: rgba(255,255,255,0.22); color: #fff; font-weight: 600; }
.nav-link i { font-size: 1.05rem; width: 22px; text-align: center; opacity: 0.9; }
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
  background: var(--role-light); color: var(--role);
  border: 1.5px solid var(--role-border); border-radius: 20px;
  padding: 0.3rem 0.9rem; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.5px;
}
.header-icon {
  width: 40px; height: 40px; border-radius: 50%;
  background: #f3f4f6; display: flex; align-items: center; justify-content: center;
  cursor: pointer; position: relative; color: #6b7280; font-size: 1rem; border: 1px solid var(--border);
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

/* ─── FILTER BAR ─── */
.filter-bar {
  background: white; border-radius: 14px; padding: 1.5rem 2rem;
  border: 1px solid var(--border); box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;
}
.filter-bar-left { display: flex; align-items: flex-end; gap: 1.25rem; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.4rem; }
.filter-label {
  font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.6px; color: var(--muted);
  display: flex; align-items: center; gap: 5px;
}
.filter-label i { color: var(--primary); font-size: 0.8rem; }
.filter-input, .filter-select-el {
  padding: 0.7rem 1rem; border: 1.5px solid var(--border); border-radius: 10px;
  font-size: 0.9rem; color: var(--text); background: #f9fafb;
  outline: none; font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s;
  min-width: 200px;
}
.filter-input:focus, .filter-select-el:focus {
  border-color: var(--primary); background: white;
  box-shadow: 0 0 0 3px rgba(15,118,110,0.1);
}
.filter-input[type="month"] { cursor: pointer; }
.reporting-period {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem; color: var(--muted);
  background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 8px; padding: 0.5rem 1rem;
}
.reporting-period i { color: var(--primary); }
.reporting-period strong { color: var(--primary); font-weight: 700; }

.btn-load {
  display: inline-flex; align-items: center; gap: 0.6rem;
  padding: 0.75rem 1.75rem; border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white; border: none; font-size: 0.92rem; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
  box-shadow: 0 4px 14px rgba(37,99,235,0.3);
  font-family: inherit; white-space: nowrap; letter-spacing: 0.3px;
}
.btn-load:hover { background: linear-gradient(135deg,#1d4ed8,#1e40af); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,0.38); }
.btn-load i { font-size: 0.95rem; }

/* ─── CONTENT AREA ─── */
.content-area { display: flex; flex-direction: column; gap: 1.5rem; }

/* ─── STATE: NO RECORD ─── */
.no-record-card {
  background: white; border-radius: 16px; border: 1px solid var(--border);
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  padding: 5rem 2rem; text-align: center;
  animation: fadeUp 0.4s ease both;
}
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
.no-record-icon {
  width: 80px; height: 80px; border-radius: 50%;
  background: #f3f4f6; margin: 0 auto 1.5rem;
  display: flex; align-items: center; justify-content: center;
  position: relative;
}
.no-record-icon i { font-size: 2rem; color: #9ca3af; }
.no-record-icon .x-badge {
  position: absolute; bottom: -2px; right: -2px;
  width: 26px; height: 26px; border-radius: 50%;
  background: #ef4444; color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem; font-weight: 900; border: 2px solid white;
}
.no-record-title { font-size: 1.35rem; font-weight: 800; color: var(--text); margin-bottom: 0.75rem; }
.no-record-sub { font-size: 0.9rem; color: var(--muted); max-width: 520px; margin: 0 auto; line-height: 1.65; }
.no-record-sub .highlight { color: #b45309; font-weight: 700; }
.no-record-sub .highlight2 { color: var(--primary); font-weight: 600; }

/* ─── STATE: DATA LOADED ─── */
.data-view { display: none; flex-direction: column; gap: 2rem; }
.data-view.visible { display: flex; animation: fadeUp 0.4s ease both; }

/* KPI row */
.kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.75rem; }
.kpi-card {
  background: white; border: 1px solid var(--border); border-radius: 16px;
  padding: 1.75rem; display: flex; align-items: flex-start; gap: 1.1rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s;
  animation: fadeUp 0.4s ease both;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.kpi-card:nth-child(1){ animation-delay:.05s; }
.kpi-card:nth-child(2){ animation-delay:.1s;  }
.kpi-card:nth-child(3){ animation-delay:.15s; }
.kpi-card:nth-child(4){ animation-delay:.2s;  }
.kpi-ico {
  width: 50px; height: 50px; border-radius: 13px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
}
.kpi-ico.blue   { background:#dbeafe; color:#2563eb; }
.kpi-ico.teal   { background:#ccfbf1; color:#0f766e; }
.kpi-ico.amber  { background:#fef3c7; color:#d97706; }
.kpi-ico.purple { background:#ede9fe; color:#7c3aed; }
.kpi-body .lbl { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--muted); margin-bottom: 8px; }
.kpi-body .val { font-size: 2.1rem; font-weight: 800; line-height: 1; color: var(--text); }
.kpi-body .sub { font-size: 0.75rem; color: var(--muted); margin-top: 6px; }
.kpi-body .sub .up { color: #16a34a; font-weight: 600; }

/* Table section */
.section {
  background: white; border: 1px solid var(--border); border-radius: 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); overflow: hidden;
}
.section-head {
  padding: 1.2rem 1.75rem; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
  background: #fafafa;
}
.section-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 700; color: var(--text); }
.section-title i { color: var(--primary); }
.sbadge {
  background: rgba(15,118,110,0.1); color: var(--primary);
  font-size: 0.72rem; font-weight: 700; padding: 2px 10px;
  border-radius: 20px; margin-left: 6px;
}
.section-actions { display: flex; gap: 8px; }
.btn-sm {
  padding: 6px 14px; border-radius: 8px; border: 1.5px solid var(--border);
  background: white; color: var(--muted); font-size: 0.8rem; font-weight: 600;
  cursor: pointer; display: flex; align-items: center; gap: 5px;
  font-family: inherit; transition: all 0.2s;
}
.btn-sm:hover { border-color: var(--primary); color: var(--primary); }
.btn-sm.primary { background: rgba(15,118,110,0.08); border-color: var(--primary); color: var(--primary); }

table { width: 100%; border-collapse: collapse; }
thead th {
  background: linear-gradient(135deg,#0f766e,#0d5f58);
  color: white; font-size: 0.76rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
  padding: 14px 20px; text-align: left; white-space: nowrap;
}
tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f9fafb; }
tbody tr:nth-child(even) { background: #fafafa; }
td { padding: 15px 20px; font-size: 0.86rem; }

.status-chip {
  display: inline-flex; align-items: center; gap: 5px;
  border-radius: 20px; padding: 4px 12px; font-size: 0.78rem; font-weight: 700;
}
.status-chip.pending  { background:#fef3c7; color:#d97706; border:1px solid #fde68a; }
.status-chip.approved { background:#d1fae5; color:#059669; border:1px solid #a7f3d0; }
.status-chip.forwarded{ background:#dbeafe; color:#2563eb; border:1px solid #bfdbfe; }

.amt-cell { font-weight: 700; color: var(--text); font-size: 0.9rem; }

.pag-row {
  padding: 1.1rem 1.75rem; border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  font-size: 0.82rem; color: var(--muted); background: #fafafa;
}
.pag-btns { display: flex; gap: 6px; }
.pag-btn {
  min-width: 34px; height: 34px; border-radius: 8px;
  border: 1.5px solid var(--border); background: white;
  cursor: pointer; font-size: 0.82rem; font-weight: 600; color: var(--muted);
  display: flex; align-items: center; justify-content: center; padding: 0 10px;
  transition: all 0.2s; font-family: inherit;
}
.pag-btn:hover { border-color: var(--primary); color: var(--primary); }
.pag-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

/* Responsive */
@media (max-width: 768px) {
  .dashboard-layout { grid-template-columns: 1fr; }
  .sidebar { position: fixed; left:0; top:0; height:100vh; transform:translateX(-100%); z-index:200; transition:transform 0.3s cubic-bezier(0.4,0,0.2,1); }
  .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
  .sidebar-close { display: flex; }
  .hamburger-btn { display: flex; }
  .main-content { padding: 1rem; }
  .kpi-row { grid-template-columns: 1fr 1fr; }
  .filter-bar { flex-direction: column; align-items: stretch; }
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">

  <!-- ═══ SIDEBAR (exact match to PHP) ═══ -->
  <aside class="sidebar" id="sidebar">
    <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
    <div class="sidebar-logo">
      <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
    </div>
    <ul class="sidebar-nav">
      <li><a class="nav-link active" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
      <li><a class="nav-link" href="monthlylpp.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Monthly LPP</span></a></li>
      <!-- <li><a class="nav-link logout-link" href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li> -->
    </ul>
  </aside>

  <!-- ═══ MAIN ═══ -->
  <main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h2>Security Billing Management Portal</h2>
      </div>
      <div class="topbar-right">
        <span class="role-badge"><i class="fa-solid fa-user-tie"></i> Finance</span>
        <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
        <a href="profile.php" style="text-decoration:none;">
          <div class="user-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
            </svg>
          </div>
        </a>
      </div>
    </header>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <div class="filter-bar-left">
        <div class="filter-group">
          <label class="filter-label"><i class="fa-regular fa-calendar"></i> Select Billing Month &amp; Year</label>
          <input type="month" class="filter-input" id="billingMonth" value="2026-01"/>
        </div>
        <button class="btn-load" id="loadBtn" onclick="loadData()">
          <i class="fa-solid fa-filter"></i> Load Data
        </button>
      </div>
      <div class="reporting-period" id="reportingPeriod">
        <i class="fa-solid fa-calendar-check"></i>
        Reporting Period: <strong id="reportingLabel">February 2026</strong>
      </div>
    </div>

    <!-- NO RECORD STATE (default) -->
    <div class="no-record-card" id="noRecordCard">
      <div class="no-record-icon">
        <i class="fa-solid fa-folder-open"></i>
        <div class="x-badge"><i class="fa-solid fa-xmark" style="font-size:0.55rem;"></i></div>
      </div>
      <div class="no-record-title">No Record Found</div>
      <div class="no-record-sub">
        <span class="highlight">Waiting for SDHOD Action:</span>
        The monthly LPP report has not been forwarded to your department yet.
        Please check back once the SDHOD completes the verification and
        <span class="highlight2">forwards it for settlement</span>.
      </div>
    </div>

    <!-- DATA VIEW (shown after Load Data) -->
    <div class="data-view" id="dataView">

      <!-- KPI Cards -->
      <div class="kpi-row">
        <div class="kpi-card">
          <div class="kpi-ico blue"><i class="fa-solid fa-file-invoice-dollar"></i></div>
          <div class="kpi-body">
            <div class="lbl">Total Bills</div>
            <div class="val" id="kpiBills">48</div>
            <div class="sub">For selected period</div>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-ico teal"><i class="fa-solid fa-circle-check"></i></div>
          <div class="kpi-body">
            <div class="lbl">Approved</div>
            <div class="val" id="kpiApproved">31</div>
            <div class="sub"><span class="up">↑ 12%</span> vs last month</div>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-ico amber"><i class="fa-solid fa-clock"></i></div>
          <div class="kpi-body">
            <div class="lbl">Pending</div>
            <div class="val" id="kpiPending">17</div>
            <div class="sub">Awaiting action</div>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-ico purple"><i class="fa-solid fa-indian-rupee-sign"></i></div>
          <div class="kpi-body">
            <div class="lbl">Total Amount</div>
            <div class="val" id="kpiAmount">₹24.6L</div>
            <div class="sub">Gross billing</div>
          </div>
        </div>
      </div>

      <!-- LPP Table -->
      <div class="section">
        <div class="section-head">
          <div class="section-title">
            <i class="fa-solid fa-table-list"></i>
            Monthly LPP Report
            <span class="sbadge" id="periodBadge">Feb 2026</span>
          </div>
          <div class="section-actions">
            <button class="btn-sm"><i class="fa-solid fa-file-excel"></i> Export</button>
            <button class="btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            <button class="btn-sm primary"><i class="fa-solid fa-paper-plane"></i> Forward</button>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>S.N.</th>
              <th>Site Code</th>
              <th>Site Name</th>
              <th>Bill Month</th>
              <th>Total Staff</th>
              <th>Amount (₹)</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="lppTableBody">
            <!-- rows injected by JS -->
          </tbody>
        </table>

        <div class="pag-row">
          <span id="pagInfo">Showing 1–8 of 48 entries</span>
          <div class="pag-btns">
            <button class="pag-btn" disabled><i class="fa-solid fa-chevron-left"></i></button>
            <button class="pag-btn active">1</button>
            <button class="pag-btn">2</button>
            <button class="pag-btn">3</button>
            <button class="pag-btn">4</button>
            <button class="pag-btn">5</button>
            <button class="pag-btn"><i class="fa-solid fa-chevron-right"></i></button>
          </div>
        </div>
      </div>

    </div><!-- /data-view -->

  </main>
</div>

<script>
  /* ── Sidebar ── */
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  document.getElementById('hamburgerBtn').addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('active'); });
  document.getElementById('sidebarClose').addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
  overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
      if (this.classList.contains('logout-link')) return;
      document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  /* ── Reporting period updates live with month picker ── */
  const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  document.getElementById('billingMonth').addEventListener('change', function() {
    updateReportingPeriod(this.value);
  });

  function updateReportingPeriod(val) {
    if (!val) return;
    const [y, m] = val.split('-');
    // Reporting period = next month (billing month + 1)
    const date = new Date(parseInt(y), parseInt(m) - 1 + 1, 1);
    const label = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
    document.getElementById('reportingLabel').textContent = label;
  }
  updateReportingPeriod(document.getElementById('billingMonth').value);

  /* ── Sample LPP data ── */
  const sampleRows = [
    { sn:1, code:'BLJR', name:'Balajore',           month:'Jan 2026', staff:42, amount:'₹1,84,200', status:'approved'  },
    { sn:2, code:'BBSR', name:'Bhubaneswar HQ',     month:'Jan 2026', staff:78, amount:'₹3,41,600', status:'approved'  },
    { sn:3, code:'BRGT', name:'Burgarh',             month:'Jan 2026', staff:35, amount:'₹1,53,300', status:'pending'   },
    { sn:4, code:'JHRS', name:'Jharsuguda',          month:'Jan 2026', staff:56, amount:'₹2,45,200', status:'forwarded' },
    { sn:5, code:'KMLB', name:'Kamalabahal',         month:'Jan 2026', staff:29, amount:'₹1,27,050', status:'pending'   },
    { sn:6, code:'LBLP', name:'Lajkura Block Plant', month:'Jan 2026', staff:48, amount:'₹2,10,240', status:'approved'  },
    { sn:7, code:'LKHN', name:'Lakhanpur',           month:'Jan 2026', staff:63, amount:'₹2,75,940', status:'approved'  },
    { sn:8, code:'LNLG', name:'Lingaraj OCP',        month:'Jan 2026', staff:51, amount:'₹2,23,380', status:'pending'   },
  ];

  function buildTable(rows) {
    const tbody = document.getElementById('lppTableBody');
    tbody.innerHTML = '';
    rows.forEach(r => {
      const statusMap = { approved:'approved', pending:'pending', forwarded:'forwarded' };
      const statusLabel = { approved:'Approved', pending:'Pending', forwarded:'Forwarded' };
      const statusIcon  = { approved:'fa-circle-check', pending:'fa-clock', forwarded:'fa-paper-plane' };
      tbody.innerHTML += `
        <tr>
          <td>${r.sn}</td>
          <td><strong>${r.code}</strong></td>
          <td>${r.name}</td>
          <td>${r.month}</td>
          <td>${r.staff}</td>
          <td class="amt-cell">${r.amount}</td>
          <td><span class="status-chip ${statusMap[r.status]}"><i class="fa-solid ${statusIcon[r.status]}"></i> ${statusLabel[r.status]}</span></td>
          <td>
            <button class="btn-sm" style="padding:4px 10px;font-size:0.75rem;">
              <i class="fa-solid fa-eye"></i> View
            </button>
          </td>
        </tr>`;
    });
  }

  /* ── Load Data button ── */
  function loadData() {
    const val = document.getElementById('billingMonth').value;
    if (!val) { alert('Please select a billing month and year.'); return; }

    const btn = document.getElementById('loadBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
    btn.disabled = true;

    // Simulate DB fetch delay (in production: PHP/AJAX call)
    setTimeout(() => {
      const [y, m] = val.split('-');
      const label = `${monthNames[parseInt(m)-1]} ${y}`;

      // Update period badge in table
      const shortMonth = monthNames[parseInt(m)-1].slice(0,3);
      document.getElementById('periodBadge').textContent = `${shortMonth} ${y}`;

      // Update row months
      const rowsWithMonth = sampleRows.map(r => ({ ...r, month: `${shortMonth} ${y}` }));
      buildTable(rowsWithMonth);

      // Show data view, hide no-record
      document.getElementById('noRecordCard').style.display = 'none';
      const dv = document.getElementById('dataView');
      dv.classList.add('visible');

      btn.innerHTML = '<i class="fa-solid fa-filter"></i> Load Data';
      btn.disabled = false;
    }, 800);
  }

  /* ── PHP INTEGRATION POINT ─────────────────────────────────────
     Replace the setTimeout above with:

       fetch(`lpp_data.php?month=${val}`)
         .then(r => r.json())
         .then(data => {
           if (!data.records.length) {
             document.getElementById('noRecordCard').style.display = 'flex';
             document.getElementById('dataView').classList.remove('visible');
           } else {
             buildTable(data.records);
             document.getElementById('noRecordCard').style.display = 'none';
             document.getElementById('dataView').classList.add('visible');
           }
         });
  ──────────────────────────────────────────────────────────────── */
</script>
</body>
</html>