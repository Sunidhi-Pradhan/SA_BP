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
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Report - PDF</title>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 15mm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        
        body { 
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h2 {
            color: #0f766e;
            margin: 10px 0;
        }
        
        .info {
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
            font-size: 11px;
        }
        
        th, td { 
            border: 1px solid #000; 
            padding: 6px;
            text-align: left;
        }
        
        th { 
            background-color: #0f766e; 
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Security Billing Portal</h2>
        <h3>Attendance Report</h3>
    </div>
    
    <div class="info">
        <strong>Report Generated:</strong> <?php echo date('d-m-Y H:i:s'); ?><br>
        <?php if ($date): ?>
            <strong>Date Filter:</strong> <?php echo htmlspecialchars($date); ?><br>
        <?php endif; ?>
        <?php if ($status && $status !== 'All Status'): ?>
            <strong>Status Filter:</strong> <?php echo htmlspecialchars($status); ?><br>
        <?php endif; ?>
        <?php if ($site && $site !== 'All Sites'): ?>
            <strong>Site Filter:</strong> <?php echo htmlspecialchars($site); ?><br>
        <?php endif; ?>
        <strong>Total Records:</strong> <?php echo count($rows); ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">S.NO</th>
                <th style="width: 10%;">EMP ID</th>
                <th style="width: 20%;">EMPLOYEE NAME</th>
                <th style="width: 15%;">SITE NAME</th>
                <th style="width: 12%;">DATE</th>
                <th style="width: 10%;">STATUS</th>
                <th style="width: 8%;">YEAR</th>
                <th style="width: 10%;">MONTH</th>
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
                    echo "<td style='text-align: center;'>" . $i++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['emp_id'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['emp_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['site_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['status'] ?? 'N/A') . "</td>";
                    echo "<td style='text-align: center;'>" . htmlspecialchars($row['attendance_year'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['attendance_month'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8' style='text-align: center; padding: 20px;'>No data available for the selected filters</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>© <?php echo date('Y'); ?> Security Billing Portal - MCI. All rights reserved.</p>
    </div>

    <script>
        // Auto-trigger print dialog when page loads
        window.onload = function() {
            window.print();
        };
        
        // Close window after printing or canceling
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>