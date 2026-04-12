<?php
session_start();
require "config.php";

$siteCode = "003";
$year = (int) date('Y', strtotime('first day of last month'));
$month = (int) date('n', strtotime('first day of last month'));

/* ---------------------------
   FETCH ATTENDANCE DATA
----------------------------*/
$stmt = $pdo->prepare("
    SELECT 
        a.*, 
        e.employee_name, 
        e.rank
    FROM attendance a
    LEFT JOIN employee_master e 
        ON a.esic_no = e.esic_no
    WHERE a.attendance_year  = :year
      AND a.attendance_month = :month
");
$stmt->execute([':year' => $year, ':month' => $month]);
$attendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

/* ---------------------------
   FETCH CURRENT WORKFLOW STATUS
----------------------------*/
$stmtWorkflow = $pdo->prepare("
    SELECT * FROM attendance_approval
    WHERE area_code        = ?
      AND attendance_month = ?
      AND attendance_year  = ?
");
$stmtWorkflow->execute([$siteCode, $month, $year]);
$workflowRow = $stmtWorkflow->fetch(PDO::FETCH_ASSOC);

$workflow = $workflowRow ? json_decode($workflowRow['attendance_workflow'], true) : null;
$currentStep = $workflow['current_step'] ?? 'ASO';
$alreadyApproved = ($currentStep !== 'ASO');

/* ---------------------------
   SAVE ASO APPROVAL
----------------------------*/
$approvalSuccess = false;

if (isset($_POST['approve_report']) && !$alreadyApproved) {

    $comment = trim($_POST['comment']);
    $userId = $_SESSION['user'] ?? null;

    $stmtUser = $pdo->prepare("SELECT id, name FROM user WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $approvedBy = $userData['name'] ?? 'Unknown';
    $approvedById = $userData['id'] ?? null;
    $actedAt = date('Y-m-d H:i:s');

    if ($workflowRow) {
        $pdo->prepare("
            UPDATE attendance_approval
            SET attendance_workflow = JSON_SET(
                attendance_workflow,
                '$.current_step',           'APM',
                '$.current_step_id',        2,
                '$.steps[0].status',        'approved',
                '$.steps[0].comment',       ?,
                '$.steps[0].acted_by',      ?,
                '$.steps[0].acted_at',      ?,
                '$.steps[0].auto_approved', false
            )
            WHERE area_code        = ?
              AND attendance_month = ?
              AND attendance_year  = ?
        ")->execute([$comment, $approvedById, $actedAt, $siteCode, $month, $year]);

    } else {
        $reportId = 'ATT-' . date('dmy') . rand(100, 999);
        $workflowJson = json_encode([
            "current_step" => "APM",
            "current_step_id" => 2,
            "steps" => [
                [
                    "id" => 1,
                    "Code" => "ASO",
                    "status" => "approved",
                    "comment" => $comment,
                    "acted_by" => $approvedById,
                    "acted_at" => $actedAt,
                    "auto_approved" => false
                ],
                [
                    "id" => 2,
                    "Code" => "APM",
                    "status" => "pending",
                    "comment" => null,
                    "acted_by" => null,
                    "acted_at" => null,
                    "auto_approved" => false
                ],
                [
                    "id" => 3,
                    "Code" => "GM",
                    "status" => "pending",
                    "comment" => null,
                    "acted_by" => null,
                    "acted_at" => null,
                    "auto_approved" => false
                ],
                [
                    "id" => 4,
                    "Code" => "HQSO",
                    "status" => "pending",
                    "comment" => null,
                    "acted_by" => null,
                    "acted_at" => null,
                    "auto_approved" => false
                ],
                [
                    "id" => 5,
                    "Code" => "SDHOD",
                    "status" => "pending",
                    "comment" => null,
                    "acted_by" => null,
                    "acted_at" => null,
                    "auto_approved" => false
                ],
            ]
        ]);

        $pdo->prepare("
            INSERT INTO attendance_approval
            (report_id, area_code, attendance_month, attendance_year,
             created_attendance_date, attendance_workflow)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ")->execute([$reportId, $siteCode, $month, $year, $workflowJson]);
    }

    $approvalSuccess = true;
}

/* ---------------------------
   COMPUTE WORKING DAYS FOR THE MONTH
----------------------------*/
$days = [];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dayOfWeek = date('N', mktime(0, 0, 0, $month, $d, $year));
    if ($dayOfWeek < 6) { // 6=Saturday, 7=Sunday
        $days[] = $d;
    }
}

/* ---------------------------
   STAT TOTALS
----------------------------*/
$totalWorking = 0;
$totalExtra = 0;
foreach ($attendanceRows as $r) {
    $ad = json_decode($r['attendance_json'], true) ?? [];
    foreach ($ad as $entry) {
        if (isset($entry['site_code']) && $entry['site_code'] == $siteCode) {
            if (($entry['status'] ?? '') === 'P')
                $totalWorking++;
            if (($entry['status'] ?? '') === 'PP')
                $totalExtra++;
        }
    }
}
$totalDuty = $totalWorking + $totalExtra;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance – Security Billing Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0f766e;
            --primary-dark: #0d5f58;
            --sidebar-width: 270px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
            background: linear-gradient(to right, #0a5c55 var(--sidebar-width), #f5f5f5 var(--sidebar-width));
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes cardPop {
            from {
                opacity: 0;
                transform: translateY(14px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes rowSlideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes popIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes dotPulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.15);
                opacity: 0.8;
            }
        }

        /* slideInLeft and sidebarSlideIn intentionally removed */

        /* ===== SIDEBAR — no animations ===== */
        .sidebar {
            background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%);
            color: white;
            padding: 0;
            box-shadow: 4px 0 24px rgba(13, 95, 88, 0.35);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            /* slideInLeft animation intentionally removed */
        }

        .sidebar-close {
            display: none;
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.12);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: background 0.2s, transform 0.2s;
        }

        .sidebar-close:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: rotate(90deg);
        }

        .sidebar-logo {
            padding: 1.4rem 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== LOGO — no pulse/pop animation ===== */
        .mcl-logo-img {
            max-width: 155px;
            height: auto;
            display: block;
            background: white;
            padding: 10px 14px;
            border-radius: 10px;
            /* popIn animation intentionally removed */
        }

        /* ===== NAV — no staggered slideInLeft ===== */
        .sidebar-nav {
            list-style: none;
            padding: 1rem 0;
            flex: 1;
        }

        .sidebar-nav li {
            margin: 0.25rem 1rem;
        }

        /* opacity: 0 and animation removed from nav items */

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.85rem 1.1rem;
            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            font-weight: 600;
        }

        .nav-link i {
            font-size: 1.05rem;
            width: 22px;
            text-align: center;
            opacity: 0.9;
            transition: transform 0.2s;
        }

        .nav-link:hover i {
            transform: scale(1.15);
        }

        .logout-link {
            color: rgba(255, 255, 255, 0.75) !important;
        }

        .logout-link:hover {
            background: rgba(239, 68, 68, 0.18) !important;
            color: #fca5a5 !important;
        }

        /* MAIN */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            min-width: 0;
            animation: fadeIn 0.4s 0.1s ease both;
        }

        /* TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-radius: 14px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 16px rgba(15, 118, 110, 0.12), 0 1px 4px rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(15, 118, 110, 0.15);
            animation: fadeUp 0.4s 0.15s ease both;
            transition: box-shadow 0.2s, border-color 0.2s;
        }

        .topbar:hover {
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.18);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .hamburger-btn {
            display: none;
            background: #f3f4f6;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #0f766e;
            font-size: 1rem;
            transition: background 0.2s, transform 0.2s;
        }

        .hamburger-btn:hover {
            background: #e5e7eb;
            transform: scale(1.05);
        }

        .topbar h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f2937;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            color: #6b7280;
            font-size: 1rem;
            border: 1px solid #e5e7eb;
            transition: background 0.2s, transform 0.2s;
        }

        .header-icon:hover {
            background: #e5e7eb;
            transform: scale(1.08);
        }

        .header-icon .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            animation: dotPulse 2s ease-in-out infinite;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #0f766e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .user-icon:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.35);
        }

        .user-icon svg {
            width: 20px;
            height: 20px;
            stroke: white;
        }

        /* ALREADY APPROVED BANNER */
        .already-approved-banner {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 14px;
            padding: 1.25rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 12px rgba(245, 158, 11, 0.2);
            animation: fadeUp 0.4s 0.3s ease both;
        }

        .already-approved-banner i {
            font-size: 1.5rem;
            color: #d97706;
        }

        .already-approved-banner .msg-title {
            font-weight: 700;
            color: #92400e;
            font-size: 1rem;
        }

        .already-approved-banner .msg-sub {
            font-size: 0.85rem;
            color: #b45309;
            margin-top: 0.2rem;
        }

        /* STAT CARDS */
        .attendance-header {
            text-align: center;
            animation: fadeUp 0.4s 0.2s ease both;
        }

        .attendance-header h1 {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.35rem;
        }

        .attendance-header p {
            font-size: 0.95rem;
            color: #6b7280;
        }

        .attendance-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .attendance-stat-card {
            background: linear-gradient(135deg, var(--card-start), var(--card-end));
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            opacity: 0;
            animation: cardPop 0.4s ease forwards;
        }

        .attendance-stat-card:nth-child(1) {
            animation-delay: 0.30s;
        }

        .attendance-stat-card:nth-child(2) {
            animation-delay: 0.40s;
        }

        .attendance-stat-card:nth-child(3) {
            animation-delay: 0.50s;
        }

        .attendance-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.18);
        }

        .attendance-stat-card.blue {
            --card-start: #0ea5e9;
            --card-end: #0284c7;
            --card-border: #0369a1;
        }

        .attendance-stat-card.amber {
            --card-start: #f59e0b;
            --card-end: #d97706;
            --card-border: #b45309;
        }

        .attendance-stat-card.green {
            --card-start: #10b981;
            --card-end: #059669;
            --card-border: #047857;
        }

        .stat-card-content {
            color: white;
        }

        .stat-card-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.95;
            margin-bottom: 0.5rem;
        }

        .stat-card-value {
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-card-icon {
            font-size: 2.75rem;
            color: rgba(255, 255, 255, 0.3);
            transition: transform 0.2s, color 0.2s;
        }

        .attendance-stat-card:hover .stat-card-icon {
            transform: scale(1.1) rotate(-5deg);
            color: rgba(255, 255, 255, 0.5);
        }

        /* CARD */
        .card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 4px 16px rgba(15, 118, 110, 0.12), 0 1px 4px rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(15, 118, 110, 0.15);
            animation: fadeUp 0.4s 0.45s ease both;
            transition: box-shadow 0.2s, border-color 0.2s;
            margin-top: 1rem;
        }

        .card:hover {
            box-shadow: 0 8px 28px rgba(15, 118, 110, 0.2);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-controls-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .table-controls-left select {
            padding: 0.5rem 0.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .table-controls-left select:focus {
            border-color: #0f766e;
        }

        .search-input {
            padding: 0.6rem 1rem 0.6rem 2.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            width: 240px;
            background: white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 1rem center;
            background-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-input:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        /* STICKY TABLE */
        .attendance-table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #0f766e #f0f0f0;
        }

        .attendance-table-wrapper::-webkit-scrollbar {
            height: 6px;
        }

        .attendance-table-wrapper::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        .attendance-table-wrapper::-webkit-scrollbar-thumb {
            background: #0f766e;
            border-radius: 3px;
        }

        .attendance-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
            background: white;
        }

        .attendance-table thead th {
            background: linear-gradient(135deg, #0f766e, #0d5f58);
            color: white;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 0.875rem 0.45rem;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .attendance-table thead th:first-child {
            text-align: left;
            padding-left: 1rem;
        }

        .attendance-table thead th.summary-col {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            font-weight: 800;
            font-size: 0.8rem;
        }

        .attendance-table thead th.extra-col {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .attendance-table thead th.total-col {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        /* Sticky left */
        .attendance-table thead th:nth-child(1),
        .attendance-table tbody td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 11;
            min-width: 48px;
            width: 48px;
        }

        .attendance-table thead th:nth-child(2),
        .attendance-table tbody td:nth-child(2) {
            position: sticky;
            left: 48px;
            z-index: 11;
            min-width: 90px;
            width: 90px;
        }

        .attendance-table thead th:nth-child(3),
        .attendance-table tbody td:nth-child(3) {
            position: sticky;
            left: 138px;
            z-index: 11;
            min-width: 140px;
            width: 140px;
            text-align: left !important;
        }

        .attendance-table thead th:nth-child(4),
        .attendance-table tbody td:nth-child(4) {
            position: sticky;
            left: 278px;
            z-index: 11;
            min-width: 90px;
            width: 90px;
            border-right: 2px solid rgba(255, 255, 255, 0.3) !important;
        }

        .attendance-table thead th:nth-child(1),
        .attendance-table thead th:nth-child(2),
        .attendance-table thead th:nth-child(3),
        .attendance-table thead th:nth-child(4) {
            background: linear-gradient(135deg, #0f766e, #0d5f58);
            z-index: 12;
        }

        .attendance-table tbody td:nth-child(1),
        .attendance-table tbody td:nth-child(2),
        .attendance-table tbody td:nth-child(3),
        .attendance-table tbody td:nth-child(4) {
            background: #fff;
        }

        .attendance-table tbody tr:nth-child(even) td:nth-child(1),
        .attendance-table tbody tr:nth-child(even) td:nth-child(2),
        .attendance-table tbody tr:nth-child(even) td:nth-child(3),
        .attendance-table tbody tr:nth-child(even) td:nth-child(4) {
            background: #fafafa;
        }

        .attendance-table tbody tr:hover td:nth-child(1),
        .attendance-table tbody tr:hover td:nth-child(2),
        .attendance-table tbody tr:hover td:nth-child(3),
        .attendance-table tbody tr:hover td:nth-child(4) {
            background: #f0fdf9;
        }

        .attendance-table tbody td:nth-child(4) {
            border-right: 2px solid #e5e7eb !important;
        }

        /* Sticky right */
        .attendance-table thead th.col-working,
        .attendance-table tbody td.col-working {
            position: sticky;
            right: 104px;
            z-index: 11;
            min-width: 72px;
            width: 72px;
        }

        .attendance-table thead th.col-extra,
        .attendance-table tbody td.col-extra {
            position: sticky;
            right: 52px;
            z-index: 11;
            min-width: 52px;
            width: 52px;
        }

        .attendance-table thead th.col-total,
        .attendance-table tbody td.col-total {
            position: sticky;
            right: 0;
            z-index: 11;
            min-width: 52px;
            width: 52px;
        }

        .attendance-table thead th.col-working {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            z-index: 12;
        }

        .attendance-table thead th.col-extra {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            z-index: 12;
        }

        .attendance-table thead th.col-total {
            background: linear-gradient(135deg, #10b981, #059669);
            z-index: 12;
        }

        .attendance-table tbody td.col-working {
            background: #dbeafe;
            border-left: 2px solid #bfdbfe !important;
        }

        .attendance-table tbody td.col-extra {
            background: #fef3c7;
        }

        .attendance-table tbody td.col-total {
            background: #d1fae5;
        }

        .attendance-table tbody tr:nth-child(even) td.col-working {
            background: #bfdbfe;
        }

        .attendance-table tbody tr:nth-child(even) td.col-extra {
            background: #fde68a;
        }

        .attendance-table tbody tr:nth-child(even) td.col-total {
            background: #a7f3d0;
        }

        .attendance-table thead th.day-col,
        .attendance-table tbody td.day-col {
            min-width: 36px;
            width: 36px;
        }

        /* staggered row animation */
        .attendance-table tbody tr {
            transition: background 0.2s;
            opacity: 0;
            animation: rowSlideIn 0.3s ease forwards;
        }

        .attendance-table tbody tr:nth-child(1) {
            animation-delay: 0.05s;
        }

        .attendance-table tbody tr:nth-child(2) {
            animation-delay: 0.10s;
        }

        .attendance-table tbody tr:nth-child(3) {
            animation-delay: 0.15s;
        }

        .attendance-table tbody tr:nth-child(4) {
            animation-delay: 0.20s;
        }

        .attendance-table tbody tr:nth-child(5) {
            animation-delay: 0.25s;
        }

        .attendance-table tbody tr:nth-child(6) {
            animation-delay: 0.30s;
        }

        .attendance-table tbody tr:nth-child(7) {
            animation-delay: 0.35s;
        }

        .attendance-table tbody tr:nth-child(8) {
            animation-delay: 0.40s;
        }

        .attendance-table tbody tr:nth-child(9) {
            animation-delay: 0.45s;
        }

        .attendance-table tbody tr:nth-child(10) {
            animation-delay: 0.50s;
        }

        .attendance-table tbody tr:nth-child(n+11) {
            animation-delay: 0.55s;
        }

        .attendance-table tbody tr:hover {
            background: #f0fdf9;
        }

        .attendance-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .attendance-table tbody td {
            padding: 0.7rem 0.45rem;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
            vertical-align: middle;
        }

        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }

        .attendance-table tbody td:first-child,
        .attendance-table tbody td:nth-child(2),
        .attendance-table tbody td:nth-child(3),
        .attendance-table tbody td:nth-child(4) {
            text-align: left;
            font-size: 0.82rem;
            color: #1f2937;
            font-weight: 500;
            padding-left: 1rem;
            white-space: nowrap;
        }

        .attendance-table tbody td:nth-child(3),
        .attendance-table tbody td:nth-child(4) {
            color: #6b7280;
            font-weight: 400;
            font-size: 0.8rem;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 7px;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: default;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .status-indicator:hover {
            transform: scale(1.15);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .status-present {
            background: #d1fae5;
            color: #059669;
        }

        .status-pp {
            background: #bfdbfe;
            color: #1d4ed8;
            font-size: 0.66rem;
            letter-spacing: -0.5px;
        }

        .status-leave {
            background: #fef3c7;
            color: #d97706;
        }

        .status-absent {
            background: #fee2e2;
            color: #dc2626;
        }

        .total-cell {
            font-weight: 700;
            color: #1f2937;
            background: #f3f4f6;
            font-size: 0.9rem;
        }

        .total-cell.working-cell {
            background: #dbeafe;
            color: #0369a1;
        }

        .total-cell.extra-cell {
            background: #fef3c7;
            color: #b45309;
        }

        .total-cell.total-value {
            background: #d1fae5;
            color: #059669;
            font-weight: 800;
            font-size: 0.95rem;
        }

        /* LEGEND */
        .legend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 10px;
            flex-wrap: wrap;
            animation: fadeUp 0.4s 0.5s ease both;
            opacity: 0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #4b5563;
            font-weight: 500;
        }

        .legend-box {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            transition: transform 0.15s;
        }

        .legend-box:hover {
            transform: scale(1.15);
        }

        /* PAGINATION */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            animation: fadeUp 0.4s 0.55s ease both;
            opacity: 0;
        }

        .page-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .page-btn:hover {
            border-color: #0f766e;
            color: #0f766e;
            transform: translateY(-1px);
        }

        .page-btn.active {
            background: #0f766e;
            color: white;
            border-color: #0f766e;
        }

        /* FORWARD */
        .forward-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
            animation: fadeUp 0.4s 0.6s ease both;
            opacity: 0;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1f2937;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            resize: vertical;
        }

        .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: #0f766e;
            color: white;
        }

        .btn-primary:hover {
            background: #0d5f58;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 118, 110, 0.35);
        }

        .forward-btn-group {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
        }

        /* SUCCESS */
        .success-page {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            padding: 2rem 0;
        }

        .success-page.visible {
            display: flex;
        }

        .success-banner {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 60%, #15803d 100%);
            border-radius: 18px;
            padding: 3.5rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            animation: fadeUp 0.4s ease both;
        }

        .success-check {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
        }

        .success-check i {
            font-size: 2rem;
            color: #16a34a;
        }

        .success-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-align: center;
            animation: fadeUp 0.45s 0.2s ease both;
        }

        .success-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem 2.25rem;
            min-width: 340px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            animation: fadeUp 0.45s 0.35s ease both;
        }

        .success-card-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.6rem;
        }

        .success-card-title i {
            color: #16a34a;
        }

        .success-card-desc {
            font-size: 0.88rem;
            color: #6b7280;
            margin-bottom: 0.85rem;
            line-height: 1.55;
        }

        .success-card-date {
            font-size: 0.87rem;
            font-weight: 600;
            color: #1f2937;
        }

        .success-card-date span {
            color: #6b7280;
            font-weight: 400;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: var(--sidebar-width);
                transform: translateX(-100%);
                animation: none;
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: 8px 0 32px rgba(0, 0, 0, 0.3);
            }

            .sidebar-close {
                display: flex;
            }

            .hamburger-btn {
                display: flex;
            }

            .main-content {
                padding: 1rem;
                gap: 1rem;
            }

            .attendance-stats {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .search-input {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/responsive.css">
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-layout">
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
            <div class="sidebar-logo">
                <img src="assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
            </div>
            <ul class="sidebar-nav">
                <li><a href="aso_dashboard.php" class="nav-link"><i
                            class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
                <li><a href="asomonthly.php" class="nav-link active"><i
                            class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
                <li><a href="logout.php" class="nav-link logout-link"><i
                            class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
                <h2>Security Billing Management Portal</h2>
                <div class="topbar-right">
                    <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                    <a href="aso_details.php" title="My Profile" style="text-decoration:none;">
                        <div class="user-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="8" r="4" />
                            </svg>
                        </div>
                    </a>
                </div>
            </header>

            <!-- ATTENDANCE CONTENT -->
            <div id="attendanceContent" <?= $approvalSuccess ? 'style="display:none"' : '' ?>>

                <div class="attendance-header">
                    <h1>MONTHLY ATTENDANCE REPORT</h1>
                    <p>Attendance Period: <?= date('F Y', strtotime('first day of last month')) ?>
                        &nbsp;|&nbsp; Working Days: <?= count($days) ?> (Weekends Excluded)
                        &nbsp;|&nbsp; Site: <strong><?= htmlspecialchars($siteCode) ?></strong></p>

                    <?php if ($alreadyApproved): ?>
                        <div class="already-approved-banner">
                            <i class="fa-solid fa-circle-check"></i>
                            <div>
                                <div class="msg-title">You have already approved this report.</div>
                                <div class="msg-sub">Current workflow step:
                                    <strong><?= htmlspecialchars($currentStep) ?></strong>. Waiting for next officer to act.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- STAT CARDS -->
                    <div class="attendance-stats">
                        <div class="attendance-stat-card blue">
                            <div class="stat-card-content">
                                <div class="stat-card-label">Working Days</div>
                                <div class="stat-card-value"><?= $totalWorking ?></div>
                            </div>
                            <div class="stat-card-icon"><i class="fa-solid fa-briefcase"></i></div>
                        </div>
                        <div class="attendance-stat-card amber">
                            <div class="stat-card-content">
                                <div class="stat-card-label">Extra Duty</div>
                                <div class="stat-card-value"><?= $totalExtra ?></div>
                            </div>
                            <div class="stat-card-icon"><i class="fa-solid fa-clock"></i></div>
                        </div>
                        <div class="attendance-stat-card green">
                            <div class="stat-card-content">
                                <div class="stat-card-label">Total Duty Days</div>
                                <div class="stat-card-value"><?= $totalDuty ?></div>
                            </div>
                            <div class="stat-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        </div>
                    </div>

                    <!-- TABLE CARD -->
                    <div class="card">
                        <div class="table-controls">
                            <div class="table-controls-left">
                                <label>Show</label>
                                <select>
                                    <option>10</option>
                                    <option>25</option>
                                    <option>50</option>
                                    <option>100</option>
                                </select>
                                <label>entries</label>
                            </div>
                            <input type="text" class="search-input" placeholder="Search">
                        </div>

                        <div class="attendance-table-wrapper">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>S.N.</th>
                                        <th>EMP CODE</th>
                                        <th>NAME</th>
                                        <th>RANK</th>
                                        <?php foreach ($days as $day): ?>
                                            <th class="day-col"><?= $day ?></th>
                                        <?php endforeach; ?>
                                        <th class="summary-col col-working">WORKING</th>
                                        <th class="summary-col extra-col col-extra">EXTRA</th>
                                        <th class="summary-col total-col col-total">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sn = 1;
                                    foreach ($attendanceRows as $row):
                                        $attendanceData = json_decode($row['attendance_json'], true) ?? [];
                                        $working = 0;
                                        $extra = 0;
                                        echo "<tr>";
                                        echo "<td>" . $sn++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['esic_no']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['rank']) . "</td>";
                                        foreach ($days as $day) {
                                            $dateKey = $year . "-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                            if (isset($attendanceData[$dateKey]) && $attendanceData[$dateKey]['site_code'] == $siteCode) {
                                                $status = $attendanceData[$dateKey]['status'];
                                                if ($status === 'P')
                                                    $working++;
                                                if ($status === 'PP')
                                                    $extra++;
                                                $class = match ($status) {
                                                    'P' => 'status-present',
                                                    'PP' => 'status-pp',
                                                    'L' => 'status-leave',
                                                    'A' => 'status-absent',
                                                    default => ''
                                                };
                                                echo "<td class='day-col'><span class='status-indicator $class'>$status</span></td>";
                                            } else {
                                                echo "<td class='day-col'>-</td>";
                                            }
                                        }
                                        $total = $working + $extra;
                                        echo "<td class='total-cell working-cell col-working'>$working</td>";
                                        echo "<td class='total-cell extra-cell col-extra'>$extra</td>";
                                        echo "<td class='total-cell total-value col-total'>$total</td>";
                                        echo "</tr>";
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-box status-present">P</div><span>Present</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box status-pp">PP</div><span>Double Duty</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box status-leave">L</div><span>Leave</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box status-absent">A</div><span>Absent</span>
                            </div>
                        </div>

                        <div class="pagination">
                            <button class="page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn">4</button>
                            <button class="page-btn">5</button>
                            <button class="page-btn"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>

                        <?php if (!$alreadyApproved): ?>
                            <form method="POST" id="approvalForm">
                                <div class="forward-section">
                                    <div class="form-group">
                                        <label class="form-label">Add Approval Comment <span
                                                class="required">*</span></label>
                                        <textarea class="form-control" name="comment" id="commentBox" rows="5"
                                            placeholder="Add your ASO approval comment..." required></textarea>
                                    </div>
                                    <div class="forward-btn-group">
                                        <button type="submit" name="approve_report" class="btn btn-primary">
                                            <i class="fa-solid fa-check"></i> APPROVE & FORWARD TO APM
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="forward-section"
                                style="text-align:center;color:#6b7280;font-size:0.9rem;padding:1rem 0;">
                                <i class="fa-solid fa-lock" style="margin-right:0.4rem;color:#d97706;"></i>
                                Approval form is locked. Report has been forwarded to
                                <strong><?= htmlspecialchars($currentStep) ?></strong>.
                            </div>
                        <?php endif; ?>

                    </div><!-- /.card -->
                </div><!-- /#attendanceContent -->

                <!-- SUCCESS PAGE -->
                <div class="success-page <?= $approvalSuccess ? 'visible' : '' ?>" id="successPage">
                    <div class="success-banner">
                        <div class="success-check"><i class="fa-solid fa-check"></i></div>
                        <div class="success-title">Report Approved &amp; Forwarded to APM!</div>
                        <div class="success-card">
                            <div class="success-card-title"><i class="fa-solid fa-user-check"></i> ASO APPROVED</div>
                            <div class="success-card-desc">
                                You have successfully reviewed and forwarded this attendance report to APM for the next
                                stage of approval.
                            </div>
                            <div class="success-card-date">
                                Processed On: <span><?= $approvalSuccess ? date('Y-m-d H:i:s') : '' ?></span>
                            </div>
                        </div>
                    </div>
                </div>

        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('hamburgerBtn').addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('active'); });
        document.getElementById('sidebarClose').addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

        const commentBox = document.getElementById('commentBox');
        if (commentBox) {
            commentBox.addEventListener('input', function () { this.classList.remove('error'); });
        }
    </script>
</body>

</html>