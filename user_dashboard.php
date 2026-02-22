<?php
session_start();
require "config.php";

/*
|--------------------------------------------------------------------------
| USER ROLE PROTECTION
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| READ FILTER VALUES
|--------------------------------------------------------------------------
*/
$status = $_GET['status'] ?? '';

// default date = today
$today = date('Y-m-d');

$date = (isset($_GET['date']) && $_GET['date'] !== '')
    ? $_GET['date']
    : $today;


/*
|--------------------------------------------------------------------------
| FETCH AND PARSE ATTENDANCE DATA
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        a.esic_no,
        a.attendance_year,
        a.attendance_month,
        a.attendance_json,
        e.employee_name,
        e.site_code
    FROM attendance a
    LEFT JOIN employee_master e ON e.esic_no = a.esic_no
    WHERE 1
";

$params = [];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PARSE JSON ARRAY AND BUILD ATTENDANCE LIST
|--------------------------------------------------------------------------
*/
$attendanceList = [];

foreach ($records as $record) {

    $jsonData = json_decode($record['attendance_json'], true);
    if (!is_array($jsonData)) {
        continue;
    }

    // 🔑 DATE IS THE KEY NOW
    foreach ($jsonData as $attendanceDate => $entry) {

        if (!isset($entry['status'])) continue;

        $attendanceStatus = $entry['status'];
        $siteCode = $entry['site_code'] ?? '';

        // Apply filters
        $matchesStatus = ($status === '' || $attendanceStatus === $status);
        $matchesDate   = ($date === '' || $attendanceDate === $date);

        if ($matchesStatus && $matchesDate) {
            $attendanceList[] = [
                'esic_no'            => $record['esic_no'],
                'employee_name'      => $record['employee_name'] ?? 'N/A',
                'attendance_date'    => $attendanceDate,
                'attendance_status'  => $attendanceStatus,
                'site_code'          => $record['site_code'] ?? $siteCode,
                'approve_status'     => $entry['approve_status'] ?? 0,
                'locked'             => $entry['locked'] ?? 0
            ];
        }
    }
}


// Sort by date descending
usort($attendanceList, function($a, $b) {
    return strtotime($b['attendance_date']) - strtotime($a['attendance_date']);
});

/*
|--------------------------------------------------------------------------
| CALCULATE SUMMARY STATISTICS
|--------------------------------------------------------------------------
*/
$totalRecords = count($attendanceList);
$presentCount = 0;
$absentCount = 0;
$leaveCount = 0;
$overtimeCount = 0;

