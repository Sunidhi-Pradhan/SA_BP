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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .profile-page {
            max-width: 900px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 40px;
        }

        /* TOP SECTION */
        .profile-top {
            display: flex;
            gap: 25px;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 25px;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: #0f766e;
            color: #fff;
            border-radius: 50%;
            font-size: 36px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-name h2 {
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .profile-name p {
            color: #666;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .role-badge {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 14px;
        }

        /* INFO GRID */
        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .info-box {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
        }

        .info-box h4 {
            font-size: 14px;
            color: #888;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .info-box p {
            font-size: 17px;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* EDIT BUTTON */
        .profile-actions {
            margin-top: 35px;
            text-align: right;
        }

        .edit-btn {
            background: #2563eb;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
        }

        .edit-btn:hover {
            background: #1d4ed8;
        }

        /* BACK BUTTON */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-size: 15px;
            margin-bottom: 20px;
            transition: color 0.2s;
        }

        .back-btn:hover {
            color: #1a1a1a;
        }

        .back-btn svg {
            width: 18px;
            height: 18px;
        }

        /* RESPONSIVE */
        @media (max-width: 600px) {
            .profile-page {
                padding: 25px 20px;
            }

            .profile-top {
                flex-direction: column;
                text-align: center;
            }

            .profile-info {
                grid-template-columns: 1fr;
            }

            .profile-actions {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- <a href="user_dashboard.php" class="back-btn" style="position: absolute; top: 30px; left: 30px;">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to Dashboard
</a> -->

<div class="profile-page">

    <!-- TOP -->
    <div class="profile-top">
        <div class="profile-avatar">
            <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
        </div>

        <div class="profile-name">
            <h2><?= htmlspecialchars($user['name'] ?? 'N/A') ?></h2>
            <p>Emp ID: <?= htmlspecialchars($user['id'] ?? 'N/A') ?></p>
            <span class="role-badge"><?= htmlspecialchars($user['role'] ?? 'N/A') ?></span>
        </div>
    </div>

    <!-- DETAILS -->
    <div class="profile-info">
        <div class="info-box">
            <h4>Email</h4>
            <p><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
        </div>

        <div class="info-box">
            <h4>Area / Site</h4>
            <p><?= htmlspecialchars($user['site'] ?? 'N/A') ?></p>
        </div>

        <div class="info-box">
            <h4>Role</h4>
            <p><?= htmlspecialchars($user['role'] ?? 'N/A') ?></p>
        </div>

        <div class="info-box">
            <h4>Account Created</h4>
            <p><?= date('d-m-Y H:i', strtotime($user['created_at'])) ?></p>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="profile-actions">
        <a href="user_dashboard.php" class="edit-btn">
            <i class="fa-solid fa-arrow-left"></i>
             Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>