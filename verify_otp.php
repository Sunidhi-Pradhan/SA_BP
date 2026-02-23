<?php
session_start();
require "config.php";
require __DIR__ . "/GoogleAuthenticator-master/PHPGangsta/GoogleAuthenticator.php";

$ga = new PHPGangsta_GoogleAuthenticator();
$error = "";

// 🔐 Must be in login flow
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['temp_user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST['otp']);

    // ✅ Basic OTP validation
    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } else {

        // Fetch secret
        $stmt = $pdo->prepare("SELECT google_secret FROM user WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['google_secret'])) {
            $error = "Authenticator not set up. Please login again.";
        } else {

            $secret = $user['google_secret'];

            // Verify OTP (2 = 60 seconds tolerance)
            if ($ga->verifyCode($secret, $otp, 2)) {

                // ✅ OTP success → full login
                // OTP success → full login
                $_SESSION['user'] = $userId;
                unset($_SESSION['temp_user_id']);

                // 🔹 Fetch user role
                $stmt = $pdo->prepare("SELECT role FROM user WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                $role = $userData['role'] ?? '';

                // 🔹 Role-based dashboard redirect
                if ($role === 'ASO') {
                    header("Location: aso_dashboard.php");
                } elseif ($role === 'Admin') {
                    header("Location: dashboard.php");
                } elseif ($role === 'HQSO') {
                    header("Location: hqso/monthly.php");
                }elseif ($role === 'user') {
                    header("Location: user_dashboard.php");
                }else if ($role === 'APM') {
                    header("Location: apm/apm_dashboard.php");
                }elseif ($role === 'GM') {
                    header("Location: gm/monthly.php");
                 } else {
                    // fallback
                    header("Location: dashboard.php");
                }
                exit;


            } else {
                $error = "Invalid OTP. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{
    box-sizing: border-box;
    font-family: "Segoe UI", sans-serif;
}
body{
    background: #f4f6f8;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.otp-box{
    background: #fff;
    padding: 30px;
    width: 100%;
    max-width: 360px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
}
.otp-box h2{
    margin-bottom: 10px;
}
.otp-box p{
    color: #555;
    font-size: 14px;
    margin-bottom: 20px;
}
.otp-input{
    width: 100%;
    padding: 14px;
    font-size: 18px;
    text-align: center;
    letter-spacing: 4px;
    border: 1px solid #ccc;
    border-radius: 8px;
    margin-bottom: 15px;
}
button{
    width: 100%;
    padding: 14px;
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}
button:hover{
    background: #115e59;
}
.error{
    color: red;
    font-size: 14px;
    margin-top: 10px;
}
.back{
    margin-top: 15px;
    font-size: 13px;
}
.back a{
    color: #0f766e;
    text-decoration: none;
}
</style>
</head>

<body>

<div class="otp-box">
    <h2>Two-Factor Authentication</h2>
    <p>Enter the 6-digit code from Google Authenticator</p>

    <form method="POST" autocomplete="off">
        <input type="text"
               name="otp"
               class="otp-input"
               maxlength="6"
               inputmode="numeric"
               placeholder="● ● ● ● ● ●"
               required>
        <button type="submit">Verify OTP</button>
    </form>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="back">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

</body>
</html>
