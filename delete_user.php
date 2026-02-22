<?php
session_start();
require "config.php";

// Only logged-in users
if (!isset($_SESSION["user"])) {
    echo "unauthorized";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "invalid";
    exit;
}

$userId = $_POST["id"] ?? "";

// Safety check
if ($userId === "") {
    echo "invalid";
    exit;
}

// ❌ Prevent deleting self
if ($userId == $_SESSION["user"]) {
    echo "self";
    exit;
}

// Delete user
$stmt = $pdo->prepare("DELETE FROM `user` WHERE id = ?");
$success = $stmt->execute([$userId]);

if ($success) {
    echo "success";
} else {
    echo "error";
}
