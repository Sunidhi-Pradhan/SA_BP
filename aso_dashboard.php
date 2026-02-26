<?php
session_start();
require "config.php";

/*
|--------------------------------------------------
| LOGIN PROTECTION (ASO)
|--------------------------------------------------
*/
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role, site_code FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u || $u['role'] !== 'ASO') {
    die("Access denied");
}

$asoSiteCode = $u['site_code'];

/*
|--------------------------------------------------
| HANDLE APPROVAL AJAX REQUEST
|--------------------------------------------------
*/
if (isset($_POST['approve_records']) && isset($_POST['esic_numbers'])) {
    header('Content-Type: application/json');

    $esicNumbers = json_decode($_POST['esic_numbers'], true);
    $today = date('Y-m-d');

    if (!is_array($esicNumbers) || empty($esicNumbers)) {
        echo json_encode(['success' => false, 'message' => 'No records selected']);
        exit;
    }

    try {
        foreach ($esicNumbers as $esic) {
            $stmt = $pdo->prepare("
                SELECT a.attendance_json
                FROM attendance a
                INNER JOIN employee_master em ON em.esic_no = a.esic_no
                WHERE a.esic_no = ? AND em.site_code = ?
            ");
            $stmt->execute([$esic, $asoSiteCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) continue;
            $json = json_decode($row['attendance_json'], true);
            if (!is_array($json)) continue;
            if (!isset($json[$today])) continue;
            if (($json[$today]['approve_status'] ?? 0) == 1) continue;

            $json[$today]['approve_status'] = 1;
            $update = $pdo->prepare("UPDATE attendance SET attendance_json = ? WHERE esic_no = ?");
            $update->execute([json_encode($json, JSON_UNESCAPED_UNICODE), $esic]);
        }

        echo json_encode(['success' => true, 'message' => 'Selected records approved successfully']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

/*
|--------------------------------------------------
| FETCH TODAY'S ATTENDANCE (JSON BASED)
|--------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT a.esic_no, em.employee_name AS emp_name, a.attendance_json
    FROM attendance a
    INNER JOIN employee_master em ON em.esic_no = a.esic_no
    WHERE em.site_code = ?
");
$stmt->execute([$asoSiteCode]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attendanceList = [];
$selectedDate = $_GET['date'] ?? date('Y-m-d');

foreach ($records as $record) {
    $json = json_decode($record['attendance_json'], true);
    if (!is_array($json)) continue;
    if (!isset($json[$selectedDate])) continue;

    $entry = $json[$selectedDate];
    if (isset($entry['approve_status']) && $entry['approve_status'] == 1) continue;

    $statusRaw = strtoupper($entry['status'] ?? '');
    switch ($statusRaw) {
        case 'P':  $status = 'present';  break;
        case 'A':  $status = 'absent';   break;
        case 'L':  $status = 'leave';    break;
        case 'PP': $status = 'overtime'; break;
        default:   $status = 'unknown';
    }

    $attendanceList[] = [
        'esic_no'           => $record['esic_no'],
        'emp_name'          => $record['emp_name'] ?? 'N/A',
        'attendance_date'   => $selectedDate,
        'attendance_status' => $status
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary-color: #0f766e;
            --primary-dark: #0d5f58;
            --primary-light: #14b8a6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* =============================================
           KEYFRAME ANIMATIONS
        ============================================= */

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.88); }
            to   { opacity: 1; transform: scale(1); }
        }

        @keyframes popIn {
            0%   { opacity: 0; transform: scale(0.6) rotate(-8deg); }
            70%  { transform: scale(1.08) rotate(2deg); }
            100% { opacity: 1; transform: scale(1) rotate(0deg); }
        }

        @keyframes rowEntrance {
            from { opacity: 0; transform: translateX(-18px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.12); }
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
            50%       { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239,68,68,0); }
        }

        @keyframes shimmer {
            0%   { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to   { transform: translateX(0);     opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0);     opacity: 1; }
            to   { transform: translateX(400px); opacity: 0; }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0) scaleY(1); max-height: 80px; }
            to   { opacity: 0; transform: translateX(-40px) scaleY(0.3); max-height: 0; padding: 0; }
        }

        @keyframes navItemReveal {
            from { opacity: 0; transform: translateX(-20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes legendSlide {
            from { opacity: 0; transform: translateX(16px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-4px); }
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes checkmarkDraw {
            from { stroke-dashoffset: 50; opacity: 0; }
            to   { stroke-dashoffset: 0;  opacity: 1; }
        }

        /* =============================================
           LAYOUT
        ============================================= */

        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* =============================================
           SIDEBAR  — animated entry + nav stagger
        ============================================= */
        .sidebar {
            background: linear-gradient(160deg, #0f766e 0%, #0d5f58 60%, #095950 100%);
            background-size: 200% 200%;
            color: white;
            padding: 2rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            animation: slideInLeft 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }

        .sidebar-logo {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 2rem;
            animation: fadeDown 0.5s 0.1s ease both;
        }

        .sidebar-logo h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .sidebar-logo p {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        .sidebar-nav { list-style: none; }

        .sidebar-nav li {
            margin: 0.25rem 1rem;
            opacity: 0;
            animation: navItemReveal 0.4s ease forwards;
        }
        .sidebar-nav li:nth-child(1) { animation-delay: 0.25s; }
        .sidebar-nav li:nth-child(2) { animation-delay: 0.35s; }
        .sidebar-nav li:nth-child(3) { animation-delay: 0.45s; }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: rgba(255,255,255,0.88);
            text-decoration: none;
            border-radius: 0.75rem;
            transition: background 0.2s, transform 0.2s, color 0.2s, box-shadow 0.2s;
            font-weight: 500;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .sidebar-nav a.active {
            background: rgba(255,255,255,0.22);
            color: white;
        }

        .sidebar-nav i {
            font-size: 1.125rem;
            width: 24px;
            transition: transform 0.25s;
        }

        .sidebar-nav a:hover i { transform: scale(1.2) rotate(-5deg); }

        /* =============================================
           MAIN CONTENT
        ============================================= */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
            animation: fadeIn 0.4s 0.2s ease both;
        }

        /* =============================================
           HEADER
        ============================================= */
        .header {
            background: var(--bg-secondary);
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            opacity: 0;
            animation: fadeDown 0.5s 0.3s ease forwards;
            transition: box-shadow 0.3s;
        }

        .header:hover { box-shadow: 0 8px 24px rgba(15,118,110,0.15); }

        .header-left h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .header-left p { color: var(--text-secondary); font-size: 0.875rem; }

        .header-right { display: flex; align-items: center; gap: 1.5rem; }

        .header-icon {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: var(--bg-primary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .header-icon:hover {
            background: var(--primary-light);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 14px rgba(15,118,110,0.3);
        }

        .header-icon .badge {
            position: absolute;
            top: -4px; right: -4px;
            background: var(--danger-color);
            color: white;
            font-size: 0.625rem;
            width: 18px; height: 18px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600;
            animation: badgePulse 2s ease-in-out infinite;
        }

        .user-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 14px rgba(15,118,110,0.35);
        }

        /* =============================================
           TOAST NOTIFICATIONS
        ============================================= */
        .toast-container {
            position: fixed;
            top: 2rem; right: 2rem;
            z-index: 9999;
            display: flex; flex-direction: column;
            gap: 1rem;
        }

        .toast {
            min-width: 320px;
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            display: flex; align-items: center; gap: 1rem;
            animation: slideIn 0.35s cubic-bezier(0.22,1,0.36,1) both;
            border-left: 4px solid var(--success-color);
        }

        .toast.success  { border-left-color: var(--success-color); }
        .toast.error    { border-left-color: var(--danger-color); }
        .toast.warning  { border-left-color: var(--warning-color); }
        .toast.hiding   { animation: slideOut 0.3s ease-out forwards; }

        .toast-icon {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            animation: popIn 0.4s 0.1s cubic-bezier(0.22,1,0.36,1) both;
        }

        .toast.success .toast-icon { background: rgba(16,185,129,0.1); color: var(--success-color); }
        .toast.error   .toast-icon { background: rgba(239,68,68,0.1);  color: var(--danger-color); }
        .toast.warning .toast-icon { background: rgba(245,158,11,0.1); color: var(--warning-color); }

        .toast-content { flex: 1; }
        .toast-title   { font-weight: 600; margin-bottom: 0.25rem; color: var(--text-primary); }
        .toast-message { font-size: 0.875rem; color: var(--text-secondary); }

        .toast-close {
            width: 24px; height: 24px;
            border: none; background: transparent;
            color: var(--text-secondary);
            cursor: pointer; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s, transform 0.2s;
        }

        .toast-close:hover { background: var(--bg-primary); transform: rotate(90deg); }

        /* =============================================
           CONTENT GRID
        ============================================= */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 1.5rem;
            align-items: start;
        }

        /* =============================================
           TABLE CARD
        ============================================= */
        .table-card {
            background: var(--bg-secondary);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            opacity: 0;
            animation: fadeUp 0.5s 0.45s ease forwards;
            transition: box-shadow 0.3s;
        }

        .table-card:hover { box-shadow: 0 12px 32px rgba(15,118,110,0.14); }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; gap: 1rem;
        }

        .table-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            transition: color 0.2s;
        }

        .table-header h3:hover { color: var(--primary-color); }

        .table-actions { display: flex; gap: 1rem; flex-wrap: wrap; }

        /* =============================================
           BUTTONS
        ============================================= */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            display: inline-flex; align-items: center; gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Ripple effect on click */
        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.25);
            transform: scale(0);
            border-radius: inherit;
            transition: transform 0.3s ease;
        }

        .btn:active::after { transform: scale(2.5); opacity: 0; transition: none; }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15,118,110,0.35);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-primary:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover { background: var(--border-color); }

        /* =============================================
           FORM CONTROLS
        ============================================= */
        .select-box {
            padding: 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
        }

        .select-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(15,118,110,0.1);
        }

        .select-box:hover { transform: translateY(-1px); }

        .table-controls {
            padding: 1rem 2rem;
            background: var(--bg-primary);
            display: flex;
            justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .search-box {
            padding: 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            font-size: 0.875rem;
            width: 100%; max-width: 300px;
            transition: border-color 0.2s, box-shadow 0.2s, width 0.3s;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(15,118,110,0.1);
            max-width: 340px;
        }

        /* =============================================
           DATA TABLE  — staggered row entrance
        ============================================= */
        .data-table { width: 100%; border-collapse: collapse; }

        .data-table thead {
            background: var(--bg-primary);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.2s;
        }

        .data-table th:hover { color: var(--primary-color); }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9375rem;
            transition: background 0.2s;
        }

        .data-table tbody tr {
            opacity: 0;
            animation: rowEntrance 0.35s ease forwards;
            transition: background 0.2s, transform 0.15s;
        }

        /* Stagger up to 15 rows, then cap */
        .data-table tbody tr:nth-child(1)  { animation-delay: 0.55s; }
        .data-table tbody tr:nth-child(2)  { animation-delay: 0.62s; }
        .data-table tbody tr:nth-child(3)  { animation-delay: 0.69s; }
        .data-table tbody tr:nth-child(4)  { animation-delay: 0.76s; }
        .data-table tbody tr:nth-child(5)  { animation-delay: 0.83s; }
        .data-table tbody tr:nth-child(6)  { animation-delay: 0.90s; }
        .data-table tbody tr:nth-child(7)  { animation-delay: 0.97s; }
        .data-table tbody tr:nth-child(8)  { animation-delay: 1.04s; }
        .data-table tbody tr:nth-child(9)  { animation-delay: 1.11s; }
        .data-table tbody tr:nth-child(10) { animation-delay: 1.18s; }
        .data-table tbody tr:nth-child(n+11) { animation-delay: 1.22s; }

        .data-table tbody tr:hover {
            background: #f0fdf9;
            transform: translateX(3px);
        }

        .data-table tbody tr.removing {
            animation: fadeOut 0.45s ease-out forwards !important;
            overflow: hidden;
        }

        /* =============================================
           CHECKBOX
        ============================================= */
        .checkbox {
            width: 18px; height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
            transition: transform 0.15s;
        }

        .checkbox:hover { transform: scale(1.15); }

        /* =============================================
           STATUS BADGES
        ============================================= */
        .status-badge {
            display: inline-flex; align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem; font-weight: 600;
            margin-right: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            animation: scaleIn 0.3s ease both;
        }

        .status-badge:hover { transform: scale(1.07); }

        .status-badge.present  { background: rgba(16,185,129,0.1); color: var(--success-color); }
        .status-badge.absent   { background: rgba(239,68,68,0.1);  color: var(--danger-color); }
        .status-badge.leave    { background: rgba(245,158,11,0.1); color: var(--warning-color); }
        .status-badge.overtime { background: rgba(15,118,110,0.1); color: var(--primary-color); }

        /* =============================================
           CHART CARD
        ============================================= */
        .chart-card {
            background: var(--bg-secondary);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
            opacity: 0;
            animation: slideInRight 0.5s 0.5s ease forwards;
            transition: box-shadow 0.3s;
        }

        .chart-card:hover { box-shadow: 0 12px 32px rgba(15,118,110,0.14); }

        .chart-header { margin-bottom: 1.5rem; animation: fadeDown 0.4s 0.6s ease both; }

        .chart-header h3 {
            font-size: 1.25rem; font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .chart-header p { font-size: 0.875rem; color: var(--text-secondary); }

        .chart-container {
            position: relative;
            height: 280px;
            margin-bottom: 1.5rem;
            display: flex; align-items: center; justify-content: center;
            animation: scaleIn 0.6s 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }

        /* =============================================
           CHART LEGEND — staggered
        ============================================= */
        .chart-legend { display: grid; grid-template-columns: 1fr; gap: 0.75rem; }

        .legend-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: 0.5rem;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            opacity: 0;
            animation: legendSlide 0.35s ease forwards;
            cursor: default;
        }

        .legend-item:nth-child(1) { animation-delay: 0.85s; }
        .legend-item:nth-child(2) { animation-delay: 0.95s; }
        .legend-item:nth-child(3) { animation-delay: 1.05s; }
        .legend-item:nth-child(4) { animation-delay: 1.15s; }

        .legend-item:hover {
            background: #e6f7f5;
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(15,118,110,0.1);
        }

        .legend-left { display: flex; align-items: center; gap: 0.75rem; }

        .legend-dot {
            width: 12px; height: 12px;
            border-radius: 50%; flex-shrink: 0;
            transition: transform 0.2s;
        }

        .legend-item:hover .legend-dot { transform: scale(1.4); }

        .legend-label { font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; }

        .legend-value {
            font-size: 1.125rem; font-weight: 700;
            color: var(--text-primary);
            transition: transform 0.2s;
        }

        .legend-item:hover .legend-value { transform: scale(1.1); }

        /* =============================================
           LOGO
        ============================================= */
        .logo img {
            max-width: 140px; height: auto; display: block;
            margin: 0 auto; border-radius: 5px;
            animation: fadeUp 0.5s 0.1s ease both;
            transition: transform 0.2s;
        }

        .logo img:hover { transform: scale(1.06) translateY(0); }

        /* =============================================
           SKELETON LOADER (used if needed)
        ============================================= */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 4px;
        }

        /* =============================================
           RESPONSIVE
        ============================================= */
        @media (max-width: 1024px) {
            .dashboard-layout   { grid-template-columns: 1fr; }
            .sidebar            { position: relative; height: auto; }
            .content-grid       { grid-template-columns: 1fr; }
            .chart-card         { position: relative; top: 0; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 1rem; }
            .header       { padding: 1rem; }
            .toast-container { left: 1rem; right: 1rem; }
            .toast        { min-width: auto; }
            .table-header { padding: 1rem; }
            .table-actions { width: 100%; }
            .table-actions .btn,
            .table-actions .select-box { width: 100%; }
            .table-controls { padding: 1rem; flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>

<body>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h2 class="logo">
                <img src="assets/logo/images.png" alt="MCL Logo">
            </h2>
        </div>
        <ul class="sidebar-nav">
            <li>
                <a href="#" class="active">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="asomonthly.php">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Monthly Attendance</span>
                </a>
            </li>
            <li>
                <a href="login.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h2>Welcome Back, ASO</h2>
                <p>Today is <?= date('l, F d, Y') ?></p>
            </div>
            <div class="header-right">
                <div class="header-icon">
                    <i class="fa-regular fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <a href="aso_details.php" style="text-decoration:none;">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Content Grid: Table + Chart -->
        <div class="content-grid">

            <!-- Table Card -->
            <div class="table-card">
                <div class="table-header">
                    <h3>Today's Attendance Records</h3>
                    <div class="table-actions">
                        <input type="date" id="dateFilter" class="select-box"
                               value="<?= $selectedDate ?>" style="min-width:160px;">
                        <select id="statusFilter" class="select-box">
                            <option value="all">All Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="leave">Leave</option>
                            <option value="overtime">Overtime</option>
                        </select>
                        <button class="btn btn-primary" id="approveBtn" onclick="approveSelected()">
                            <i class="fa-solid fa-check"></i>
                            Approve Selected <span id="selectedCount">(0)</span>
                        </button>
                    </div>
                </div>

                <div class="table-controls">
                    <div>
                        Show
                        <select class="select-box" style="width:80px;">
                            <option selected>10</option>
                            <option>25</option>
                            <option>50</option>
                            <option>100</option>
                        </select>
                        entries
                    </div>
                    <input type="text" id="searchInput" class="search-box" placeholder="Search by name, ESIC…">
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" class="checkbox"></th>
                            <th>S.No</th>
                            <th>ESIC No</th>
                            <th>Employee Name</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                    <?php if ($attendanceList && count($attendanceList) > 0): ?>
                        <?php $i = 1; foreach ($attendanceList as $row):
                            $statusClass = strtolower(trim($row['attendance_status']));
                        ?>
                        <tr data-esic="<?= htmlspecialchars($row['esic_no']) ?>">
                            <td><input type="checkbox" class="rowCheck checkbox"></td>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['esic_no']) ?></strong></td>
                            <td><?= htmlspecialchars($row['emp_name'] ?? 'N/A') ?></td>
                            <td><?= date('d-m-Y', strtotime($row['attendance_date'])) ?></td>
                            <td>
                                <?php if ($row['attendance_status'] === 'overtime'): ?>
                                    <span class="status-badge present">Present</span>
                                    <span class="status-badge overtime">Overtime</span>
                                <?php else: ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= ucfirst(htmlspecialchars($row['attendance_status'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noRecordsRow">
                            <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-secondary);">
                                <i class="fa-solid fa-circle-check"
                                   style="font-size:3rem; margin-bottom:0.5rem; color:var(--success-color);
                                          display:block; animation: popIn 0.5s cubic-bezier(0.22,1,0.36,1) both;"></i>
                                <p style="font-size:1.125rem; font-weight:600; margin-bottom:0.25rem;">All records approved!</p>
                                <p style="font-size:0.875rem;">No pending attendance records to display.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div><!-- /.table-card -->

            <!-- Chart Card -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Attendance Overview</h3>
                    <p>Today's distribution</p>
                </div>
                <div class="chart-container">
                    <canvas id="attendancePieChart"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-left">
                            <div class="legend-dot" style="background:#10b981;"></div>
                            <span class="legend-label">Present</span>
                        </div>
                        <span class="legend-value" id="legendPresent">0</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-left">
                            <div class="legend-dot" style="background:#ef4444;"></div>
                            <span class="legend-label">Absent</span>
                        </div>
                        <span class="legend-value" id="legendAbsent">0</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-left">
                            <div class="legend-dot" style="background:#f59e0b;"></div>
                            <span class="legend-label">Leave</span>
                        </div>
                        <span class="legend-value" id="legendLeave">0</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-left">
                            <div class="legend-dot" style="background:#0f766e;"></div>
                            <span class="legend-label">Overtime</span>
                        </div>
                        <span class="legend-value" id="legendOvertime">0</span>
                    </div>
                </div>
            </div><!-- /.chart-card -->

        </div><!-- /.content-grid -->

    </main>
</div>

<!-- =============================================
     JAVASCRIPT
============================================= -->
<script>
/* --- Toast System ---------------------------------------- */
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation' };

    toast.innerHTML = `
        <div class="toast-icon"><i class="fa-solid ${icons[type] || 'fa-circle-info'}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this)"><i class="fa-solid fa-xmark"></i></button>
    `;
    container.appendChild(toast);
    setTimeout(() => closeToast(toast.querySelector('.toast-close')), duration);
}

function closeToast(btn) {
    const toast = btn.closest('.toast');
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 300);
}

/* --- Select All ------------------------------------------ */
document.getElementById("selectAll").addEventListener("change", function () {
    document.querySelectorAll(".rowCheck")
        .forEach(cb => { if (cb.closest('tr').style.display !== 'none') cb.checked = this.checked; });
    updateSelectedCount();
});

document.addEventListener('change', e => {
    if (e.target.classList.contains('rowCheck')) updateSelectedCount();
});

function updateSelectedCount() {
    const n = [...document.querySelectorAll(".rowCheck:checked")]
        .filter(cb => cb.closest('tr').style.display !== 'none').length;
    document.getElementById('selectedCount').textContent = `(${n})`;
}

/* --- Approve Selected ------------------------------------ */
async function approveSelected() {
    const checked = [...document.querySelectorAll(".rowCheck:checked")]
        .filter(cb => cb.closest('tr').style.display !== 'none');

    if (checked.length === 0) {
        showToast('warning', 'No Selection', 'Please select at least one record to approve.');
        return;
    }

    const esicNumbers = checked.map(cb => cb.closest('tr').getAttribute('data-esic'));
    const btn = document.getElementById('approveBtn');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner" style="animation:spin 0.8s linear infinite;"></i> Processing… (${checked.length})`;

    try {
        const fd = new FormData();
        fd.append('approve_records', '1');
        fd.append('esic_numbers', JSON.stringify(esicNumbers));

        const res    = await fetch(window.location.href, { method: 'POST', body: fd });
        const result = await res.json();

        if (result.success) {
            showToast('success', 'Approval Successful!', result.message);
            checked.forEach(cb => {
                const row = cb.closest('tr');
                row.classList.add('removing');
                setTimeout(() => {
                    row.remove();
                    updateSerialNumbers();
                    updateStats();
                    checkEmptyTable();
                    document.getElementById('selectAll').checked = false;
                    updateSelectedCount();
                }, 450);
            });
        } else {
            showToast('error', 'Approval Failed', result.message);
        }
    } catch (err) {
        showToast('error', 'System Error', 'An error occurred while processing the request.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Approve Selected <span id="selectedCount">(0)</span>';
        updateSelectedCount();
    }
}

function updateSerialNumbers() {
    document.querySelectorAll('#attendanceTableBody tr:not(#noRecordsRow)').forEach((row, i) => {
        if (row.cells[1]) row.cells[1].textContent = i + 1;
    });
}

function checkEmptyTable() {
    const tbody = document.getElementById('attendanceTableBody');
    if (tbody.querySelectorAll('tr:not(#noRecordsRow)').length === 0 && !document.getElementById('noRecordsRow')) {
        const row = document.createElement('tr');
        row.id = 'noRecordsRow';
        row.innerHTML = `
            <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-secondary);">
                <i class="fa-solid fa-circle-check"
                   style="font-size:3rem; margin-bottom:0.5rem; color:var(--success-color);
                          display:block; animation:popIn 0.5s cubic-bezier(0.22,1,0.36,1) both;"></i>
                <p style="font-size:1.125rem; font-weight:600; margin-bottom:0.25rem;">All records approved!</p>
                <p style="font-size:0.875rem;">No pending attendance records to display.</p>
            </td>`;
        tbody.appendChild(row);
    }
}

/* --- Search & Filter ------------------------------------- */
document.getElementById("searchInput").addEventListener("keyup", function () {
    const v = this.value.toLowerCase();
    document.querySelectorAll("#attendanceTableBody tr:not(#noRecordsRow)").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(v) ? "" : "none";
    });
    updateStats(); updateSelectedCount();
});

