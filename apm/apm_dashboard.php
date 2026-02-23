<?php
session_start();
require "../config.php";

/*
|--------------------------------------------------
| LOGIN PROTECTION (ASO)
|--------------------------------------------------
*/
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

// role check
// role + site check
$stmt = $pdo->prepare("SELECT role, site_code FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u || $u['role'] !== 'APM') {
    die("Access denied");
}

$asoSiteCode = $u['site_code'];   // 🔐 VERY IMPORTANT
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Security Billing Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --sidebar-width: 270px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%);
            color: white;
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
            transition: background 0.25s ease, color 0.25s ease,
                padding-left 0.25s ease, letter-spacing 0.25s ease;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            padding-left: 1.45rem;
            letter-spacing: 0.15px;
        }

        .nav-link:active {
            transform: scale(0.98);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            font-weight: 600;
            padding-left: 1.45rem;
        }

        .nav-link i {
            font-size: 1.05rem;
            width: 22px;
            text-align: center;
            opacity: 0.9;
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
        }

        .nav-link:hover i {
            transform: scale(1.25) rotate(-6deg);
            opacity: 1;
        }

        .nav-link.active i {
            transform: scale(1.15);
            opacity: 1;
        }

        .nav-link span {
            transition: letter-spacing 0.25s ease;
        }

        /* Logout special hover */
        .logout-link {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .logout-link:hover {
            background: rgba(239, 68, 68, 0.2) !important;
            color: #fca5a5 !important;
            padding-left: 1.45rem;
        }

        .logout-link:hover i {
            color: #fca5a5;
            transform: scale(1.2) translateX(2px) !important;
        }

        .mcl-logo-img {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .mcl-logo-img:hover {
            transform: scale(1.04);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .header-icon {
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .header-icon:hover {
            background: #e5e7eb;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .user-icon {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .user-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(15, 118, 110, 0.4);
        }

        .hamburger-btn {
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .hamburger-btn:hover {
            background: #e5e7eb;
            transform: scale(1.08);
        }

        .sidebar-close {
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .sidebar-close:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg);
        }

        /* ── TOPBAR ── */
        .topbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.06);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

        .topbar-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .topbar-center h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f2937;
            white-space: nowrap;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 38px;
            height: 38px;
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
            font-size: 0.6rem;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .user-icon {
            width: 38px;
            height: 38px;
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

        /* ── MAIN CONTENT ── */
        .main-content {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .page-content {
            flex: 1;
            padding: 2rem;
            /* ── Your page content goes here ── */
        }

        /* ── OVERLAY ── */
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

        /* ── DASHBOARD CONTENT ── */
        .dash-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Page header */
        .dash-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dash-header-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .dash-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0f766e, #0d5f58);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .dash-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1f2937;
        }

        .dash-subtitle {
            font-size: 0.82rem;
            color: #6b7280;
            margin-top: 0.1rem;
        }

        .fy-selector {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.55rem 1rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .fy-selector label {
            font-size: 0.82rem;
            color: #6b7280;
            font-weight: 500;
            white-space: nowrap;
        }

        .fy-selector select {
            border: none;
            outline: none;
            font-size: 0.9rem;
            font-weight: 700;
            color: #1f2937;
            background: transparent;
            cursor: pointer;
        }

        /* Stat cards */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.4rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.09);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon.purple {
            background: #ede9fe;
            color: #7c3aed;
        }

        .stat-icon.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .stat-icon.amber {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-icon.green {
            background: #d1fae5;
            color: #059669;
        }

        .stat-label {
            font-size: 0.72rem;
            color: #9ca3af;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 2.1rem;
            font-weight: 800;
            color: #1f2937;
            line-height: 1;
        }

        /* Table card */
        .table-card {
            background: white;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            min-width: 700px;
        }

        .data-table thead tr {
            background: linear-gradient(135deg, #0f766e, #0d5f58);
        }

        .data-table thead th {
            padding: 1rem 1.25rem;
            text-align: left;
            color: white;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .data-table thead th .sort {
            opacity: 0.7;
            font-size: 0.65rem;
            margin-left: 3px;
        }

        /* Month row */
        .month-row {
            cursor: pointer;
            transition: background 0.2s;
        }

        .month-row:hover td {
            background: #f9fafb;
        }

        .month-row td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f0f0f0;
            color: #374151;
            vertical-align: middle;
        }

        .month-name {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 600;
            color: #1f2937;
        }

        .expand-btn {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: #e5e7eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            color: #6b7280;
            transition: background 0.2s, transform 0.25s ease;
            flex-shrink: 0;
        }

        .month-row.expanded .expand-btn {
            background: #0f766e;
            color: white;
            transform: rotate(90deg);
        }

        /* Breakdown row */
        .breakdown-row td {
            padding: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .breakdown-row.hidden {
            display: none;
        }

        .breakdown-inner {
            padding: 0 1.25rem 1.25rem;
            background: #f9fafb;
        }

        .breakdown-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 0;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 0.5rem;
        }

        .breakdown-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            min-width: 500px;
        }

        .breakdown-table thead th {
            background: #1f2937;
            color: white;
            padding: 0.65rem 1rem;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .breakdown-table tbody td {
            padding: 0.7rem 1rem;
            border-bottom: 1px solid #eeeeee;
            color: #374151;
        }

        .breakdown-table tbody tr:last-child td {
            border-bottom: none;
        }

        .breakdown-table tbody tr:hover td {
            background: #f3f4f6;
        }

        /* Badge */
        .badge-blue {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 0.8rem;
            font-weight: 700;
            width: 28px;
            height: 28px;
            border-radius: 50%;
        }

        /* Attendance bar */
        .att-wrap {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .att-bar-bg {
            flex: 1;
            height: 9px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
            min-width: 80px;
        }

        .att-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 5px;
        }

        .att-pct {
            font-size: 0.84rem;
            font-weight: 700;
            color: #059669;
            white-space: nowrap;
        }

        /* Pagination */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.4rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid #f0f0f0;
        }

        .pg-btn {
            height: 34px;
            min-width: 34px;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.75rem;
            transition: all 0.2s;
        }

        .pg-btn:hover {
            border-color: #0f766e;
            color: #0f766e;
        }

        .pg-btn.active {
            background: #0f766e;
            color: white;
            border-color: #0f766e;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .stat-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .stat-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.7rem;
            }

            .dash-title {
                font-size: 1.2rem;
            }

            .fy-selector {
                width: 100%;
                justify-content: space-between;
            }

            .dash-header {
                flex-direction: column;
            }

            .att-bar-bg {
                display: none;
            }
        }

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

            .topbar-center h2 {
                font-size: 0.9rem;
            }

            .page-content {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .topbar-center h2 {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-layout">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
            <div class="sidebar-logo">
                <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
            </div>
            <ul class="sidebar-nav">
                <li><a href="apm_dashboard.php" class="nav-link active"><i
                            class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
                <li><a href="monthly_attendance.php" class="nav-link"><i
                            class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
                <li><a href="../login.php" class="nav-link"><i
                            class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- MAIN -->
        <div class="main-content">

            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
                </div>
                <div class="topbar-center">
                    <h2>Security Billing Management Portal</h2>
                </div>
                <div class="topbar-right">
                    <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                    <a href="profile.php" title="My Profile" style="text-decoration:none;">
    <div class="user-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
        </svg>
    </div>
</a>
                </div>
            </header>

            <!-- PAGE CONTENT — add your content below -->
            <div class="page-content">
                <div class="dash-page">

                    <!-- Header -->
                    <div class="dash-header">
                        <div class="dash-header-left">
                            <div class="dash-icon"><i class="fa-solid fa-chart-pie"></i></div>
                            <div>
                                <div class="dash-title">Attendance Dashboard</div>
                                <div class="dash-subtitle">Detailed attendance analysis</div>
                            </div>
                        </div>
                        <div class="fy-selector">
                            <label>Financial Year:</label>
                            <select>
                                <option>2025-2026</option>
                                <option>2024-2025</option>
                                <option>2023-2024</option>
                            </select>
                        </div>
                    </div>

                    <!-- Stat Cards -->
                    <div class="stat-cards">
                        <div class="stat-card">
                            <div class="stat-icon purple"><i class="fa-solid fa-users"></i></div>
                            <div>
                                <div class="stat-label">Total Staff</div>
                                <div class="stat-value">93</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fa-solid fa-user-check"></i></div>
                            <div>
                                <div class="stat-label">Working Days</div>
                                <div class="stat-value">550</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon amber"><i class="fa-solid fa-briefcase"></i></div>
                            <div>
                                <div class="stat-label">Extra Duties</div>
                                <div class="stat-value">2</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
                            <div>
                                <div class="stat-label">Grand Total</div>
                                <div class="stat-value">552</div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-card">
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Month / Year <span class="sort">▲▼</span></th>
                                        <th>Active Staff <span class="sort">▲▼</span></th>
                                        <th>Working Days <span class="sort">▲▼</span></th>
                                        <th>Extra Duty <span class="sort">▲▼</span></th>
                                        <th>Absents <span class="sort">▲▼</span></th>
                                        <th>Attendance % <span class="sort">▲▼</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Jan 2026 -->
                                    <tr class="month-row expanded" onclick="toggleRow('jan2026', this)">
                                        <td>
                                            <div class="month-name"><span class="expand-btn"><i
                                                        class="fa-solid fa-chevron-right"></i></span>Jan 2026</div>
                                        </td>
                                        <td>93</td>
                                        <td>458</td>
                                        <td><span class="badge-blue">2</span></td>
                                        <td>7</td>
                                        <td>
                                            <div class="att-wrap">
                                                <div class="att-bar-bg">
                                                    <div class="att-bar-fill" style="width:98.5%"></div>
                                                </div><span class="att-pct">98.5%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="breakdown-row" id="breakdown-jan2026">
                                        <td colspan="6">
                                            <div class="breakdown-inner">
                                                <div class="breakdown-label"><i class="fa-solid fa-list-ul"></i>
                                                    Breakdown for Jan 2026</div>
                                                <div class="breakdown-scroll">
                                                    <table class="breakdown-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Designation</th>
                                                                <th>Headcount</th>
                                                                <th>Working Days</th>
                                                                <th>Extra Duty</th>
                                                                <th>Absents</th>
                                                                <th>Leaves</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Security Supervisor</td>
                                                                <td>26</td>
                                                                <td>125</td>
                                                                <td><span class="badge-blue">2</span></td>
                                                                <td>5</td>
                                                                <td>3</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Security Guard</td>
                                                                <td>67</td>
                                                                <td>333</td>
                                                                <td><span class="badge-blue">0</span></td>
                                                                <td>2</td>
                                                                <td>1</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Feb 2026 -->
                                    <tr class="month-row" onclick="toggleRow('feb2026', this)">
                                        <td>
                                            <div class="month-name"><span class="expand-btn"><i
                                                        class="fa-solid fa-chevron-right"></i></span>Feb 2026</div>
                                        </td>
                                        <td>93</td>
                                        <td>92</td>
                                        <td><span class="badge-blue">0</span></td>
                                        <td>1</td>
                                        <td>
                                            <div class="att-wrap">
                                                <div class="att-bar-bg">
                                                    <div class="att-bar-fill" style="width:98.9%"></div>
                                                </div><span class="att-pct">98.9%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="breakdown-row hidden" id="breakdown-feb2026">
                                        <td colspan="6">
                                            <div class="breakdown-inner">
                                                <div class="breakdown-label"><i class="fa-solid fa-list-ul"></i>
                                                    Breakdown for Feb 2026</div>
                                                <div class="breakdown-scroll">
                                                    <table class="breakdown-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Designation</th>
                                                                <th>Headcount</th>
                                                                <th>Working Days</th>
                                                                <th>Extra Duty</th>
                                                                <th>Absents</th>
                                                                <th>Leaves</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Security Supervisor</td>
                                                                <td>26</td>
                                                                <td>30</td>
                                                                <td><span class="badge-blue">0</span></td>
                                                                <td>1</td>
                                                                <td>0</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Security Guard</td>
                                                                <td>67</td>
                                                                <td>62</td>
                                                                <td><span class="badge-blue">0</span></td>
                                                                <td>0</td>
                                                                <td>1</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-bar">
                            <button class="pg-btn">Previous</button>
                            <button class="pg-btn active">1</button>
                            <button class="pg-btn">Next</button>
                        </div>
                    </div>

                </div>
            </div>
            <!-- END PAGE CONTENT -->

        </div>
    </div>

    <script>
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

        function toggleRow(id, rowEl) {
            const breakdown = document.getElementById('breakdown-' + id);
            const isExpanded = rowEl.classList.contains('expanded');
            if (isExpanded) {
                rowEl.classList.remove('expanded');
                breakdown.classList.add('hidden');
            } else {
                rowEl.classList.add('expanded');
                breakdown.classList.remove('hidden');
            }
        }
    </script>

</body>

</html>