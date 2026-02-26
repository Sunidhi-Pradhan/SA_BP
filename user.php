<?php
session_start();
if (!isset($_SESSION["user"])) { header("Location: login.php"); exit; }
require "config.php";

$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, s.SiteName
    FROM user u
    LEFT JOIN site_master s ON u.site_code = s.SiteCode
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users – Security Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Same CSS as dashboard — picks up sidebar, header, layout, theme vars -->
    <link rel="stylesheet" href="assets/desh.css">
    <style>
        /* ── Tab Nav ── */
        .tab-nav {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            display: flex; align-items: center; gap: .5rem;
            padding: .8rem 1.5rem;
            font-size: .88rem; font-weight: 600;
            color: var(--subtext);
            background: none; border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer; font-family: inherit;
            transition: color .2s, border-color .2s;
        }
        .tab-btn:hover { color: #0f766e; }
        .tab-btn.active { color: #0f766e; border-bottom-color: #0f766e; }

        .tab-badge {
            background: var(--border); color: var(--subtext);
            font-size: .7rem; font-weight: 800;
            padding: 2px 8px; border-radius: 20px;
            transition: background .2s, color .2s;
        }
        .tab-btn.active .tab-badge { background: #0f766e; color: #fff; }

        /* ── Panels ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Card ── */
        .u-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .u-card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: var(--card);
        }
        .u-card-title {
            font-size: .92rem; font-weight: 700; color: var(--text);
            display: flex; align-items: center; gap: .45rem;
        }
        .u-card-title i { color: #0f766e; }

        /* ── Table ── */
        .users-table { width: 100%; border-collapse: collapse; }
        .users-table thead tr { background: var(--bg); }
        .users-table th {
            padding: .75rem 1.1rem;
            font-size: .71rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .6px;
            color: var(--subtext); text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .users-table td {
            padding: .85rem 1.1rem;
            font-size: .875rem; color: var(--text);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .users-table tbody tr:last-child td { border-bottom: none; }
        .users-table tbody tr:hover { background: rgba(15,118,110,.04); transition: background .15s; }

        .emp-id {
            font-size: .76rem; font-weight: 700;
            background: var(--bg); color: var(--subtext);
            padding: 3px 9px; border-radius: 6px; display: inline-block;
        }
        .role-badge {
            font-size: .72rem; font-weight: 700; letter-spacing: .3px;
            padding: 3px 10px; border-radius: 20px;
            background: #dbeafe; color: #1d4ed8; display: inline-block;
        }
        .role-badge.admin  { background: #f3e8ff; color: #7c3aed; }
        .role-badge.gm     { background: #fef3c7; color: #b45309; }
        .role-badge.plain  { background: var(--bg);  color: var(--subtext); }

        .action-btns { display: flex; gap: .4rem; }
        .btn-icon {
            width: 32px; height: 32px; border: none; border-radius: 7px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: .8rem; transition: transform .15s, filter .15s;
        }
        .btn-edit   { background: #dbeafe; color: #2563eb; }
        .btn-delete { background: #fee2e2; color: #ef4444; }
        .btn-icon:hover { transform: scale(1.1); filter: brightness(.9); }

        .empty-state {
            padding: 3rem 1rem; text-align: center; color: var(--subtext);
        }
        .empty-state i { font-size: 2.2rem; margin-bottom: .5rem; display: block; opacity: .35; }
        .empty-state p { font-size: .88rem; }

        /* ── Form ── */
        .form-body { padding: 1.25rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: .3rem; }
        .form-label {
            font-size: .73rem; font-weight: 700;
            color: var(--subtext); text-transform: uppercase; letter-spacing: .5px;
        }
        .form-control {
            padding: .6rem .9rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: .9rem; color: var(--text);
            background: var(--bg); font-family: inherit; outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15,118,110,.12);
            background: var(--card);
        }

        .form-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: flex-end; gap: .6rem;
        }
        .btn-submit {
            display: flex; align-items: center; gap: .45rem;
            padding: .62rem 1.5rem;
            background: #0f766e; color: #fff;
            border: none; border-radius: 8px;
            font-size: .88rem; font-weight: 700;
            cursor: pointer; font-family: inherit;
            transition: background .2s, transform .15s;
            box-shadow: 0 3px 10px rgba(15,118,110,.25);
        }
        .btn-submit:hover { background: #0d5f58; transform: translateY(-1px); }
        .btn-reset {
            padding: .62rem 1.1rem;
            background: var(--bg); color: var(--subtext);
            border: 1px solid var(--border); border-radius: 8px;
            font-size: .88rem; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: background .2s;
        }
        .btn-reset:hover { background: var(--border); }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ========== SIDEBAR — exact copy from dashboard ========== -->
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

        <!-- HEADER — exact same structure as dashboard -->
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

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <button class="tab-btn active" id="tab-existing" onclick="switchTab('existing')">
                    <i class="fa-solid fa-users"></i>
                    Existing Users
                    <span class="tab-badge"><?php echo count($users); ?></span>
                </button>
                <button class="tab-btn" id="tab-add" onclick="switchTab('add')">
                    <i class="fa-solid fa-user-plus"></i>
                    Add New User
                </button>
            </div>

            <!-- Panel: Existing Users -->
            <div class="tab-panel active" id="panel-existing">
                <div class="u-card">
                    <div class="u-card-header">
                        <div class="u-card-title"><i class="fa-solid fa-users"></i> All Users</div>
                        <span style="font-size:.8rem;color:var(--subtext);font-weight:600;"><?php echo count($users); ?> records</span>
                    </div>
                    <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-users-slash"></i>
                        <p>No users found. Click <strong>Add New User</strong> to get started.</p>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Emp ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Area</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u):
                                $r  = strtolower($u['role'] ?? '');
                                $rc = $r === 'admin' ? 'admin' : ($r === 'gm' ? 'gm' : ($r === 'user' ? 'plain' : ''));
                            ?>
                                <tr>
                                    <td><span class="emp-id"><?= htmlspecialchars($u['id']) ?></span></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                                    <td style="color:var(--subtext);font-size:.82rem;"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                                    <td><span class="role-badge <?= $rc ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td style="font-size:.84rem;color:var(--subtext);"><?= htmlspecialchars($u['SiteName'] ?? '—') ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="edit_user.php?id=<?= $u['id'] ?>">
                                                <button class="btn-icon btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                            </a>
                                            <button class="btn-icon btn-delete" title="Delete" onclick="deleteUser(<?= $u['id'] ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel: Add New User -->
            <div class="tab-panel" id="panel-add">
                <div class="u-card">
                    <div class="u-card-header">
                        <div class="u-card-title"><i class="fa-solid fa-user-plus"></i> Add New User</div>
                    </div>
                    <form method="POST" action="add_user.php">
                        <div class="form-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">User ID</label>
                                    <input class="form-control" type="text" name="emp_id" placeholder="e.g. 1007" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">user Name</label>
                                    <input class="form-control" type="text" name="name" placeholder="Full name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input class="form-control" type="email" name="email" placeholder="email@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Password</label>
                                    <input class="form-control" type="text" name="password" placeholder="Set a password" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Role</label>
                                    <select class="form-control" name="role" required>
                                        <option value="">-- Select Role --</option>
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
                                    <label class="form-label">Area / Site</label>
                                    <select class="form-control" name="site_code" required>
                                        <option value="">-- Select Area --</option>
                                        <?php
                                        $sites = $pdo->query("SELECT SiteCode, SiteName FROM site_master");
                                        while ($row = $sites->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . htmlspecialchars($row['SiteCode']) . "'>" . htmlspecialchars($row['SiteName']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-footer">
                            <button type="reset"  class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                            <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Add User</button>
                        </div>
                    </form>
                </div>
            </div>

        </div><!-- /.page-content -->
    </main>
</div>

<script src="assets/users.js"></script>
<script>
/* ── Tab switching ── */
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

/* ── Sidebar toggle — identical to dashboard ── */
const menuBtn = document.getElementById('menuBtn');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');

function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

menuBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar .menu').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); });
});
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }
});

/* ── Theme toggle — identical to dashboard ── */
const themeToggle = document.getElementById('themeToggle');
if (localStorage.getItem('theme') === 'dark') { document.body.classList.add('dark'); themeToggle.checked = true; }
themeToggle.addEventListener('change', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});
</script>
</body>
</html>