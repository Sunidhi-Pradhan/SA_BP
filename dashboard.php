<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- link to css -->
    <link rel="stylesheet" href="assets/desh.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <!-- 🔹 added id="sidebar" -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>

        <nav>
            <a href="#" class="menu active">
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

            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>

            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>

            <a href="unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                <span>Unlock Attendance</span>
            </a>

            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                <span>Attendance Request</span>
            </a>

            <a href="download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-download"></i></span>
                <span>Download Attendance</span>
            </a>

            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Wage Report</span>
            </a>

            <a href="monthly_att/monthly_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                <span>Monthly Attendance</span>
            </a>

            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                <span>Download Salary</span>
            </a>

            <a href="logout.php" class="menu logout">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <header>
            <!-- 🔹 hamburger button ADDED -->
            <button class="menu-btn" id="menuBtn">
                <i class="fa-solid fa-bars"></i>
            </button>

            <h1>Dashboard</h1>

            <!-- 🌗 THEME TOGGLE -->
            <label class="theme-toggle">
                <input type="checkbox" id="themeToggle">
                <span class="slider"></span>
            </label>
        </header>

        <!-- STATS -->
        <section class="stats">
            <div class="stat-card"><p>Total Employees</p><h2>3,555</h2></div>
            <div class="stat-card"><p>Today Present</p><h2>0</h2></div>
            <div class="stat-card"><p>Today Absent</p><h2>0</h2></div>
            <div class="stat-card"><p>On Leave Today</p><h2>0</h2></div>
            <div class="stat-card"><p>Attendance Uploaded</p><h2>0</h2></div>
            <div class="stat-card"><p>Pending Attendance</p><h2 class="warning">3,555</h2></div>
            <div class="stat-card"><p>Active Sites</p><h2>35</h2></div>
            <div class="stat-card"><p>Attendance Rate</p><h2>0%</h2></div>
        </section>

        <!-- GRAPH + SUMMARY -->
        <section class="graph-section">
            <div class="chart-box">
                <h3>Attendance Trend</h3>
                <div class="chart-wrapper">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <div class="summary-box">
                <h3>Today Summary</h3>
                <div class="summary-item"><span>Total Employees</span><b>3,555</b></div>
                <div class="summary-item"><span>Present</span><b class="green">0</b></div>
                <div class="summary-item"><span>Absent</span><b class="red">0</b></div>
                <div class="summary-item"><span>On Leave</span><b>0</b></div>
                <div class="summary-item"><span>Pending Attendance</span><b class="red">3,555</b></div>
                <div class="summary-item"><span>Attendance Rate</span><b>0%</b></div>
            </div>
        </section>

    </main>
</div>

<script src="assets/dashboard.js"></script>
</body>
</html>
