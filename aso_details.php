<?php
session_start();
require "config.php";

/* -------- LOGIN CHECK -------- */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

/* -------- ROLE CHECK -------- */
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$aso = $stmt->fetch();

if (!$aso || $aso['role'] !== 'ASO') {
    die("Access denied");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASO Profile Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Your main CSS -->
    <link rel="stylesheet" href="assets/aso.css">

    <style>
        /* ===== ASO DETAILS PAGE ===== */
        .profile-page {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 30px;
        }

        .profile-top {
            display: flex;
            gap: 20px;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #0f766e;
            color: #fff;
            border-radius: 50%;
            font-size: 32px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-name h2 {
            margin: 0;
        }

        .profile-name p {
            margin: 4px 0;
            color: #555;
        }

        .role-badge {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 12px;
            margin-top: 6px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 25px;
        }

        .info-box {
            background: #f9fafb;
            border-radius: 10px;
            padding: 16px;
        }

        .info-box h4 {
            margin: 0 0 6px;
            font-size: 14px;
            color: #666;
        }

        .info-box p {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }

        .profile-actions {
            margin-top: 30px;
            text-align: right;
        }

        .edit-btn {
            background: #2563eb;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .edit-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body style="background:#f4f6f8;">

<div class="profile-page">

    <!-- TOP -->
    <div class="profile-top">
        <div class="profile-avatar">
            <?= strtoupper(substr($aso['name'], 0, 1)) ?>
        </div>

        <div class="profile-name">
            <h2><?= htmlspecialchars($aso['name']) ?></h2>
            <p>Emp ID: <?= htmlspecialchars($aso['id']) ?></p>
            <span class="role-badge"><?= htmlspecialchars($aso['role']) ?></span>
        </div>
    </div>

    <!-- DETAILS -->
    <div class="profile-info">
        <div class="info-box">
            <h4>Email</h4>
            <p><?= htmlspecialchars($aso['email']) ?></p>
        </div>

        <div class="info-box">
            <h4>Area / Site</h4>
            <p><?= htmlspecialchars($aso['site']) ?></p>
        </div>

        <div class="info-box">
            <h4>Role</h4>
            <p><?= htmlspecialchars($aso['role']) ?></p>
        </div>

        <div class="info-box">
            <h4>Account Created</h4>
            <p><?= date('d-m-Y H:i', strtotime($aso['created_at'])) ?></p>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="profile-actions">
        <a href="aso_dashboard.php" class="edit-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
