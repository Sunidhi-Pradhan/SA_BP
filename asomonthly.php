<?php
session_start();
require "config.php";

$siteCode = "003";
$year = 2026;
$month = 1;

/* ---------------------------
   FETCH ATTENDANCE DATA
----------------------------*/
$stmt = $pdo->prepare("
    SELECT 
        a.*, 
        e.employee_name, 
        e.rank
    FROM attendance a
    LEFT JOIN employee_master e 
        ON a.esic_no = e.esic_no
    WHERE a.attendance_year = :year
    AND a.attendance_month = :month
");


$stmt->execute([
    ':year' => $year,
    ':month' => $month
]);

$attendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);


if(!$attendanceRows){
    $attendanceRows = [];
}

/* ---------------------------
   SAVE COMMENT
----------------------------*/
$approvalSuccess = false;

if(isset($_POST['approve_report'])){

    $comment = $_POST['comment'];
    $siteCode = "003";
    $year = 2026;
    $month = 1;

    // ✅ Get user ID from session
    $userId = $_SESSION['user'] ?? null;

    // ✅ Fetch name from user table
    $stmtUser = $pdo->prepare("SELECT name FROM user WHERE id = ?");
    $stmtUser->execute([$userId]);

    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $approvedBy = $userData['name'] ?? 'Unknown';

    // ✅ Insert into approval_comments
    $stmt = $pdo->prepare("
        INSERT INTO approval_comments
        (site_code, attendance_year, attendance_month, comment, approved_by)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $siteCode,
        $year,
        $month,
        $comment,
        $approvedBy
    ]);

    $approvalSuccess = true;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance – Security Billing Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0f766e;
            --primary-dark: #0d5f58;
            --sidebar-width: 270px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 4px 0 24px rgba(13, 95, 88, 0.35);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .sidebar-close {
            display: none;
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.12);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .sidebar-logo {
            padding: 1.4rem 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mcl-logo-img {
            max-width: 155px;
            height: auto;
            display: block;
            background: white;
            padding: 10px 14px;
            border-radius: 10px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 1rem 0;
            flex: 1;
        }

        .sidebar-nav li {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.85rem 1.1rem;
            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            font-weight: 600;
        }

        .nav-link i {
            font-size: 1.05rem;
            width: 22px;
            text-align: center;
            opacity: 0.9;
        }

        .logout-link {
            color: rgba(255, 255, 255, 0.75) !important;
        }

        .logout-link:hover {
            background: rgba(239, 68, 68, 0.18) !important;
            color: #fca5a5 !important;
        }

        /* MAIN */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-radius: 14px;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .hamburger-btn {
            display: none;
            background: #f3f4f6;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #0f766e;
            font-size: 1rem;
        }

        .topbar h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f2937;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            color: #6b7280;
            font-size: 1rem;
            border: 1px solid #e5e7eb;
        }

        .header-icon .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #0f766e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .user-icon svg {
            width: 20px;
            height: 20px;
            stroke: white;
        }

        .attendance-header {
            text-align: center;
            margin-bottom: 1rem;
        }

        .attendance-header h1 {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.35rem;
        }

        .attendance-header p {
            font-size: 0.95rem;
            color: #6b7280;
        }

        .attendance-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .attendance-stat-card {
            background: linear-gradient(135deg, var(--card-start), var(--card-end));
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .attendance-stat-card:hover {
            transform: translateY(-3px);
        }

        .attendance-stat-card.blue {
            --card-start: #0ea5e9;
            --card-end: #0284c7;
            --card-border: #0369a1;
        }

        .attendance-stat-card.amber {
            --card-start: #f59e0b;
            --card-end: #d97706;
            --card-border: #b45309;
        }

        .attendance-stat-card.green {
            --card-start: #10b981;
            --card-end: #059669;
            --card-border: #047857;
        }

        .stat-card-content {
            color: white;
        }

        .stat-card-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.95;
            margin-bottom: 0.5rem;
        }

        .stat-card-value {
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-card-icon {
            font-size: 2.75rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-controls-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .table-controls-left select {
            padding: 0.5rem 0.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-excel {
            background: #10b981;
            color: white;
        }

        .btn-excel:hover {
            background: #059669;
        }

        .btn-pdf {
            background: #ef4444;
            color: white;
        }

        .btn-pdf:hover {
            background: #dc2626;
        }

        .search-input {
            padding: 0.6rem 1rem 0.6rem 2.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            width: 240px;
            background: white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 1rem center;
            background-size: 16px;
        }

        /* TABLE */
        .attendance-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .attendance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
            background: white;
            min-width: 1200px;
        }

        .attendance-table thead th {
            background: linear-gradient(135deg, #0f766e, #0d5f58);
            color: white;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 0.875rem 0.45rem;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .attendance-table thead th:first-child {
            text-align: left;
            padding-left: 1rem;
        }

        .attendance-table thead th.summary-col {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            font-weight: 800;
            font-size: 0.8rem;
        }

        .attendance-table thead th.extra-col {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .attendance-table thead th.total-col {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .attendance-table tbody tr {
            transition: background 0.2s;
        }

        .attendance-table tbody tr:hover {
            background: #f9fafb;
        }

        .attendance-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .attendance-table tbody tr:nth-child(even):hover {
            background: #f9fafb;
        }

        .attendance-table tbody td {
            padding: 0.7rem 0.45rem;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
            vertical-align: middle;
        }

        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }

        .attendance-table tbody td:first-child,
        .attendance-table tbody td:nth-child(2),
        .attendance-table tbody td:nth-child(3),
        .attendance-table tbody td:nth-child(4) {
            text-align: left;
            font-size: 0.82rem;
            color: #1f2937;
            font-weight: 500;
            padding-left: 1rem;
            white-space: nowrap;
        }

        .attendance-table tbody td:nth-child(3),
        .attendance-table tbody td:nth-child(4) {
            color: #6b7280;
            font-weight: 400;
            font-size: 0.8rem;
        }

        /* STATUS */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 7px;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: default;
            transition: transform 0.15s;
        }

        .status-indicator:hover {
            transform: scale(1.1);
        }

        .status-present {
            background: #d1fae5;
            color: #059669;
        }

        .status-pp {
            background: #bfdbfe;
            color: #1d4ed8;
            font-size: 0.66rem;
            letter-spacing: -0.5px;
        }

        .status-leave {
            background: #fef3c7;
            color: #d97706;
        }

        .status-absent {
            background: #fee2e2;
            color: #dc2626;
        }

        /* TOTALS */
        .total-cell {
            font-weight: 700;
            color: #1f2937;
            background: #f3f4f6;
            font-size: 0.9rem;
        }

        .total-cell.working-cell {
            background: #dbeafe;
            color: #0369a1;
        }

        .total-cell.extra-cell {
            background: #fef3c7;
            color: #b45309;
        }

        .total-cell.total-value {
            background: #d1fae5;
            color: #059669;
            font-weight: 800;
            font-size: 0.95rem;
        }

        /* LEGEND */
        .legend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 10px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #4b5563;
            font-weight: 500;
        }

        .legend-box {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* FORWARD */
        .forward-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1f2937;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            resize: vertical;
        }

        .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .form-control.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: #0f766e;
            color: white;
        }

        .btn-primary:hover {
            background: #0d5f58;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);
        }

        .forward-btn-group {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
        }

        /* PAGINATION */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .page-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .page-btn:hover {
            border-color: #0f766e;
            color: #0f766e;
        }

        .page-btn.active {
            background: #0f766e;
            color: white;
            border-color: #0f766e;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* SUCCESS PAGE */
        .success-page {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            padding: 2rem 0;
        }

        .success-page.visible {
            display: flex;
        }

        .success-banner {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 60%, #15803d 100%);
            border-radius: 18px;
            padding: 3.5rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            position: relative;
            overflow: hidden;
        }

        .success-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(255, 255, 255, 0.12) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .success-check {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
        }

        .success-check i {
            font-size: 2rem;
            color: #16a34a;
        }

        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-align: center;
            animation: fadeUp 0.45s 0.2s ease both;
        }

        @keyframes fadeUp {
            0% { transform: translateY(16px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        .success-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem 2.25rem;
            min-width: 340px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            animation: fadeUp 0.45s 0.35s ease both;
        }

        .success-card-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.6rem;
        }

        .success-card-title i {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .success-card-desc {
            font-size: 0.88rem;
            color: #6b7280;
            margin-bottom: 0.85rem;
            line-height: 1.55;
        }

        .success-card-date {
            font-size: 0.87rem;
            font-weight: 600;
            color: #1f2937;
        }

        .success-card-date span {
            color: #6b7280;
            font-weight: 400;
        }

        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.75rem;
            animation: fadeUp 0.45s 0.5s ease both;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: #16a34a;
            border: 2px solid white;
            font-weight: 700;
        }

        .btn-white:hover {
            background: #f0fdf4;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: var(--sidebar-width);
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: 8px 0 32px rgba(0, 0, 0, 0.3);
            }

            .sidebar-close {
                display: flex;
            }

            .hamburger-btn {
                display: flex;
            }

            .topbar {
                padding: 0.875rem 1rem;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .topbar h2 {
                font-size: 1.1rem;
                order: 2;
                flex-basis: 100%;
                text-align: center;
            }

            .main-content {
                padding: 1rem;
                gap: 1rem;
            }

            .attendance-stats {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .table-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                width: 100%;
            }

            .export-buttons {
                width: 100%;
                flex-wrap: wrap;
            }

            .btn-export {
                flex: 1;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-layout">
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
            <div class="sidebar-logo">
                <img src="assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
            </div>
            <ul class="sidebar-nav">
                <li><a href="aso_dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
                <li><a href="asomonthly.php" class="nav-link active"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
                <li><a href="login.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
                <h2>Security Billing Management Portal</h2>
                <div class="topbar-right">
                    <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                    <div class="user-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="8" r="4" />
                        </svg>
                    </div>
                </div>
            </header>

            <!-- ===================== ATTENDANCE CONTENT (hidden on success) ===================== -->
            <div id="attendanceContent" <?= $approvalSuccess ? 'style="display:none"' : '' ?>>

                <div class="attendance-header">
                    <h1>MONTHLY ATTENDANCE REPORT</h1>
                    <p>Attendance Period: January 2026 &nbsp;|&nbsp; Working Days: 22 (Weekends Excluded)</p>
                </div>

                <div class="attendance-stats">
                    <div class="attendance-stat-card blue">
                        <div class="stat-card-content">
                            <div class="stat-card-label">Working Days</div>
                            <div class="stat-card-value">458</div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-briefcase"></i></div>
                    </div>
                    <div class="attendance-stat-card amber">
                        <div class="stat-card-content">
                            <div class="stat-card-label">Extra Duty</div>
                            <div class="stat-card-value">2</div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-clock"></i></div>
                    </div>
                    <div class="attendance-stat-card green">
                        <div class="stat-card-content">
                            <div class="stat-card-label">Total Duty Days</div>
                            <div class="stat-card-value">460</div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    </div>
                </div>

                <div class="card">
                    <div class="table-controls">
                        <div class="table-controls-left">
                            <label>Show</label>
                            <select>
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                                <option>100</option>
                            </select>
                            <label>entries</label>
                        </div>
                        <div class="export-buttons">
                            <button class="btn-export btn-excel"><i class="fa-solid fa-file-excel"></i> Excel</button>
                            <button class="btn-export btn-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                        </div>
                        <input type="text" class="search-input" placeholder="Search">
                    </div>

                    <div class="attendance-table-wrapper">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>S.N.</th>
                                    <th>EMP CODE</th>
                                    <th>NAME</th>
                                    <th>RANK</th>
                                    <th>1</th><th>2</th><th>5</th><th>6</th><th>7</th>
                                    <th>8</th><th>9</th><th>12</th><th>13</th><th>14</th>
                                    <th>15</th><th>16</th><th>19</th><th>20</th><th>21</th>
                                    <th>22</th><th>23</th><th>26</th><th>27</th><th>28</th>
                                    <th>29</th><th>30</th>
                                    <th class="summary-col">WORKING</th>
                                    <th class="summary-col extra-col">EXTRA</th>
                                    <th class="summary-col total-col">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
$sn = 1;
foreach ($attendanceRows as $row):
    $attendanceData = json_decode($row['attendance_json'], true);
    $working = 0;
    $extra = 0;

    echo "<tr>";
    echo "<td>".$sn++."</td>";
    echo "<td>".$row['esic_no']."</td>";
    echo "<td>".$row['employee_name']."</td>";
    echo "<td>".$row['rank']."</td>";


    $days = [1,2,5,6,7,8,9,12,13,14,15,16,19,20,21,22,23,26,27,28,29,30];

    foreach ($days as $day) {
        $dateKey = $year . "-" . str_pad($month,2,'0',STR_PAD_LEFT) . "-" . str_pad($day,2,'0',STR_PAD_LEFT);

        if (isset($attendanceData[$dateKey]) && $attendanceData[$dateKey]['site_code'] == $siteCode) {
            $status = $attendanceData[$dateKey]['status'];

            if ($status == 'P')  $working++;
            if ($status == 'PP') $extra++;

            $class = match($status) {
                'P'  => 'status-present',
                'PP' => 'status-pp',
                'L'  => 'status-leave',
                'A'  => 'status-absent',
                default => ''
            };

            echo "<td><span class='status-indicator $class'>$status</span></td>";
        } else {
            echo "<td>-</td>";
        }
    }

    $total = $working + $extra;
    echo "<td class='total-cell working-cell'>$working</td>";
    echo "<td class='total-cell extra-cell'>$extra</td>";
    echo "<td class='total-cell total-value'>$total</td>";
    echo "</tr>";
endforeach;
?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-box status-present">P</div><span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box status-pp">PP</div><span>Overtime</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box status-leave">L</div><span>Leave</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box status-absent">A</div><span>Absent</span>
                        </div>
                    </div>

                    <div class="pagination">
                        <button class="page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <button class="page-btn">4</button>
                        <button class="page-btn">5</button>
                        <button class="page-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>

                    <!-- Approval Form -->
                    <div class="forward-section">
                        <form method="POST" id="approvalForm">
                            <div class="form-group">
                                <label class="form-label">
                                    Add Approval Comment <span class="required">*</span>
                                </label>
                                <textarea class="form-control" name="comment" id="commentBox" rows="5"
                                    placeholder="Add your approval comment..." required></textarea>
                            </div>

                            <div class="forward-btn-group">
                                <button type="submit" name="approve_report" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> APPROVE REPORT
                                </button>

                            </div>
                        </form>
                    </div>

                </div><!-- /.card -->

            </div><!-- /#attendanceContent -->

            <!-- ===================== SUCCESS PAGE ===================== -->
            <div class="success-page <?= $approvalSuccess ? 'visible' : '' ?>" id="successPage">
                <div class="success-banner">
                    <div class="success-check">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="success-title">Report Processed Successfully!</div>
                    <div class="success-card">
                        <div class="success-card-title">
                            <i class="fa-solid fa-user-check"></i>
                            MANUALLY FORWARDED
                        </div>
                        <div class="success-card-desc">
                            You have successfully reviewed and forwarded this attendance report to the next stage.
                        </div>
                        <div class="success-card-date">
                            Processed On: <span><?= $approvalSuccess ? date('Y-m-d H:i:s') : '' ?></span>
                        </div>
                    </div>
                    <!-- <div class="success-actions">
                        <a href="asomonthly.php" class="btn btn-white">
                            <i class="fa-solid fa-calendar-days"></i> View Attendance
                        </a>
                        <a href="login.php" class="btn btn-white" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.4);">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div> -->
                </div>
            </div>

        </main>
    </div>

    <script>
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        });
        document.getElementById('sidebarClose').addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });

        // Approve Report button — validate then submit form
        // document.getElementById('forwardBtn').addEventListener('click', function () {
        //     const commentBox = document.getElementById('commentBox');
        //     const comment = commentBox.value.trim();

        //     if (!comment) {
        //         commentBox.focus();
        //         commentBox.classList.add('error');
        //         return;
        //     }

        //     // Submit the PHP form
        //     document.getElementById('approvalForm').submit();
        // });

        // Remove error style on input
        const commentBox = document.getElementById('commentBox');
        if (commentBox) {
            commentBox.addEventListener('input', function () {
                this.classList.remove('error');
            });
        }
    </script>
</body>

</html>