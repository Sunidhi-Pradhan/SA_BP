<?php
$host = "localhost";
$dbname = "demo";        // Your database name
$username = "root";     // Change if needed
$password = "";         // Change if needed

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    // SQL to create users table
    $sql = "
        CREATE TABLE IF NOT EXISTS user (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            role VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'Active',
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";

    // Execute query
    $pdo->exec($sql);

    echo "✅ User table created successfully!";

} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage();
}
?>
