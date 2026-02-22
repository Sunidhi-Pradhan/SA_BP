<?php
session_start();
require "config.php";

/* Example values (normally from form / dashboard) */
$esicNo = "445731275";
$attendanceDate = "2026-01-10";

$attendanceData = [
    "date" => $attendanceDate,
    "status" => "P",
    "site_code" => "911",
    "approve_status" => 0,
    "locked" => 1,
    "created_by" => "95004205",
    "created_at" => date("Y-m-d H:i:s"),
    "updated_by" => null,
    "updated_at" => null
];

$json = json_encode($attendanceData, JSON_UNESCAPED_SLASHES);

$sql = "
INSERT INTO attendance (
    esic_no,
    attendance_year,
    attendance_month,
    attendance_date,
    attendance_json,
    backup_attendance_json
) VALUES (
    :esic_no, YEAR(:date), MONTH(:date), :date, :json, :json
)
ON DUPLICATE KEY UPDATE
    attendance_json = :json
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":esic_no" => $esicNo,
    ":date" => $attendanceDate,
    ":json" => $json
]);

echo "Saved successfully";

