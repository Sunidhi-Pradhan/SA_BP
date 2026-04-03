<?php
session_start();
require "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$_SESSION["user"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'Admin') {
    die("Access denied");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile – Security Billing Management Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/desh.css">
<style>
/* ── Profile Page ── */
.profile-wrapper {
    flex: 1; display: flex; align-items: flex-start; justify-content: center;
    padding: 2rem 1.5rem;
    animation: contentFadeUp 0.5s 0.2s ease both;
}
.profile-page {
    width: 100%; max-width: 700px;
    background: var(--card); border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    border: 1px solid var(--border);
    overflow: hidden;
}
.profile-header {
    background: linear-gradient(135deg, #0f766e 0%, #0a5c55 100%);
    padding: 2rem 2rem 1.5rem;
    text-align: center; color: white;
}
.profile-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(255,255,255,0.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; font-weight: 800; color: #fff;
    margin: 0 auto 1rem; letter-spacing: 1px;
    border: 3px solid rgba(255,255,255,0.3);
}
.profile-header h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: .25rem; }
.profile-header .sub { font-size: .85rem; opacity: .8; }
.role-badge {
    display: inline-block; margin-top: .5rem;
    background: rgba(255,255,255,0.2); color: #fff;
    border: 1px solid rgba(255,255,255,0.35);
    padding: .25rem .9rem; border-radius: 20px;
    font-size: .78rem; font-weight: 700; letter-spacing: .5px;
}
.profile-body { padding: 1.75rem 2rem; }
.profile-info {
    display: grid; grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
}
.info-box {
    background: var(--bg); border-radius: 10px;
    padding: 1rem 1.25rem; border: 1px solid var(--border);
    transition: transform .2s, box-shadow .2s;
}
.info-box:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
.info-box h4 { font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; color: var(--subtext); margin-bottom: .4rem; font-weight: 600; }
.info-box p { font-size: .92rem; font-weight: 600; color: var(--text); word-break: break-word; }

.profile-actions {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.action-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .65rem 1.25rem; border-radius: 8px;
    font-size: .88rem; font-weight: 600; text-decoration: none;
    border: none; cursor: pointer; transition: all .2s;
    font-family: inherit;
}
.action-btn.primary {
    background: #0f766e; color: #fff;
}
.action-btn.primary:hover { background: #0d9488; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,118,110,0.3); }
.action-btn.amber { background: #d97706; color: #fff; }
.action-btn.amber:hover { background: #b45309; transform: translateY(-1px); }

/* ── Change Password Modal ── */
.modal-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
    z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(3px);
}
.modal-overlay.active { display:flex; }
.modal-box {
    background: var(--card); border-radius:14px; width:400px; max-width:92vw;
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
.form-group label { display:block; font-size:.82rem; font-weight:600; color:var(--text); margin-bottom:.4rem; }
.form-group input {
    width:100%; padding:.7rem 1rem; border:1.5px solid var(--border); border-radius:8px;
    font-size:.9rem; outline:none; transition:border-color .2s; background:var(--bg); color:var(--text);
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

/* ── Dark mode extras ── */
body.dark .profile-page { background: var(--card); border-color: var(--border); }
body.dark .info-box { background: #111827; border-color: var(--border); }
body.dark .modal-box { background: var(--card); }

@media (max-width: 600px) {
    .profile-info { grid-template-columns: 1fr; }
    .profile-actions { flex-direction: column; }
    .action-btn { width: 100%; justify-content: center; }
    .profile-wrapper { padding: 1rem; }
}
</style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

  <!-- ========== SIDEBAR (same as dashboard.php) ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="logo">
      <img src="assets/logo/images.png" alt="MCL Logo">
    </div>
    <nav>
      <a href="dashboard.php" class="menu">
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
      <a href="admin/wage_report.php" class="menu">
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
      <h1>Admin Profile</h1>
      <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
        <i class="fa-solid fa-moon"></i>
      </button>
      <a href="admin_profile.php" title="My Profile" style="text-decoration:none;">
        <div style="width:40px;height:40px;border-radius:50%;background:#0f766e;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s;flex-shrink:0;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
          </svg>
        </div>
      </a>
    </header>

    <!-- PROFILE CONTENT -->
    <div class="profile-wrapper">
      <div class="profile-page">

        <div class="profile-header">
          <div class="profile-avatar">
            <?= strtoupper(substr($user['name'] ?? 'A', 0, 2)) ?>
          </div>
          <h2><?= htmlspecialchars($user['name'] ?? 'N/A') ?></h2>
          <p class="sub">User ID: <?= htmlspecialchars($user['id'] ?? 'N/A') ?></p>
          <span class="role-badge"><i class="fa-solid fa-shield-halved" style="margin-right:4px;"></i> <?= htmlspecialchars($user['role'] ?? 'Admin') ?></span>
        </div>

        <div class="profile-body">
          <div class="profile-info">
            <div class="info-box">
              <h4><i class="fa-solid fa-envelope" style="margin-right:4px;color:#0f766e;"></i> Email</h4>
              <p><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
            </div>
            <div class="info-box">
              <h4><i class="fa-solid fa-location-dot" style="margin-right:4px;color:#0f766e;"></i> Area / Site</h4>
              <p><?= htmlspecialchars($user['site_code'] ?? $user['site'] ?? 'N/A') ?></p>
            </div>
            <div class="info-box">
              <h4><i class="fa-solid fa-user-shield" style="margin-right:4px;color:#0f766e;"></i> Role</h4>
              <p><?= htmlspecialchars($user['role'] ?? 'N/A') ?></p>
            </div>
            <div class="info-box">
              <h4><i class="fa-solid fa-calendar" style="margin-right:4px;color:#0f766e;"></i> Account Created</h4>
              <p><?= !empty($user['created_at']) ? date('d-m-Y H:i', strtotime($user['created_at'])) : 'N/A' ?></p>
            </div>
          </div>

          <div class="profile-actions">
            <a href="dashboard.php" class="action-btn primary">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Dashboard
            </a>
            <button class="action-btn amber" onclick="document.getElementById('pwModal').classList.add('active')">
              <i class="fa-solid fa-key"></i>
              Change Password
            </button>
          </div>
        </div>

      </div>
    </div>

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
      <!-- Step 1: Google Authenticator Code -->
      <div class="modal-step active" id="pwStep1">
        <p style="font-size:.84rem;color:var(--subtext);margin-bottom:1rem;">Enter the 6-digit code from your Google Authenticator app to verify your identity.</p>
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
/* ── Sidebar toggle (mobile) ── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const menuBtn = document.getElementById('menuBtn');

menuBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('active'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

/* ── Theme toggle ── */
const themeToggle = document.getElementById('themeToggle');
const themeIcon = themeToggle.querySelector('i');
function applyTheme(d) {
    if (d) { document.body.classList.add('dark'); themeToggle.classList.add('active'); themeIcon.className='fa-solid fa-sun'; }
    else { document.body.classList.remove('dark'); themeToggle.classList.remove('active'); themeIcon.className='fa-solid fa-moon'; }
}
applyTheme(localStorage.getItem('theme')==='dark');
themeToggle.addEventListener('click', () => { const d=document.body.classList.contains('dark'); applyTheme(!d); localStorage.setItem('theme',!d?'dark':'light'); });

/* ── Change Password Modal ── */
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
