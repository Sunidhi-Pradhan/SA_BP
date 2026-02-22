<?php
session_start();
require "config.php";

// Fetch one record from attendance table
$sql = "SELECT * FROM attendance LIMIT 3";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Attendance</title></head><body>";
echo "<h2>Attendance Table Debug</h2>";
echo "<p>Total records found: " . count($records) . "</p>";

foreach ($records as $index => $record) {
    echo "<hr>";
    echo "<h3>Record " . ($index + 1) . ":</h3>";
    echo "<pre>";
    echo "<strong>ESIC No:</strong> " . htmlspecialchars($record['esic_no']) . "\n";
    echo "<strong>Year:</strong> " . htmlspecialchars($record['attendance_year']) . "\n";
    echo "<strong>Month:</strong> " . htmlspecialchars($record['attendance_month']) . "\n";
    echo "\n<strong>Raw attendance_json:</strong>\n";
    echo htmlspecialchars($record['attendance_json']);
    echo "\n\n<strong>Decoded attendance_json:</strong>\n";
    $decoded = json_decode($record['attendance_json'], true);
    print_r($decoded);
    
    if (isset($record['backup_attendance_json'])) {
        echo "\n\n<strong>Raw backup_attendance_json:</strong>\n";
        echo htmlspecialchars($record['backup_attendance_json']);
        echo "\n\n<strong>Decoded backup_attendance_json:</strong>\n";
        $decodedBackup = json_decode($record['backup_attendance_json'], true);
        print_r($decodedBackup);
    }
    
    echo "</pre>";
}

echo "</body></html>";
?>
