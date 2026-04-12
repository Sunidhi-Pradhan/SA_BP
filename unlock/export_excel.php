<?php
require_once '../config.php';

// Get filter parameters
$date = $_GET['date'] ?? '';
$status = $_GET['status'] ?? '';
$site = $_GET['site'] ?? '';

// Build SQL query
$sql = "SELECT * FROM attendance WHERE 1=1";
$params = [];

if (!empty($date)) {
    $sql .= " AND attendance_json LIKE :date";
    $params[':date'] = "%$date%";
}

if (!empty($status) && $status !== 'All Status') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}

if (!empty($site) && $site !== 'All Sites') {
    $sql .= " AND site_id = :site";
    $params[':site'] = $site;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=attendance_report_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #0f766e; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Security Attendance and Billing Portal - Attendance Report</h2>
    <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>S.NO</th>
                <th>EMP ID</th>
                <th>EMPLOYEE NAME</th>
                <th>SITE NAME</th>
                <th>DATE</th>
                <th>STATUS</th>
                <th>YEAR</th>
                <th>MONTH</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (count($rows) > 0) {
                $i = 1;
                foreach ($rows as $row) {
                    // Decode attendance JSON if needed
                    $attendanceData = json_decode($row['attendance_json'] ?? '{}', true);
                    
                    echo "<tr>";
                    echo "<td>" . $i++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['emp_id'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['emp_name'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['site_name'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['status'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['attendance_year'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['attendance_month'] ?? '') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8' style='text-align: center;'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>