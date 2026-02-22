<?php
session_start();
require "config.php";

// echo $_SESSION['user'];
// exit;

/*
|--------------------------------------------------
| LOGIN PROTECTION (ASO)
|--------------------------------------------------
*/
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// role check
// role + site check
$stmt = $pdo->prepare("SELECT role, site_code FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u || $u['role'] !== 'ASO') {
    die("Access denied");
}

$asoSiteCode = $u['site_code'];   // 🔐 VERY IMPORTANT


/*
|--------------------------------------------------
| HANDLE APPROVAL AJAX REQUEST
|--------------------------------------------------
*/
if (isset($_POST['approve_records']) && isset($_POST['esic_numbers'])) {
    header('Content-Type: application/json');

    $esicNumbers = json_decode($_POST['esic_numbers'], true);
    $today = date('Y-m-d');

    if (!is_array($esicNumbers) || empty($esicNumbers)) {
        echo json_encode(['success' => false, 'message' => 'No records selected']);
        exit;
    }

    try {
        foreach ($esicNumbers as $esic) {

            // 1️⃣ Fetch existing JSON
            $stmt = $pdo->prepare("
                SELECT a.attendance_json
                FROM attendance a
                INNER JOIN employee_master em 
                    ON em.esic_no = a.esic_no
                WHERE a.esic_no = ?
                AND em.site_code = ?
            ");
            $stmt->execute([$esic, $asoSiteCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$row) continue;

            $json = json_decode($row['attendance_json'], true);
            if (!is_array($json)) continue;

            // 2️⃣ Date must exist
            if (!isset($json[$today])) continue;

            // 3️⃣ Already approved → skip
            if (($json[$today]['approve_status'] ?? 0) == 1) {
                continue;
            }

            // 4️⃣ Apply approval
            $json[$today]['approve_status'] = 1;

            // 5️⃣ Save back to DB
            $update = $pdo->prepare("
                UPDATE attendance 
                SET attendance_json = ? 
                WHERE esic_no = ?
            ");
            $update->execute([
                json_encode($json, JSON_UNESCAPED_UNICODE),
                $esic
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Selected records approved successfully'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}


/*
|--------------------------------------------------
| FETCH TODAY'S ATTENDANCE (JSON BASED)
|--------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        a.esic_no,
        em.employee_name AS emp_name,
        a.attendance_json
    FROM attendance a
    INNER JOIN employee_master em 
        ON em.esic_no = a.esic_no
    WHERE em.site_code = ?
");

$stmt->execute([$asoSiteCode]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);


// $stmt->execute();
$attendanceList = [];
$selectedDate = $_GET['date'] ?? date('Y-m-d');


foreach ($records as $record) {

    $json = json_decode($record['attendance_json'], true);
    if (!is_array($json)) continue;

    /* 🔑 DATE IS THE KEY NOW */
    if (!isset($json[$selectedDate])) continue;

    $entry = $json[$selectedDate];

    // ✅ FIXED: Skip already approved (check approve_status in JSON)
    if (isset($entry['approve_status']) && $entry['approve_status'] == 1) {
        continue;
    }

    $statusRaw = strtoupper($entry['status'] ?? '');

    switch ($statusRaw) {
        case 'P':  $status = 'present'; break;
        case 'A':  $status = 'absent'; break;
        case 'L':  $status = 'leave'; break;
        case 'PP': $status = 'overtime'; break;
        default:   $status = 'unknown';
    }

    $attendanceList[] = [
        'esic_no'           => $record['esic_no'],
        'emp_name'          => $record['emp_name'] ?? 'N/A',
        'attendance_date'   => $selectedDate,
        'attendance_status' => $status
    ];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #0f766e;
            --primary-dark: #0d5f58;
            --primary-light: #14b8a6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .sidebar-logo h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .sidebar-logo p {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin: 0.25rem 1rem;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-nav i {
            font-size: 1.125rem;
            width: 24px;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        /* Header */
        .header {
            background: var(--bg-secondary);
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header-left h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .header-left p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .header-icon:hover {
            background: var(--primary-light);
            color: white;
            transform: scale(1.05);
        }

        .header-icon .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger-color);
            color: white;
            font-size: 0.625rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--border-color);
        }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .toast {
            min-width: 320px;
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease-out;
            border-left: 4px solid var(--success-color);
        }

        .toast.success {
            border-left-color: var(--success-color);
        }

        .toast.error {
            border-left-color: var(--danger-color);
        }

        .toast.warning {
            border-left-color: var(--warning-color);
        }

        .toast-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .toast.success .toast-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .toast.error .toast-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .toast.warning .toast-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .toast-message {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .toast-close {
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .toast-close:hover {
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .toast.hiding {
            animation: slideOut 0.3s ease-out forwards;
        }

        /* Table Card */
        .table-card {
            background: var(--bg-secondary);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .table-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .select-box {
            padding: 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .select-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .table-controls {
            padding: 1rem 2rem;
            background: var(--bg-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .search-box {
            padding: 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            font-size: 0.875rem;
            width: 100%;
            max-width: 300px;
            transition: all 0.2s;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: var(--bg-primary);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9375rem;
        }

        .data-table tbody tr {
            transition: all 0.2s;
        }

        .data-table tbody tr:hover {
            background: var(--bg-primary);
        }

        .data-table tbody tr.removing {
            animation: fadeOut 0.5s ease-out forwards;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50px);
            }
        }

        .checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .status-badge.present {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-badge.absent {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-badge.leave {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-badge.overtime {
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary-color);
        }

        /* Content Grid - Table and Chart side by side */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 1.5rem;
            align-items: start;
        }

        /* Chart Card */
        .chart-card {
            background: var(--bg-secondary);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }

        .chart-header {
            margin-bottom: 1.5rem;
        }

        .chart-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .chart-header p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .chart-container {
            position: relative;
            height: 280px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-legend {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .legend-item:hover {
            background: var(--border-color);
            transform: translateX(4px);
        }

        .legend-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .legend-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .legend-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                height: auto;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .chart-card {
                position: relative;
                top: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }

            .toast-container {
                left: 1rem;
                right: 1rem;
            }

            .toast {
                min-width: auto;
            }

            .table-header {
                padding: 1rem;
            }

            .table-actions {
                width: 100%;
            }

            .table-actions .btn,
            .table-actions .select-box {
                width: 100%;
            }

            .table-controls {
                padding: 1rem;
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }
        }
        .logo img {
            max-width: 140px;   /* adjust as needed */
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 5px;
            }
    </style>
</head>

<body>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h2 class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </h2>
        </div>
        <ul class="sidebar-nav">
            <li>
                <a href="#" class="active">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="asomonthly.php">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Monthly Attendance</span>
                </a>
            </li>
            <li>
                <a href="login.php">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h2>Welcome Back, ASO</h2>
                <p>Today is <?= date('l, F d, Y') ?></p>
            </div>
            <div class="header-right">
                <div class="header-icon">
                    <i class="fa-regular fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <a href="aso_details.php" style="text-decoration: none;">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Content Grid: Table and Chart -->
        <div class="content-grid">
            <!-- Table Card -->
            <div class="table-card">
            <div class="table-header">
                <h3>Today's Attendance Records</h3>
                <div class="table-actions">
                    <input type="date" id="dateFilter" class="select-box" value="<?= $selectedDate ?>" style="min-width: 160px;">
                    <select id="statusFilter" class="select-box">
                        <option value="all">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="leave">Leave</option>
                        <option value="overtime">Overtime</option>
                    </select>
                    <button class="btn btn-primary" id="approveBtn" onclick="approveSelected()">
                        <i class="fa-solid fa-check"></i>
                        Approve Selected <span id="selectedCount">(0)</span>
                    </button>
                </div>
            </div>

            <div class="table-controls">
                <div>
                    Show
                    <select class="select-box" style="width: 80px;">
                        <option selected>10</option>
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                    </select>
                    entries
                </div>
                <input type="text" id="searchInput" class="search-box" placeholder="Search by name, ESIC...">
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="checkbox"></th>
                        <th>S.No</th>
                        <th>ESIC No</th>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                <?php if ($attendanceList && count($attendanceList) > 0): ?>
                    <?php $i = 1; foreach ($attendanceList as $row): 
                        $statusClass = strtolower(trim($row['attendance_status']));
                    ?>
                    <tr data-esic="<?= htmlspecialchars($row['esic_no']) ?>">
                        <td><input type="checkbox" class="rowCheck checkbox"></td>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($row['esic_no']) ?></strong></td>
                        <td><?= htmlspecialchars($row['emp_name'] ?? 'N/A') ?></td>
                        <td><?= date('d-m-Y', strtotime($row['attendance_date'])) ?></td>
                        <td>
                            <?php if ($row['attendance_status'] === 'overtime'): ?>
                                <span class="status-badge present">Present</span>
                                <span class="status-badge overtime">Overtime</span>
                            <?php else: ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= ucfirst(htmlspecialchars($row['attendance_status'])) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noRecordsRow">
                        <td colspan="6" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--success-color);"></i>
                            <p style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">All records approved!</p>
                            <p style="font-size: 0.875rem;">No pending attendance records to display.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Chart Card -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>Attendance Overview</h3>
                <p>Today's distribution</p>
            </div>
            <div class="chart-container">
                <canvas id="attendancePieChart"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-left">
                        <div class="legend-dot" style="background: #10b981;"></div>
                        <span class="legend-label">Present</span>
                    </div>
                    <span class="legend-value" id="legendPresent">0</span>
                </div>
                <div class="legend-item">
                    <div class="legend-left">
                        <div class="legend-dot" style="background: #ef4444;"></div>
                        <span class="legend-label">Absent</span>
                    </div>
                    <span class="legend-value" id="legendAbsent">0</span>
                </div>
                <div class="legend-item">
                    <div class="legend-left">
                        <div class="legend-dot" style="background: #f59e0b;"></div>
                        <span class="legend-label">Leave</span>
                    </div>
                    <span class="legend-value" id="legendLeave">0</span>
                </div>
                <div class="legend-item">
                    <div class="legend-left">
                        <div class="legend-dot" style="background: #0f766e;"></div>
                        <span class="legend-label">Overtime</span>
                    </div>
                    <span class="legend-value" id="legendOvertime">0</span>
                </div>
            </div>
        </div>

    </div>
    <!-- End Content Grid -->

    </main>
</div>

<!-- JS -->
<script>
// Toast Notification System
function showToast(type, title, message, duration = 5000) {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const iconMap = {
        'success': 'fa-circle-check',
        'error': 'fa-circle-xmark',
        'warning': 'fa-triangle-exclamation'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fa-solid ${iconMap[type] || 'fa-circle-info'}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this)">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        closeToast(toast.querySelector('.toast-close'));
    }, duration);
}

function closeToast(btn) {
    const toast = btn.closest('.toast');
    toast.classList.add('hiding');
    setTimeout(() => {
        toast.remove();
    }, 300);
}

// Select All Checkbox
document.getElementById("selectAll").addEventListener("change", function () {
    const visibleCheckboxes = Array.from(document.querySelectorAll(".rowCheck"))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    visibleCheckboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

// Update selected count for individual checkboxes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('rowCheck')) {
        updateSelectedCount();
    }
});

// Function to update the selected count display
function updateSelectedCount() {
    const checked = Array.from(document.querySelectorAll(".rowCheck:checked"))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const count = checked.length;
    document.getElementById('selectedCount').textContent = `(${count})`;
}

// Approve Selected Function
async function approveSelected() {
    const checked = Array.from(document.querySelectorAll(".rowCheck:checked"))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    
    if (checked.length === 0) {
        showToast('warning', 'No Selection', 'Please select at least one record to approve.');
        return;
    }

    // Get ESIC numbers
    const esicNumbers = checked.map(cb => cb.closest('tr').getAttribute('data-esic'));
    
    // Disable approve button
    const approveBtn = document.getElementById('approveBtn');
    const currentCount = checked.length;
    approveBtn.disabled = true;
    approveBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Processing... (${currentCount})`;

    try {
        // Send AJAX request
        const formData = new FormData();
        formData.append('approve_records', '1');
        formData.append('esic_numbers', JSON.stringify(esicNumbers));

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Show success message
            showToast('success', 'Approval Successful!', result.message);

            // Remove approved rows with animation
            checked.forEach((cb, index) => {
                const row = cb.closest('tr');
                row.classList.add('removing');
                
                setTimeout(() => {
                    row.remove();
                    
                    // Update serial numbers
                    updateSerialNumbers();
                    
                    // Update stats and chart
                    updateStats();
                    
                    // Check if table is empty
                    checkEmptyTable();
                    
                    // Uncheck select all
                    document.getElementById('selectAll').checked = false;
                    
                    // Reset selected count
                    updateSelectedCount();
                }, 500);
            });
        } else {
            showToast('error', 'Approval Failed', result.message);
        }
    } catch (error) {
        showToast('error', 'System Error', 'An error occurred while processing the request.');
        console.error('Error:', error);
    } finally {
        // Re-enable approve button
        approveBtn.disabled = false;
        approveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Approve Selected <span id="selectedCount">(0)</span>';
        updateSelectedCount();
    }
}

// Update serial numbers after row removal
function updateSerialNumbers() {
    const rows = document.querySelectorAll('#attendanceTableBody tr:not(#noRecordsRow)');
    rows.forEach((row, index) => {
        const sNoCell = row.cells[1];
        if (sNoCell) {
            sNoCell.textContent = index + 1;
        }
    });
}

// Check if table is empty and show message
function checkEmptyTable() {
    const tbody = document.getElementById('attendanceTableBody');
    const rows = tbody.querySelectorAll('tr:not(#noRecordsRow)');
    
    if (rows.length === 0) {
        const existingNoRecordsRow = document.getElementById('noRecordsRow');
        if (!existingNoRecordsRow) {
            const noRecordsRow = document.createElement('tr');
            noRecordsRow.id = 'noRecordsRow';
            noRecordsRow.innerHTML = `
                <td colspan="6" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-circle-check" style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--success-color);"></i>
                    <p style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">All records approved!</p>
                    <p style="font-size: 0.875rem;">No pending attendance records to display.</p>
                </td>
            `;
            tbody.appendChild(noRecordsRow);
        }
    }
}

// Search Functionality
document.getElementById("searchInput").addEventListener("keyup", function () {
    let value = this.value.toLowerCase();
    document.querySelectorAll("#attendanceTableBody tr:not(#noRecordsRow)").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
    updateStats();
    updateSelectedCount();
});

// Status Filter
document.getElementById("statusFilter").addEventListener("change", function () {
    let value = this.value;
    document.querySelectorAll("#attendanceTableBody tr:not(#noRecordsRow)").forEach(row => {
        let statusBadges = row.querySelectorAll(".status-badge");
        let hasStatus = false;
        
        statusBadges.forEach(badge => {
            if (value === "all" || badge.classList.contains(value)) {
                hasStatus = true;
            }
        });
        
        row.style.display = hasStatus ? "" : "none";
    });
    updateStats();
    updateSelectedCount();
});

// Date Filter Change
document.getElementById("dateFilter").addEventListener("change", function () {
    const selectedDate = this.value;
    if (!selectedDate) return;

    const url = new URL(window.location.href);
    url.searchParams.set("date", selectedDate);

    window.location.href = url.toString();
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initChart();
    updateStats();
});

// Initialize Pie Chart
let attendanceChart;

function initChart() {
    const ctx = document.getElementById('attendancePieChart');
    
    if (!ctx) {
        console.error('Canvas element not found');
        return;
    }
    
    attendanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Leave', 'Overtime'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: [
                    '#10b981',
                    '#ef4444',
                    '#f59e0b',
                    '#0f766e'
                ],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 15,
                hoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    padding: 16,
                    cornerRadius: 8,
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 14
                    },
                    displayColors: true,
                    boxWidth: 12,
                    boxHeight: 12,
                    boxPadding: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '65%',
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000
            }
        }
    });
}

// Update Statistics and Chart
function updateStats() {
    let present = 0, absent = 0, leave = 0, overtime = 0;

    document.querySelectorAll("#attendanceTableBody tr:not(#noRecordsRow)").forEach(row => {
        if (row.style.display === "none") return;
        
        let badges = row.querySelectorAll(".status-badge");
        let hasPresent = false;
        let hasAbsent = false;
        let hasLeave = false;
        let hasOvertime = false;
        
        badges.forEach(badge => {
            if (badge.classList.contains("present")) hasPresent = true;
            if (badge.classList.contains("absent")) hasAbsent = true;
            if (badge.classList.contains("leave")) hasLeave = true;
            if (badge.classList.contains("overtime")) hasOvertime = true;
        });
        
        if (hasAbsent) {
            absent++;
        } else if (hasLeave) {
            leave++;
        } else if (hasPresent) {
            present++;
            if (hasOvertime) {
                overtime++;
            }
        }
    });

    // Update chart legend
    document.getElementById("legendPresent").innerText = present;
    document.getElementById("legendAbsent").innerText = absent;
    document.getElementById("legendLeave").innerText = leave;
    document.getElementById("legendOvertime").innerText = overtime;

    // Update chart
    if (attendanceChart) {
        attendanceChart.data.datasets[0].data = [present, absent, leave, overtime];
        attendanceChart.update('active');
    }
}
</script>

</body>
</html>