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
    <title>Security Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/desh.css">
</head>

<body>

<!-- Overlay — tap to close sidebar on mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ========== SIDEBAR ========== -->
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

    <!-- ========== MAIN ========== -->
    <main class="main">

        <!-- HEADER -->
        <header>
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1>Security Billing Portal</h1>
            <label class="theme-toggle" title="Toggle dark mode">
                <input type="checkbox" id="themeToggle">
                <span class="slider"></span>
            </label>
        </header>

        <!-- PAGE CONTENT -->
        <div class="page-content">

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
                    <h3>📈 Attendance Trend</h3>
                    <div class="chart-wrapper">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <div class="summary-box">
                    <h3>📋 Today Summary</h3>
                    <div class="summary-item">
                        <span>Total Employees</span><b>3,555</b>
                    </div>
                    <div class="summary-item">
                        <span>Present</span><b class="green">0</b>
                    </div>
                    <div class="summary-item">
                        <span>Absent</span><b class="red">0</b>
                    </div>
                    <div class="summary-item">
                        <span>On Leave</span><b>0</b>
                    </div>
                    <div class="summary-item">
                        <span>Pending Attendance</span><b class="red">3,555</b>
                    </div>
                    <div class="summary-item">
                        <span>Attendance Rate</span><b>0%</b>
                    </div>
                </div>

            </section>

        </div><!-- /.page-content -->

    </main>
</div>

<script>
/* ================================================
   SIDEBAR TOGGLE
================================================ */
const menuBtn = document.getElementById('menuBtn');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

menuBtn.addEventListener('click', function () {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
});
overlay.addEventListener('click', closeSidebar);

document.querySelectorAll('.sidebar .menu').forEach(function (link) {
    link.addEventListener('click', function () {
        if (window.innerWidth <= 768) closeSidebar();
    });
});
window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

/* ================================================
   THEME TOGGLE
================================================ */
const themeToggle = document.getElementById('themeToggle');
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
    themeToggle.checked = true;
}
themeToggle.addEventListener('change', function () {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});

/* ================================================
   ATTENDANCE TREND CHART
   responsive: true + maintainAspectRatio: false
   are the two essential settings for Chart.js
   to respect the .chart-wrapper container size
================================================ */
const ctx = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [
            {
                label: 'Present',
                data: [2800, 3000, 2900, 3100, 2950, 3200, 3100],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10b981',
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Absent',
                data: [755, 555, 655, 455, 605, 355, 455],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.06)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ef4444',
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,   /* CRITICAL — lets CSS control height */
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                labels: { font: { size: 12 }, usePointStyle: true, padding: 16 }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, maxRotation: 0 }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { font: { size: 11 } }
            }
        }
    }
});
</script>

</body>
</html>