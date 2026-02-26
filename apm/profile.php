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

if (!$user || $user['role'] !== 'APM') {
    die("Access denied");
}

$initials  = strtoupper(substr($user['name'], 0, 1));
$createdAt = isset($user['created_at']) ? date('d-m-Y H:i', strtotime($user['created_at'])) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile – APM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root { --primary:#0f766e; --sidebar-width:270px; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; overflow-x:hidden; }
        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(22px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity:0; }
            to   { opacity:1; }
        }
        @keyframes fadeDown {
            from { opacity:0; transform:translateY(-16px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity:0; transform:translateX(-40px); }
            to   { opacity:1; transform:translateX(0); }
        }
        @keyframes navItemReveal {
            from { opacity:0; transform:translateX(-14px); }
            to   { opacity:1; transform:translateX(0); }
        }
        @keyframes cardPop {
            from { opacity:0; transform:translateY(20px) scale(0.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        @keyframes avatarPop {
            0%   { opacity:0; transform:scale(0.5) rotate(-10deg); }
            70%  { transform:scale(1.1) rotate(3deg); }
            100% { opacity:1; transform:scale(1) rotate(0deg); }
        }
        @keyframes infoBoxSlide {
            from { opacity:0; transform:translateX(-12px); }
            to   { opacity:1; transform:translateX(0); }
        }
        @keyframes dotPulse {
            0%, 100% { transform:scale(1); box-shadow:0 0 0 0 rgba(239,68,68,0.4); }
            50%       { transform:scale(1.1); box-shadow:0 0 0 6px rgba(239,68,68,0); }
        }

        /* SIDEBAR */
        .sidebar {
            background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%);
            color:white; padding:0;
            position:sticky; top:0; height:100vh; overflow-y:auto;
            display:flex; flex-direction:column;
            animation: slideInLeft 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .sidebar-logo {
            padding:1.4rem 1.5rem 1.2rem;
            border-bottom:1px solid rgba(255,255,255,0.15);
            display:flex; align-items:center; justify-content:center;
        }
        .mcl-logo-img {
            max-width:155px; height:auto;
            background:white; padding:10px 14px; border-radius:10px;
            animation: fadeUp 0.5s 0.15s ease both;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .mcl-logo-img:hover { transform:scale(1.04); box-shadow:0 4px 16px rgba(0,0,0,0.15); }

        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li {
            margin:0.25rem 1rem;
            opacity:0;
            animation: navItemReveal 0.4s ease forwards;
        }
        .sidebar-nav li:nth-child(1) { animation-delay:0.25s; }
        .sidebar-nav li:nth-child(2) { animation-delay:0.35s; }
        .sidebar-nav li:nth-child(3) { animation-delay:0.45s; }

        .nav-link {
            display:flex; align-items:center; gap:0.9rem;
            padding:0.85rem 1.1rem;
            color:rgba(255,255,255,0.88); text-decoration:none;
            border-radius:12px;
            transition: background 0.2s, color 0.2s, transform 0.2s, box-shadow 0.2s;
            font-weight:500; font-size:0.95rem;
        }
        .nav-link:hover  { background:rgba(255,255,255,0.15); color:#fff; transform:translateX(5px); box-shadow:0 4px 12px rgba(0,0,0,0.12); }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i {
            font-size:1.05rem; width:22px; text-align:center; opacity:0.9;
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        .nav-link:hover i  { transform:scale(1.25) rotate(-6deg); opacity:1; }
        .nav-link.active i { transform:scale(1.15); opacity:1; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }
        .logout-link:hover i { color:#fca5a5; transform:scale(1.2) translateX(2px) !important; }

        /* MAIN */
        .main-content {
            padding:2rem; display:flex; flex-direction:column; gap:1.5rem;
            animation: fadeIn 0.4s 0.2s ease both;
        }

        /* TOPBAR */
        .topbar {
            display:flex; justify-content:space-between; align-items:center;
            background:white; border-radius:14px; padding:1rem 1.5rem;
            box-shadow:0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border:1px solid rgba(15,118,110,0.15);
            opacity:0;
            animation: fadeDown 0.5s 0.3s ease forwards;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .topbar:hover { box-shadow:0 8px 24px rgba(15,118,110,0.18); border-color:rgba(16,185,129,0.3); }
        .topbar h2 { font-size:1.4rem; font-weight:700; color:#1f2937; }
        .topbar-right { display:flex; align-items:center; gap:12px; }

        .role-badge {
            display:inline-flex; align-items:center; gap:0.4rem;
            background:#fef2f2; color:#dc2626;
            border:1.5px solid #fca5a5; border-radius:20px;
            padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .role-badge:hover { transform:scale(1.05); box-shadow:0 2px 8px rgba(220,38,38,0.2); }

        .header-icon {
            width:40px; height:40px; border-radius:50%;
            background:#f3f4f6;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; color:#6b7280; font-size:1rem;
            border:1px solid #e5e7eb; position:relative;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .header-icon:hover { background:#e5e7eb; transform:scale(1.1); box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .header-icon .badge {
            position:absolute; top:-4px; right:-4px;
            background:#ef4444; color:white;
            font-size:0.65rem; width:18px; height:18px;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-weight:700;
            animation: dotPulse 2s ease-in-out infinite;
        }
        .user-icon {
            width:40px; height:40px; border-radius:50%;
            background:#0f766e;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; color:white; font-size:1rem; font-weight:700;
            text-decoration:none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .user-icon:hover { transform:scale(1.1); box-shadow:0 4px 12px rgba(15,118,110,0.35); }

        /* PROFILE WRAPPER */
        .profile-wrapper {
            display:flex; justify-content:center; align-items:flex-start; padding:1rem 0;
        }

        /* PROFILE CARD */
        .profile-card {
            background:white; border-radius:16px;
            box-shadow:0 4px 24px rgba(0,0,0,0.10);
            border:1px solid #e5e7eb;
            width:100%; max-width:680px;
            padding:2rem 2.5rem 2rem;
            opacity:0;
            animation: cardPop 0.5s 0.4s cubic-bezier(0.22,1,0.36,1) forwards;
            transition: box-shadow 0.3s, border-color 0.3s;
        }
        .profile-card:hover { box-shadow:0 12px 36px rgba(15,118,110,0.16); border-color:rgba(15,118,110,0.25); }

        /* PROFILE HEADER */
        .profile-header {
            display:flex; align-items:center; gap:1.25rem;
            padding-bottom:1.5rem; border-bottom:1px solid #f0f0f0; margin-bottom:1.5rem;
        }

        .avatar {
            width:68px; height:68px; border-radius:50%;
            background:#0f766e;
            display:flex; align-items:center; justify-content:center;
            font-size:1.8rem; font-weight:800; color:white; flex-shrink:0;
            opacity:0;
            animation: avatarPop 0.55s 0.55s cubic-bezier(0.22,1,0.36,1) forwards;
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .avatar:hover { transform:scale(1.08); box-shadow:0 6px 20px rgba(15,118,110,0.4); }

        .profile-header-info {
            flex:1;
            opacity:0;
            animation: fadeUp 0.4s 0.65s ease forwards;
        }
        .profile-name  { font-size:1.4rem; font-weight:800; color:#1f2937; margin-bottom:3px; }
        .profile-empid { font-size:0.9rem; color:#6b7280; margin-bottom:6px; }
        .role-chip {
            display:inline-flex; align-items:center; gap:0.35rem;
            background:#0f766e; color:white; border-radius:20px;
            padding:0.25rem 0.9rem; font-size:0.78rem; font-weight:700; letter-spacing:0.3px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .role-chip:hover { transform:scale(1.05); box-shadow:0 3px 10px rgba(15,118,110,0.35); }

        /* INFO GRID */
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem; }

        .info-box {
            background:#f9fafb; border-radius:10px;
            padding:1rem 1.25rem; border:1px solid #f0f0f0;
            opacity:0;
            animation: infoBoxSlide 0.35s ease forwards;
            transition: background 0.2s, border-color 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        /* staggered delays for info boxes */
        .info-box:nth-child(1) { animation-delay:0.75s; }
        .info-box:nth-child(2) { animation-delay:0.83s; }
        .info-box:nth-child(3) { animation-delay:0.91s; }
        .info-box:nth-child(4) { animation-delay:0.99s; }
        .info-box:nth-child(5) { animation-delay:1.07s; }
        .info-box:nth-child(6) { animation-delay:1.15s; }

        .info-box:hover {
            background:#f0fdf9; border-color:#a7f3d0;
            transform:translateY(-2px);
            box-shadow:0 4px 14px rgba(15,118,110,0.1);
        }

        .info-label {
            font-size:0.75rem; font-weight:700; color:#9ca3af;
            text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.4rem;
        }
        .info-value { font-size:0.95rem; font-weight:600; color:#1f2937; word-break:break-word; }
        .info-value.muted { color:#6b7280; font-weight:400; font-style:italic; }
        .info-label i { color:#0f766e; margin-right:4px; }

        /* BACK BUTTON */
        .btn-back {
            display:inline-flex; align-items:center; gap:0.5rem;
            padding:0.75rem 1.75rem; background:#0f766e; color:white;
            border:none; border-radius:10px;
            font-size:0.95rem; font-weight:600; cursor:pointer;
            text-decoration:none;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            float:right;
            opacity:0;
            animation: fadeUp 0.4s 1.2s ease forwards;
            position: relative; overflow: hidden;
        }
        .btn-back::after {
            content:''; position:absolute; inset:0;
            background:rgba(255,255,255,0.2); transform:scale(0);
            border-radius:inherit; transition:transform 0.3s ease;
        }
        .btn-back:active::after { transform:scale(2.5); opacity:0; transition:none; }
        .btn-back:hover { background:#0d5f58; transform:translateY(-2px); box-shadow:0 6px 16px rgba(15,118,110,0.35); }
        .btn-back i { font-size:0.9rem; transition: transform 0.2s; }
        .btn-back:hover i { transform: translateX(-3px); }

        .clearfix::after { content:''; display:table; clear:both; }

        @media(max-width:768px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { display:none; }
            .info-grid { grid-template-columns:1fr; }
            .profile-card { padding:1.25rem; }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthly.php"   class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h2>Security Billing Management Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-gear"></i> APM</span>
                <div class="header-icon">
                    <i class="fa-regular fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <a href="profile.php" class="user-icon" title="My Profile"><?= htmlspecialchars($initials) ?></a>
            </div>
        </header>

        <div class="profile-wrapper">
            <div class="profile-card">

                <div class="profile-header">
                    <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="profile-header-info">
                        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="profile-empid">Emp ID: <?= htmlspecialchars($user['id']) ?></div>
                        <span class="role-chip"><i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($user['role']) ?></span>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-envelope"></i> Email</div>
                        <div class="info-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-location-dot"></i> Area / Site</div>
                        <?php if (!empty($user['site_code'])): ?>
                            <?php
                            $stmtSite = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
                            $stmtSite->execute([$user['site_code']]);
                            $siteName = $stmtSite->fetchColumn();
                            ?>
                            <div class="info-value"><?= htmlspecialchars($user['site_code']) ?><?= $siteName ? ' – ' . htmlspecialchars($siteName) : '' ?></div>
                        <?php else: ?>
                            <div class="info-value muted">All Sites (APM)</div>
                        <?php endif; ?>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-id-badge"></i> Role</div>
                        <div class="info-value"><?= htmlspecialchars($user['role']) ?></div>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-calendar-plus"></i> Account Created</div>
                        <div class="info-value"><?= $createdAt ?></div>
                    </div>

                    <?php if (!empty($user['phone'])): ?>
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-phone"></i> Phone</div>
                        <div class="info-value"><?= htmlspecialchars($user['phone']) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($user['username'])): ?>
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-at"></i> Username</div>
                        <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="clearfix">
                    <a href="apm_dashboard.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>