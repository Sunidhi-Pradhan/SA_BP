<?php
/**
 * One-time migration: add OTP lockout columns to `user` table.
 * Run once via: http://localhost:8080/prj1/SABP/add_otp_lockout_columns.php
 */
require "config.php";

try {
    $pdo->exec("ALTER TABLE `user` ADD COLUMN `otp_failed_attempts` INT DEFAULT 0");
    echo "✅ Added otp_failed_attempts column.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ️ otp_failed_attempts column already exists.<br>";
    } else {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE `user` ADD COLUMN `otp_locked_until` DATETIME DEFAULT NULL");
    echo "✅ Added otp_locked_until column.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ️ otp_locked_until column already exists.<br>";
    } else {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo "<br>✅ Migration complete!";
