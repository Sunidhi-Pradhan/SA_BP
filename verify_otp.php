<?php
session_start();
require "config.php";
require __DIR__ . "/GoogleAuthenticator-master/PHPGangsta/GoogleAuthenticator.php";

$ga    = new PHPGangsta_GoogleAuthenticator();
$error = "";

// Must be in login flow
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['temp_user_id'];

// ── Check if user is locked out ──
$stmtLock = $pdo->prepare("SELECT otp_failed_attempts, otp_locked_until, email FROM user WHERE id = ?");
$stmtLock->execute([$userId]);
$lockData = $stmtLock->fetch(PDO::FETCH_ASSOC);

if ($lockData && !empty($lockData['otp_locked_until'])) {
    $lockedUntil = new DateTime($lockData['otp_locked_until']);
    $now = new DateTime();
    if ($now < $lockedUntil) {
        $remaining = $now->diff($lockedUntil);
        $mins = ($remaining->h * 60) + $remaining->i;
        $error = "Account is temporarily locked. Try again in {$mins} minute(s).";
        // Block form submission
        $_SERVER["REQUEST_METHOD"] = "BLOCKED";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        $stmt = $pdo->prepare("SELECT google_secret FROM user WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['google_secret'])) {
            $error = "Authenticator not set up. Please login again.";
        } else {
            if ($ga->verifyCode($user['google_secret'], $otp, 2)) {
                // ── Success: Reset failed attempts and login ──
                $pdo->prepare("UPDATE user SET otp_failed_attempts = 0, otp_locked_until = NULL WHERE id = ?")->execute([$userId]);

                $_SESSION['user'] = $userId;
                unset($_SESSION['temp_user_id']);

                $stmt2 = $pdo->prepare("SELECT role FROM user WHERE id = ?");
                $stmt2->execute([$userId]);
                $userData = $stmt2->fetch(PDO::FETCH_ASSOC);
                $role = $userData['role'] ?? '';

                if ($role === 'ASO')         header("Location: aso_dashboard.php");
                elseif ($role === 'Admin')   header("Location: dashboard.php");
                elseif ($role === 'HQSO')    header("Location: hqso/monthly.php");
                elseif ($role === 'user')    header("Location: user_dashboard.php");
                elseif ($role === 'APM')     header("Location: apm/apm_dashboard.php");
                elseif ($role === 'GM')      header("Location: gm/monthly.php");
                elseif ($role === 'SDHOD')   header("Location: sdhod/dashboard.php");
                elseif ($role === 'Finance') header("Location: finance/dashboard.php");
                else                         header("Location: dashboard.php");
                exit;
            } else {
                // ── Failed: Increment attempts ──
                $currentAttempts = (int)($lockData['otp_failed_attempts'] ?? 0) + 1;

                if ($currentAttempts >= 5) {
                    // Lock for 2 hours
                    $lockUntil = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i:s');
                    $pdo->prepare("UPDATE user SET otp_failed_attempts = ?, otp_locked_until = ? WHERE id = ?")
                        ->execute([$currentAttempts, $lockUntil, $userId]);

                    // Send restriction email
                    $userEmail = $lockData['email'] ?? '';
                    if (!empty($userEmail)) {
                        try {
                            sendLockoutEmail($userEmail, $userId);
                        } catch (Exception $e) {
                            // Log error but don't block the flow
                        }
                    }

                    $error = "Too many failed attempts. Your account is locked for 2 hours. A notification email has been sent.";
                } else {
                    $pdo->prepare("UPDATE user SET otp_failed_attempts = ? WHERE id = ?")
                        ->execute([$currentAttempts, $userId]);
                    $remaining = 5 - $currentAttempts;
                    $error = "Invalid code. {$remaining} attempt(s) remaining.";
                }
            }
        }
    }
}

