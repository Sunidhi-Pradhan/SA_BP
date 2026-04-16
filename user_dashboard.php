<?php
session_start();
require "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$status = $_GET['status'] ?? '';
$today  = date('Y-m-d');
$date   = (isset($_GET['date']) && $_GET['date'] !== '') ? $_GET['date'] : $today;

$sql = "
    SELECT a.esic_no, a.attendance_year, a.attendance_month, a.attendance_json,
           e.employee_name, e.site_code
    FROM attendance a
    LEFT JOIN employee_master e ON e.esic_no = a.esic_no
    WHERE 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attendanceList = [];
foreach ($records as $record) {
    $jsonData = json_decode($record['attendance_json'], true);
    if (!is_array($jsonData)) continue;
    foreach ($jsonData as $attendanceDate => $entry) {
        if (!isset($entry['status'])) continue;
        $attendanceStatus = $entry['status'];
        $siteCode         = $entry['site_code'] ?? '';
        $matchesStatus    = ($status === '' || $attendanceStatus === $status);
        $matchesDate      = ($date === '' || $attendanceDate === $date);
        if ($matchesStatus && $matchesDate) {
            $attendanceList[] = [
                'esic_no'           => $record['esic_no'],
                'employee_name'     => $record['employee_name'] ?? 'N/A',
                'attendance_date'   => $attendanceDate,
                'attendance_status' => $attendanceStatus,
                'site_code'         => $record['site_code'] ?? $siteCode,
                'approve_status'    => $entry['approve_status'] ?? 0,
                'locked'            => $entry['locked'] ?? 0,
            ];
        }
    }
}

usort($attendanceList, fn($a, $b) => strtotime($b['attendance_date']) - strtotime($a['attendance_date']));

