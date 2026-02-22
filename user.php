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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/users.css">
</head>

<body>

    <div class="container">

        <!-- SIDEBAR (UNCHANGED) -->
        <aside class="sidebar">
            <div class="logo">Admin Panel</div>
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

        <!-- MAIN -->
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
                                    <!-- EDIT -->
                                    <a href="edit_user.php?id=<?= $u['id'] ?>">
                                        <button class="edit-btn">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </a>

                                    <!-- DELETE -->
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

                                <!-- <option value="">--Select Area--</option>
                                

                                    <option value="MAHANADI COAL FIELD">MAHANADI COAL FIELD</option>
                                    <option value="MAHANADI COAL FIELD BHUBANESHWAR">MAHANADI COAL FIELD BHUBANESWAR</option>
                                    <option value="MAHANADI COAL FIELD SAMBHALPUR">MAHANADI COAL FIELD SAMBALPUR</option>
                                    <option value="MAHANADI COAL FIELDS LIMITED">MAHANADI COAL FIELDS LIMITED</option>
                                    <option value="MAHANADI COALFIELD LIMITED (NSCH COLLEGE Additional 93 Deployment)">MAHANADI COALFIELD LIMITED (NSCH COLLEGE Additional 93 Deployment)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (NSCH COLLEGE Extra Deployment)">MAHANADI COALFIELD LIMITED (NSCH COLLEGE Extra Deployment)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (NSCH COLLEGE)">MAHANADI COALFIELD LIMITED (NSCH COLLEGE)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (NSCH HOSPITAL 2)">MAHANADI COALFIELD LIMITED (NSCH HOSPITAL 2)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (BALABHADRA OCP)">MAHANADI COALFIELD LIMITED (BALABHADRA OCP)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (HINGULA GM)">MAHANADI COALFIELD LIMITED (HINGULA GM)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (BALANDA OCP)">MAHANADI COALFIELD LIMITED (BALANDA OCP)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (BALARAM OCP)">MAHANADI COALFIELD LIMITED (BALARAM OCP)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (HINGULA OCP)">MAHANADI COALFIELD LIMITED (HINGULA OCP)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (JAGANNATH OCP)">MAHANADI COALFIELD LIMITED (JAGANNATH OCP)</option>
                                    <option value="MAHANADI COALFIELD LIMITED (JAGANNATH GM)">MAHANADI COALFIELD LIMITED (JAGANNATH GM)</option>
                                    <option value="MAHANADI COALFIELDS LIMITED (NSCH)">MAHANADI COALFIELDS LIMITED (NSCH)</option>
                                    <option value="MAHANADI COALFIELDS LIMITED (ANANTA OCP)">MAHANADI COALFIELDS LIMITED (ANANTA OCP)</option>
                                    <option value="MAHANADI COALFIELDS LIMITED (CWS (X) TALCHER)">MAHANADI COALFIELDS LIMITED (CWS (X) TALCHER)</option>
                                    <option value="MAHANADI COALFIELDS LIMITED (KANIHA AREA)">MAHANADI COALFIELDS LIMITED (KANIHA AREA)</option>
                                    <option value="MAHANADI COALFIELDS LIMITED (TALCHER AREA)">MAHANADI COALFIELDS LIMITED (TALCHER AREA)</option>
                                    <option value="MAHANANDI COAL FIELD (SUBHADRA AREA)">MAHANANDI COAL FIELD (SUBHADRA AREA)</option>
                                    <option value="MCL (CWS IBV)">MCL (CWS IBV)</option>
                                    <option value="MCL (IB VALLEY LAJKURA OCP)">MCL (IB VALLEY LAJKURA OCP)</option>
                                    <option value="MCL (LAKHANPUR AREA LILARI)">MCL (LAKHANPUR AREA LILARI)</option>
                                    <option value="MCL (ORIENT RAMPUR SUB AREA)">MCL (ORIENT RAMPUR SUB AREA)</option>
                                    <option value="MCL (B&G GM UNIT)">MCL (B&G GM UNIT)</option>
                                    <option value="MCL (B&G KANIKA RLY SIDING)">MCL (B&G KANIKA RLY SIDING)</option>
                                    <option value="MCL (B&G KULDA OCP)">MCL (B&G KULDA OCP)</option>
                                    <option value="MCL (B&G MAHALAXMI)">MCL (B&G MAHALAXMI)</option>
                                    <option value="MCL (GARJANBAHAL)">MCL (GARJANBAHAL)</option>
                                    <option value="MCL (HBM BUNDIA)">MCL (HBM BUNDIA)</option>
                                    <option value="MCL (IB VALLEY GM UNIT)">MCL (IB VALLEY GM UNIT)</option>
                                    <option value="MCL (IB VALLEY SAMLESWARI OCP)">MCL (IB VALLEY SAMLESWARI OCP)</option>
                                    <option value="MCL (LAKHANPUR GM UNIT)">MCL (LAKHANPUR GM UNIT)</option>
                                    <option value="MCL (LAKHANPUR LOCM)">MCL (LAKHANPUR LOCM)</option>
                                    <option value="MCL (ORIENT GM AREA)">MCL (ORIENT GM AREA)</option>
                                    <option value="MCL (ORIENT SUB AREA)">MCL (ORIENT SUB AREA)</option>
                                    <option value="MCL (LAKHANPUR BOCM)">MCL (LAKHANPUR BOCM)</option>



                                
                            </select> -->
                        </div>
                    </div>
                    
                    <button type="submit">Add</button>
                </form>

            </div>
            </div>
    </div>
    </main>

    </div>
    
</body>
<script src="assets/users.js"></script>
</html>