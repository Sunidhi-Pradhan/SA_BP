<?php
// Prevent any output
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'SABP');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Set charset to utf8 if connection is successful
if (!$conn->connect_error) {
    $conn->set_charset("utf8");
}
?>