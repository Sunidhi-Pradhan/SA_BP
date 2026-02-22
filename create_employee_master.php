<?php
// Show errors (for development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
require "config.php";

try {
    $sql = "
        CREATE TABLE IF NOT EXISTS employee_master (
            id INT AUTO_INCREMENT PRIMARY KEY,
            esic_no VARCHAR(30) NOT NULL,
            employee_name VARCHAR(150),
            employee_json JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_esic (esic_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $pdo->exec($sql);

    echo "✅ employee_master table created successfully";

} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage();
}
