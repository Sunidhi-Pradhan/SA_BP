<?php
session_start();
require "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userid   = $_POST["userid"] ?? '';
    $password = $_POST["password"] ?? '';

    if ($userid === "" || $password === "") {
        $error = "All fields are required";
    } else {

        // Fetch user by ID
        $stmt = $pdo->prepare("SELECT * FROM `user` WHERE id = ?");
        $stmt->execute([$userid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($user && password_verify($password, $user["password"])) {

    // 🔐 Store temp user ID
    $_SESSION["temp_user_id"] = $user["id"];

    // 🔍 CHECK: Google Authenticator setup hai ya nahi
    if ($user["google_secret"] == NULL) {
        // ❗ First-time user → setup required
        header("Location: setup_2fa.php");
        exit;
    } else {
        // ✅ Already setup → OTP verify
        header("Location: verify_otp.php");
        exit;
    }

}
}}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Login</title>
    <!-- <link rel="stylesheet" href="assets/style.css"> -->
     <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* BACKGROUND */
body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #ffffff;
}

/* LOGIN CARD */
.login-container {
    width: 360px;
    padding: 35px 30px;
    border-radius: 15px;
    background: #f8f9fa;
    box-shadow: 0 4px 20px rgba(15, 118, 110, 0.1);
    border: 1px solid #e5e7eb;
}

/* HEADING */
.login-container h1 {
    text-align: center;
    margin-bottom: 25px;
    color: #0f766e;
    letter-spacing: 1px;
}

/* FORM GROUP */
.form-group {
    margin-bottom: 18px;
}

/* LABELS */
.form-group label {
    display: block;
    margin-bottom: 6px;
    color: #374151;
    font-size: 14px;
    font-weight: 500;
}

/* INPUTS */
.form-group input {
    width: 100%;
    padding: 12px;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #1f2937;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
}

.form-group input::placeholder {
    color: #9ca3af;
}

.form-group input:focus {
    border-color: #0f766e;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

/* LOGIN BUTTON */
.login-btn {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: none;
    background: #0f766e;
    color: #ffffff;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.login-btn:hover {
    background: #0d5f58;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);
}

/* ERROR MESSAGE */
.login-container p {
    margin-bottom: 12px;
    font-size: 14px;
}

/* EXTRA LINKS */
.extra-links {
    text-align: center;
    margin-top: 18px;
}

.extra-links a {
    color: #0f766e;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.extra-links a:hover {
    color: #0d5f58;
    text-decoration: underline;
}

     </style>
</head>
<body>

<div class="login-container">
    <h1>Login</h1>

    <?php if($error!=""): ?>
        <p style="color:red; text-align:center;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post">

        <div class="form-group">
            <label>Employee ID</label>
            <input type="text" name="userid" placeholder="Enter your Employee ID" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="login-btn">Login</button>

        <div class="extra-links">
            <a href="#">Forgot Password?</a>
        </div>
    </form>
</div>

<script src="assets/script.js"></script>
</body>
</html>