document.getElementById("statusFilter").addEventListener("change", function () {
    const v = this.value;
    document.querySelectorAll("#attendanceTableBody tr:not(#noRecordsRow)").forEach(row => {
        let has = [...row.querySelectorAll(".status-badge")].some(b => v === "all" || b.classList.contains(v));
        row.style.display = has ? "" : "none";
    });
    updateStats(); updateSelectedCount();
});

document.getElementById("dateFilter").addEventListener("change", function () {
    if (!this.value) return;
    const url = new URL(window.location.href);
    url.searchParams.set("date", this.value);
    window.location.href = url.toString();
});

/* --- Chart ----------------------------------------------- */
let attendanceChart;

function initChart() {
    const ctx = document.getElementById('attendancePieChart');
    if (!ctx) return;

    attendanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present','Absent','Leave','Overtime'],
            datasets: [{
                data: [0,0,0,0],
                backgroundColor: ['#10b981','#ef4444','#f59e0b','#0f766e'],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 15,
                hoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0,0,0,0.88)',
                    padding: 16, cornerRadius: 8,
                    titleFont: { size: 16, weight: 'bold' },
                    bodyFont:  { size: 14 },
                    displayColors: true,
                    boxWidth: 12, boxHeight: 12, boxPadding: 8,
                    callbacks: {
                        label(ctx) {
                            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            },
            cutout: '65%',
            animation: { animateRotate: true, animateScale: true, duration: 1000,
                         easing: 'easeInOutQuart' }
        }
    });
}

