<?php
session_start();
require 'config.php';
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
    <style>
        /* ── Stat card icon badge ── */
        .stat-card {
            position: relative;
        }
        .card-icon {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }
        /* Icon colours matching the screenshot */
        .stat-card:nth-child(1) .card-icon { background: #ede9fe; color: #7c3aed; }
        .stat-card:nth-child(2) .card-icon { background: #d1fae5; color: #059669; }
        .stat-card:nth-child(3) .card-icon { background: #fee2e2; color: #dc2626; }
        .stat-card:nth-child(4) .card-icon { background: #fef3c7; color: #d97706; }
        .stat-card:nth-child(5) .card-icon { background: #dbeafe; color: #2563eb; }
        .stat-card:nth-child(6) .card-icon { background: #fef9c3; color: #ca8a04; }
        .stat-card:nth-child(7) .card-icon { background: #d1fae5; color: #059669; }
        .stat-card:nth-child(8) .card-icon { background: #dbeafe; color: #2563eb; }
    </style>
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
            <a href="#" class="menu">
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
            <h1>Security Billing Portal</h1>
            <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <!-- PAGE CONTENT -->
        <div class="page-content">

            <!-- STATS -->
            <section class="stats">
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-solid fa-users"></i></span>
                    <p>Total Employees</p>
                    <h2>3,555</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-solid fa-user-check"></i></span>
                    <p>Today Present</p>
                    <h2>0</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-solid fa-user-xmark"></i></span>
                    <p>Today Absent</p>
                    <h2>0</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-regular fa-calendar-xmark"></i></span>
                    <p>On Leave Today</p>
                    <h2>0</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-solid fa-upload"></i></span>
                    <p>Attendance Uploaded</p>
                    <h2>0</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-regular fa-clock"></i></span>
                    <p>Pending Attendance</p>
                    <h2 class="warning">3,555</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <p>Active Sites</p>
                    <h2>35</h2>
                </div>
                <div class="stat-card">
                    <span class="card-icon"><i class="fa-solid fa-arrow-trend-up"></i></span>
                    <p>Attendance Rate</p>
                    <h2>0%</h2>
                </div>
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
                    <div class="summary-items-wrap">
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
const themeIcon   = themeToggle.querySelector('i');

function applyTheme(dark) {
    if (dark) {
        document.body.classList.add('dark');
        themeToggle.classList.add('active');
        themeIcon.className = 'fa-solid fa-sun';
    } else {
        document.body.classList.remove('dark');
        themeToggle.classList.remove('active');
        themeIcon.className = 'fa-solid fa-moon';
    }
}

applyTheme(localStorage.getItem('theme') === 'dark');

themeToggle.addEventListener('click', function () {
    const isDark = document.body.classList.contains('dark');
    applyTheme(!isDark);
    localStorage.setItem('theme', !isDark ? 'dark' : 'light');
});

/* ================================================
   ATTENDANCE TREND CHART
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
        maintainAspectRatio: false,
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