foreach ($attendanceList as $row) {
    switch ($row['attendance_status']) {
        case 'P':
            $presentCount++;
            break;
        case 'A':
            $absentCount++;
            break;
        case 'L':
            $leaveCount++;
            break;
        case 'PP':
        case 'O':
            $overtimeCount++;
            break;
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
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            position: relative;
        }

        .topbar h2 {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            text-align: center;
            width: auto;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #0f766e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .user-icon:hover {
            background: #138496;
        }

        .user-icon svg {
            width: 18px;
            height: 18px;
            stroke: white;
        }
        /* Additional styles */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filters {
            display: none;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .filters.show {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        
        .filters input[type="date"],
        .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            background: #0f766e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #138496;
        }
        
        .btn.small {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn.clear {
            background: #6c757d;
        }
        
        .btn.clear:hover {
            background: #5a6268;
        }
        
        .summary-section {
            margin-bottom: 25px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
        }
        
        .summary-card {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
            text-align: center;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .summary-card.present {
            border-left-color: #28a745;
        }
        
        .summary-card.absent {
            border-left-color: #dc3545;
        }
        
        .summary-card.leave {
            border-left-color: #ffc107;
        }
        
        .summary-card.overtime {
            border-left-color: #17a2b8;
        }
        
        .summary-card h5 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            font-weight: normal;
        }
        
        .summary-card .count {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .active-filters {
            margin-bottom: 15px;
            padding: 10px;
            background: #e7f3ff;
            border-radius: 4px;
            display: none;
        }
        
        .active-filters.show {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }
        
        .filter-tag {
            display: inline-block;
            padding: 4px 12px;
            background: #0f766e;
            color: white;
            border-radius: 20px;
            margin-right: 8px;
            font-size: 13px;
        }
        
        .filter-tag .remove {
            margin-left: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .empty {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }
        
        /* Table styling for better visibility */
       table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            table-layout: fixed;
        }
        
        table thead th {
            padding: 15px 12px;
            background: #0f766e;
            color: white;
            text-align: left;
            font-weight: 600;
        }

        table thead th:nth-child(1) { width: 15%; text-align: center; }
        table thead th:nth-child(2) { width: 20%; }
        table thead th:nth-child(3) { width: 25%; }
        table thead th:nth-child(4) { width: 30%; }
        table thead th:nth-child(5) { width: 22%; }
        
        table tbody td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        table tbody td:nth-child(1) { text-align: center; }
        
        table tbody tr:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 1200px) {
            .summary-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            table {
                table-layout: auto;
                font-size: 13px;
            }

            table thead th,
            table tbody td {
                padding: 10px 8px;
            }

            table thead th:nth-child(1),
            table thead th:nth-child(2),
            table thead th:nth-child(3),
            table thead th:nth-child(4),
            table thead th:nth-child(5) {
                width: auto;
            }

            .filters.show {
                justify-content: center;
            }

            .active-filters.show {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .summary-card {
                padding: 10px;
            }

            .summary-card .count {
                font-size: 22px;
            }

            table {
                font-size: 12px;
            }

            table thead th,
            table tbody td {
                padding: 8px 5px;
            }

            /* Hide Employee Name on very small screens */
            table thead th:nth-child(3),
            table tbody td:nth-child(3) {
                display: none;
            }

            .status-badge {
                padding: 3px 8px;
                font-size: 11px;
            }

            .filters.show {
                justify-content: center;
                padding: 10px;
            }

            .filters input[type="date"],
            .filters select {
                width: 100%;
            }

            .btn.small {
                width: 100%;
                text-align: center;
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

<div class="container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2 class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </h2>

        <ul>
            <li>
                <a href="#" class="active">Dashboard</a>
            </li>
            <li>
                <a href="user_attendance.php">Upload Attendance</a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main">

        <!-- Top Bar -->
        <header class="topbar">
            <h2>Security Management Portal</h2>
            <div class="topbar-right">
                <div class="user-icon"><a href="user_profile.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="8" r="4"/>
                    </svg>
                </div>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </header>

        <!-- Summary Section (Above Table) -->
        <section class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h4 style="margin: 0 0 5px 0;">Attendance Summary</h4>
                    <p class="muted" style="margin: 0;">
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
            
            <!-- Active Filters Display -->
            <div class="active-filters <?= ($status || $date) ? 'show' : '' ?>">
                <strong>Active Filters:</strong>
                <?php if ($date): ?>
                    <span class="filter-tag">
                        Date: <?= date('d-m-Y', strtotime($date)) ?>
                        <a href="?status=<?= urlencode($status) ?>" class="remove" style="color: white; text-decoration: none;">×</a>
                    </span>
                <?php endif; ?>
                <?php if ($status): ?>
                    <span class="filter-tag">
                        Status: <?php
                            switch($status) {
                                case 'P': echo 'Present'; break;
                                case 'A': echo 'Absent'; break;
                                case 'L': echo 'Leave'; break;
                                case 'PP': echo 'Present (Overtime)'; break;
                                default: echo htmlspecialchars($status);
                            }
                        ?>
                        <a href="?date=<?= urlencode($date) ?>" class="remove" style="color: white; text-decoration: none;">×</a>
                    </span>
                <?php endif; ?>
                <a href="user_dashboard.php" style="color: #dc3545; margin-left: 10px; text-decoration: none; font-weight: bold;">Clear All</a>
            </div>

            <form method="GET" class="filters" id="filterForm">
                <input 
                    type="date" 
                    name="date" 
                    value="<?= htmlspecialchars($date) ?>"
                    placeholder="Select Date"
                >

                <select name="status">
                    <option value="">All Status</option>
                    <option value="P" <?= $status === 'P' ? 'selected' : '' ?>>Present</option>
                    <option value="A" <?= $status === 'A' ? 'selected' : '' ?>>Absent</option>
                    <option value="L" <?= $status === 'L' ? 'selected' : '' ?>>Leave</option>
                    <option value="PP" <?= $status === 'PP' ? 'selected' : '' ?>>Overtime</option>
                </select>

                <button type="submit" class="btn small">Apply Filters</button>
                <a href="user_dashboard.php" class="btn small clear" style="text-decoration: none; display: inline-block; text-align: center;">Clear Filters</a>
            </form>
        </section>

        <!-- Attendance Table Section -->
        <section class="card" style="margin-top: 30px;">
            <div class="card-header">
             <h4>
                Employee Attendance (<?= date('F Y', strtotime($date)) ?>)
            </h4>

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
                                $statusDisplay = $row['attendance_status'];
                                switch($statusDisplay) {
                                    case 'P':
                                        $statusText = 'Present';
                                        $statusStyle = 'background: #d4edda; color: #155724;';
                                        break;
                                    case 'A':
                                        $statusText = 'Absent';
                                        $statusStyle = 'background: #f8d7da; color: #721c24;';
                                        break;
                                    case 'L':
                                        $statusText = 'Leave';
                                        $statusStyle = 'background: #fff3cd; color: #856404;';
                                        break;
                                    case 'PP':
                                        $statusText = '<span class="status-badge" style="background: #d4edda; color: #155724; margin-right: 4px;">Present</span><span class="status-badge" style="background: #d1ecf1; color: #0c5460;">Overtime</span>';
                                        $statusStyle = '';
                                        break;
                                    default:
                                        $statusText = htmlspecialchars($statusDisplay);
                                        $statusStyle = 'background: #e2e3e5; color: #383d41;';
                                }
                                ?>
                                <?php if ($statusDisplay === 'PP'): ?>
                                    <?= $statusText ?>
                                <?php else: ?>
                                    <span class="status-badge" style="<?= $statusStyle ?>">
                                        <?= $statusText ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">No matching records found</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <footer>
            © 2026 MCL — All Rights Reserved
        </footer>

    </main>
</div>

<script>
    function toggleFilters() {
        const filterForm = document.getElementById('filterForm');
        const btnText = document.getElementById('filterBtnText');
        
        if (filterForm.classList.contains('show')) {
            filterForm.classList.remove('show');
            btnText.textContent = 'Show Filters';
        } else {
            filterForm.classList.add('show');
            btnText.textContent = 'Hide Filters';
        }
    }
    
    // Auto-show filters if any filter is active
    <?php if ($status || $date): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('filterForm').classList.add('show');
            document.getElementById('filterBtnText').textContent = 'Hide Filters';
        });
    <?php endif; ?>
</script>

</body>
</html>