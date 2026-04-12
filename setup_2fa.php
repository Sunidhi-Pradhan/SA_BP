<?php
session_start();
require "config.php";
require __DIR__ . "/GoogleAuthenticator-master/PHPGangsta/GoogleAuthenticator.php";

$error = "";
$success = "";

// 🔐 User must be in login flow
if (!isset($_SESSION["temp_user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["temp_user_id"];

// 🔍 Fetch user
$stmt = $pdo->prepare("SELECT google_secret, role FROM user WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

$ga = new PHPGangsta_GoogleAuthenticator();

// If user already has a secret but we are here, it means they might have hit the back button.
// Or we are generating a new one if it's NULL
if (empty($user["google_secret"])) {
    // Generate secret ONLY ONCE
    $secret = $ga->createSecret();
    // Save secret to DB
    $stmtUpdate = $pdo->prepare("UPDATE user SET google_secret = ? WHERE id = ?");
    $stmtUpdate->execute([$secret, $userId]);
    // Refresh user data
    $user["google_secret"] = $secret;
}

$secret = $user["google_secret"];
$qrCodeUrl = $ga->getQRCodeGoogleUrl("SecurityPortal-{$userId}", $secret);

// Handle OTP submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        if ($ga->verifyCode($secret, $otp, 2)) {
            // ── Success: Reset failed attempts and login ──
            $pdo->prepare("UPDATE user SET otp_failed_attempts = 0, otp_locked_until = NULL WHERE id = ?")->execute([$userId]);

            $_SESSION['user'] = $userId;
            unset($_SESSION['temp_user_id']);

            $role = $user['role'] ?? '';
            if ($role === 'ASO')         header("Location: aso_dashboard.php");
            elseif ($role === 'Admin')   header("Location: dashboard.php");
            elseif ($role === 'User')    header("Location: user_dashboard.php");
            elseif ($role === 'APM')     header("Location: apm/dashboard.php");
            elseif ($role === 'GM')      header("Location: gm/dashboard.php");
            elseif ($role === 'HQSO')    header("Location: hqso/dashboard.php");
            elseif ($role === 'SDHOD')   header("Location: sdhod/dashboard.php");
            else                         header("Location: login.php");
            exit;
        } else {
            $error = "Incorrect code. Please check your Authenticator app and try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup 2FA – Security Attendance and Billing Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
html, body { height:100%; }
body { min-height:100vh; background:#f0f2f5; display:flex; flex-direction:column; }

.top-header {
  background:#fff; border-bottom:1px solid #e5e7eb;
  box-shadow:0 1px 4px rgba(0,0,0,0.08);
  padding:0 2rem; height:64px;
  display:flex; align-items:center; justify-content:space-between; flex-shrink:0;
}
.header-left { display:flex; align-items:center; gap:.75rem; }
.header-left img { height:40px; }
.header-center {
  position:absolute; left:50%; transform:translateX(-50%);
  font-size:1.1rem; font-weight:700; color:#1f2937; white-space:nowrap;
}
.header-right { display:flex; align-items:center; gap:.5rem; text-align:right; }
.header-right img { height:44px; width:44px; object-fit:contain; flex-shrink:0; }
.header-right .moc-text { font-size:.72rem; color:#6b7280; line-height:1.35; }
.header-right .moc-text strong { display:block; color:#374151; font-size:.8rem; }

.page-body { flex:1; display:flex; align-items:center; justify-content:center; padding:2rem 1rem; }

.setup-card {
  width:100%; max-width:420px; border-radius:10px; overflow:hidden;
  box-shadow:0 4px 24px rgba(0,0,0,0.12), 0 1px 6px rgba(0,0,0,0.06);
  background:#fff; animation:cardIn 0.45s cubic-bezier(0.22,1,0.36,1) both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(24px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}
.card-header {
  background:linear-gradient(135deg,#0f766e 0%,#0a5c55 100%);
  padding:1.35rem 1.5rem; text-align:center;
}
.card-header h1 { font-size:1.35rem; font-weight:800; color:#fff; letter-spacing:.5px; }
.card-header p { font-size:.82rem; color:rgba(255,255,255,0.78); margin-top:.25rem; }
.card-body {
  padding:1.6rem 1.5rem 1.5rem; border:1px solid #e5e7eb;
  border-top:none; border-radius:0 0 10px 10px; text-align:center;
}

.alert-error {
  background:#fef2f2; border:1px solid #fecaca; border-radius:7px;
  color:#dc2626; font-size:.82rem; font-weight:600;
  padding:.6rem .9rem; margin-bottom:1rem; text-align:left;
  display:flex; align-items:center; gap:.5rem;
}

.qr-box {
  background:#f9fafb; border:1px solid #d1d5db; border-radius:8px;
  padding:1rem; margin-bottom:1.5rem; display:inline-block;
}
.qr-box img { max-width:180px; height:auto; display:block; margin:0 auto; }
.qr-hint { font-size:.8rem; color:#4b5563; margin-top:.5rem; font-weight:600; }

.otp-group { margin-bottom:1rem; text-align:left; }
.otp-group label {
  display:block; font-size:.8rem; font-weight:600;
  color:#374151; margin-bottom:.35rem;
}
.otp-input {
  width:100%; padding:.75rem 1rem; font-size:1.05rem;
  letter-spacing:6px; font-weight:700; text-align:center;
  color:#111827; background:#f9fafb;
  border:1.5px solid #d1d5db; border-radius:8px;
  outline:none; font-family:'Courier New', monospace;
  transition:border-color .2s, box-shadow .2s;
}
.otp-input::placeholder { color:#d1d5db; letter-spacing:4px; font-size:.95rem; font-family:inherit; }
.otp-input:focus {
  border-color:#0f766e; background:#fff;
  box-shadow:0 0 0 3px rgba(15,118,110,0.12);
}

.btn-verify {
  width:100%; padding:.72rem;
  background:linear-gradient(135deg,#0f766e 0%,#0d5f58 100%);
  color:#fff; border:none; border-radius:8px;
  font-size:.92rem; font-weight:700; letter-spacing:.3px;
  cursor:pointer; font-family:inherit;
  display:flex; align-items:center; justify-content:center; gap:.5rem;
  box-shadow:0 3px 10px rgba(15,118,110,0.3);
  transition:filter .2s, transform .15s;
}
.btn-verify:hover { filter:brightness(1.07); transform:translateY(-1px); }
.btn-verify:active { transform:translateY(0); }

.page-footer {
  background:#fff; border-top:1px solid #e5e7eb;
  text-align:center; padding:.75rem 1rem;
  font-size:.76rem; color:#9ca3af; flex-shrink:0;
}

@media (max-width:480px) {
  .top-header { padding:0 1rem; }
  .header-center { font-size:.85rem; position:static; transform:none; flex:1; text-align:center; }
  .header-right .moc-text { display:none; }
}
</style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<header class="top-header">
  <div class="header-left">
    <img src="assets/logo/images.png" alt="MCL Logo" onerror="this.style.display='none'">
  </div>
  <div class="header-center"><h1>Security Attendance and Billing Portal</h1></div>
  <div class="header-right">
    <img src="assets/logo/image.png" alt="Ministry of Coal" onerror="this.style.display='none'">
    <div class="moc-text">
      <strong>Ministry of Coal</strong>
      Government of India
    </div>
  </div>
</header>

<div class="page-body">
  <div class="setup-card">

    <div class="card-header">
      <h1>Setup 2FA</h1>
      <p>Secure your account with Google Authenticator</p>
    </div>

    <div class="card-body">
      
      <div class="qr-box">
        <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code">
        <div class="qr-hint">Scan this QR code with your app</div>
      </div>

      <?php if ($error !== ""): ?>
        <div class="alert-error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="otp-group">
          <label for="otp">Enter 6-digit code to verify and login:</label>
          <input
            type="text" name="otp" id="otp" class="otp-input"
            maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
            placeholder="000000" required autofocus
          >
        </div>
        <button type="submit" class="btn-verify">
          <i class="fa-solid fa-shield-halved"></i> Verify & Login
        </button>
      </form>

    </div>
  </div>
</div>

<footer class="page-footer">
  © 2026 Mahanadi Coalfields Limited. All Rights Reserved.
</footer>

</body>
</html>
