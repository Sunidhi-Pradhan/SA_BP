<?php
session_start();
require "config.php";
require __DIR__ . "/GoogleAuthenticator-master/PHPGangsta/GoogleAuthenticator.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user"])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$userId = $_SESSION["user"];
$action = $_POST['action'] ?? '';

// ── Step 1: Verify Google Authenticator Code ──
if ($action === 'verify_code') {
    $code = trim($_POST['code'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit code.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT google_secret FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['google_secret'])) {
        echo json_encode(['success' => false, 'message' => 'Authenticator not set up.']);
        exit;
    }

    $ga = new PHPGangsta_GoogleAuthenticator();
    if ($ga->verifyCode($user['google_secret'], $code, 2)) {
        $_SESSION['pw_change_verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Code verified.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid code. Please try again.']);
    }
    exit;
}

// ── Step 2: Change Password ──
if ($action === 'change_password') {
    if (empty($_SESSION['pw_change_verified'])) {
        echo json_encode(['success' => false, 'message' => 'Please verify your authenticator code first.']);
        exit;
    }

    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $userId]);

    unset($_SESSION['pw_change_verified']);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
