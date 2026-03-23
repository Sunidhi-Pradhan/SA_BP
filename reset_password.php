<?php
session_start();
require "config.php";

// Must come from forgot_password.php with verified 2FA
if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = "";
$success = "";
$userId = $_SESSION['reset_user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPass     = $_POST["new_password"]     ?? '';
    $confirmPass = $_POST["confirm_password"] ?? '';

    if ($newPass === "" || $confirmPass === "") {
        $error = "All fields are required.";
    } elseif (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } else {
        // Update password in database
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE `user` SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $userId]);

        // Clear reset session
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_user_id']);

        $success = "Password updated successfully! Redirecting to login...";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password – Security Billing Management Portal</title>
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

.reset-card {
  width:100%; max-width:400px; border-radius:10px; overflow:hidden;
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
  border-top:none; border-radius:0 0 10px 10px;
}

.alert-error {
  background:#fef2f2; border:1px solid #fecaca; border-radius:7px;
  color:#dc2626; font-size:.82rem; font-weight:600;
  padding:.6rem .9rem; margin-bottom:1rem;
  display:flex; align-items:center; gap:.5rem;
}
.alert-success {
  background:#f0fdf4; border:1px solid #bbf7d0; border-radius:7px;
  color:#16a34a; font-size:.82rem; font-weight:600;
  padding:.6rem .9rem; margin-bottom:1rem;
  display:flex; align-items:center; gap:.5rem;
}

.form-group { margin-bottom:1rem; }
.form-group label {
  display:block; font-size:.8rem; font-weight:600;
  color:#374151; margin-bottom:.35rem;
}
.form-group input {
  width:100%; padding:.6rem .85rem; font-size:.9rem;
  color:#111827; background:#f9fafb;
  border:1px solid #d1d5db; border-radius:7px;
  outline:none; font-family:inherit;
  transition:border-color .2s, box-shadow .2s, background .2s;
}
.form-group input::placeholder { color:#9ca3af; }
.form-group input:focus {
  border-color:#0f766e; background:#fff;
  box-shadow:0 0 0 3px rgba(15,118,110,0.12);
}

.verified-badge {
  display:flex; align-items:center; gap:.5rem;
  background:#f0fdf4; border:1px solid #bbf7d0; border-radius:7px;
  padding:.6rem .9rem; margin-bottom:1rem;
  font-size:.82rem; font-weight:600; color:#16a34a;
}

.btn-reset {
  width:100%; padding:.7rem;
  background:linear-gradient(135deg,#0f766e 0%,#0d5f58 100%);
  color:#fff; border:none; border-radius:7px;
  font-size:.95rem; font-weight:700; letter-spacing:.3px;
  cursor:pointer; font-family:inherit; margin-top:.35rem;
  transition:filter .2s, transform .15s, box-shadow .2s;
  box-shadow:0 3px 10px rgba(15,118,110,0.3);
}
.btn-reset:hover { filter:brightness(1.07); transform:translateY(-1px); box-shadow:0 6px 18px rgba(15,118,110,0.38); }
.btn-reset:active { transform:translateY(0); }

.btn-back {
  display:block; width:100%; margin-top:.75rem;
  padding:.6rem; background:#f3f4f6; color:#374151;
  border:1px solid #d1d5db; border-radius:7px;
  font-size:.88rem; font-weight:600; cursor:pointer;
  text-align:center; text-decoration:none; font-family:inherit;
  transition:background .2s;
}
.btn-back:hover { background:#e5e7eb; }

.page-footer {
  background:#fff; border-top:1px solid #e5e7eb;
  text-align:center; padding:.75rem 1rem;
  font-size:.76rem; color:#9ca3af; flex-shrink:0;
}

@media (max-width:480px) {
  .top-header { padding:0 1rem; }
  .header-center { font-size:.85rem; position:static; transform:none; flex:1; text-align:center; }
  .header-right .moc-text { display:none; }
  .card-body { padding:1.3rem 1.1rem; }
}
</style>
</head>
<body>

<header class="top-header">
  <div class="header-left">
    <img src="assets/logo/images.png" alt="MCL Logo" onerror="this.style.display='none'">
  </div>
  <div class="header-center"><h1>Security Billing Management Portal</h1></div>
  <div class="header-right">
    <img src="assets/logo/image.png" alt="Ministry of Coal" onerror="this.style.display='none'">
    <div class="moc-text">
      <strong>Ministry of Coal</strong>
      Government of India
    </div>
  </div>
</header>

<div class="page-body">
  <div class="reset-card">

    <div class="card-header">
      <h1>Reset Password</h1>
      <p>Step 2: Set your new password</p>
    </div>

    <div class="card-body">

      <div class="verified-badge">
        <i class="fa-solid fa-circle-check"></i>
        Identity verified for Employee ID: <strong><?= htmlspecialchars($userId) ?></strong>
      </div>

      <?php if ($error !== ""): ?>
        <div class="alert-error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success !== ""): ?>
        <div class="alert-success">
          <i class="fa-solid fa-circle-check"></i>
          <?= htmlspecialchars($success) ?>
        </div>
        <script>setTimeout(function(){ window.location.href = 'login.php'; }, 2500);</script>
        <a href="login.php" class="btn-back">Go to Login</a>
      <?php else: ?>

        <form method="POST" autocomplete="off">

          <div class="form-group">
            <label for="new_password">New Password</label>
            <input
              type="password" id="new_password" name="new_password"
              placeholder="At least 6 characters"
              minlength="6" required autofocus
            >
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
              type="password" id="confirm_password" name="confirm_password"
              placeholder="Re-enter new password"
              minlength="6" required
            >
          </div>

          <button type="submit" class="btn-reset">
            <i class="fa-solid fa-lock"></i> Update Password
          </button>
          <a href="login.php" class="btn-back">Cancel</a>

        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer class="page-footer">
  © 2026 Mahanadi Coalfields Limited. All Rights Reserved.
</footer>

</body>
</html>
