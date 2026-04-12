<?php
session_start();
require "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// Fetch user details from user table
$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION["user"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/useratt.css">
    <style>
    /* ============================================================
       KEYFRAMES (same as dashboard)
    ============================================================ */
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-44px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes logoFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes navReveal {
        from { opacity: 0; transform: translateX(-18px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes fadeDown {
        from { opacity: 0; transform: translateY(-14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    /* ============================================================
       SIDEBAR ENHANCEMENTS (same as dashboard)
    ============================================================ */
    .sidebar {
        box-shadow: 4px 0 22px rgba(13,95,88,0.32);
        animation: slideInLeft 0.5s cubic-bezier(0.22,1,0.36,1) both;
    }

    .sidebar .logo {
        padding-bottom: 22px;
        border-bottom: 1px solid rgba(255,255,255,0.15);
        margin-bottom: 26px !important;
        font-size: unset !important;
        font-weight: unset !important;
    }

    .sidebar .logo img {
        max-width: 140px;
        height: auto;
        display: block;
        margin: 0 auto;
        background: #fff;
        padding: 10px 14px;
        border-radius: 10px;
        animation: logoFadeUp 0.5s 0.1s ease both;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .sidebar .logo img:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }

    .sidebar ul li {
        opacity: 0;
        animation: navReveal 0.4s ease forwards;
        display: flex;
        align-items: center;
        padding: 0 !important;
        border-radius: 10px !important;
        transition: background 0.22s ease, box-shadow 0.22s ease, transform 0.2s ease;
    }
    .sidebar ul li:nth-child(1) { animation-delay: 0.30s; }
    .sidebar ul li:nth-child(2) { animation-delay: 0.42s; }
    .sidebar ul li:nth-child(3) { animation-delay: 0.54s; }
    .sidebar ul li:nth-child(4) { animation-delay: 0.66s; }

    .sidebar ul li:hover {
        background: rgba(255,255,255,0.15) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.13);
        transform: translateX(4px);
    }
    .sidebar ul li.active {
        background: rgba(255,255,255,0.22) !important;
        transform: translateX(4px);
    }

    .sidebar ul li a {
        display: flex !important;
        align-items: center !important;
        gap: 13px !important;
        padding: 13px 16px !important;
        color: rgba(255,255,255,0.85) !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        border-radius: 10px !important;
        width: 100%;
        transition: color 0.2s ease !important;
    }

    .sidebar ul li:hover a,
    .sidebar ul li.active a {
        color: #fff !important;
        font-weight: 600 !important;
    }

    .sidebar ul li a i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.80;
        transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s ease;
    }

    .sidebar ul li:hover a i  { transform: scale(1.28) rotate(-6deg); opacity: 1; }
    .sidebar ul li.active a i { transform: scale(1.18); opacity: 1; }



    /* ============================================================
       TOPBAR (same as dashboard)
    ============================================================ */
    .topbar {
        animation: fadeDown 0.4s 0.3s ease both;
        position: relative;
        height: 80px;
    }
    .topbar h2 {
        position: absolute;
        left: 50%; transform: translateX(-50%);
        margin: 0;
        white-space: nowrap;
    }
    .main { animation: fadeIn 0.4s 0.35s ease both; }

    /* ============================================================
       PROFILE CARD
    ============================================================ */
    .profile-wrapper {
        display: flex;
        justify-content: center;
        padding: 10px 0 40px;
    }

    .profile-page {
        width: 100%;
        max-width: 820px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.10);
        padding: 36px;
        animation: fadeIn 0.45s 0.4s ease both;
        opacity: 0;
    }

    /* ── Top section ── */
    .profile-top {
        display: flex;
        gap: 24px;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 24px;
        flex-wrap: wrap;
    }

    .profile-avatar {
        width: 88px;
        height: 88px;
        background: linear-gradient(135deg, #0f766e, #0d9488);
        color: #fff;
        border-radius: 50%;
        font-size: 34px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(15,118,110,0.35);
        letter-spacing: 1px;
    }

    .profile-name h2 {
        font-size: 22px;
        color: #1a1a1a;
        margin-bottom: 4px;
    }

    .profile-name .sub {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .role-badge {
        display: inline-block;
        background: #16a34a;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 14px;
        border-radius: 14px;
        letter-spacing: 0.4px;
    }

    /* ── Info grid ── */
    .profile-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-top: 28px;
    }

    .info-box {
        background: #f9fafb;
        border-radius: 10px;
        padding: 18px 20px;
        border-left: 3px solid #0f766e;
        transition: transform 0.18s, box-shadow 0.18s;
    }

    .info-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    }

    .info-box h4 {
        font-size: 12px;
        color: #888;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 6px;
    }

    .info-box p {
        font-size: 16px;
        font-weight: 600;
        color: #1a1a1a;
        word-break: break-word;
    }

    /* ── Actions ── */
    .profile-actions {
        margin-top: 32px;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 11px 22px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.18s, box-shadow 0.18s;
        border: none;
        white-space: nowrap;
    }

    .action-btn.primary {
        background: #0f766e;
        color: #fff;
    }

    .action-btn.primary:hover {
        background: #0d9488;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15,118,110,0.3);
    }

    /* ============================================================
       TOPBAR MOBILE FIX
    ============================================================ */
    @media (max-width: 600px) {
        .topbar h2 {
            position: static;
            transform: none;
            font-size: 14px;
            text-align: center;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-page  { padding: 22px 16px; }
        .profile-top   { flex-direction: column; text-align: center; }
        .profile-avatar { margin: 0 auto; }

        .profile-info  { grid-template-columns: 1fr; }

        .profile-actions { justify-content: center; }
        .action-btn { width: 100%; justify-content: center; }
    }

    /* ── Change Password Modal ── */
    .modal-overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
        z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(3px);
    }
    .modal-overlay.active { display:flex; }
    .modal-box {
        background:#fff; border-radius:14px; width:400px; max-width:92vw;
        box-shadow:0 20px 60px rgba(0,0,0,0.2); overflow:hidden;
        animation:modalIn .35s ease;
    }
    @keyframes modalIn { from{opacity:0;transform:scale(.93) translateY(20px);} to{opacity:1;transform:scale(1) translateY(0);} }
    .modal-header {
        background:linear-gradient(135deg,#0f766e,#0d5f58); padding:1.25rem 1.5rem;
        display:flex; align-items:center; justify-content:space-between;
    }
    .modal-header h3 { color:#fff; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem; }
    .modal-close {
        background:rgba(255,255,255,.15); border:none; color:#fff; width:30px; height:30px;
        border-radius:8px; cursor:pointer; font-size:.9rem; display:flex; align-items:center; justify-content:center;
    }
    .modal-body { padding:1.5rem; }
    .modal-step { display:none; }
    .modal-step.active { display:block; }
    .form-group { margin-bottom:1rem; }
    .form-group label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.4rem; }
    .form-group input {
        width:100%; padding:.7rem 1rem; border:1.5px solid #d1d5db; border-radius:8px;
        font-size:.9rem; outline:none; transition:border-color .2s;
    }
    .form-group input:focus { border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.1); }
    .modal-msg {
        padding:.5rem .8rem; border-radius:7px; font-size:.82rem; font-weight:600; margin-bottom:1rem; display:none;
    }
    .modal-msg.error { display:flex; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; align-items:center; gap:.4rem; }
    .modal-msg.success { display:flex; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; align-items:center; gap:.4rem; }
    .modal-btn {
        width:100%; padding:.7rem; border:none; border-radius:8px; font-size:.9rem; font-weight:700;
        cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem;
        background:linear-gradient(135deg,#0f766e,#0d5f58); color:#fff;
        box-shadow:0 3px 10px rgba(15,118,110,.3); transition:filter .2s,transform .15s;
    }
    .modal-btn:hover { filter:brightness(1.07); transform:translateY(-1px); }
    .modal-btn:disabled { opacity:.6; cursor:not-allowed; }
    </style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container">

    <!-- ===================================================
         SIDEBAR
    =================================================== -->
    <aside class="sidebar" id="sidebar">

        <button class="sidebar-close" id="sidebarClose">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Logo -->
        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>

        <!-- Nav — no item is "active" here since profile isn't in the nav,
             but Dashboard is highlighted to keep context clear -->
        <ul>
            <li>
                <a href="user_dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="user_attendance.php">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Upload Attendance
                </a>
            </li>
            <li>
                <a href="user_update_attendance.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    Update Attendance
                </a>
            </li>

        </ul>

    </aside>

    <!-- ===================================================
         MAIN
    =================================================== -->
    <main class="main">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <h2>Security Attendance and Billing Portal</h2>
            <div class="topbar-right">
                <div class="user-icon">
                    <a href="user_profile.php" aria-label="Profile">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="8" r="4"/>
                        </svg>
                    </a>
                </div>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </header>

        <!-- Profile Content -->
        <div class="profile-wrapper">
            <div class="profile-page">

                <!-- Top -->
                <div class="profile-top">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                    </div>
                    <div class="profile-name">
                        <h2><?= htmlspecialchars($user['name'] ?? 'N/A') ?></h2>
                        <p class="sub">Employee ID: <?= htmlspecialchars($user['id'] ?? 'N/A') ?></p>
                        <span class="role-badge"><?= htmlspecialchars($user['role'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="profile-info">
                    <div class="info-box">
                        <h4><i class="fa-solid fa-envelope" style="margin-right:5px;color:#0f766e;"></i>Email</h4>
                        <p><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                    </div>
                    <div class="info-box">
                        <h4><i class="fa-solid fa-location-dot" style="margin-right:5px;color:#0f766e;"></i>Area / Site</h4>
                        <p><?= htmlspecialchars($user['site'] ?? 'N/A') ?></p>
                    </div>
                    <div class="info-box">
                        <h4><i class="fa-solid fa-user-shield" style="margin-right:5px;color:#0f766e;"></i>Role</h4>
                        <p><?= htmlspecialchars($user['role'] ?? 'N/A') ?></p>
                    </div>
                    <div class="info-box">
                        <h4><i class="fa-solid fa-calendar" style="margin-right:5px;color:#0f766e;"></i>Account Created</h4>
                        <p><?= date('d-m-Y H:i', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="profile-actions">
                    <a href="user_dashboard.php" class="action-btn primary">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <button class="action-btn primary" onclick="document.getElementById('pwModal').classList.add('active')" style="background:#d97706;border:none;cursor:pointer;">
                        <i class="fa-solid fa-key"></i>
                        Change Password
                    </button>
                </div>

            </div>
        </div>

        <footer>© 2026 MCL — All Rights Reserved</footer>

    </main>
</div>

<!-- ═══════════════ CHANGE PASSWORD MODAL ═══════════════ -->
<div class="modal-overlay" id="pwModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fa-solid fa-key"></i> Change Password</h3>
      <button class="modal-close" onclick="closePwModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-msg" id="pwMsg"></div>

      <!-- Step 1: Verify Google Authenticator Code -->
      <div class="modal-step active" id="pwStep1">
        <p style="font-size:.84rem;color:#6b7280;margin-bottom:1rem;">Enter the 6-digit code from your Google Authenticator app to verify your identity.</p>
        <div class="form-group">
          <label>Authenticator Code</label>
          <input type="text" id="authCode" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit code" autocomplete="off">
        </div>
        <button class="modal-btn" id="verifyCodeBtn" onclick="verifyCode()">
          <i class="fa-solid fa-shield-halved"></i> Verify & Proceed
        </button>
      </div>

      <!-- Step 2: New Password -->
      <div class="modal-step" id="pwStep2">
        <p style="font-size:.84rem;color:#16a34a;margin-bottom:1rem;font-weight:600;"><i class="fa-solid fa-circle-check"></i> Identity verified. Enter your new password.</p>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" id="newPassword" placeholder="At least 6 characters">
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" id="confirmPassword" placeholder="Re-enter new password">
        </div>
        <button class="modal-btn" id="changePwBtn" onclick="changePassword()">
          <i class="fa-solid fa-lock"></i> Update Password
        </button>
      </div>
    </div>
  </div>
</div>

<script>
    /* ── Mobile sidebar ── */
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarClose');

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

    hamburger && hamburger.addEventListener('click', openSidebar);
    closeBtn  && closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });
    /* ── Change Password Modal Logic ── */
    function closePwModal() {
        document.getElementById('pwModal').classList.remove('active');
        document.getElementById('pwStep1').classList.add('active');
        document.getElementById('pwStep2').classList.remove('active');
        document.getElementById('pwMsg').className='modal-msg';
        document.getElementById('pwMsg').textContent='';
        document.getElementById('authCode').value='';
        document.getElementById('newPassword').value='';
        document.getElementById('confirmPassword').value='';
    }

    // Click outside modal to close
    document.getElementById('pwModal').addEventListener('click', function(e) {
        if (e.target === this) closePwModal();
    });

    async function verifyCode() {
        const btn = document.getElementById('verifyCodeBtn');
        const code = document.getElementById('authCode').value.trim();
        const msg = document.getElementById('pwMsg');
        if (!/^[0-9]{6}$/.test(code)) { showMsg(msg,'error','Please enter a valid 6-digit code.'); return; }
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';
        try {
            const fd = new FormData(); fd.append('action','verify_code'); fd.append('code',code);
            const res = await fetch('change_password.php',{method:'POST',body:fd});
            const data = await res.json();
            if (data.success) {
                document.getElementById('pwStep1').classList.remove('active');
                document.getElementById('pwStep2').classList.add('active');
                msg.className='modal-msg'; msg.textContent='';
            } else { showMsg(msg,'error',data.message); }
        } catch(e) { showMsg(msg,'error','Network error.'); }
        btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Verify & Proceed';
    }

    async function changePassword() {
        const btn = document.getElementById('changePwBtn');
        const np = document.getElementById('newPassword').value;
        const cp = document.getElementById('confirmPassword').value;
        const msg = document.getElementById('pwMsg');
        if (np.length < 6) { showMsg(msg,'error','Password must be at least 6 characters.'); return; }
        if (np !== cp) { showMsg(msg,'error','Passwords do not match.'); return; }
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
        try {
            const fd = new FormData(); fd.append('action','change_password'); fd.append('new_password',np); fd.append('confirm_password',cp);
            const res = await fetch('change_password.php',{method:'POST',body:fd});
            const data = await res.json();
            if (data.success) { showMsg(msg,'success',data.message); btn.innerHTML = '<i class="fa-solid fa-check"></i> Done!'; setTimeout(()=>closePwModal(),2000); }
            else { showMsg(msg,'error',data.message); btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-lock"></i> Update Password'; }
        } catch(e) { showMsg(msg,'error','Network error.'); btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-lock"></i> Update Password'; }
    }

    function showMsg(el,type,text) { el.className='modal-msg '+type; el.innerHTML='<i class="fa-solid fa-'+(type==='error'?'circle-exclamation':'circle-check')+'"></i> '+text; }
</script>

</body>
</html>