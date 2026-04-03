<?php
session_start();
require "../config.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'HQSO') {
    die("Access denied");
}

$initials  = strtoupper(substr($user['name'], 0, 1));
$createdAt = isset($user['created_at']) ? date('d-m-Y H:i', strtotime($user['created_at'])) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile – HQSO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root { --primary:#0f766e; --sidebar-width:270px; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; }

        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }

        /* Sidebar */
        .sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; position:sticky; top:0; height:100vh; overflow-y:auto; display:flex; flex-direction:column; }
        .sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .mcl-logo-img { max-width:155px; height:auto; background:white; padding:10px 14px; border-radius:10px; }
        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li { margin:0.25rem 1rem; }
        .nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; }
        .nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i { font-size:1.05rem; width:22px; text-align:center; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }

        /* Main */
        .main-content { padding:2rem; display:flex; flex-direction:column; gap:1.5rem; }

        /* Topbar */
        .topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .topbar h2 { font-size:1.4rem; font-weight:700; color:#1f2937; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; border-radius:20px; padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700; }
        .header-icon { width:40px; height:40px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#6b7280; font-size:1rem; border:1px solid #e5e7eb; position:relative; }
        .header-icon .badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:0.65rem; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-icon { width:40px; height:40px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; color:white; font-size:1rem; font-weight:700; text-decoration:none; }

        /* Profile card */
        .profile-wrapper { display:flex; justify-content:center; align-items:flex-start; padding:1rem 0; }
        .profile-card { background:white; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.10); border:1px solid #e5e7eb; width:100%; max-width:680px; padding:2rem 2.5rem 2rem; }

        /* Header section */
        .profile-header { display:flex; align-items:center; gap:1.25rem; padding-bottom:1.5rem; border-bottom:1px solid #f0f0f0; margin-bottom:1.5rem; }
        .avatar { width:68px; height:68px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:800; color:white; flex-shrink:0; }
        .profile-header-info { flex:1; }
        .profile-name { font-size:1.4rem; font-weight:800; color:#1f2937; margin-bottom:3px; }
        .profile-empid { font-size:0.9rem; color:#6b7280; margin-bottom:6px; }
        .role-chip { display:inline-flex; align-items:center; gap:0.35rem; background:#0f766e; color:white; border-radius:20px; padding:0.25rem 0.9rem; font-size:0.78rem; font-weight:700; letter-spacing:0.3px; }

        /* Info grid */
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem; }
        .info-box { background:#f9fafb; border-radius:10px; padding:1rem 1.25rem; border:1px solid #f0f0f0; }
        .info-label { font-size:0.75rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.4rem; }
        .info-value { font-size:0.95rem; font-weight:600; color:#1f2937; word-break:break-word; }
        .info-value.muted { color:#6b7280; font-weight:400; font-style:italic; }

        /* Back button */
        .btn-back { display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.75rem; background:#0f766e; color:white; border:none; border-radius:10px; font-size:0.95rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all 0.2s; float:right; }
        .btn-back:hover { background:#0d5f58; transform:translateY(-1px); box-shadow:0 4px 14px rgba(15,118,110,0.3); }
        .btn-back i { font-size:0.9rem; }
        .clearfix::after { content:''; display:table; clear:both; }

        @media(max-width:768px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { display:none; }
            .info-grid { grid-template-columns:1fr; }
            .profile-card { padding:1.25rem; }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthly.php" class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <h2>Security Billing Management Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-shield"></i> HQSO</span>
                <div class="header-icon">
                    <i class="fa-regular fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <a href="profile.php" class="user-icon" title="My Profile"><?= htmlspecialchars($initials) ?></a>
            </div>
        </header>

        <!-- Profile Card -->
        <div class="profile-wrapper">
            <div class="profile-card">

                <!-- Header -->
                <div class="profile-header">
                    <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="profile-header-info">
                        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="profile-empid">Emp ID: <?= htmlspecialchars($user['id']) ?></div>
                        <span class="role-chip"><i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($user['role']) ?></span>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-envelope" style="color:#0f766e;margin-right:4px;"></i> Email</div>
                        <div class="info-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-location-dot" style="color:#0f766e;margin-right:4px;"></i> Area / Site</div>
                        <?php if (!empty($user['site_code'])): ?>
                            <?php
                            $stmtSite = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
                            $stmtSite->execute([$user['site_code']]);
                            $siteName = $stmtSite->fetchColumn();
                            ?>
                            <div class="info-value"><?= htmlspecialchars($user['site_code']) ?><?= $siteName ? ' – ' . htmlspecialchars($siteName) : '' ?></div>
                        <?php else: ?>
                            <div class="info-value muted">All Sites (HQSO)</div>
                        <?php endif; ?>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-id-badge" style="color:#0f766e;margin-right:4px;"></i> Role</div>
                        <div class="info-value"><?= htmlspecialchars($user['role']) ?></div>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-calendar-plus" style="color:#0f766e;margin-right:4px;"></i> Account Created</div>
                        <div class="info-value"><?= $createdAt ?></div>
                    </div>

                    <?php if (!empty($user['phone'])): ?>
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-phone" style="color:#0f766e;margin-right:4px;"></i> Phone</div>
                        <div class="info-value"><?= htmlspecialchars($user['phone']) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($user['username'])): ?>
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-at" style="color:#0f766e;margin-right:4px;"></i> Username</div>
                        <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Back button -->
                <div class="clearfix">
                    <a href="monthly.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>