<?php
session_start();
if (!isset($_SESSION["user"])) { header("Location: login.php"); exit; }
require "config.php";

$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, s.SiteName
    FROM user u
    LEFT JOIN site_master s ON u.site_code = s.SiteCode
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users – Security Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== RESET ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

        /* ===== THEME VARIABLES ===== */
        :root {
            --bg: #f4f6f9;
            --card: #ffffff;
            --text: #111827;
            --subtext: #6b7280;
            --border: #e5e7eb;
        }
        body.dark {
            --bg: #0b1120;
            --card: #111827;
            --text: #e5e7eb;
            --subtext: #9ca3af;
            --border: #1f2937;
        }

        /* ===== DARK MODE — SIDEBAR ===== */
        body.dark .sidebar { background: #0d1526; box-shadow: 2px 0 12px rgba(0,0,0,0.5); }
        body.dark .sidebar .menu:hover { background: rgba(255,255,255,0.06); }
        body.dark .sidebar .menu.active { background: rgba(255,255,255,0.10); }

        /* ===== DARK MODE — STAT CARDS ===== */
        body.dark .stat-card {
            box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12);
            border-color: rgba(15,118,110,0.25);
        }
        body.dark .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(15,118,110,0.4), 0 2px 10px rgba(16,185,129,0.2);
            border-color: rgba(16,185,129,0.4);
        }

        /* ===== DARK MODE — CHART & SUMMARY BOXES ===== */
        body.dark .chart-box, body.dark .summary-box {
            box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12);
            border-color: rgba(15,118,110,0.25);
        }
        body.dark .chart-box:hover, body.dark .summary-box:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.4), 0 2px 10px rgba(16,185,129,0.2);
            border-color: rgba(16,185,129,0.4);
        }

        /* ===== DARK MODE — CARD ICONS ===== */
        body.dark .stat-card:nth-child(1) .card-icon { background: #2e1065; color: #a78bfa; }
        body.dark .stat-card:nth-child(2) .card-icon { background: #064e3b; color: #34d399; }
        body.dark .stat-card:nth-child(3) .card-icon { background: #450a0a; color: #f87171; }
        body.dark .stat-card:nth-child(4) .card-icon { background: #451a03; color: #fbbf24; }
        body.dark .stat-card:nth-child(5) .card-icon { background: #1e3a5f; color: #60a5fa; }
        body.dark .stat-card:nth-child(6) .card-icon { background: #422006; color: #fcd34d; }
        body.dark .stat-card:nth-child(7) .card-icon { background: #064e3b; color: #34d399; }
        body.dark .stat-card:nth-child(8) .card-icon { background: #1e3a5f; color: #60a5fa; }

        /* ===== DARK MODE — THEME BUTTON ===== */
        body.dark .theme-btn { background: #1e293b; color: #fbbf24; border-color: #334155; }
        body.dark .theme-btn:hover { background: #293548; }

        body {
            background: var(--bg);
            color: var(--text);
            transition: background 0.3s, color 0.3s;
            overflow-x: hidden;
        }

        /* ===== LAYOUT ===== */
        .dashboard { display: flex; min-height: 100vh; }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { display: block; }

        /* ===== SIDEBAR — no animations ===== */
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: #0f766e;
            color: #ffffff;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 8px rgba(0,0,0,0.12);
            flex-shrink: 0;
            z-index: 999;
            overflow-y: auto;
            position: relative;
            transition: transform 0.3s ease;
            /* sidebarSlideIn animation intentionally removed */
        }

        /* ===== LOGO — no pulse animation ===== */
        .logo { padding: 20px 15px; margin-bottom: 10px; }
        .logo img {
            max-width: 160px;
            height: auto;
            display: block;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 10px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            /* logoPulse animation intentionally removed */
        }

        /* ===== NAV — no staggered menuFadeIn ===== */
        nav {
            display: flex;
            flex-direction: column;
            gap: 0;
            padding: 0 15px;
            flex: 1;
        }
        .menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 6px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: all 0.25s ease;
            position: relative;
            margin-bottom: 2px;
            letter-spacing: 0.1px;
            white-space: nowrap;
            /* menuFadeIn animation intentionally removed */
        }
        .menu .icon {
            font-size: 16px;
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.95;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .menu:hover .icon { transform: scale(1.2); }
        .menu:hover { background: rgba(255,255,255,0.1); color: #ffffff; }
        .menu.active {
            background: rgba(255,255,255,0.15);
            color: #ffffff;
            font-weight: 500;
        }
        .menu.active::before {
            content: "";
            position: absolute;
            left: -15px; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 70%;
            background: #ffffff;
            border-radius: 0 4px 4px 0;
        }
        .menu.logout {
            margin-top: auto;
            margin-bottom: 15px;
            border-top: 1px solid rgba(255,255,255,0.15);
            padding-top: 15px;
        }

        /* ===== MAIN ===== */
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow-x: hidden; }

        /* ===== HEADER ===== */
        header {
            display: flex; align-items: center; gap: 14px;
            padding: 0 25px; height: 62px;
            background: var(--card);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            position: sticky; top: 0; z-index: 50;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            animation: headerDrop 0.4s ease both;
        }
        @keyframes headerDrop {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }
        header h1 { font-size: 1.5rem; font-weight: 700; color: var(--text); flex: 1; text-align: center; }

        /* ===== HAMBURGER ===== */
        .menu-btn {
            background: none; border: none;
            font-size: 22px; cursor: pointer;
            color: var(--text); padding: 6px 8px;
            border-radius: 6px; display: none;
            align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s, transform 0.2s;
        }
        .menu-btn:hover { background: rgba(0,0,0,0.06); transform: rotate(90deg); }

        /* ===== THEME BUTTON ===== */
        .theme-btn {
            width: 44px; height: 44px; border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--card); color: var(--subtext);
            font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s, color 0.2s, border-color 0.2s, transform 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }
        .theme-btn:hover { background: #f3f4f6; color: var(--text); transform: scale(1.08); }
        .theme-btn.active { background: #1e293b; color: #a5b4fc; border-color: #334155; }

        /* ===== PAGE CONTENT ===== */
        .page-content {
            padding: 24px 25px 32px;
            display: flex; flex-direction: column; gap: 20px;
            width: 100%; min-width: 0; box-sizing: border-box;
            animation: contentFadeUp 0.5s 0.2s ease both;
        }
        @keyframes contentFadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== STATS ===== */
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; width: 100%; }
        .stat-card {
            background: var(--card); padding: 18px; border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            min-width: 0; opacity: 0;
            animation: cardPop 0.4s ease forwards;
        }
        @keyframes cardPop {
            from { opacity: 0; transform: translateY(16px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }
        .stats .stat-card:nth-child(1) { animation-delay: 0.30s; }
        .stats .stat-card:nth-child(2) { animation-delay: 0.38s; }
        .stats .stat-card:nth-child(3) { animation-delay: 0.46s; }
        .stats .stat-card:nth-child(4) { animation-delay: 0.54s; }
        .stats .stat-card:nth-child(5) { animation-delay: 0.62s; }
        .stats .stat-card:nth-child(6) { animation-delay: 0.70s; }
        .stats .stat-card:nth-child(7) { animation-delay: 0.78s; }
        .stats .stat-card:nth-child(8) { animation-delay: 0.86s; }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(15,118,110,0.22), 0 2px 8px rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.35);
        }
        .stat-card p  { font-size: 13px; color: var(--subtext); font-weight: 500; }
        .stat-card h2 { margin-top: 8px; font-size: 26px; font-weight: 700; color: var(--text); }
        .warning { color: #ef4444; }

        /* ===== GRAPH SECTION ===== */
        .graph-section { display: grid; grid-template-columns: 1fr 300px; gap: 20px; width: 100%; min-width: 0; align-items: stretch; }

        /* ===== CHART BOX ===== */
        .chart-box {
            background: var(--card); padding: 20px; border-radius: 14px;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            min-width: 0; width: 100%; overflow: hidden;
            opacity: 0; transition: box-shadow 0.2s, border-color 0.2s;
            animation: cardPop 0.4s 0.55s ease forwards;
        }
        .chart-box:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.22), 0 2px 8px rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.35);
        }
        .chart-box h3 { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: var(--text); }
        .chart-wrapper { position: relative; width: 100%; height: 280px; min-height: 0; }

        /* ===== SUMMARY BOX ===== */
        .summary-box {
            background: var(--card); padding: 20px; border-radius: 14px;
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            border: 1px solid rgba(15,118,110,0.15);
            min-width: 0; width: 100%; display: flex; flex-direction: column;
            opacity: 0; transition: box-shadow 0.2s, border-color 0.2s;
            animation: cardPop 0.4s 0.65s ease forwards;
        }
        .summary-box:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.22), 0 2px 8px rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.35);
        }
        .summary-box h3 { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: var(--text); flex-shrink: 0; }
        .summary-box .summary-items-wrap { display: flex; flex-direction: column; flex: 1; }
        .summary-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 11px 0; border-bottom: 1px solid var(--border);
            font-size: 14px; transition: padding-left 0.2s ease; flex: 1;
        }
        .summary-item:hover { padding-left: 6px; }
        .summary-item:last-child { border-bottom: none; }
        .summary-item span { color: var(--subtext); }
        .summary-item b { color: var(--text); font-weight: 600; }
        .green { color: #16a34a; }
        .red   { color: #dc2626; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) { .graph-section { grid-template-columns: 1fr 260px; } }
        @media (max-width: 900px)  { .stats { grid-template-columns: repeat(2, 1fr); } .graph-section { grid-template-columns: 1fr; } .chart-wrapper { height: 250px; } }
        @media (max-width: 768px) {
            .menu-btn { display: flex; }
            .sidebar { position: fixed; top: 0; left: 0; height: 100vh; transform: translateX(-100%); animation: none; }
            .sidebar.open { transform: translateX(0); }
            header { padding: 0 16px; }
            header h1 { font-size: 1.1rem; }
            .page-content { padding: 16px 16px 28px; gap: 14px; }
            .stats { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .graph-section { grid-template-columns: 1fr; gap: 14px; }
            .chart-wrapper { height: 230px; }
            .stat-card h2 { font-size: 22px; }
        }
        @media (max-width: 480px) {
            .page-content { padding: 12px 12px 24px; }
            .stats { gap: 10px; }
            .stat-card { padding: 14px 12px; }
            .stat-card p { font-size: 12px; }
            .stat-card h2 { font-size: 20px; }
            .chart-wrapper { height: 210px; }
            .summary-item { font-size: 13px; padding: 9px 0; }
            header h1 { font-size: 0.95rem; }
        }
        @media (max-width: 360px) { .stats { grid-template-columns: 1fr; } .chart-wrapper { height: 190px; } }

        /* ══════════════════════════════════════
           USER PAGE — SPECIFIC STYLES
        ══════════════════════════════════════ */

        .tab-nav {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
            animation: fadeUp 0.4s ease both;
        }
        .tab-btn {
            display: flex; align-items: center; gap: .5rem;
            padding: .8rem 1.5rem;
            font-size: .88rem; font-weight: 600;
            color: var(--subtext);
            background: none; border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer; font-family: inherit;
            transition: color .2s, border-color .2s;
        }
        .tab-btn:hover { color: #0f766e; }
        .tab-btn.active { color: #0f766e; border-bottom-color: #0f766e; }
        .tab-badge {
            background: var(--border); color: var(--subtext);
            font-size: .7rem; font-weight: 800;
            padding: 2px 8px; border-radius: 20px;
            transition: background .2s, color .2s;
        }
        .tab-btn.active .tab-badge { background: #0f766e; color: #fff; }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: fadeUp 0.35s ease both; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes rowSlideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .u-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(15,118,110,0.15);
            box-shadow: 0 4px 16px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            overflow: hidden;
            transition: box-shadow 0.2s, border-color 0.2s;
            animation: fadeUp 0.4s 0.1s ease both;
        }
        .u-card:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.22), 0 2px 8px rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.35);
        }
        .u-card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: var(--card);
        }
        .u-card-title { font-size: .92rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: .45rem; }
        .u-card-title i { color: #0f766e; }

        .users-table { width: 100%; border-collapse: collapse; }
        .users-table thead tr { background: var(--bg); }
        .users-table th {
            padding: .75rem 1.1rem;
            font-size: .71rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .6px;
            color: var(--subtext); text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .users-table td {
            padding: .85rem 1.1rem;
            font-size: .875rem; color: var(--text);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .users-table tbody tr:last-child td { border-bottom: none; }
        .users-table tbody tr:hover { background: rgba(15,118,110,.04); transition: background .15s; }

        .users-table tbody tr { opacity: 0; animation: rowSlideIn 0.3s ease forwards; }
        .users-table tbody tr:nth-child(1)    { animation-delay: 0.10s; }
        .users-table tbody tr:nth-child(2)    { animation-delay: 0.15s; }
        .users-table tbody tr:nth-child(3)    { animation-delay: 0.20s; }
        .users-table tbody tr:nth-child(4)    { animation-delay: 0.25s; }
        .users-table tbody tr:nth-child(5)    { animation-delay: 0.30s; }
        .users-table tbody tr:nth-child(6)    { animation-delay: 0.35s; }
        .users-table tbody tr:nth-child(7)    { animation-delay: 0.40s; }
        .users-table tbody tr:nth-child(8)    { animation-delay: 0.45s; }
        .users-table tbody tr:nth-child(9)    { animation-delay: 0.50s; }
        .users-table tbody tr:nth-child(10)   { animation-delay: 0.55s; }
        .users-table tbody tr:nth-child(n+11) { animation-delay: 0.60s; }

        .emp-id {
            font-size: .76rem; font-weight: 700;
            background: var(--bg); color: var(--subtext);
            padding: 3px 9px; border-radius: 6px; display: inline-block;
        }
        .role-badge {
            font-size: .72rem; font-weight: 700; letter-spacing: .3px;
            padding: 3px 10px; border-radius: 20px;
            background: #dbeafe; color: #1d4ed8; display: inline-block;
        }
        .role-badge.admin { background: #f3e8ff; color: #7c3aed; }
        .role-badge.gm    { background: #fef3c7; color: #b45309; }
        .role-badge.plain { background: var(--bg); color: var(--subtext); }

        .action-btns { display: flex; gap: .4rem; }
        .btn-icon {
            width: 32px; height: 32px; border: none; border-radius: 7px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: .8rem; transition: transform .15s, filter .15s;
        }
        .btn-edit   { background: #dbeafe; color: #2563eb; }
        .btn-delete { background: #fee2e2; color: #ef4444; }
        .btn-icon:hover { transform: scale(1.12); filter: brightness(.9); }

        .empty-state { padding: 3rem 1rem; text-align: center; color: var(--subtext); animation: fadeUp 0.4s ease both; }
        .empty-state i { font-size: 2.2rem; margin-bottom: .5rem; display: block; opacity: .35; }
        .empty-state p { font-size: .88rem; }

        .form-body { padding: 1.25rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: .3rem; opacity: 0; animation: fadeUp 0.35s ease forwards; }
        .form-grid .form-group:nth-child(1) { animation-delay: 0.10s; }
        .form-grid .form-group:nth-child(2) { animation-delay: 0.16s; }
        .form-grid .form-group:nth-child(3) { animation-delay: 0.22s; }
        .form-grid .form-group:nth-child(4) { animation-delay: 0.28s; }
        .form-grid .form-group:nth-child(5) { animation-delay: 0.34s; }
        .form-grid .form-group:nth-child(6) { animation-delay: 0.40s; }

        .form-label { font-size: .73rem; font-weight: 700; color: var(--subtext); text-transform: uppercase; letter-spacing: .5px; }
        .form-control {
            padding: .6rem .9rem;
            border: 1px solid var(--border); border-radius: 8px;
            font-size: .9rem; color: var(--text);
            background: var(--bg); font-family: inherit; outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15,118,110,.12);
            background: var(--card);
        }

        .form-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: flex-end; gap: .6rem;
            animation: fadeUp 0.35s 0.45s ease both;
            opacity: 0;
        }
        .btn-submit {
            display: flex; align-items: center; gap: .45rem;
            padding: .62rem 1.5rem;
            background: #0f766e; color: #fff;
            border: none; border-radius: 8px;
            font-size: .88rem; font-weight: 700;
            cursor: pointer; font-family: inherit;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 3px 10px rgba(15,118,110,.25);
        }
        .btn-submit:hover { background: #0d5f58; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,118,110,.35); }
        .btn-reset {
            padding: .62rem 1.1rem;
            background: var(--bg); color: var(--subtext);
            border: 1px solid var(--border); border-radius: 8px;
            font-size: .88rem; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: background .2s, transform .15s;
        }
        .btn-reset:hover { background: var(--border); transform: translateY(-1px); }

        body.dark .u-card {
            box-shadow: 0 4px 18px rgba(15,118,110,0.25), 0 1px 4px rgba(16,185,129,0.12);
            border-color: rgba(15,118,110,0.25);
        }
        body.dark .u-card:hover {
            box-shadow: 0 8px 28px rgba(15,118,110,0.4), 0 2px 10px rgba(16,185,129,0.2);
            border-color: rgba(16,185,129,0.4);
        }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>
        <nav>
            <a href="dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="user.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <span>Add Users</span>
            </a>
            <a href="employees.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-plus"></i></span>
                <span>Add Employee</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>
            <a href="unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                <span>Unlock Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                <span>Attendance Request</span>
            </a>
            <a href="download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-download"></i></span>
                <span>Download Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Wage Report</span>
            </a>
            <a href="monthly_att/monthly_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                <span>Monthly Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                <span>Download Salary</span>
            </a>
            <a href="logout.php" class="menu logout">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- ========== MAIN ========== -->
    <main class="main">

        <!-- HEADER -->
        <header>
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1>Security Billing Portal</h1>
            <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <!-- PAGE CONTENT -->
        <div class="page-content">

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <button class="tab-btn active" id="tab-existing" onclick="switchTab('existing')">
                    <i class="fa-solid fa-users"></i>
                    Existing Users
                    <span class="tab-badge"><?php echo count($users); ?></span>
                </button>
                <button class="tab-btn" id="tab-add" onclick="switchTab('add')">
                    <i class="fa-solid fa-user-plus"></i>
                    Add New User
                </button>
            </div>

            <!-- Panel: Existing Users -->
            <div class="tab-panel active" id="panel-existing">
                <div class="u-card">
                    <div class="u-card-header">
                        <div class="u-card-title"><i class="fa-solid fa-users"></i> All Users</div>
                        <span style="font-size:.8rem;color:var(--subtext);font-weight:600;"><?php echo count($users); ?> records</span>
                    </div>
                    <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-users-slash"></i>
                        <p>No users found. Click <strong>Add New User</strong> to get started.</p>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Emp ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Area</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u):
                                $r  = strtolower($u['role'] ?? '');
                                $rc = $r === 'admin' ? 'admin' : ($r === 'gm' ? 'gm' : ($r === 'user' ? 'plain' : ''));
                            ?>
                                <tr>
                                    <td><span class="emp-id"><?= htmlspecialchars($u['id']) ?></span></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                                    <td style="color:var(--subtext);font-size:.82rem;"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                                    <td><span class="role-badge <?= $rc ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td style="font-size:.84rem;color:var(--subtext);"><?= htmlspecialchars($u['SiteName'] ?? '—') ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="edit_user.php?id=<?= $u['id'] ?>">
                                                <button class="btn-icon btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                            </a>
                                            <button class="btn-icon btn-delete" title="Delete" onclick="deleteUser(<?= $u['id'] ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel: Add New User -->
            <div class="tab-panel" id="panel-add">
                <div class="u-card">
                    <div class="u-card-header">
                        <div class="u-card-title"><i class="fa-solid fa-user-plus"></i> Add New User</div>
                    </div>
                    <form method="POST" action="add_user.php">
                        <div class="form-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">User ID</label>
                                    <input class="form-control" type="text" name="emp_id" placeholder="e.g. 1007" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">User Name</label>
                                    <input class="form-control" type="text" name="name" placeholder="Full name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input class="form-control" type="email" name="email" placeholder="email@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Password</label>
                                    <input class="form-control" type="text" name="password" placeholder="Set a password" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Role</label>
                                    <select class="form-control" name="role" required>
                                        <option value="">-- Select Role --</option>
                                        <option>Admin</option>
                                        <option>user</option>
                                        <option>ASO</option>
                                        <option>APM</option>
                                        <option>GM</option>
                                        <option>HQSO</option>
                                        <option>SDHOD</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Area / Site</label>
                                    <select class="form-control" name="site_code" required>
                                        <option value="">-- Select Area --</option>
                                        <?php
                                        $sites = $pdo->query("SELECT SiteCode, SiteName FROM site_master");
                                        while ($row = $sites->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . htmlspecialchars($row['SiteCode']) . "'>" . htmlspecialchars($row['SiteName']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-footer">
                            <button type="reset"  class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                            <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Add User</button>
                        </div>
                    </form>
                </div>
            </div>

        </div><!-- /.page-content -->
    </main>
</div>

<script src="assets/users.js"></script>
<script>
/* ── Tab switching ── */
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

/* ── Sidebar toggle ── */
const menuBtn = document.getElementById('menuBtn');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');

function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

menuBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar .menu').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); });
});
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }
});

/* ── Theme toggle (moon/sun) ── */
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = themeToggle.querySelector('i');

function applyTheme(dark) {
    if (dark) {
        document.body.classList.add('dark');
        themeToggle.classList.add('active');
        themeIcon.className = 'fa-solid fa-sun';
    } else {
        document.body.classList.remove('dark');
        themeToggle.classList.remove('active');
        themeIcon.className = 'fa-solid fa-moon';
    }
}

applyTheme(localStorage.getItem('theme') === 'dark');

themeToggle.addEventListener('click', function () {
    const isDark = document.body.classList.contains('dark');
    applyTheme(!isDark);
    localStorage.setItem('theme', !isDark ? 'dark' : 'light');
});
</script>
</body>
</html>