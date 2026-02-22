<?php
require "config.php";

$sql = "
CREATE TABLE attendance (
    esic_no VARCHAR(20) NOT NULL,

    attendance_year YEAR NOT NULL,
    attendance_month TINYINT NOT NULL,
    attendance_date DATE NOT NULL,

    attendance_json JSON NOT NULL,
    backup_attendance_json JSON DEFAULT NULL,

    PRIMARY KEY (esic_no, attendance_date)
);

";

try {
    $pdo->exec($sql);
    echo "✅ Attendance table created successfully.";
} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage();
}
