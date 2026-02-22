<?php
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('Configuration file not found');
    }

    ob_start();
    require_once __DIR__ . '/config.php';
    ob_end_clean();

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Fetch all sites using correct column names
    $query = "SELECT SiteCode, SiteName FROM site_master ORDER BY SiteName ASC";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = [
            'code' => $row['SiteCode'],
            'name' => $row['SiteName']
        ];
    }

    $conn->close();

    $response = [
        'success' => true,
        'data' => $sites
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ];
}

ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
ob_end_flush();
exit(0);
?>