// ── Send lockout email function ──
function sendLockoutEmail($toEmail, $userId) {

    require_once __DIR__ . "/PHPMailer/src/Exception.php";
    require_once __DIR__ . "/PHPMailer/src/PHPMailer.php";
    require_once __DIR__ . "/PHPMailer/src/SMTP.php";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "test.work3589@gmail.com";
    $mail->Password = "qfwi zclu oelb fxuh";
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("test.work3589@gmail.com", "Security Billing Portal");
    $mail->addAddress($toEmail);

    $mail->isHTML(true);
    $mail->Subject = "⚠️ Account Temporarily Locked – Security Alert";
    $mail->Body = "
        <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#0f766e,#0d5f58);padding:1.5rem;text-align:center;border-radius:10px 10px 0 0;'>
                <h2 style='color:#fff;margin:0;'>🔒 Account Locked</h2>
            </div>
            <div style='padding:1.5rem;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 10px 10px;'>
                <p>Dear User (ID: <strong>{$userId}</strong>),</p>
                <p>Your account has been <strong>temporarily locked for 2 hours</strong> due to 5 consecutive failed Google Authenticator code attempts.</p>
                <p style='background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;color:#dc2626;font-weight:600;'>
                    ⚠️ If this was not you, please contact the administrator immediately.
                </p>
                <p style='color:#6b7280;font-size:0.85rem;'>You can try logging in again after the lockout period expires.</p>
                <hr style='border:none;border-top:1px solid #e5e7eb;margin:1rem 0;'>
                <p style='color:#9ca3af;font-size:0.75rem;'>Security Billing Management Portal – Mahanadi Coalfields Limited</p>
            </div>
        </div>
    ";

    $mail->send();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP – Security Billing Management Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }

html, body { height:100%; }
body {
  min-height: 100vh;
  background: #f0f2f5;
  display: flex;
  flex-direction: column;
}

/* ── TOP HEADER ── */
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
  position: relative;
}
.header-left {
  display: flex;
  align-items: center;
  gap: .75rem;
}
.header-left img { height: 40px; }
.header-center {
  position: absolute; left: 50%; transform: translateX(-50%);
  font-size: 1.1rem; font-weight: 700; color: #1f2937; white-space: nowrap;
}
.header-right {
  display: flex; align-items: center; gap: .5rem; text-align: right;
}
.header-right img { height: 44px; width: 44px; object-fit: contain; flex-shrink: 0; }