$totalRecords = count($attendanceList);
$presentCount = $absentCount = $leaveCount = $overtimeCount = 0;
foreach ($attendanceList as $row) {
    switch ($row['attendance_status']) {
        case 'P':             $presentCount++;  break;
        case 'A':             $absentCount++;   break;
        case 'L':             $leaveCount++;    break;
        case 'PP': case 'O':  $overtimeCount++; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard</title>
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
       SIDEBAR
       Structure = useratt.css  (width 240, padding 25px 20px,
       li padding 14px, margin-bottom 12px, border-radius 8px)
       + gradient background + APM entrance animations
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
        animation: slideInLeft 0.5s cubic-bezier(0.22,1,0.36,1) both;
        transition: transform 0.3s ease;
        z-index: 100;
    }

    /* mobile close button */
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

    /* ------ Logo ------ */
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
        animation: logoFadeUp 0.5s 0.1s ease both;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .sidebar .logo img:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }

    /* ------ Nav list ------ */
    .sidebar ul {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
        flex: 1;
    }

    /* ------ Nav item (pill on the LI, matching useratt.css) ------ */
    .sidebar ul li {
        padding: 0 !important;           /* anchor handles inner padding */
        margin-bottom: 10px !important;
        border-radius: 10px !important;
        background: transparent;
        /* staggered reveal */
        opacity: 0;
        animation: navReveal 0.4s ease forwards;
        transition: background 0.22s ease,
                    box-shadow 0.22s ease,
                    transform  0.2s  ease;
    }
    .sidebar ul li:nth-child(1) { animation-delay: 0.30s; }
    .sidebar ul li:nth-child(2) { animation-delay: 0.42s; }
    .sidebar ul li:nth-child(3) { animation-delay: 0.54s; }
    .sidebar ul li:nth-child(4) { animation-delay: 0.66s; }

    /* hover state on LI */
    .sidebar ul li:hover {
        background: rgba(255,255,255,0.15) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.13);
        transform: translateX(4px);
    }
    /* active state on LI */
    .sidebar ul li.active {
        background: rgba(255,255,255,0.22) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        transform: translateX(4px);
    }

    /* ------ Anchor inside LI ------ */
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

    /* ------ Icon ------ */
    .sidebar ul li a i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.80;
        transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1),
                    opacity 0.2s ease;
    }
    .sidebar ul li:hover  a i { transform: scale(1.28) rotate(-6deg); opacity: 1; }
    .sidebar ul li.active a i { transform: scale(1.18); opacity: 1; }

    /* ------ Last item (Logout) ------ */
    .sidebar ul li:last-child {
        margin-top: 10px !important;
        border-top: 1px solid rgba(255,255,255,0.12) !important;
        padding-top: 6px !important;
    }
    .sidebar ul li:last-child a      { color: rgba(255,255,255,0.58) !important; }
    .sidebar ul li:last-child:hover  { background: rgba(239,68,68,0.18) !important; }
    .sidebar ul li:last-child:hover a{ color: #fca5a5 !important; }
    .sidebar ul li:last-child:hover a i {
        color: #fca5a5;
        transform: scale(1.18) translateX(3px) !important;
    }

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
    .topbar h2 {
        position: absolute;
        left: 50%; transform: translateX(-50%);
        margin: 0; text-align: center;
        white-space: nowrap;
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
    .user-icon svg  { width: 18px; height: 18px; stroke: white; }

    /* main content */
    .main { animation: fadeIn 0.4s 0.35s ease both; }

    /* ============================================================
       FILTER / SUMMARY / TABLE  (unchanged)
    ============================================================ */
    .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }

    .filters { display:none; gap:10px; margin-bottom:20px; padding:15px; background:#f5f5f5; border-radius:8px; }
    .filters.show { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
    .filters input[type="date"], .filters select { padding:8px 12px; border:1px solid #ddd; border-radius:4px; font-size:14px; }

    .btn { padding:10px 20px; background:#0f766e; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px; }
    .btn:hover { background:#138496; }
    .btn.small { padding:8px 16px; }
    .btn.clear { background:#6c757d; }
    .btn.clear:hover { background:#5a6268; }

    .summary-section { margin-bottom:25px; }
    .summary-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:15px; }
    .summary-card { padding:15px; background:#f8f9fa; border-radius:8px; border-left:4px solid #007bff; transition:transform 0.2s; text-align:center; }
    .summary-card:hover { transform:translateY(-2px); box-shadow:0 4px 8px rgba(0,0,0,0.1); }
    .summary-card.present  { border-left-color:#28a745; }
    .summary-card.absent   { border-left-color:#dc3545; }
    .summary-card.leave    { border-left-color:#ffc107; }
    .summary-card.overtime { border-left-color:#17a2b8; }
    .summary-card h5 { margin:0 0 10px 0; color:#666; font-size:14px; font-weight:normal; }
    .summary-card .count { font-size:28px; font-weight:bold; color:#333; }

    .active-filters { margin-bottom:15px; padding:10px; background:#e7f3ff; border-radius:4px; display:none; }
    .active-filters.show { display:flex; justify-content:flex-end; align-items:center; gap:8px; }
    .filter-tag { display:inline-block; padding:4px 12px; background:#0f766e; color:white; border-radius:20px; margin-right:8px; font-size:13px; }

    .empty { text-align:center; padding:20px; color:#666; }
    .status-badge { padding:4px 12px; border-radius:4px; font-size:13px; font-weight:500; display:inline-block; }

    table { width:100%; border-collapse:collapse; font-size:14px; table-layout:fixed; }
    table thead th { padding:15px 12px; background:#0f766e; color:white; text-align:left; font-weight:600; }
    table thead th:nth-child(1) { width:15%; text-align:center; }
    table thead th:nth-child(2) { width:20%; }
    table thead th:nth-child(3) { width:25%; }
    table thead th:nth-child(4) { width:30%; }
    table thead th:nth-child(5) { width:22%; }
    table tbody td { padding:12px; border-bottom:1px solid #dee2e6; }
    table tbody td:nth-child(1) { text-align:center; }
    table tbody tr:hover { background:#f8f9fa; }

    /* ============================================================
       RESPONSIVE
    ============================================================ */
    @media (max-width:1200px) { .summary-grid { grid-template-columns:repeat(3,1fr); } }

    @media (max-width:768px) {
        .sidebar {
            position: fixed !important;
            left: 0; top: 0;
            transform: translateX(-100%);
            animation: none !important;
        }
        .sidebar.open { transform: translateX(0) !important; box-shadow: 8px 0 32px rgba(0,0,0,0.3) !important; }
        .sidebar-close  { display: flex !important; }
        .hamburger-btn  { display: flex !important; }
        .summary-grid   { grid-template-columns: repeat(2,1fr); }
        table { table-layout:auto; font-size:13px; }
        table thead th, table tbody td { padding:10px 8px; }
        table thead th:nth-child(1),
        table thead th:nth-child(2),
        table thead th:nth-child(3),
        table thead th:nth-child(4),
        table thead th:nth-child(5) { width:auto; }
    }

    @media (max-width:480px) {
        .summary-grid { grid-template-columns:repeat(2,1fr); }
        .summary-card { padding:10px; }
        .summary-card .count { font-size:22px; }
        table { font-size:12px; }
        table thead th, table tbody td { padding:8px 5px; }
        table thead th:nth-child(3),
        table tbody td:nth-child(3) { display:none; }
        .status-badge { padding:3px 8px; font-size:11px; }
    }
    </style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container">

    <!-- ===================================================
         SIDEBAR
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
            <li class="active">
                <a href="user_dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Dashboard
                </a>
            </li>
            <li>
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
            <h2>Security Attendance and Billing Portal</h2>
            <div class="topbar-right">
                <button class="theme-btn" id="themeToggle" title="Toggle dark mode" style="width:36px;height:36px;border-radius:50%;border:1px solid #e5e7eb;background:#f3f4f6;color:#6b7280;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s,color .2s,transform .2s;"><i class="fa-solid fa-moon"></i></button>
                <div class="user-icon">
                    <a href="user_profile.php" aria-label="Profile">
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

        <!-- Summary Section -->
        <section class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <div>
                    <h4 style="margin:0 0 5px 0;">Attendance Summary</h4>
                    <p class="muted" style="margin:0;">
                        <?= ($status || $date) ? 'Filtered Results' : 'All Records' ?>
                    </p>
                </div>
                <button class="btn" onclick="toggleFilters()">
                    <span id="filterBtnText">Show Filters</span>
                </button>
            </div>

            <div class="summary-section">
                <div class="summary-grid">
                    <div class="summary-card">
                        <h5>Total Records</h5>
                        <div class="count"><?= $totalRecords ?></div>
                    </div>
                    <div class="summary-card present">
                        <h5>Present</h5>
                        <div class="count"><?= $presentCount ?></div>
                    </div>
                    <div class="summary-card absent">
                        <h5>Absent</h5>
                        <div class="count"><?= $absentCount ?></div>
                    </div>
                    <div class="summary-card leave">
                        <h5>On Leave</h5>
                        <div class="count"><?= $leaveCount ?></div>
                    </div>
                    <div class="summary-card overtime">
                        <h5>Overtime</h5>
                        <div class="count"><?= $overtimeCount ?></div>
                    </div>
                </div>
            </div>

            <!-- Active Filters -->
            <div class="active-filters <?= ($status || $date) ? 'show' : '' ?>">
                <strong>Active Filters:</strong>
                <?php if ($date): ?>
                    <span class="filter-tag">
                        Date: <?= date('d-m-Y', strtotime($date)) ?>
                        <a href="?status=<?= urlencode($status) ?>" class="remove" style="color:white;text-decoration:none;">×</a>
                    </span>
                <?php endif; ?>
                <?php if ($status): ?>
                    <span class="filter-tag">
                        Status: <?php
                            switch($status) {
                                case 'P':  echo 'Present'; break;
                                case 'A':  echo 'Absent';  break;
                                case 'L':  echo 'Leave';   break;
                                case 'PP': echo 'Present (Overtime)'; break;
                                default:   echo htmlspecialchars($status);
                            }
                        ?>
                        <a href="?date=<?= urlencode($date) ?>" class="remove" style="color:white;text-decoration:none;">×</a>
                    </span>
                <?php endif; ?>
                <a href="user_dashboard.php" style="color:#dc3545;margin-left:10px;text-decoration:none;font-weight:bold;">Clear All</a>
            </div>

            <form method="GET" class="filters" id="filterForm">
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="P"  <?= $status==='P'  ?'selected':'' ?>>Present</option>
                    <option value="A"  <?= $status==='A'  ?'selected':'' ?>>Absent</option>
                    <option value="L"  <?= $status==='L'  ?'selected':'' ?>>Leave</option>
                    <option value="PP" <?= $status==='PP' ?'selected':'' ?>>Overtime</option>
                </select>
                <button type="submit" class="btn small">Apply Filters</button>
                <a href="user_dashboard.php" class="btn small clear" style="text-decoration:none;display:inline-block;text-align:center;">Clear Filters</a>
            </form>
        </section>

        <!-- Attendance Table -->
        <section class="card" style="margin-top:30px;">
            <div class="card-header">
                <h4>Employee Attendance (<?= date('F Y', strtotime($date)) ?>)</h4>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>ESIC No</th>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($attendanceList)): ?>
                    <?php $i = 1; foreach ($attendanceList as $row): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['esic_no']) ?></td>
                            <td><?= htmlspecialchars($row['employee_name']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['attendance_date'])) ?></td>
                            <td>
                                <?php
                                $sd = $row['attendance_status'];
                                switch ($sd) {
                                    case 'P':
                                        echo '<span class="status-badge" style="background:#d4edda;color:#155724;">Present</span>';
                                        break;
                                    case 'A':
                                        echo '<span class="status-badge" style="background:#f8d7da;color:#721c24;">Absent</span>';
                                        break;
                                    case 'L':
                                        echo '<span class="status-badge" style="background:#fff3cd;color:#856404;">Leave</span>';
                                        break;
                                    case 'PP':
                                        echo '<span class="status-badge" style="background:#d4edda;color:#155724;margin-right:4px;">Present</span>';
                                        echo '<span class="status-badge" style="background:#d1ecf1;color:#0c5460;">Overtime</span>';
                                        break;
                                    default:
                                        echo '<span class="status-badge" style="background:#e2e3e5;color:#383d41;">'.htmlspecialchars($sd).'</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty">No matching records found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <footer>© 2026 MCL — All Rights Reserved</footer>

    </main>
</div>

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

    /* Filter toggle */
    function toggleFilters() {
        const form = document.getElementById('filterForm');
        const txt  = document.getElementById('filterBtnText');
        const open = form.classList.toggle('show');
        txt.textContent = open ? 'Hide Filters' : 'Show Filters';
    }

    <?php if ($status || $date): ?>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('filterForm').classList.add('show');
        document.getElementById('filterBtnText').textContent = 'Hide Filters';
    });
    <?php endif; ?>

    /* ── Theme toggle ── */
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
      const themeIcon = themeToggle.querySelector('i');
      function applyTheme(d) {
        if (d) { document.body.classList.add('dark'); themeToggle.classList.add('active'); themeToggle.style.background='#1e293b'; themeToggle.style.color='#fbbf24'; themeToggle.style.borderColor='#334155'; themeIcon.className='fa-solid fa-sun'; }
        else { document.body.classList.remove('dark'); themeToggle.classList.remove('active'); themeToggle.style.background='#f3f4f6'; themeToggle.style.color='#6b7280'; themeToggle.style.borderColor='#e5e7eb'; themeIcon.className='fa-solid fa-moon'; }
      }
      applyTheme(localStorage.getItem('theme')==='dark');
      themeToggle.addEventListener('click', () => { const d=document.body.classList.contains('dark'); applyTheme(!d); localStorage.setItem('theme',!d?'dark':'light'); });
    }
</script>
<?php include 'chatbot/chatbot_widget.php'; ?>
</body>
</html>