<?php
session_start();
require_once '../config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unlock Attendance</title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

        :root {
            --bg: #f4f6f9;
            --card: #ffffff;
            --text: #111827;
            --sidebar: #0f172a;
            --primary: #0f766e;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body.dark {
            --bg: #0b1120;
            --card: #111827;
            --text: #e5e7eb;
            --sidebar: #020617;
        }

        body {
            background: var(--bg);
            color: var(--text);
            transition: 0.3s;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: #0f766e;
            color: #ffffff;
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
        }

        .logo img {
            max-width: 140px;
            height: auto;
            display: block;
            margin: 0 auto 20px;
            border-radius: 0px;
        }

        nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .menu .icon {
            font-size: 18px;
        }

        .menu:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(4px);
        }

        .menu.active {
            background: rgba(59, 130, 246, 0.15);
            color: #ffffff;
        }

        .menu.active::before {
            content: "";
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: #3b82f6;
            border-radius: 4px;
        }

        /* MAIN */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* TOPBAR */
        .topbar {
            background: var(--card);
            padding: 1rem 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            height: 70px;
        }

        .topbar h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: absolute;
            right: 2rem;
        }

        .header-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .header-icon:hover {
            background: #0f766e;
            color: white;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }

        /* CONTENT */
        .content-wrapper {
            padding: 20px 30px;
            overflow-y: auto;
        }

        .header {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .header .export-buttons {
            position: absolute;
            top: 15px;
            right: 20px;
        }

        /* Attendance Counters */
        .attendance-counters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .counter-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background-color: #f9fafb;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .counter-item:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
        }

        .counter-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: dotPulse 2s infinite;
        }

        @keyframes dotPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .counter-dot.present { background-color: #10b981; box-shadow: 0 0 6px rgba(16, 185, 129, 0.4); }
        .counter-dot.absent { background-color: #ef4444; box-shadow: 0 0 6px rgba(239, 68, 68, 0.4); }
        .counter-dot.leave { background-color: #f59e0b; box-shadow: 0 0 6px rgba(245, 158, 11, 0.4); }
        .counter-dot.overtime { background-color: #8b5cf6; box-shadow: 0 0 6px rgba(139, 92, 246, 0.4); }

        .counter-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .counter-number {
            font-size: 14px;
            font-weight: 700;
        }

        .counter-item.present-counter .counter-number { color: #10b981; }
        .counter-item.absent-counter .counter-number { color: #ef4444; }
        .counter-item.leave-counter .counter-number { color: #f59e0b; }
        .counter-item.overtime-counter .counter-number { color: #8b5cf6; }

        /* Filters */
        .filters {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            min-width: 160px;
            background: white;
            color: #111827;
            transition: all 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        /* Table Section */
        .table-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }

        .table-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .table-header .search-input-wrapper {
            position: relative;
            width: 300px;
        }

        .table-header .search-input-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .table-header .search-input {
            width: 100%;
            padding: 8px 12px 8px 35px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        .table-header .search-input:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-export {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-excel {
            background: #10b981;
            color: white;
        }

        .btn-excel:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-pdf {
            background: #ef4444;
            color: white;
        }

        .btn-pdf:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #0f766e;
        }

        th {
            padding: 12px 15px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #0d5f58;
        }

        th input[type="checkbox"] {
            cursor: pointer;
        }

        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        td {
            padding: 12px 15px;
            font-size: 14px;
            color: #374151;
        }

        td input[type="checkbox"] {
            cursor: pointer;
        }

        .employee-name {
            font-weight: 500;
            color: #111827;
        }

        .site-name {
            font-size: 13px;
            color: #6b7280;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-badge.locked {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .status-present {
            background: #d1fae5;
            color: #065f46;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-leave {
            background: #fef3c7;
            color: #92400e;
        }

        .status-overtime {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Status Dropdown */
        .status-dropdown {
            padding: 6px 12px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            background: white;
            color: #111827;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 120px;
            text-transform: capitalize;
        }

        .status-dropdown:hover {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .status-dropdown:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        }

        /* Style dropdown options */
        .status-dropdown option {
            padding: 8px;
            font-weight: 600;
        }

        /* Action Button */
        .btn-unlock {
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-unlock:hover {
            transform: scale(1.05);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            gap: 8px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .pagination button:hover:not(:disabled) {
            background: #f9fafb;
            border-color: #0f766e;
        }

        .pagination button.active {
            background: #0f766e;
            color: white;
            border-color: #0f766e;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Main Layout with Chart and Table */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            align-items: start;
        }

        /* Statistics Card */
        .stats-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 20px;
        }

        .stats-header {
            margin-bottom: 20px;
        }

        .stats-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .stats-header h3 {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .total-records {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
        }

        .filter-info {
            font-size: 11px;
            color: #9ca3af;
        }

        .percentage-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .percentage-item {
            text-align: left;
        }

        .percentage-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .percentage-value {
            font-size: 24px;
            font-weight: 700;
        }

        .percentage-item.present .percentage-value { color: #10b981; }
        .percentage-item.absent .percentage-value { color: #ef4444; }
        .percentage-item.leave .percentage-value { color: #f59e0b; }
        .percentage-item.overtime .percentage-value { color: #3b82f6; }

        .stats-chart {
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
        }

        .chart-canvas {
            max-height: 220px;
            max-width: 220px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #111827;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: "Segoe UI", sans-serif;
            resize: vertical;
            min-height: 100px;
            transition: all 0.2s;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .modal-btn-submit {
            background: #0f766e;
            color: white;
        }

        .modal-btn-submit:hover {
            background: #0d5f58;
            transform: translateY(-1px);
        }

        /* Status Change Modal */
        .status-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .status-option {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            font-size: 14px;
        }

        .status-option:hover {
            border-color: #0f766e;
            background: #f0fdfa;
        }

        .status-option.selected {
            border-color: #0f766e;
            background: #0f766e;
            color: white;
        }

        .status-option.present {
            border-color: #10b981;
        }

        .status-option.present:hover,
        .status-option.present.selected {
            border-color: #10b981;
            background: #10b981;
            color: white;
        }

        .status-option.absent {
            border-color: #ef4444;
        }

        .status-option.absent:hover,
        .status-option.absent.selected {
            border-color: #ef4444;
            background: #ef4444;
            color: white;
        }

        .status-option.leave {
            border-color: #f59e0b;
        }

        .status-option.leave:hover,
        .status-option.leave.selected {
            border-color: #f59e0b;
            background: #f59e0b;
            color: white;
        }

        .status-option.overtime {
            border-color: #3b82f6;
        }

        .status-option.overtime:hover,
        .status-option.overtime.selected {
            border-color: #3b82f6;
            background: #3b82f6;
            color: white;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                position: relative;
            }
        }

        /* Custom SweetAlert2 Styling */
        .swal2-popup {
            font-family: "Segoe UI", sans-serif;
            border-radius: 12px;
        }

        .swal2-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        .swal2-html-container {
            font-size: 15px;
            color: #374151;
        }

        .swal2-confirm {
            border-radius: 6px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 14px;
        }

        .swal2-cancel {
            border-radius: 6px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 14px;
        }

        .swal2-timer-progress-bar {
            background: rgba(15, 118, 110, 0.8);
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <h2 class="logo">
                <img src="../assets/logo/images.png" alt="MCL Logo">
            </h2>

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
                <a href="#" class="menu">
                    <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                    <span>Basic Pay Update</span>
                </a>
                <a href="#" class="menu">
                    <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                    <span>Add Extra Manpower</span>
                </a>
                <a href="unlock.php" class="menu active">
                    <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                    <span>Unlock Attendance</span>
                </a>
                <a href="#" class="menu">
                    <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                    <span>Attendance Request</span>
                </a>
                <a href="../download_attendance/download_attendance.php" class="menu">
                    <span class="icon"><i class="fa-solid fa-download"></i></span>
                    <span>Download Attendance</span>
                </a>
                <a href="#" class="menu">
                    <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                    <span>Wage Report</span>
                </a>
                <a href="#" class="menu">
                    <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                    <span>Monthly Attendance</span>
                </a>
                <a href="#" class="menu">
                    <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                    <span>Download Salary</span>
                </a>
                <a href="../login.php" class="menu logout">
                    <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN -->
        <main class="main">
            <header class="topbar">
                <h2>Security Billing Portal</h2>
                <div class="topbar-right">
                    <div class="header-icon">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <a href="#" style="text-decoration: none;">
                        <div class="user-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- Page Title -->
                <div class="header">
                    <div class="export-buttons">
                        <button class="btn-export btn-excel" onclick="exportToExcel()">
                            <i class="fa-solid fa-file-excel"></i>
                            Excel
                        </button>
                        <button class="btn-export btn-pdf" onclick="exportToPDF()">
                            <i class="fa-solid fa-file-pdf"></i>
                            PDF
                        </button>
                    </div>
                    <h2>Employee Attendance</h2>
                    <p>Unlock attendance to change status. Click unlock button and provide reason.</p>
                    <!-- Attendance Counters -->
                    <div class="attendance-counters">
                        <div class="counter-item present-counter">
                            <div class="counter-dot present"></div>
                            <span class="counter-label">Present:</span>
                            <span class="counter-number" id="presentCount">0</span>
                        </div>
                        <div class="counter-item absent-counter">
                            <div class="counter-dot absent"></div>
                            <span class="counter-label">Absent:</span>
                            <span class="counter-number" id="absentCount">0</span>
                        </div>
                        <div class="counter-item leave-counter">
                            <div class="counter-dot leave"></div>
                            <span class="counter-label">Leave:</span>
                            <span class="counter-number" id="leaveCount">0</span>
                        </div>
                        <div class="counter-item overtime-counter">
                            <div class="counter-dot overtime"></div>
                            <span class="counter-label">Overtime:</span>
                            <span class="counter-number" id="overtimeCount">0</span>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>DATE</label>
                            <input type="date" id="dateFilter" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="filter-group">
                            <label>STATUS</label>
                            <select id="statusFilter">
                                <option value="">All Status</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="leave">Leave</option>
                                <option value="overtime">Overtime</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>SITE</label>
                            <select id="siteFilter">
                                <option value="">All Sites</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Main Layout with Chart and Table -->
                <div class="main-layout">
                    <!-- Table Section on Left -->
                    <div class="table-section">
                        <div class="table-header">
                            <h3>Attendance Records</h3>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" class="search-input" placeholder="Search..." id="searchInput">
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>S.NO</th>
                                        <th>EMP ID</th>
                                        <th>EMPLOYEE NAME</th>
                                        <th>SITE NAME</th>
                                        <th>DATE</th>
                                        <th>STATUS</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                                            Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination" id="pagination">
                            <button id="prevPage">Previous</button>
                            <button class="active">1</button>
                            <button id="nextPage">Next</button>
                        </div>
                    </div>

                    <!-- Statistics on Right -->
                    <div class="stats-container">
                        <div class="stats-header">
                            <div class="stats-header-row">
                                <h3>Total Records</h3>
                                <div class="total-records">0</div>
                            </div>
                            <div class="filter-info">Filtered by date / site / status</div>
                        </div>

                        <div class="percentage-grid">
                            <div class="percentage-item present">
                                <div class="percentage-label">Present %</div>
                                <div class="percentage-value">0%</div>
                            </div>
                            <div class="percentage-item absent">
                                <div class="percentage-label">Absent %</div>
                                <div class="percentage-value">0%</div>
                            </div>
                            <div class="percentage-item leave">
                                <div class="percentage-label">Leave %</div>
                                <div class="percentage-value">0%</div>
                            </div>
                            <div class="percentage-item overtime">
                                <div class="percentage-label">OT %</div>
                                <div class="percentage-value">0%</div>
                            </div>
                        </div>

                        <div class="stats-chart">
                            <canvas id="attendanceChart" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Unlock Reason Modal -->
    <div id="unlockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-lock-open"></i> Unlock Attendance</h3>
                <button class="modal-close" onclick="closeUnlockModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Employee: <span id="unlockEmployeeName"></span></label>
                </div>
                <div class="form-group">
                    <label>Date: <span id="unlockDate"></span></label>
                </div>
                <div class="form-group">
                    <label>Reason for Unlocking *</label>
                    <textarea id="unlockReason" placeholder="Please provide a reason for unlocking this attendance record..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeUnlockModal()">Cancel</button>
                <button class="modal-btn modal-btn-submit" onclick="submitUnlock()">Unlock</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let attendanceData = [];
        let currentStatistics = {
            total: 0,
            present: 0,
            absent: 0,
            leave: 0,
            overtime: 0,
            presentPercent: 0,
            absentPercent: 0,
            leavePercent: 0,
            overtimePercent: 0
        };
        let attendanceChart = null;
        let currentUnlockData = null;

        // Fetch sites and populate dropdown
        async function loadSites() {
            try {
                const response = await fetch('fetch_sites.php');
                const result = await response.json();
                
                if (result.success) {
                    const siteFilter = document.getElementById('siteFilter');
                    siteFilter.innerHTML = '<option value="">All Sites</option>';
                    
                    result.data.forEach(site => {
                        const option = document.createElement('option');
                        option.value = site.code;
                        option.textContent = site.name;
                        siteFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading sites:', error);
            }
        }

        // Fetch attendance data
        async function loadAttendance() {
            const date = document.getElementById('dateFilter').value;
            const status = document.getElementById('statusFilter').value;
            const site = document.getElementById('siteFilter').value;

            // Get month and year from date
            const dateObj = new Date(date);
            const month = dateObj.getMonth() + 1;
            const year = dateObj.getFullYear();

            try {
                const params = new URLSearchParams({
                    date: date,
                    status: status,
                    site: site,
                    month: month,
                    year: year
                });

                const response = await fetch(`fetch_attendance.php?${params}`);
                const result = await response.json();

                if (result.success) {
                    attendanceData = result.data;
                    currentStatistics = result.statistics;
                    
                    // Update UI
                    updateStatistics();
                    updateChart();
                    renderTable();
                } else {
                    console.error('Error:', result.error);
                    showError('Error loading data: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading attendance:', error);
                showError('Error connecting to server: ' + error.message);
            }
        }

        // Update statistics display
        function updateStatistics() {
            document.getElementById('presentCount').textContent = currentStatistics.present;
            document.getElementById('absentCount').textContent = currentStatistics.absent;
            document.getElementById('leaveCount').textContent = currentStatistics.leave;
            document.getElementById('overtimeCount').textContent = currentStatistics.overtime;

            // Update statistics panel
            document.querySelector('.total-records').textContent = currentStatistics.total;
            
            const percentageValues = document.querySelectorAll('.percentage-value');
            percentageValues[0].textContent = currentStatistics.presentPercent + '%';
            percentageValues[1].textContent = currentStatistics.absentPercent + '%';
            percentageValues[2].textContent = currentStatistics.leavePercent + '%';
            percentageValues[3].textContent = currentStatistics.overtimePercent + '%';
        }

        // Update chart
        function updateChart() {
            if (attendanceChart) {
                attendanceChart.data.datasets[0].data = [
                    currentStatistics.present,
                    currentStatistics.absent,
                    currentStatistics.leave,
                    currentStatistics.overtime
                ];
                attendanceChart.update();
            }
        }

        // Render Table
        function renderTable() {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            
            if (attendanceData.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">No data available for the selected filters</td>';
                tbody.appendChild(tr);
                return;
            }
            
            attendanceData.forEach((row) => {
                const tr = document.createElement('tr');
                const lockIcon = row.locked == 1 ? 'fa-lock' : 'fa-lock-open';
                const lockColor = row.locked == 1 ? '#ef4444' : '#10b981';
                const lockText = row.locked == 1 ? 'Unlock' : 'Lock';
                
                // Status column - show dropdown if unlocked, badge if locked
                let statusHtml = '';
                if (row.locked == 1) {
                    // Show badge when locked
                    statusHtml = `
                        <span class="status-badge status-${row.status} locked" 
                              title="Unlock to change status">
                            ${row.status}
                        </span>
                    `;
                } else {
                    // Show dropdown when unlocked
                    statusHtml = `
                        <select class="status-dropdown" onchange="changeStatus('${row.empId}', '${row.date}', this.value, '${row.name}')" data-current="${row.status}">
                            <option value="present" ${row.status === 'present' ? 'selected' : ''}>Present</option>
                            <option value="absent" ${row.status === 'absent' ? 'selected' : ''}>Absent</option>
                            <option value="leave" ${row.status === 'leave' ? 'selected' : ''}>Leave</option>
                            <option value="overtime" ${row.status === 'overtime' ? 'selected' : ''}>Overtime</option>
                        </select>
                    `;
                }
                
                tr.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-esic="${row.empId}"></td>
                    <td>${row.id}</td>
                    <td>${row.empId}</td>
                    <td class="employee-name">${row.name}</td>
                    <td class="site-name">${row.site}</td>
                    <td>${row.date}</td>
                    <td>${statusHtml}</td>
                    <td>
                        <button class="btn-unlock" onclick="openUnlockModal('${row.empId}', '${row.date}', '${row.name}', ${row.locked})" style="background: ${lockColor}">
                            <i class="fa-solid ${lockIcon}"></i>
                            ${lockText}
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Change status directly from dropdown
        async function changeStatus(esicNo, date, newStatus, name) {
            const dropdown = event.target;
            const oldStatus = dropdown.dataset.current;

            if (newStatus === oldStatus) {
                return; // No change
            }

            // const result = await Swal.fire({
            //     title: 'Confirm Status Change',
            //     html: `Change status for <strong>${name}</strong><br>from <span style="color: #ef4444; font-weight: 600;">${oldStatus.toUpperCase()}</span> to <span style="color: #10b981; font-weight: 600;">${newStatus.toUpperCase()}</span>?`,
            //     icon: 'question',
            //     showCancelButton: true,
            //     confirmButtonColor: '#0f766e',
            //     cancelButtonColor: '#6b7280',
            //     confirmButtonText: 'Yes, change it!',
            //     cancelButtonText: 'Cancel'
            // });

            // if (!result.isConfirmed) {
            //     dropdown.value = oldStatus; // Reset to old value
            //     return;
            // }

            try {
                const formData = new FormData();
                formData.append('esic_no', esicNo);
                formData.append('date', date);
                formData.append('new_status', newStatus);

                const response = await fetch('change_status.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    dropdown.dataset.current = newStatus;
                    loadAttendance();
                }
                else {
                    await Swal.fire({
                        title: 'Error!',
                        text: result.error,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                    dropdown.value = oldStatus; // Reset to old value
                }
            } catch (error) {
                await Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
                dropdown.value = oldStatus; // Reset to old value
            }
        }

        // Open unlock modal
        function openUnlockModal(esicNo, date, name, isLocked) {
            if (isLocked == 0) {
                // If already unlocked, lock it
                lockAttendance(esicNo, date);
                return;
            }

            currentUnlockData = { esicNo, date, name };
            document.getElementById('unlockEmployeeName').textContent = name;
            document.getElementById('unlockDate').textContent = date;
            document.getElementById('unlockReason').value = '';
            document.getElementById('unlockModal').classList.add('active');
        }

        // Close unlock modal
        function closeUnlockModal() {
            document.getElementById('unlockModal').classList.remove('active');
            currentUnlockData = null;
        }

        // Submit unlock with reason
        async function submitUnlock() {
            const reason = document.getElementById('unlockReason').value.trim();
            
            if (!reason) {
                await Swal.fire({
                    title: 'Validation Error',
                    text: 'Please provide a reason for unlocking.',
                    icon: 'warning',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }

            if (!currentUnlockData) {
                await Swal.fire({
                    title: 'Error',
                    text: 'Invalid unlock request.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
                return;
            }

            try {
                const formData = new FormData();
                formData.append('esic_no', currentUnlockData.esicNo);
                formData.append('date', currentUnlockData.date);
                formData.append('action', 'unlock');
                formData.append('reason', reason);

                const response = await fetch('unlock_attendance.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    closeUnlockModal();
                    await Swal.fire({
                        title: 'Unlocked!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    loadAttendance(); // Reload data
                } else {
                    await Swal.fire({
                        title: 'Error!',
                        text: result.error,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (error) {
                await Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Lock attendance (without reason)
        async function lockAttendance(esicNo, date) {
            const result = await Swal.fire({
                title: 'Lock Attendance?',
                text: `Are you sure you want to lock attendance for employee ${esicNo} on ${date}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, lock it!',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('esic_no', esicNo);
                formData.append('date', date);
                formData.append('action', 'lock');

                const response = await fetch('unlock_attendance.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        title: 'Locked!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonColor: '#ef4444',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    loadAttendance(); // Reload data
                } else {
                    await Swal.fire({
                        title: 'Error!',
                        text: result.error,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (error) {
                await Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Open status change modal
        function openStatusModal(esicNo, date, currentStatus, name, isLocked) {
            if (isLocked == 1) {
                alert('Please unlock the attendance first to change status.');
                return;
            }

            currentStatusData = { esicNo, date, currentStatus, name };
            selectedNewStatus = null;

            document.getElementById('statusEmployeeName').textContent = name;
            document.getElementById('statusDate').textContent = date;
            document.getElementById('currentStatus').textContent = currentStatus.toUpperCase();
            
            // Reset status options
            document.querySelectorAll('.status-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            document.getElementById('statusModal').classList.add('active');
        }

        // Close status modal
        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
            currentStatusData = null;
            selectedNewStatus = null;
        }

        // Status option click handler
        document.querySelectorAll('.status-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.status-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                selectedNewStatus = this.dataset.status;
            });
        });

        // Submit status change
        async function submitStatusChange() {
            if (!selectedNewStatus) {
                alert('Please select a new status.');
                return;
            }

            if (!currentStatusData) {
                alert('Invalid status change request.');
                return;
            }

            if (selectedNewStatus === currentStatusData.currentStatus) {
                alert('Please select a different status.');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('esic_no', currentStatusData.esicNo);
                formData.append('date', currentStatusData.date);
                formData.append('new_status', selectedNewStatus);

                const response = await fetch('change_status.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    closeStatusModal();
                    alert(result.message);
                    loadAttendance(); // Reload data
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Export functions
        // Export to Excel function
        function exportToExcel() {
            const date = document.getElementById('dateFilter').value;
            const status = document.getElementById('statusFilter').value;
            const site = document.getElementById('siteFilter').value;
            
            // Build query string
            const params = new URLSearchParams();
            if (date) params.append('date', date);
            if (status && status !== 'All Status') params.append('status', status);
            if (site && site !== 'All Sites') params.append('site', site);
            
            // Redirect to export_excel.php with filters
            window.location.href = `export_excel.php?${params.toString()}`;
        }

        // Export to PDF function
        function exportToPDF() {
            const date = document.getElementById('dateFilter').value;
            const status = document.getElementById('statusFilter').value;
            const site = document.getElementById('siteFilter').value;
            
            // Build query string
            const params = new URLSearchParams();
            if (date) params.append('date', date);
            if (status && status !== 'All Status') params.append('status', status);
            if (site && site !== 'All Sites') params.append('site', site);
            
            // Open PDF export in new window
            window.open(`export_pdf.php?${params.toString()}`, '_blank');
        }


        // Show error message
        function showError(message) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">${message}</td></tr>`;
        }

        // Initialize Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Leave', 'Overtime'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#3b82f6'],
                    borderWidth: 0,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 11 },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Select All Checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filter change handlers
        document.getElementById('dateFilter').addEventListener('change', loadAttendance);
        document.getElementById('statusFilter').addEventListener('change', loadAttendance);
        document.getElementById('siteFilter').addEventListener('change', loadAttendance);

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', function() {
            loadSites();
            loadAttendance();
        });
    </script>
</body>

</html>