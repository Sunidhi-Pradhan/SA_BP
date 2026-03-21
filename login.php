<?php
session_start();
require "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userid   = $_POST["userid"]   ?? '';
    $password = $_POST["password"] ?? '';

    if ($userid === "" || $password === "") {
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `user` WHERE id = ?");
        $stmt->execute([$userid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["temp_user_id"] = $user["id"];

            if ($user["google_secret"] == NULL) {
                header("Location: setup_2fa.php");
            } else {
                header("Location: verify_otp.php");
            }
            exit;
        } else {
            $error = "Invalid Employee ID or Password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Security Billing Management Portal – Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }

/* ── PAGE LAYOUT ── */
html, body { height:100%; }
body {
  min-height: 100vh;
  background: #f0f2f5;
  display: flex;
  flex-direction: column;
}

/* ── TOP HEADER BAR ── */
.top-header {
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  padding: 0 2rem;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: .75rem;
}
.header-left img {
  height: 40px;
}
.header-left .mcl-wordmark {
  font-size: 1.5rem;
  font-weight: 900;
  color: #0f766e;
  letter-spacing: 1px;
}
.header-center {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  white-space: nowrap;
}
.header-right {
  display: flex;
  align-items: center;
  gap: .5rem;
  text-align: right;
}
.header-right img {
  height: 44px;
  width: 44px;
  object-fit: contain;
  flex-shrink: 0;
}

.header-right .moc-text {
  font-size: .72rem;
  color: #6b7280;
  line-height: 1.35;
}
.header-right .moc-text strong {
  display: block;
  color: #374151;
  font-size: .8rem;
}

/* ── MAIN CONTENT ── */
.page-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

/* ── LOGIN CARD ── */
.login-card {
  width: 100%;
  max-width: 360px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.12), 0 1px 6px rgba(0,0,0,0.06);
  background: #ffffff;
  animation: cardIn 0.45s cubic-bezier(0.22,1,0.36,1) both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(24px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

/* Card header — dark teal */
.card-header {
  background: linear-gradient(135deg, #0f766e 0%, #0a5c55 100%);
  padding: 1.35rem 1.5rem;
  text-align: center;
}
.card-header h1 {
  font-size: 1.45rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: .5px;
}
.card-header p {
  font-size: .82rem;
  color: rgba(255,255,255,0.78);
  margin-top: .25rem;
}

/* Card body */
.card-body {
  padding: 1.6rem 1.5rem 1.5rem;
  border: 1px solid #e5e7eb;
  border-top: none;
  border-radius: 0 0 10px 10px;
}

/* Error alert */
.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 7px;
  color: #dc2626;
  font-size: .82rem;
  font-weight: 600;
  padding: .6rem .9rem;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}

/* Form groups */
.form-group { margin-bottom: 1rem; }
.form-group label {
  display: block;
  font-size: .8rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: .35rem;
}
.form-group input {
  width: 100%;
  padding: .6rem .85rem;
  font-size: .9rem;
  color: #111827;
  background: #f9fafb;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-family: inherit;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
.form-group input::placeholder { color: #9ca3af; }
.form-group input:focus {
  border-color: #0f766e;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(15,118,110,0.12);
}

/* Password field wrapper */
.pw-wrap { position: relative; }
.pw-toggle {
  position: absolute;
  right: .75rem; top: 50%;
  transform: translateY(-50%);
  background: none; border: none;
  cursor: pointer; color: #9ca3af; font-size: .9rem;
  padding: 0; line-height: 1;
  transition: color .2s;
}
.pw-toggle:hover { color: #374151; }

/* Login button */
.btn-login {
  width: 100%;
  padding: .7rem;
  background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
  color: white;
  border: none;
  border-radius: 7px;
  font-size: .95rem;
  font-weight: 700;
  letter-spacing: .3px;
  cursor: pointer;
  font-family: inherit;
  margin-top: .35rem;
  transition: filter .2s, transform .15s, box-shadow .2s;
  box-shadow: 0 3px 10px rgba(15,118,110,0.3);
}
.btn-login:hover {
  filter: brightness(1.07);
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(15,118,110,0.38);
}
.btn-login:active { transform: translateY(0); }

/* Extra links */
.extra-links {
  margin-top: 1.1rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: .3rem;
}
.extra-links span {
  font-size: .8rem;
  color: #6b7280;
}
.extra-links a {
  color: #0f766e;
  text-decoration: none;
  font-weight: 600;
  font-size: .8rem;
}
.extra-links a:hover { text-decoration: underline; }

/* ── FOOTER ── */
.page-footer {
  background: #fff;
  border-top: 1px solid #e5e7eb;
  text-align: center;
  padding: .75rem 1rem;
  font-size: .76rem;
  color: #9ca3af;
  flex-shrink: 0;
}

/* ── RESPONSIVE ── */
@media (max-width: 480px) {
  .top-header { padding: 0 1rem; }
  .header-center { font-size: .85rem; position: static; transform: none; flex: 1; text-align: center; }
  .header-right .moc-text { display: none; }
  .card-body { padding: 1.3rem 1.1rem; }
}
</style>
</head>
<body>

<!-- ── TOP HEADER ── -->
<header class="top-header">
  <div class="header-left">
    <img src="assets/logo/images.png" alt="MCL Logo" onerror="this.style.display='none'">
  </div>
  <div class="header-center">Security Billing Management Portal</div>
  <div class="header-right">
    <img src="assets/logo/image.png" alt="Ministry of Coal" onerror="this.style.display='none'">
    <div class="moc-text">
      <strong>Ministry of Coal</strong>
      Government of India
    </div>
  </div>
</header>

<!-- ── MAIN ── -->
<div class="page-body">
  <div class="login-card">

    <!-- Card Header -->
    <div class="card-header">
      <h1>Welcome</h1>
      <p>Please login to continue</p>
    </div>

    <!-- Card Body -->
    <div class="card-body">

      <?php if ($error !== ""): ?>
        <div class="alert-error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">

        <div class="form-group">
          <label for="userid">Employee ID</label>
          <input
            type="text"
            id="userid"
            name="userid"
            placeholder="Enter your employee id"
            value="<?= htmlspecialchars($_POST['userid'] ?? '') ?>"
            required
            autofocus
          >
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="pw-wrap">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            >
            <button type="button" class="pw-toggle" id="pwToggle" title="Show/Hide password">
              <i class="fa-regular fa-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login">Login</button>

        <div class="extra-links">
          <span>Forgot Password? <a href="#">Click Here</a></span>
          <span>User Manual? <a href="#">Click Here</a></span>
        </div>

      </form>
    </div><!-- /.card-body -->
  </div><!-- /.login-card -->
</div><!-- /.page-body -->

<!-- ── FOOTER ── -->
<footer class="page-footer">
  © 2025 Mahanadi Coalfields Limited. All Rights Reserved.
</footer>

<script>
/* Password show/hide toggle */
const pwToggle = document.getElementById('pwToggle');
const pwInput  = document.getElementById('password');
const pwIcon   = document.getElementById('pwIcon');
if (pwToggle) {
  pwToggle.addEventListener('click', function () {
    const isText = pwInput.type === 'text';
    pwInput.type = isText ? 'password' : 'text';
    pwIcon.className = isText ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
  });
}
</script>
</body>
</html>