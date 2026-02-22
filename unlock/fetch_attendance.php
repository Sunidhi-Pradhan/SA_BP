<?php
// Ultra-strict output control
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

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

    // Get parameters
    $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $site = isset($_GET['site']) ? trim($_GET['site']) : '';
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }

    if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
        throw new Exception('Invalid month or year');
    }

    // Build site filter
    $siteCond = '';
    if (!empty($site)) {
        $siteEsc = $conn->real_escape_string($site);
        $siteCond = " AND em.site_code = '$siteEsc'";
    }

    // Query using correct column names from your schema
    $sql = "SELECT 
        a.esic_no,
        a.attendance_year,
        a.attendance_month,
        a.attendance_json,
        em.employee_name,
        em.site_code,
        sm.SiteName,
        em.rank
    FROM attendance a
    LEFT JOIN employee_master em ON a.esic_no = em.esic_no
    LEFT JOIN site_master sm ON em.site_code = sm.SiteCode
    WHERE a.attendance_year = $year
    AND a.attendance_month = $month
    $siteCond
    ORDER BY em.employee_name ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Database query failed: ' . $conn->error);
    }

    // Process results
    $employees = [];
    $stats = ['present' => 0, 'absent' => 0, 'leave' => 0, 'overtime' => 0];

    while ($row = $result->fetch_assoc()) {
        $attendanceData = @json_decode($row['attendance_json'], true);
        
        if (!is_array($attendanceData)) {
            continue;
        }

        // Find record for specified date
        $dateKey = $date;
        $record = null;
        
        foreach ($attendanceData as $item) {
            if (isset($item['created_at'])) {
                // Extract date from created_at (format: "2026-02-02 HH:MM:SS")
                $itemDate = substr($item['created_at'], 0, 10);
                if ($itemDate === $dateKey) {
                    $record = $item;
                    break;
                }
            }
        }

        // Skip if no record for this date
        if (!$record) {
            continue;
        }

        $recordStatusRaw = strtolower(trim($record['status'] ?? 'absent'));

$statusMap = [
    'p' => 'present',
    'present' => 'present',
    'a' => 'absent',
    'absent' => 'absent',
    'l' => 'leave',
    'leave' => 'leave',
    'pp' => 'overtime',
    'ot' => 'overtime',
    'overtime' => 'overtime'
];

$displayStatus = $statusMap[$recordStatusRaw] ?? 'absent';

// ✅ NOW apply filter on mapped value
if (!empty($status) && $displayStatus !== strtolower($status)) {
    continue;
}

        
        // Update statistics
        if (isset($stats[$displayStatus])) {
            $stats[$displayStatus]++;
        }

        $employees[] = [
            'id' => count($employees) + 1,
            'empId' => $row['esic_no'] ?? '',
            'name' => $row['employee_name'] ?? 'N/A',
            'site' => $row['SiteName'] ?? 'N/A',
            'siteCode' => $row['site_code'] ?? '',
            'rank' => $row['rank'] ?? 'N/A',
            'date' => $date,
            'status' => $displayStatus,
            'approveStatus' => (int)($record['approve_status'] ?? 0),
            'locked' => (int)($record['locked'] ?? 0),
            'createdBy' => $record['created_by'] ?? null,
            'createdAt' => $record['created_at'] ?? null
        ];
    }

    $result->free();
    $conn->close();

    // Calculate percentages
    $total = count($employees);
    
    $response = [
        'success' => true,
        'data' => $employees,
        'statistics' => [
            'total' => $total,
            'present' => $stats['present'],
            'absent' => $stats['absent'],
            'leave' => $stats['leave'],
            'overtime' => $stats['overtime'],
            'presentPercent' => $total > 0 ? round(($stats['present'] / $total) * 100, 1) : 0,
            'absentPercent' => $total > 0 ? round(($stats['absent'] / $total) * 100, 1) : 0,
            'leavePercent' => $total > 0 ? round(($stats['leave'] / $total) * 100, 1) : 0,
            'overtimePercent' => $total > 0 ? round(($stats['overtime'] / $total) * 100, 1) : 0
        ]
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
        'statistics' => [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'overtime' => 0,
            'presentPercent' => 0,
            'absentPercent' => 0,
            'leavePercent' => 0,
            'overtimePercent' => 0
        ]
    ];
}

ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
ob_end_flush();
exit(0);