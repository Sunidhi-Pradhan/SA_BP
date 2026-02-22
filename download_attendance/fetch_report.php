<?php
header("Content-Type: application/json");

require_once "../config.php"; // must create $pdo

try {

    if (!isset($pdo)) {
        throw new Exception("Database connection failed");
    }

    // ==============================
    // GET PARAMETERS
    // ==============================
    $esic_no  = $_GET['employee_id'] ?? '';
    $fromDate = $_GET['from_date'] ?? '';
    $toDate   = $_GET['to_date'] ?? '';

    if (empty($esic_no) || empty($fromDate) || empty($toDate)) {
        throw new Exception("All fields are required");
    }

    $from = new DateTime($fromDate);
    $to   = new DateTime($toDate);

    if ($from > $to) {
        throw new Exception("Invalid date range");
    }

    $month = (int)$from->format('n');
    $year  = (int)$from->format('Y');

    // ==============================
    // FETCH ATTENDANCE DATA
    // ==============================
    $stmt = $pdo->prepare("
        SELECT 
            a.attendance_json,
            em.employee_name,
            sm.SiteName
        FROM attendance a
        INNER JOIN employee_master em ON a.esic_no = em.esic_no
        INNER JOIN site_master sm ON em.site_code = sm.SiteCode

        WHERE a.esic_no = :esic
        AND a.attendance_year = :year
        AND a.attendance_month = :month
    ");

    $stmt->execute([
        ':esic' => $esic_no,
        ':year' => $year,
        ':month' => $month
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("No attendance found");
    }

    $attendanceJson = json_decode($row['attendance_json'], true);

    if (!is_array($attendanceJson)) {
        throw new Exception("Invalid attendance data format");
    }

    // ==============================
    // REMOVE DUPLICATE DATES
    // ==============================
    $uniqueDates = [];

    foreach ($attendanceJson as $record) {

        if (!isset($record['created_at'])) continue;

        $recordDate = substr($record['created_at'], 0, 10);

        // Keep latest record for same date
        $uniqueDates[$recordDate] = $record;
    }

    // ==============================
    // PROCESS DATA
    // ==============================
    $data = [];
    $present = 0;
    $absent = 0;
    $leave = 0;
    $overtime = 0;
    $sno = 1;

    foreach ($uniqueDates as $recordDate => $record) {

        $recordDateObj = new DateTime($recordDate);

        if ($recordDateObj >= $from && $recordDateObj <= $to) {

            $statusRaw = strtolower(trim($record['status'] ?? 'a'));

            if ($statusRaw === 'pp') {
                $status = 'Present + Overtime';
                $present++;
                $overtime++;
            }
            elseif ($statusRaw === 'p') {
                $status = 'Present';
                $present++;
            }
            elseif ($statusRaw === 'a') {
                $status = 'Absent';
                $absent++;
            }
            elseif ($statusRaw === 'l') {
                $status = 'Leave';
                $leave++;
            }
            else {
                $status = 'Absent';
                $absent++;
            }

            $data[] = [
                "sno" => $sno++,
                "date" => date("d-m-Y", strtotime($recordDate)),
                "day" => date("l", strtotime($recordDate)),
                "status" => $status
            ];
        }
    }

    // ==============================
    // CALCULATE TOTAL DAYS
    // ==============================
    $totalDays = $from->diff($to)->days + 1;

    // ==============================
    // RETURN RESPONSE
    // ==============================
    echo json_encode([
        "success" => true,
        "employeeName" => $row['employee_name'] ?? '',
        "employeeId" => $esic_no,
        "siteLocation" => $row['SiteName'] ?? '',
        "period" => $fromDate . " to " . $toDate,
        "totalDays" => $totalDays,
        "daysRecorded" => count($data),
        "present" => $present,
        "absent" => $absent,
        "leave" => $leave,
        "overtime" => $overtime,
        "attendanceData" => array_values($data)
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