.header-right .moc-text { font-size: .72rem; color: #6b7280; line-height: 1.35; }
.header-right .moc-text strong { display: block; color: #374151; font-size: .8rem; }

/* ── PAGE BODY ── */
.page-body {
  flex: 1;
  display: flex; align-items: center; justify-content: center;
  padding: 2rem 1rem;
}

/* ── OTP CARD ── */
.otp-card {
  width: 100%; max-width: 370px;
  border-radius: 10px; overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.12), 0 1px 6px rgba(0,0,0,0.06);
  background: #ffffff;
  animation: cardIn 0.45s cubic-bezier(0.22,1,0.36,1) both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(26px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

/* Card header — dark teal */
.card-header {
  background: linear-gradient(135deg, #0f766e 0%, #0a5c55 100%);
  padding: 1.3rem 1.5rem;
  text-align: center;
}
.card-header .shield-wrap {
  width: 42px; height: 42px; background: rgba(255,255,255,0.18);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  margin: 0 auto .7rem;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.25); }
  50%      { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
}
.card-header .shield-wrap i { font-size: 1.25rem; color: #ffffff; }
.card-header h1 {
  font-size: 1.1rem; font-weight: 800; color: #ffffff; letter-spacing: .3px;
}
.card-header p {
  font-size: .78rem; color: rgba(255,255,255,0.78); margin-top: .25rem;
}

/* Card body */
.card-body {
  padding: 1.5rem 1.5rem;
  border: 1px solid #e5e7eb; border-top: none;
  border-radius: 0 0 10px 10px;
}

.instructions {
  font-size: .82rem; color: #6b7280; text-align: center; line-height: 1.55;
  margin-bottom: 1.2rem;
}

/* Error */
.alert-error {
  background: #fef2f2; border: 1px solid #fecaca; border-radius: 7px;
  color: #dc2626; font-size: .82rem; font-weight: 600;
  padding: .6rem .9rem; margin-bottom: 1rem;
  display: flex; align-items: center; gap: .5rem;
}

/* Warning (lockout) */
.alert-warning {
  background: #fffbeb; border: 1px solid #fde68a; border-radius: 7px;
  color: #92400e; font-size: .82rem; font-weight: 600;
  padding: .6rem .9rem; margin-bottom: 1rem;
  display: flex; align-items: center; gap: .5rem;
}

/* OTP input */
.otp-group { margin-bottom: 1rem; }
.otp-input {
  width: 100%;
  padding: .75rem 1rem;
  font-size: 1.05rem;
  text-align: center;
  letter-spacing: 6px;
  font-weight: 700;
  color: #111827;
  background: #f9fafb;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  outline: none;
  font-family: 'Courier New', monospace;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
.otp-input::placeholder {
  color: #d1d5db; letter-spacing: 4px; font-size: .95rem; font-family: inherit;
}
.otp-input:focus {
  border-color: #0f766e; background: #fff;
  box-shadow: 0 0 0 3px rgba(15,118,110,0.12);
}

/* Verify button */
.btn-verify {
  width: 100%;
  padding: .72rem;
  background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
  color: white; border: none; border-radius: 8px;
  font-size: .92rem; font-weight: 700; letter-spacing: .3px;
  cursor: pointer; font-family: inherit;
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  box-shadow: 0 3px 10px rgba(15,118,110,0.3);
  transition: filter .2s, transform .15s, box-shadow .2s;
}
.btn-verify:hover {
  filter: brightness(1.07); transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(15,118,110,0.38);
}
.btn-verify:active { transform: translateY(0); }
.btn-verify:disabled {
  opacity: 0.6; cursor: not-allowed; filter: none; transform: none;
}

/* Back link */
.back-link {
  text-align: center; margin-top: 1rem;
  font-size: .79rem; color: #6b7280;
}
.back-link a { color: #0f766e; text-decoration: none; font-weight: 600; }
.back-link a:hover { text-decoration: underline; }

/* ── FOOTER ── */
.page-footer {
  background: #fff; border-top: 1px solid #e5e7eb;
  text-align: center; padding: .75rem 1rem;
  font-size: .76rem; color: #9ca3af; flex-shrink: 0;
}

/* ── RESPONSIVE ── */
@media (max-width: 480px) {
  .top-header { padding: 0 1rem; }
  .header-center { font-size: .85rem; position: static; transform: none; flex: 1; text-align: center; }
  .header-right .moc-text { display: none; }
}
</style>
</head>
<body>

<!-- ── HEADER ── -->
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

<!-- ── MAIN ── -->
<div class="page-body">
  <div class="otp-card">

    <!-- Card Header -->
    <div class="card-header">
      <div class="shield-wrap">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <h1>Verify Two-Factor<br>Authentication</h1>
      <p>Enter the 6-digit code from your Authenticator app</p>
    </div>

    <!-- Card Body -->
    <div class="card-body">

      <?php if ($error !== ""): ?>
        <?php
          $alertClass = (strpos($error, 'locked') !== false || strpos($error, 'Locked') !== false)
            ? 'alert-warning' : 'alert-error';
        ?>
        <div class="<?= $alertClass ?>">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <p class="instructions">
        Open the Google Authenticator app on your phone and
        enter the current 6-digit code for this account.
      </p>

      <form method="POST" autocomplete="off">
        <div class="otp-group">
          <input
            type="text"
            name="otp"
            class="otp-input"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            placeholder="Enter code from app"
            autofocus
            required
            <?php if (strpos($error, 'locked') !== false || strpos($error, 'Locked') !== false): ?>disabled<?php endif; ?>
          >
        </div>
        <button type="submit" class="btn-verify"
          <?php if (strpos($error, 'locked') !== false || strpos($error, 'Locked') !== false): ?>disabled<?php endif; ?>>
          <i class="fa-solid fa-shield-halved"></i>
          Verify &amp; Continue
        </button>
      </form>

      <div class="back-link">
        <a href="login.php"><i class="fa-solid fa-arrow-left" style="font-size:.75rem;"></i> Back to Login</a>
      </div>

    </div><!-- /.card-body -->
  </div><!-- /.otp-card -->
</div>

<!-- ── FOOTER ── -->
<footer class="page-footer">
  © Copyright 2025 Mahanadi Coalfields Limited. All Rights Reserved.
</footer>

<script>
/* Allow only numeric input in OTP field */
const otpInput = document.querySelector('.otp-input');
if (otpInput) {
  otpInput.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
  });
}
</script>
</body>
</html>