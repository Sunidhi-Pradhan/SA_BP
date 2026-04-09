<?php
session_start();
require "../config.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT role, site_code, name FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'GM') {
    die("Access denied");
}

$siteCode   = $user['site_code'];
$approvedBy = $user['name'];
$userId     = $_SESSION['user'];

$year  = (int) date('Y', strtotime('first day of last month'));
$month = (int) date('n', strtotime('first day of last month'));

/* FETCH ATTENDANCE DATA */
$stmt = $pdo->prepare("
    SELECT a.*, e.employee_name, e.rank, e.site_code
    FROM attendance a
    LEFT JOIN employee_master e ON a.esic_no = e.esic_no
    WHERE a.attendance_year = :year AND a.attendance_month = :month AND e.site_code = :siteCode
");
$stmt->execute([':year' => $year, ':month' => $month, ':siteCode' => $siteCode]);
$attendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

/* FETCH WORKFLOW */
$stmtWorkflow = $pdo->prepare("SELECT * FROM attendance_approval WHERE area_code = ? AND attendance_month = ? AND attendance_year = ?");
$stmtWorkflow->execute([$siteCode, $month, $year]);
$workflowRow = $stmtWorkflow->fetch(PDO::FETCH_ASSOC);

$workflow        = $workflowRow ? json_decode($workflowRow['attendance_workflow'], true) : null;
$currentStep     = $workflow['current_step'] ?? 'ASO';
$alreadyApproved = false;

if ($workflow) {
    foreach ($workflow['steps'] as $step) {
        if ($step['Code'] === 'GM' && $step['status'] === 'approved') {
            $alreadyApproved = true;
            break;
        }
    }
}

/* SAVE GM APPROVAL */
$approvalSuccess = false;

if (isset($_POST['approve_report']) && !$alreadyApproved) {
    $comment = trim($_POST['comment']);
    $actedAt = date('Y-m-d H:i:s');

    if ($workflowRow) {
        $pdo->prepare("
            UPDATE attendance_approval
            SET attendance_workflow = JSON_SET(
                attendance_workflow,
                '$.current_step',           'HQSO',
                '$.current_step_id',        4,
                '$.steps[2].status',        'approved',
                '$.steps[2].comment',       ?,
                '$.steps[2].acted_by',      ?,
                '$.steps[2].acted_at',      ?,
                '$.steps[2].auto_approved', false
            )
            WHERE area_code = ? AND attendance_month = ? AND attendance_year = ?
        ")->execute([$comment, $userId, $actedAt, $siteCode, $month, $year]);
    } else {
        $reportId     = 'ATT-' . date('dmy') . rand(100, 999);
        $workflowJson = json_encode([
            "current_step" => "HQSO", "current_step_id" => 4,
            "steps" => [
                ["id"=>1,"Code"=>"ASO",  "status"=>"approved","comment"=>null,"acted_by"=>null,"acted_at"=>null,"auto_approved"=>true],
                ["id"=>2,"Code"=>"APM",  "status"=>"approved","comment"=>null,"acted_by"=>null,"acted_at"=>null,"auto_approved"=>true],
                ["id"=>3,"Code"=>"GM",   "status"=>"approved","comment"=>$comment,"acted_by"=>$userId,"acted_at"=>$actedAt,"auto_approved"=>false],
                ["id"=>4,"Code"=>"HQSO", "status"=>"pending","comment"=>null,"acted_by"=>null,"acted_at"=>null,"auto_approved"=>false],
                ["id"=>5,"Code"=>"SDHOD","status"=>"pending","comment"=>null,"acted_by"=>null,"acted_at"=>null,"auto_approved"=>false],
            ]
        ]);
        $pdo->prepare("INSERT INTO attendance_approval (report_id, area_code, attendance_month, attendance_year, created_attendance_date, attendance_workflow) VALUES (?, ?, ?, ?, NOW(), ?)")
            ->execute([$reportId, $siteCode, $month, $year, $workflowJson]);
    }

    $approvalSuccess = true;
    $stmtWorkflow->execute([$siteCode, $month, $year]);
    $workflowRow  = $stmtWorkflow->fetch(PDO::FETCH_ASSOC);
    $workflow     = $workflowRow ? json_decode($workflowRow['attendance_workflow'], true) : null;
    $currentStep  = $workflow['current_step'] ?? 'HQSO';
}

/* BUILD STEP STATUSES */
$stepStatuses = ['ASO' => 'pending', 'APM' => 'pending', 'GM' => 'pending', 'HQSO' => 'pending', 'SDHOD' => 'pending'];
if ($workflow) {
    foreach ($workflow['steps'] as $step) {
        if ($step['status'] === 'approved') $stepStatuses[$step['Code']] = 'approved';
    }
    if (isset($stepStatuses[$currentStep]) && $stepStatuses[$currentStep] !== 'approved') {
        $stepStatuses[$currentStep] = 'current';
    }
}

/* FETCH COMMENTS */
$comments = [];
if ($workflow) {
    foreach ($workflow['steps'] as $step) {
        if (!empty($step['comment']) && $step['status'] === 'approved') {
            $actorName = 'Unknown';
            if (!empty($step['acted_by'])) {
                $stmtActor = $pdo->prepare("SELECT name FROM user WHERE id = ?");
                $stmtActor->execute([$step['acted_by']]);
                $actor     = $stmtActor->fetch(PDO::FETCH_ASSOC);
                $actorName = $actor['name'] ?? 'Unknown';
            }
            $comments[] = ['role' => $step['Code'], 'approved_by' => $actorName, 'comment' => $step['comment'], 'created_at' => $step['acted_at'] ?? ''];
        }
    }
}

/* COMPUTE WORKING DAYS FOR THE MONTH */
$days = [];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dayOfWeek = date('N', mktime(0,0,0,$month,$d,$year));
    if ($dayOfWeek < 6) {
        $days[] = $d;
    }
}

/* STAT TOTALS */
$totalWorking = 0; $totalExtra = 0;
foreach ($attendanceRows as $r) {
    $ad = json_decode($r['attendance_json'], true) ?? [];
    foreach ($ad as $entry) {
        if (($entry['status'] ?? '') === 'P')  $totalWorking++;
        if (($entry['status'] ?? '') === 'PP') $totalExtra++;
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #0f766e; --primary-dark: #0d5f58; --sidebar-width: 270px; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; overflow-x: hidden; }
        .dashboard-layout { display: grid; grid-template-columns: var(--sidebar-width) 1fr; min-height: 100vh; }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes navItemReveal {
            from { opacity: 0; transform: translateX(-14px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes cardPop {
            from { opacity: 0; transform: translateY(14px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes rowSlideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes popIn {
            0%   { transform: scale(0); opacity: 0; }
            70%  { transform: scale(1.08); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes dotPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
            50%       { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239,68,68,0); }
        }
        @keyframes stepFadeIn {
            from { opacity: 0; transform: translateY(10px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes arrowFadeIn {
            from { opacity: 0; transform: scaleX(0); }
            to   { opacity: 1; transform: scaleX(1); }
        }
        @keyframes workflowSlide {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* SIDEBAR */
        .sidebar {
            background: linear-gradient(180deg,#0f766e 0%,#0a5c55 100%);
            color: white; padding: 0;
            box-shadow: 4px 0 24px rgba(13,95,88,0.35);
            position: sticky; top: 0; height: 100vh; overflow-y: auto;
            z-index: 100; transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            display: flex; flex-direction: column;
            animation: slideInLeft 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .sidebar-close {
            display: none; position: absolute; top: 1rem; right: 1rem;
            background: rgba(255,255,255,0.12); border: none; color: white;
            width: 32px; height: 32px; border-radius: 8px; cursor: pointer;
            font-size: 1rem; align-items: center; justify-content: center; z-index: 2;
            transition: background 0.2s, transform 0.2s;
        }
        .sidebar-close:hover { background: rgba(255,255,255,0.22); transform: rotate(90deg); }

        .sidebar-logo {
            padding: 1.4rem 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
        }
        .mcl-logo-img {
            max-width: 155px; height: auto; display: block;
            background: white; padding: 10px 14px; border-radius: 10px;
            animation: fadeUp 0.5s 0.15s ease both;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .mcl-logo-img:hover { transform: scale(1.04); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }

        .sidebar-nav { list-style: none; padding: 1rem 0; flex: 1; }
        .sidebar-nav li {
            margin: 0.25rem 1rem;
            opacity: 0;
            animation: navItemReveal 0.4s ease forwards;
        }
        .sidebar-nav li:nth-child(1) { animation-delay: 0.25s; }
        .sidebar-nav li:nth-child(2) { animation-delay: 0.35s; }
        .sidebar-nav li:nth-child(3) { animation-delay: 0.45s; }

        .nav-link {
            display: flex; align-items: center; gap: 0.9rem;
            padding: 0.85rem 1.1rem;
            color: rgba(255,255,255,0.88); text-decoration: none;
            border-radius: 12px;
            transition: background 0.2s, color 0.2s, transform 0.2s, box-shadow 0.2s;
            font-weight: 500; font-size: 0.95rem;
        }
        .nav-link:hover  { background: rgba(255,255,255,0.15); color: #fff; transform: translateX(5px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .nav-link.active { background: rgba(255,255,255,0.22); color: #fff; font-weight: 600; }
        .nav-link i {
            font-size: 1.05rem; width: 22px; text-align: center; opacity: 0.9;
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        .nav-link:hover i  { transform: scale(1.25) rotate(-6deg); opacity: 1; }
        .nav-link.active i { transform: scale(1.15); opacity: 1; }
        .logout-link { color: rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background: rgba(239,68,68,0.18) !important; color: #fca5a5 !important; }
        .logout-link:hover i { color: #fca5a5; transform: scale(1.2) translateX(2px) !important; }

        /* MAIN */
        .main-content {
            padding: 2rem; overflow-y: auto;
            display: flex; flex-direction: column; gap: 1.5rem; min-width: 0;
            animation: fadeIn 0.4s 0.2s ease both;
        }

        /* TOPBAR */
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            background: white; border-radius: 14px; padding: 1rem 1.5rem;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            opacity: 0;
            animation: fadeDown 0.5s 0.3s ease forwards;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .topbar:hover { box-shadow: 0 8px 24px rgba(15,118,110,0.18); border-color: rgba(16,185,129,0.3); }
        .hamburger-btn {
            display: none; background: #f3f4f6; border: 1.5px solid #e5e7eb;
            border-radius: 8px; width: 38px; height: 38px;
            align-items: center; justify-content: center;
            cursor: pointer; color: #0f766e; font-size: 1rem;
            transition: background 0.2s, transform 0.2s;
        }
        .hamburger-btn:hover { background: #e5e7eb; transform: scale(1.05); }
        .topbar h2 { font-size: 1.4rem; font-weight: 700; color: #1f2937; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .header-icon {
            width: 40px; height: 40px; border-radius: 50%; background: #f3f4f6;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative; color: #6b7280; font-size: 1rem; border: 1px solid #e5e7eb;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .header-icon:hover { background: #e5e7eb; transform: scale(1.1); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header-icon .badge {
            position: absolute; top: -4px; right: -4px;
            background: #ef4444; color: white; font-size: 0.65rem;
            width: 18px; height: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-weight: 700;
            animation: dotPulse 2s ease-in-out infinite;
        }
        .user-icon {
            width: 40px; height: 40px; border-radius: 50%; background: #0f766e;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .user-icon:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(15,118,110,0.35); }
        .user-icon svg { width: 20px; height: 20px; stroke: white; }

        /* ALREADY APPROVED BANNER */
        .already-approved-banner {
            background: linear-gradient(135deg,#fef3c7,#fde68a);
            border: 2px solid #f59e0b; border-radius: 14px;
            padding: 1.25rem 1.75rem;
            display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 2px 12px rgba(245,158,11,0.2);
            margin-bottom: 0.5rem;
            opacity: 0;
            animation: fadeUp 0.4s 0.5s ease forwards;
        }
        .already-approved-banner i { font-size: 1.5rem; color: #d97706; }
        .already-approved-banner .msg-title { font-weight: 700; color: #92400e; font-size: 1rem; }
        .already-approved-banner .msg-sub   { font-size: 0.85rem; color: #b45309; margin-top: 0.2rem; }

        /* ATTENDANCE HEADER */
        .attendance-header {
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.4s 0.35s ease forwards;
        }
        .attendance-header h1 { font-size: 1.85rem; font-weight: 800; color: #1f2937; margin-bottom: 0.35rem; }
        .attendance-header p  { font-size: 0.95rem; color: #6b7280; }

        /* WORKFLOW SECTION */
        .workflow-section {
            background: white; border-radius: 14px; padding: 1.5rem 2rem;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            display: flex; flex-direction: column; align-items: center; gap: 1.2rem;
            opacity: 0;
            animation: workflowSlide 0.45s 0.45s ease forwards;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .workflow-section:hover { box-shadow: 0 8px 24px rgba(15,118,110,0.18); border-color: rgba(16,185,129,0.3); }

        .workflow-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 600; color: #374151; }
        .workflow-title i { color: #0f766e; }
        .workflow-meta { font-size: 0.82rem; color: #9ca3af; text-align: center; }
        .workflow-meta strong { color: #1f2937; font-weight: 700; }

        .workflow-steps { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 0; }

        .workflow-step {
            display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
            opacity: 0;
            animation: stepFadeIn 0.35s ease forwards;
        }
        .workflow-step:nth-child(1) { animation-delay: 0.60s; }
        .workflow-step:nth-child(3) { animation-delay: 0.72s; }
        .workflow-step:nth-child(5) { animation-delay: 0.84s; }
        .workflow-step:nth-child(7) { animation-delay: 0.96s; }
        .workflow-step:nth-child(9) { animation-delay: 1.08s; }

        .workflow-arrow {
            display: flex; align-items: center;
            padding: 0 0.6rem; color: #9ca3af; font-size: 1rem; margin-bottom: 1.6rem;
            opacity: 0;
            animation: arrowFadeIn 0.3s ease forwards;
        }
        .workflow-arrow:nth-child(2) { animation-delay: 0.66s; }
        .workflow-arrow:nth-child(4) { animation-delay: 0.78s; }
        .workflow-arrow:nth-child(6) { animation-delay: 0.90s; }
        .workflow-arrow:nth-child(8) { animation-delay: 1.02s; }

        .step-card {
            width: 95px; height: 115px; border-radius: 14px;
            border: 2px solid #e5e7eb; background: #f9fafb;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 0.35rem; position: relative; padding-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .step-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.12); }

        .step-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            background: #d1d5db;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s;
        }
        .step-card:hover .step-avatar { transform: scale(1.08); }
        .step-avatar i { font-size: 1.6rem; color: white; }
        .step-label { font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; }
        .step-sub   { font-size: 0.62rem; color: #9ca3af; margin-top: -0.2rem; }

        .step-check {
            position: absolute; top: -11px; left: 50%; transform: translateX(-50%);
            width: 22px; height: 22px; border-radius: 50%;
            background: #16a34a; color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem; font-weight: 700;
            border: 2px solid white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
            animation: popIn 0.4s cubic-bezier(0.22,1,0.36,1) both;
        }

        .workflow-step.approved .step-card { border-color: #86efac; background: #f0fdf4; }
        .workflow-step.approved .step-avatar { background: #16a34a; }
        .workflow-step.approved .step-label { color: #15803d; }
        .workflow-step.current .step-card { border-color: #fdba74; background: #fff7ed; box-shadow: 0 0 0 3px rgba(251,146,60,0.2); }
        .workflow-step.current .step-avatar { background: #f97316; }
        .workflow-step.current .step-label { color: #c2410c; }
        .workflow-step.pending .step-card { border-color: #e5e7eb; background: #f9fafb; }
        .workflow-step.pending .step-avatar { background: #9ca3af; }
        .workflow-step.pending .step-label { color: #9ca3af; }

        .btn-approval {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1.4rem; border-radius: 8px;
            background: #f0fdf4; color: #15803d;
            border: 1.5px solid #86efac;
            font-size: 0.84rem; font-weight: 600; cursor: pointer;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .btn-approval:hover { background: #dcfce7; border-color: #4ade80; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22,163,74,0.2); }

        .approval-comments {
            display: none; width: 100%; margin-top: 0.5rem;
            border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;
        }
        .approval-comments.open { display: block; animation: fadeUp 0.3s ease; }

        .comment-item {
            padding: 0.9rem 1.25rem; border-bottom: 1px solid #f0f0f0; background: white;
            transition: background 0.15s;
        }
        .comment-item:last-child { border-bottom: none; }
        .comment-item:hover { background: #f9fafb; }
        .comment-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem; }
        .comment-role { font-size: 0.85rem; font-weight: 700; color: #1f2937; }
        .comment-role span { color: #0f766e; font-weight: 600; margin-left: 0.3rem; }
        .comment-time { display: flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; color: #9ca3af; }
        .comment-text {
            font-size: 0.84rem; color: #4b5563; background: #f9fafb;
            border-radius: 8px; padding: 0.5rem 0.85rem;
            border-left: 3px solid #86efac; font-style: italic;
        }

        /* STAT CARDS */
        .attendance-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; margin-top: 1rem; }
        .attendance-stat-card {
            background: linear-gradient(135deg,var(--card-start),var(--card-end));
            border: 1px solid var(--card-border); border-radius: 14px; padding: 1.75rem;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            opacity: 0;
            animation: cardPop 0.4s ease forwards;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .attendance-stat-card:nth-child(1) { animation-delay: 0.65s; }
        .attendance-stat-card:nth-child(2) { animation-delay: 0.75s; }
        .attendance-stat-card:nth-child(3) { animation-delay: 0.85s; }
        .attendance-stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.18); }
        .attendance-stat-card.blue  { --card-start:#0ea5e9; --card-end:#0284c7; --card-border:#0369a1; }
        .attendance-stat-card.amber { --card-start:#f59e0b; --card-end:#d97706; --card-border:#b45309; }
        .attendance-stat-card.green { --card-start:#10b981; --card-end:#059669; --card-border:#047857; }
        .stat-card-content { color: white; }
        .stat-card-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; margin-bottom: 0.5rem; }
        .stat-card-value { font-size: 2.75rem; font-weight: 800; line-height: 1; }
        .stat-card-icon  {
            font-size: 2.75rem; color: rgba(255,255,255,0.3);
            transition: transform 0.2s, color 0.2s;
        }
        .attendance-stat-card:hover .stat-card-icon { transform: scale(1.1) rotate(-5deg); color: rgba(255,255,255,0.5); }

        /* CARD */
        .card {
    background: white; border-radius: 14px; padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
    border: 1px solid rgba(15,118,110,0.15);
    animation: fadeUp 0.4s 0.45s ease both;
    transition: box-shadow 0.2s, border-color 0.2s;
    margin-top: 1rem;  /* ← add this */
}
        .card:hover { box-shadow: 0 8px 28px rgba(15,118,110,0.2); border-color: rgba(16,185,129,0.3); }

        .table-controls { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
        .table-controls-left { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: #6b7280; }
        .table-controls-left select {
            padding: 0.5rem 0.75rem; border: 1.5px solid #e5e7eb; border-radius: 8px;
            font-size: 0.9rem; outline: none; transition: border-color 0.2s;
        }
        .table-controls-left select:focus { border-color: #0f766e; }

        .export-buttons { display: flex; gap: 0.5rem; }
        .btn-export {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.5rem 1rem; border-radius: 8px;
            font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none;
            transition: all 0.2s;
            position: relative; overflow: hidden;
        }
        .btn-export::after {
            content:''; position:absolute; inset:0;
            background:rgba(255,255,255,0.2); transform:scale(0);
            border-radius:inherit; transition:transform 0.3s ease;
        }
        .btn-export:active::after { transform:scale(2.5); opacity:0; transition:none; }
        .btn-excel { background: #10b981; color: white; }
        .btn-excel:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(16,185,129,0.3); }
        .btn-pdf   { background: #ef4444; color: white; }
        .btn-pdf:hover   { background: #dc2626; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(239,68,68,0.3); }

        .search-input {
            padding: 0.6rem 1rem 0.6rem 2.75rem; border: 1.5px solid #e5e7eb;
            border-radius: 8px; font-size: 0.9rem; outline: none; width: 240px;
            background: white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 1rem center;
            background-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s, width 0.3s;
        }
        .search-input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); width: 280px; }

        /* TABLE */
        .attendance-table-wrapper {
            overflow-x: auto; overflow-y: visible; border-radius: 12px; border: 1px solid #e5e7eb;
            -webkit-overflow-scrolling: touch; scrollbar-width: thin; scrollbar-color: #0f766e #f0f0f0;
        }
        .attendance-table-wrapper::-webkit-scrollbar { height: 6px; }
        .attendance-table-wrapper::-webkit-scrollbar-track { background: #f0f0f0; }
        .attendance-table-wrapper::-webkit-scrollbar-thumb { background: #0f766e; border-radius: 3px; }

        .attendance-table { width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.85rem; background: white; }
        .attendance-table thead th { background: linear-gradient(135deg,#0f766e,#0d5f58); color: white; font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.3px; padding: 0.875rem 0.45rem; text-align: center; position: sticky; top: 0; z-index: 10; white-space: nowrap; }
        .attendance-table thead th:first-child { text-align: left; padding-left: 1rem; }
        .attendance-table thead th.summary-col { background: linear-gradient(135deg,#0ea5e9,#0284c7); font-weight: 800; font-size: 0.8rem; }
        .attendance-table thead th.extra-col   { background: linear-gradient(135deg,#f59e0b,#d97706); }
        .attendance-table thead th.total-col   { background: linear-gradient(135deg,#10b981,#059669); }

        /* Sticky left */
        .attendance-table thead th:nth-child(1),.attendance-table tbody td:nth-child(1) { position:sticky; left:0;    z-index:11; min-width:48px;  width:48px; }
        .attendance-table thead th:nth-child(2),.attendance-table tbody td:nth-child(2) { position:sticky; left:48px;  z-index:11; min-width:90px;  width:90px; }
        .attendance-table thead th:nth-child(3),.attendance-table tbody td:nth-child(3) { position:sticky; left:138px; z-index:11; min-width:140px; width:140px; text-align:left !important; }
        .attendance-table thead th:nth-child(4),.attendance-table tbody td:nth-child(4) { position:sticky; left:278px; z-index:11; min-width:90px;  width:90px; border-right:2px solid rgba(255,255,255,0.3) !important; }
        .attendance-table thead th:nth-child(1),.attendance-table thead th:nth-child(2),.attendance-table thead th:nth-child(3),.attendance-table thead th:nth-child(4) { background:linear-gradient(135deg,#0f766e,#0d5f58); z-index:12; }
        .attendance-table tbody td:nth-child(1),.attendance-table tbody td:nth-child(2),.attendance-table tbody td:nth-child(3),.attendance-table tbody td:nth-child(4) { background:#fff; }
        .attendance-table tbody tr:nth-child(even) td:nth-child(1),.attendance-table tbody tr:nth-child(even) td:nth-child(2),.attendance-table tbody tr:nth-child(even) td:nth-child(3),.attendance-table tbody tr:nth-child(even) td:nth-child(4) { background:#fafafa; }
        .attendance-table tbody tr:hover td:nth-child(1),.attendance-table tbody tr:hover td:nth-child(2),.attendance-table tbody tr:hover td:nth-child(3),.attendance-table tbody tr:hover td:nth-child(4) { background:#f0fdf9; }
        .attendance-table tbody td:nth-child(4) { border-right:2px solid #e5e7eb !important; }

        /* Sticky right */
        .attendance-table thead th.col-working,.attendance-table tbody td.col-working { position:sticky; right:104px; z-index:11; min-width:72px; width:72px; }
        .attendance-table thead th.col-extra,  .attendance-table tbody td.col-extra   { position:sticky; right:52px;  z-index:11; min-width:52px; width:52px; }
        .attendance-table thead th.col-total,  .attendance-table tbody td.col-total   { position:sticky; right:0;     z-index:11; min-width:52px; width:52px; }
        .attendance-table thead th.col-working { background:linear-gradient(135deg,#0ea5e9,#0284c7); z-index:12; }
        .attendance-table thead th.col-extra   { background:linear-gradient(135deg,#f59e0b,#d97706); z-index:12; }
        .attendance-table thead th.col-total   { background:linear-gradient(135deg,#10b981,#059669); z-index:12; }
        .attendance-table tbody td.col-working { background:#dbeafe; border-left:2px solid #bfdbfe !important; }
        .attendance-table tbody td.col-extra   { background:#fef3c7; }
        .attendance-table tbody td.col-total   { background:#d1fae5; }
        .attendance-table tbody tr:nth-child(even) td.col-working { background:#bfdbfe; }
        .attendance-table tbody tr:nth-child(even) td.col-extra   { background:#fde68a; }
        .attendance-table tbody tr:nth-child(even) td.col-total   { background:#a7f3d0; }
        .attendance-table tbody tr:hover td.col-working,.attendance-table tbody tr:hover td.col-extra,.attendance-table tbody tr:hover td.col-total { filter:brightness(0.96); }

        .attendance-table thead th.day-col,.attendance-table tbody td.day-col { min-width:36px; width:36px; }

        /* Staggered row entrance */
        .attendance-table tbody tr {
            transition: background 0.2s, transform 0.15s;
            opacity: 0;
            animation: rowSlideIn 0.3s ease forwards;
        }
        .attendance-table tbody tr:nth-child(1)  { animation-delay: 1.00s; }
        .attendance-table tbody tr:nth-child(2)  { animation-delay: 1.07s; }
        .attendance-table tbody tr:nth-child(3)  { animation-delay: 1.14s; }
        .attendance-table tbody tr:nth-child(4)  { animation-delay: 1.21s; }
        .attendance-table tbody tr:nth-child(5)  { animation-delay: 1.28s; }
        .attendance-table tbody tr:nth-child(6)  { animation-delay: 1.35s; }
        .attendance-table tbody tr:nth-child(7)  { animation-delay: 1.42s; }
        .attendance-table tbody tr:nth-child(8)  { animation-delay: 1.49s; }
        .attendance-table tbody tr:nth-child(9)  { animation-delay: 1.56s; }
        .attendance-table tbody tr:nth-child(10) { animation-delay: 1.63s; }
        .attendance-table tbody tr:nth-child(n+11) { animation-delay: 1.68s; }

        .attendance-table tbody tr:hover { background: #f0fdf9; transform: translateX(2px); }
        .attendance-table tbody tr:nth-child(even) { background:#fafafa; }
        .attendance-table tbody td { padding:0.7rem 0.45rem; border-bottom:1px solid #f0f0f0; text-align:center; vertical-align:middle; }
        .attendance-table tbody tr:last-child td { border-bottom:none; }
        .attendance-table tbody td:first-child,.attendance-table tbody td:nth-child(2),.attendance-table tbody td:nth-child(3),.attendance-table tbody td:nth-child(4) { text-align:left; font-size:0.82rem; color:#1f2937; font-weight:500; padding-left:1rem; white-space:nowrap; }
        .attendance-table tbody td:nth-child(3),.attendance-table tbody td:nth-child(4) { color:#6b7280; font-weight:400; font-size:0.8rem; }

        .status-indicator {
            display:inline-flex; align-items:center; justify-content:center;
            width:30px; height:30px; border-radius:7px;
            font-size:0.72rem; font-weight:700; cursor:default;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .status-indicator:hover { transform:scale(1.18); box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
        .status-present { background:#d1fae5; color:#059669; }
        .status-pp      { background:#bfdbfe; color:#1d4ed8; font-size:0.66rem; letter-spacing:-0.5px; }
        .status-leave   { background:#fef3c7; color:#d97706; }
        .status-absent  { background:#fee2e2; color:#dc2626; }

        .total-cell { font-weight:700; color:#1f2937; background:#f3f4f6; font-size:0.9rem; }
        .total-cell.working-cell { background:#dbeafe; color:#0369a1; }
        .total-cell.extra-cell   { background:#fef3c7; color:#b45309; }
        .total-cell.total-value  { background:#d1fae5; color:#059669; font-weight:800; font-size:0.95rem; }

        /* LEGEND */
        .legend {
            display:flex; align-items:center; justify-content:center; gap:2rem;
            margin-top:1.5rem; padding:1.25rem; background:#f9fafb; border-radius:10px; flex-wrap:wrap;
            opacity: 0;
            animation: fadeUp 0.4s 1.3s ease forwards;
        }
        .legend-item { display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#4b5563; font-weight:500; }
        .legend-box  {
            width:32px; height:32px; border-radius:6px;
            display:flex; align-items:center; justify-content:center;
            font-size:0.75rem; font-weight:700;
            transition: transform 0.15s;
        }
        .legend-box:hover { transform: scale(1.15); }

        /* PAGINATION */
        .pagination {
            display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-top:1.5rem;
            opacity: 0;
            animation: fadeUp 0.4s 1.35s ease forwards;
        }
        .page-btn {
            width:38px; height:38px; border-radius:8px; border:1.5px solid #e5e7eb; background:white;
            cursor:pointer; font-size:0.9rem; font-weight:600; color:#6b7280;
            display:flex; align-items:center; justify-content:center;
            transition: all 0.2s;
        }
        .page-btn:hover  { border-color:#0f766e; color:#0f766e; transform: translateY(-2px); }
        .page-btn.active { background:#0f766e; color:white; border-color:#0f766e; }

        /* FORWARD SECTION */
        .forward-section {
            margin-top:2rem; padding-top:2rem; border-top:2px solid #e5e7eb;
            opacity: 0;
            animation: fadeUp 0.4s 1.4s ease forwards;
        }
        .form-group { margin-bottom:1.25rem; }
        .form-label { display:block; font-size:0.9rem; font-weight:600; color:#1f2937; margin-bottom:0.5rem; }
        .form-label .required { color:#ef4444; margin-left:0.25rem; }
        .form-control {
            width:100%; padding:0.75rem 1rem; border:1.5px solid #e5e7eb; border-radius:10px;
            font-size:0.9rem; font-family:inherit; color:#1f2937; background:white;
            transition: border-color 0.2s, box-shadow 0.2s; outline:none; resize:vertical;
        }
        .form-control:focus { border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,0.1); }

        .btn {
            display:inline-flex; align-items:center; gap:0.5rem;
            padding:0.75rem 1.5rem; border-radius:10px;
            font-size:0.9rem; font-weight:600; cursor:pointer;
            transition: all 0.2s; text-decoration:none; border:none; font-family:inherit;
            position: relative; overflow: hidden;
        }
        .btn::after {
            content:''; position:absolute; inset:0;
            background:rgba(255,255,255,0.2); transform:scale(0);
            border-radius:inherit; transition:transform 0.3s ease;
        }
        .btn:active::after { transform:scale(2.5); opacity:0; transition:none; }
        .btn-primary { background:#0f766e; color:white; }
        .btn-primary:hover { background:#0d5f58; transform:translateY(-2px); box-shadow:0 6px 16px rgba(15,118,110,0.35); }
        .forward-btn-group { display:flex; justify-content:center; margin-top:1.5rem; }

        /* SUCCESS */
        .success-page { display:none; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; padding:2rem 0; }
        .success-page.visible { display:flex; }
        .success-banner {
            width:100%;
            background:linear-gradient(135deg,#22c55e 0%,#16a34a 60%,#15803d 100%);
            border-radius:18px; padding:3.5rem 2rem;
            display:flex; flex-direction:column; align-items:center; gap:1.25rem;
            box-shadow:0 8px 32px rgba(34,197,94,0.3);
            animation: fadeUp 0.4s ease both;
        }
        .success-check {
            width:74px; height:74px; border-radius:50%; background:white;
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 4px 20px rgba(0,0,0,0.15);
            animation: popIn 0.5s cubic-bezier(0.175,0.885,0.32,1.275) both;
        }
        .success-check i { font-size:2rem; color:#16a34a; }
        .success-title { font-size:1.8rem; font-weight:800; color:white; text-align:center; animation: fadeUp 0.45s 0.2s ease both; }
        .success-card {
            background:white; border-radius:14px; padding:1.5rem 2.25rem;
            min-width:340px; max-width:500px; width:90%; text-align:center;
            box-shadow:0 4px 20px rgba(0,0,0,0.12); animation: fadeUp 0.45s 0.35s ease both;
        }
        .success-card-title { display:flex; align-items:center; justify-content:center; gap:0.5rem; font-size:0.88rem; font-weight:700; color:#1f2937; text-transform:uppercase; letter-spacing:0.6px; margin-bottom:0.6rem; }
        .success-card-title i { color:#16a34a; }
        .success-card-desc  { font-size:0.88rem; color:#6b7280; margin-bottom:0.85rem; line-height:1.55; }
        .success-card-date  { font-size:0.87rem; font-weight:600; color:#1f2937; }
        .success-card-date span { color:#6b7280; font-weight:400; }

        /* OVERLAY */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); animation:none; }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .topbar { padding:0.875rem 1rem; flex-wrap:wrap; gap:0.75rem; }
            .topbar h2 { font-size:1.1rem; order:2; flex-basis:100%; text-align:center; }
            .main-content { padding:1rem; gap:1rem; }
            .attendance-stats { grid-template-columns:1fr; gap:0.75rem; }
            .workflow-steps { gap:0; }
            .table-controls { flex-direction:column; align-items:stretch; }
            .search-input { width:100%; }
            .export-buttons { width:100%; flex-wrap:wrap; }
            .btn-export { flex:1; }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"  class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="gm_monthly.php" class="nav-link active"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="../logout.php"  class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
            <h2>Security Billing Management Portal</h2>
            <div class="topbar-right">
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <a href="profile.php" title="My Profile" style="text-decoration:none;">
                    <div class="user-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
                        </svg>
                    </div>
                </a>
            </div>
        </header>

        <!-- ATTENDANCE CONTENT -->
        <div id="attendanceContent" <?= $approvalSuccess ? 'style="display:none"' : '' ?>>

            <div class="attendance-header">
                <h1>MONTHLY ATTENDANCE REPORT</h1>
                <p>Attendance Period: <?= date('F Y', strtotime('first day of last month')) ?> &nbsp;|&nbsp; Working Days: <?= count($days) ?> (Weekends Excluded) &nbsp;|&nbsp; Site: <strong><?= htmlspecialchars($siteCode) ?></strong></p>
            </div>

            <?php if ($alreadyApproved): ?>
            <div class="already-approved-banner">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <div class="msg-title">You have already approved this report.</div>
                    <div class="msg-sub">Current workflow step: <strong><?= htmlspecialchars($currentStep) ?></strong>. Waiting for next officer to act.</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- APPROVAL WORKFLOW FLOWCHART -->
            <div class="workflow-section">
                <div class="workflow-title">
                    <i class="fa-solid fa-sitemap"></i>
                    Monthly Attendance Report Approval Workflow
                </div>
                <div class="workflow-meta">
                    Current: <strong><?= htmlspecialchars($currentStep) ?></strong>
                    &nbsp;|&nbsp;
                    Last Approved By: <strong>
                        <?php
                        $lastApprovedCode = '—';
                        if ($workflow) {
                            foreach (array_reverse($workflow['steps']) as $s) {
                                if ($s['status'] === 'approved') { $lastApprovedCode = $s['Code']; break; }
                            }
                        }
                        echo htmlspecialchars($lastApprovedCode);
                        ?>
                    </strong>
                </div>

                <div class="workflow-steps">
                    <?php
                    $flowSteps = [
                        ['code' => 'ASO',   'sub' => 'Officer'],
                        ['code' => 'APM',   'sub' => 'Officer'],
                        ['code' => 'GM',    'sub' => 'Officer'],
                        ['code' => 'HQSO',  'sub' => 'Officer'],
                        ['code' => 'SDHOD', 'sub' => 'Officer'],
                    ];
                    foreach ($flowSteps as $i => $fs):
                        $status = $stepStatuses[$fs['code']] ?? 'pending';
                    ?>
                        <div class="workflow-step <?= $status ?>">
                            <div class="step-card">
                                <?php if ($status === 'approved'): ?>
                                    <span class="step-check"><i class="fa-solid fa-check"></i></span>
                                <?php endif; ?>
                                <div class="step-avatar"><i class="fa-solid fa-user"></i></div>
                                <span class="step-label"><?= $fs['code'] ?></span>
                                <span class="step-sub"><?= $fs['sub'] ?></span>
                            </div>
                        </div>
                        <?php if ($i < count($flowSteps) - 1): ?>
                        <div class="workflow-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <button class="btn-approval" id="toggleCommentsBtn" onclick="toggleComments()">
                    <i class="fa-solid fa-comments"></i> View Approval Comments
                </button>

                <div id="approvalComments" class="approval-comments">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-role"><?= htmlspecialchars($c['role']) ?> : <span><?= htmlspecialchars($c['approved_by']) ?></span></div>
                                    <div class="comment-time"><i class="fa-regular fa-clock"></i><?= htmlspecialchars($c['created_at']) ?></div>
                                </div>
                                <div class="comment-text">"<?= htmlspecialchars($c['comment']) ?>"</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center;color:#999;padding:1rem;">No comments yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="attendance-stats">
                <div class="attendance-stat-card blue">
                    <div class="stat-card-content"><div class="stat-card-label">Working Days</div><div class="stat-card-value"><?= $totalWorking ?></div></div>
                    <div class="stat-card-icon"><i class="fa-solid fa-briefcase"></i></div>
                </div>
                <div class="attendance-stat-card amber">
                    <div class="stat-card-content"><div class="stat-card-label">Extra Duty</div><div class="stat-card-value"><?= $totalExtra ?></div></div>
                    <div class="stat-card-icon"><i class="fa-solid fa-clock"></i></div>
                </div>
                <div class="attendance-stat-card green">
                    <div class="stat-card-content"><div class="stat-card-label">Total Duty Days</div><div class="stat-card-value"><?= $totalDuty ?></div></div>
                    <div class="stat-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="card">
                <div class="table-controls">
                    <div class="table-controls-left">
                        <label>Show</label>
                        <select><option>10</option><option>25</option><option>50</option><option>100</option></select>
                        <label>entries</label>
                    </div>
                    <!-- <div class="export-buttons">
                        <button class="btn-export btn-excel"><i class="fa-solid fa-file-excel"></i> Excel</button>
                        <button class="btn-export btn-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                    </div> -->
                    <input type="text" class="search-input" placeholder="Search">
                </div>

                <div class="attendance-table-wrapper">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>S.N.</th><th>EMP CODE</th><th>NAME</th><th>RANK</th>
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
                            $working = 0; $extra = 0;
                            echo "<tr>";
                            echo "<td>" . $sn++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['esic_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['rank']) . "</td>";
                            foreach ($days as $day) {
                                $dateKey = $year."-".str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($day,2,'0',STR_PAD_LEFT);
                                if (isset($attendanceData[$dateKey])) {
                                    $status = $attendanceData[$dateKey]['status'];
                                    if ($status === 'P')  $working++;
                                    if ($status === 'PP') $extra++;
                                    $class = match($status) { 'P'=>'status-present','PP'=>'status-pp','L'=>'status-leave','A'=>'status-absent',default=>'' };
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
                    <div class="legend-item"><div class="legend-box status-present">P</div><span>Present</span></div>
                    <div class="legend-item"><div class="legend-box status-pp">PP</div><span>Double Duty</span></div>
                    <div class="legend-item"><div class="legend-box status-leave">L</div><span>Leave</span></div>
                    <div class="legend-item"><div class="legend-box status-absent">A</div><span>Absent</span></div>
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
                            <label class="form-label">Add Approval Comment <span class="required">*</span></label>
                            <textarea class="form-control" name="comment" id="commentBox" rows="5"
                                placeholder="Add your GM approval comment..." required></textarea>
                        </div>
                        <div class="forward-btn-group">
                            <button type="submit" name="approve_report" class="btn btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> APPROVE & FORWARD TO HQSO
                            </button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <div class="forward-section" style="text-align:center;color:#6b7280;font-size:0.9rem;padding:1rem 0;">
                    <i class="fa-solid fa-lock" style="margin-right:0.4rem;color:#d97706;"></i>
                    Approval form is locked. Report has been forwarded to <strong><?= htmlspecialchars($currentStep) ?></strong>.
                </div>
                <?php endif; ?>

            </div><!-- /.card -->
        </div><!-- /#attendanceContent -->

        <!-- SUCCESS PAGE -->
        <div class="success-page <?= $approvalSuccess ? 'visible' : '' ?>" id="successPage">
            <div class="success-banner">
                <div class="success-check"><i class="fa-solid fa-check"></i></div>
                <div class="success-title">Report Approved &amp; Forwarded to HQSO!</div>
                <div class="success-card">
                    <div class="success-card-title"><i class="fa-solid fa-user-check"></i> GM APPROVED</div>
                    <div class="success-card-desc">You have successfully reviewed and forwarded this attendance report to HQSO for the next stage of approval.</div>
                    <div class="success-card-date">Processed On: <span><?= $approvalSuccess ? date('Y-m-d H:i:s') : '' ?></span></div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    document.getElementById('hamburgerBtn').addEventListener('click', () => { sidebar.classList.add('open');    overlay.classList.add('active'); });
    document.getElementById('sidebarClose').addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

    function toggleComments() {
        const panel = document.getElementById('approvalComments');
        const btn   = document.getElementById('toggleCommentsBtn');
        const isOpen = panel.classList.contains('open');
        panel.classList.toggle('open');
        btn.innerHTML = isOpen
            ? '<i class="fa-solid fa-comments"></i> View Approval Comments'
            : '<i class="fa-solid fa-comments"></i> Hide Approval Comments';
    }

    const commentBox = document.getElementById('commentBox');
    if (commentBox) {
        commentBox.addEventListener('input', function () {
            this.style.borderColor = '';
            this.style.boxShadow   = '';
        });
    }
</script>
</body>
</html>