/* Animate number change smoothly */
function animateNumber(el, targetVal) {
    const start  = parseInt(el.textContent) || 0;
    const diff   = targetVal - start;
    const dur    = 400;
    const startT = performance.now();

    function step(now) {
        const t = Math.min((now - startT) / dur, 1);
        const ease = t < 0.5 ? 2*t*t : -1+(4-2*t)*t;
        el.textContent = Math.round(start + diff * ease);
        if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

function updateStats() {
    let present = 0, absent = 0, leave = 0, overtime = 0;

    document.querySelectorAll("#attendanceTableBody tr:not(#noRecordsRow)").forEach(row => {
        if (row.style.display === "none") return;
        const badges = row.querySelectorAll(".status-badge");
        let hasPresent=false, hasAbsent=false, hasLeave=false, hasOvertime=false;
        badges.forEach(b => {
            if (b.classList.contains("present"))  hasPresent  = true;
            if (b.classList.contains("absent"))   hasAbsent   = true;
            if (b.classList.contains("leave"))    hasLeave    = true;
            if (b.classList.contains("overtime")) hasOvertime = true;
        });
        if      (hasAbsent) absent++;
        else if (hasLeave)  leave++;
        else if (hasPresent) { present++; if (hasOvertime) overtime++; }
    });

    animateNumber(document.getElementById("legendPresent"),  present);
    animateNumber(document.getElementById("legendAbsent"),   absent);
    animateNumber(document.getElementById("legendLeave"),    leave);
    animateNumber(document.getElementById("legendOvertime"), overtime);

    if (attendanceChart) {
        attendanceChart.data.datasets[0].data = [present, absent, leave, overtime];
        attendanceChart.update('active');
    }
}

/* --- Init ----------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    initChart();
    updateStats();
});
</script>

</body>
</html>