<?php
session_start();
require "config.php";
require __DIR__ . "/GoogleAuthenticator-master/PHPGangsta/GoogleAuthenticator.php";

// 🔐 User must be in login flow
if (!isset($_SESSION["temp_user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["temp_user_id"];

// 🔍 Fetch user
$stmt = $pdo->prepare("SELECT google_secret FROM user WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

// ❌ If already setup → no need to show QR again
if (!empty($user["google_secret"])) {
    header("Location: verify_otp.php");
    exit;
}

$ga = new PHPGangsta_GoogleAuthenticator();

// ✅ Generate secret ONLY ONCE
$secret = $ga->createSecret();

// Save secret to DB
$stmt = $pdo->prepare("UPDATE user SET google_secret = ? WHERE id = ?");
$stmt->execute([$secret, $userId]);

// Generate QR
$qrCodeUrl = $ga->getQRCodeGoogleUrl("SecurityPortal-User-$userId", $secret);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Google Authenticator</title>
</head>
<body style="font-family:Segoe UI; text-align:center; margin-top:50px;">
    <h2>Setup Google Authenticator</h2>
    <p>Scan this QR code using Google Authenticator app</p>

    <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code"><br><br>

    <p><b>After scanning:</b></p>
    <ol style="display:inline-block; text-align:left;">
        <li>Do NOT refresh this page</li>
        <li>Logout</li>
        <li>Login again</li>
        <li>Enter OTP</li>
    </ol>
</body>
</html>
