<?php
// --------------------
// SESSION CHECK
// --------------------
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// --------------------
// DB CONNECTION
// --------------------
require "config.php";

// --------------------
// FETCH USERS
// --------------------
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, s.SiteName
    FROM user u
    LEFT JOIN site_master s 
        ON u.site_code = s.SiteCode
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/users.css">
</head>

<body>

<!-- Overlay — tap to close sidebar on mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container">

    <!-- ========== SIDEBAR — identical to dashboard ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>
        <nav>
            <a href="dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="user.php" class="menu active">
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
            <a href="#" class="menu">
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

    <!-- MAIN — completely unchanged from your original -->
    <main class="main">
        <h2 class="page-title">
            <center>Security Management Portal</center>
        </h2>

        <div class="content">

            <!-- EXISTING USERS -->
            <div class="card">
                <h3 class="card-title">Existing Users</h3>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Emp ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Area</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['id']) ?></td>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><?= htmlspecialchars($u['SiteName']) ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_user.php?id=<?= $u['id'] ?>">
                                        <button class="edit-btn">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </a>
                                    <button class="delete-btn"
                                            onclick="deleteUser(<?= $u['id'] ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ADD USER -->
            <form method="POST" action="add_user.php" class="add-user-form">
                <div class="card">
                    <h3 class="card-title">
                        <i class="fa-solid fa-user-plus"></i> Add New User
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Employee ID</label>
                            <input type="text" name="emp_id" placeholder="Enter Employee ID" required>
                        </div>
                        <div class="form-group">
                            <label>Employee Name</label>
                            <input type="text" name="name" placeholder="Enter Employee Name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Employee Email</label>
                            <input type="email" name="email" placeholder="Enter Email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" name="password" placeholder="Password" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="">--Select Role--</option>
                                <option>Admin</option>
                                <option>user</option>
                                <option>ASO</option>
                                <option>APM</option>
                                <option>GM</option>
                                <option>HQSO</option>
                                <option>SDHOD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Area</label>
                            <select name="site_code" required>
                                <option value="">--Select Area--</option>
                                <?php
                                $sites = $pdo->query("SELECT SiteCode, SiteName FROM site_master");
                                while ($row = $sites->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['SiteCode']}'>{$row['SiteName']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit">Add</button>
                </div>
            </form>

        </div>
    </main>

</div>

<script src="assets/users.js"></script>

<script>
/* ===== SIDEBAR TOGGLE (mobile) ===== */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (overlay) {
    overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
}
window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});
</script>

</body>
</html>