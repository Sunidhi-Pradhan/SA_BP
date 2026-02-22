<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "config.php"; // must contain $pdo

try {
    $sql = "
        ALTER TABLE `user`
        ADD COLUMN `google_secret` VARCHAR(32) DEFAULT NULL
    ";

    $pdo->exec($sql);

    echo "✅ Column 'google_secret' added successfully.";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
