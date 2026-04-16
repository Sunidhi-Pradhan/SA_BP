<?php
session_start();
require "config.php";

// login check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// role check
$stmt = $pdo->prepare("SELECT role FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if ($u['role'] !== 'user') {
    die("Access denied");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/useratt.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>

    /* ============================================================
       KEYFRAMES
    ============================================================ */
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-44px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes logoFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes navReveal {
        from { opacity: 0; transform: translateX(-18px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes fadeDown {
        from { opacity: 0; transform: translateY(-18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(28px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    @keyframes panelSlideLeft {
        from { opacity: 0; transform: translateX(-32px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes panelSlideRight {
        from { opacity: 0; transform: translateX(32px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes tableRowIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes spin {
        0%   { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes pulseGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(15,118,110,0.25); }
        50%       { box-shadow: 0 0 0 8px rgba(15,118,110,0); }
    }
    @keyframes shimmer {
        0%   { background-position: -200% center; }
        100% { background-position:  200% center; }
    }
    @keyframes iconBounce {
        0%, 100% { transform: translateY(0); }
        40%       { transform: translateY(-6px); }
        60%       { transform: translateY(-3px); }
    }

    /* ============================================================
       LAYOUT
    ============================================================ */
    .container {
        display: flex !important;
        min-height: 100vh;
    }

    /* ============================================================
       SIDEBAR
    ============================================================ */
    .sidebar {
        width: 240px !important;
        background: linear-gradient(180deg, #0f766e 0%, #0a5c55 100%) !important;
        color: #fff !important;
        padding: 25px 20px !important;
        min-height: 100vh;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 22px rgba(13,95,88,0.32);
        transition: transform 0.3s ease;
        z-index: 100;
        animation: slideInLeft 0.5s cubic-bezier(0.22,1,0.36,1) both;
    }

    .sidebar-close {
        display: none;
        position: absolute;
        top: 14px; right: 14px;
        background: rgba(255,255,255,0.14);
        border: none; color: #fff;
        width: 30px; height: 30px;
        border-radius: 7px; cursor: pointer; font-size: 15px;
        align-items: center; justify-content: center;
        transition: background 0.2s, transform 0.25s;
        z-index: 2;
    }
    .sidebar-close:hover { background: rgba(255,255,255,0.28); transform: rotate(90deg); }

    .sidebar .logo {
        text-align: center !important;
        margin-bottom: 30px !important;
        font-size: unset !important;
        font-weight: unset !important;
        padding-bottom: 22px;
        border-bottom: 1px solid rgba(255,255,255,0.15);
    }
    .sidebar .logo img {
        max-width: 140px; height: auto;
        display: block; margin: 0 auto;
        background: #fff; padding: 10px 14px;
        border-radius: 10px;
        animation: logoFadeUp 0.5s 0.1s ease both;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .sidebar .logo img:hover { transform: scale(1.05); box-shadow: 0 6px 18px rgba(0,0,0,0.2); }

    .sidebar ul { list-style: none !important; padding: 0 !important; margin: 0 !important; flex: 1; }

    .sidebar ul li {
        padding: 0 !important;
        margin-bottom: 10px !important;
        border-radius: 10px !important;
        background: transparent;
        opacity: 0;
        animation: navReveal 0.4s ease forwards;
        transition: background 0.22s ease, box-shadow 0.22s ease, transform 0.2s ease;
    }
    .sidebar ul li:nth-child(1) { animation-delay: 0.30s; }
    .sidebar ul li:nth-child(2) { animation-delay: 0.42s; }
    .sidebar ul li:nth-child(3) { animation-delay: 0.54s; }
    .sidebar ul li:nth-child(4) { animation-delay: 0.66s; }

    .sidebar ul li:hover {
        background: rgba(255,255,255,0.15) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.13);
        transform: translateX(4px);
    }
    .sidebar ul li.active {
        background: rgba(255,255,255,0.22) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        transform: translateX(4px);
    }

    .sidebar ul li a {
        display: flex !important; align-items: center !important;
        gap: 13px !important; padding: 13px 16px !important;
        text-decoration: none !important;
        color: rgba(255,255,255,0.85) !important;
        font-size: 0.95rem !important; font-weight: 500 !important;
        background: transparent !important; border-radius: 10px !important;
        transition: color 0.2s ease !important; white-space: nowrap;
    }
    .sidebar ul li:hover a,
    .sidebar ul li.active a { color: #fff !important; font-weight: 600 !important; }

    .sidebar ul li a i {
        font-size: 1rem; width: 20px; text-align: center;
        flex-shrink: 0; opacity: 0.80;
        transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s ease;
    }
    .sidebar ul li:hover  a i { transform: scale(1.28) rotate(-6deg); opacity: 1; }
    .sidebar ul li.active a i { transform: scale(1.18); opacity: 1; }



    /* ============================================================
       MOBILE OVERLAY
    ============================================================ */
    .sidebar-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 99;
        backdrop-filter: blur(2px);
    }
    .sidebar-overlay.active { display: block; }

    /* ============================================================
       TOPBAR
    ============================================================ */
    .topbar {
        position: relative;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        animation: fadeDown 0.45s 0.25s cubic-bezier(0.22,1,0.36,1) both;
    }
    .topbar h3 {
        position: absolute; left: 50%; transform: translateX(-50%);
        margin: 0; font-size: 1.1rem; font-weight: 700;
        color: #1f2937; white-space: nowrap;
        pointer-events: none;
    }
    .topbar-left  { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .topbar-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }

    .hamburger-btn {
        display: none;
        background: #f3f4f6; border: 1.5px solid #e5e7eb;
        border-radius: 8px; width: 38px; height: 38px;
        align-items: center; justify-content: center;
        cursor: pointer; color: #0f766e; font-size: 1rem;
        transition: background 0.2s, transform 0.2s;
    }
    .hamburger-btn:hover { background: #e5e7eb; transform: scale(1.07); }

    .user-icon {
        width: 36px; height: 36px; border-radius: 50%;
        background: #0f766e;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
    }
    .user-icon:hover { transform: scale(1.1); box-shadow: 0 2px 10px rgba(15,118,110,0.4); }
    .user-icon svg { width: 18px; height: 18px; stroke: white; }

    /* ============================================================
       PAGE BODY — animated grid entry
    ============================================================ */
    .page-body {
        display: grid !important;
        grid-template-columns: 1fr 1.6fr;
        gap: 24px;
        margin-top: 0;
        width: 100%;
        box-sizing: border-box;
    }

    /* ===== LEFT panel ===== */
    .edit-panel {
        background: #fff;
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        height: fit-content;
        min-width: 0;
        overflow: hidden;
        /* entrance */
        animation: panelSlideLeft 0.55s 0.35s cubic-bezier(0.22,1,0.36,1) both;
    }

    .edit-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .edit-panel-header h4 {
        font-size: 1rem; font-weight: 700; color: #1f2937;
        display: flex; align-items: center; gap: 8px;
    }
    .edit-panel-header h4 i { color: #0f766e; }

    .sample-link {
        display: inline-flex; align-items: center; gap: 6px;
        color: #0f766e; font-size: 0.85rem; font-weight: 600;
        text-decoration: none;
        padding: 6px 14px;
        border: 1.5px solid #0f766e;
        border-radius: 8px;
        transition: background 0.2s, color 0.2s, transform 0.2s;
    }
    .sample-link:hover { background: #0f766e; color: #fff; transform: translateY(-1px); }

    /* Instruction box — subtle pulse on load */
    .instruction-box {
        display: flex; align-items: flex-start; gap: 10px;
        background: #fff8f0; border: 1px solid #fed7aa;
        border-radius: 8px; padding: 12px 14px;
        margin-bottom: 20px; font-size: 0.85rem; color: #92400e;
        animation: fadeUp 0.4s 0.55s ease both;
    }
    .instruction-box i { color: #f97316; margin-top: 1px; flex-shrink: 0; }

    /* Upload drop zone */
    .upload-drop-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f9fafb;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
        padding: 40px 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 20px;
        min-height: 180px;
        animation: fadeUp 0.45s 0.65s ease both;
        position: relative;
        overflow: hidden;
    }
    /* shimmer sweep on hover */
    .upload-drop-zone::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(105deg, transparent 40%, rgba(15,118,110,0.07) 50%, transparent 60%);
        background-size: 200% 100%;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .upload-drop-zone:hover::after { opacity: 1; animation: shimmer 1.2s ease infinite; }
    .upload-drop-zone:hover { background: #f0fdfa; border-color: #0f766e; }

    .upload-drop-zone i {
        font-size: 2.5rem; color: #0f766e; margin-bottom: 12px;
        transition: transform 0.3s;
    }
    .upload-drop-zone:hover i { animation: iconBounce 0.7s ease; }
    .upload-drop-zone p { font-size: 0.95rem; color: #374151; font-weight: 500; margin: 0 0 4px; }
    .upload-drop-zone span { font-size: 0.82rem; color: #6b7280; }
    .upload-drop-zone input { display: none; }
    .selected-filename { font-size: 0.88rem; color: #0f766e; font-weight: 600; margin-top: 8px; }

    /* Upload button */
    .upload-process-btn {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, #0f766e, #0d9488);
        color: #fff; border: none; border-radius: 10px;
        font-size: 0.95rem; font-weight: 600;
        cursor: pointer; display: flex; align-items: center;
        justify-content: center; gap: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
        animation: fadeUp 0.4s 0.75s ease both;
    }
    .upload-process-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(15,118,110,0.35);
        animation: pulseGlow 1.6s ease infinite;
    }
    .upload-process-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; animation: none; }

    /* ===== RIGHT panel ===== */
    .history-panel {
        background: #fff;
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        min-width: 0;
        overflow: hidden;
        animation: panelSlideRight 0.55s 0.4s cubic-bezier(0.22,1,0.36,1) both;
    }

    .history-header {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 20px;
        animation: fadeDown 0.4s 0.55s ease both;
    }
    .history-header h4 {
        font-size: 1rem; font-weight: 700; color: #1f2937;
        display: flex; align-items: center; gap: 8px;
    }
    .history-header h4 i { color: #0f766e; }

    /* Tab buttons */
    .tab-bar {
        display: flex; gap: 10px; margin-bottom: 20px;
        animation: fadeUp 0.4s 0.6s ease both;
    }

    .tab-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 18px;
        border-radius: 8px; border: none; cursor: pointer;
        font-size: 0.88rem; font-weight: 600;
        transition: all 0.22s ease;
        position: relative; overflow: hidden;
    }
    .tab-btn::after {
        content: '';
        position: absolute; inset: 0;
        background: rgba(255,255,255,0.15);
        opacity: 0; transition: opacity 0.2s;
    }
    .tab-btn:hover::after { opacity: 1; }
    .tab-btn:active { transform: scale(0.96); }

    .tab-btn.pending { background: #3b82f6; color: #fff; }
    .tab-btn.pending:not(.active-tab) { background: #eff6ff; color: #3b82f6; border: 1.5px solid #bfdbfe; }
    .tab-btn.processed { background: #f3f4f6; color: #6b7280; border: 1.5px solid #e5e7eb; }
    .tab-btn.processed.active-tab { background: #059669; color: #fff; border-color: #059669; }
    .tab-btn .tab-count { background: rgba(255,255,255,0.3); border-radius: 20px; padding: 1px 7px; font-size: 0.78rem; }

    /* Table controls */
    .table-controls {
        display: flex; justify-content: space-between;
        align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;
        animation: fadeIn 0.4s 0.7s ease both;
    }
    .show-entries {
        display: flex; align-items: center; gap: 8px;
        font-size: 0.85rem; color: #6b7280;
    }
    .show-entries select {
        padding: 5px 10px; border: 1px solid #e5e7eb;
        border-radius: 6px; font-size: 0.85rem; color: #374151;
        transition: border-color 0.2s;
    }
    .show-entries select:focus { border-color: #0f766e; outline: none; }
    .search-box { display: flex; align-items: center; gap: 8px; }
    .search-box label { font-size: 0.85rem; color: #6b7280; }
    .search-box input {
        padding: 6px 12px; border: 1px solid #e5e7eb;
        border-radius: 6px; font-size: 0.85rem; color: #374151;
        outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .search-box input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.12); }

    /* History table */
    .history-table-wrap {
        overflow-x: auto;
        animation: fadeIn 0.4s 0.75s ease both;
    }
    .history-table {
        width: 100%; border-collapse: collapse;
        font-size: 0.86rem; min-width: 600px;
    }
    .history-table thead tr { background: #f8f9fa; }
    .history-table thead th {
        padding: 12px 14px; text-align: left;
        color: #374151; font-weight: 600; font-size: 0.8rem;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }
    .history-table thead th .sort-icon { color: #9ca3af; margin-left: 4px; font-size: 0.7rem; }
    .history-table tbody td {
        padding: 12px 14px; border-bottom: 1px solid #f0f0f0;
        color: #374151; vertical-align: middle;
    }
    /* animated row hovers */
    .history-table tbody tr {
        transition: background 0.18s ease, transform 0.18s ease;
    }
    .history-table tbody tr:hover td {
        background: #f0fdf9;
    }
    .history-table .no-data {
        text-align: center; color: #9ca3af;
        padding: 40px; font-size: 0.9rem;
    }

    /* Status badges */
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
    .badge.pending   { background: #dbeafe; color: #1d4ed8; }
    .badge.approved  { background: #d1fae5; color: #065f46; }
    .badge.rejected  { background: #fee2e2; color: #991b1b; }

    /* Pagination */
    .pagination-row {
        display: flex; justify-content: space-between;
        align-items: center; margin-top: 18px;
        font-size: 0.85rem; color: #6b7280; flex-wrap: wrap; gap: 10px;
        animation: fadeIn 0.4s 0.85s ease both;
    }
    .pg-btns { display: flex; gap: 6px; }
    .pg-btn {
        padding: 6px 16px; border-radius: 6px;
        border: 1.5px solid #e5e7eb; background: #fff;
        color: #374151; font-size: 0.85rem; font-weight: 500;
        cursor: pointer; transition: all 0.2s;
    }
    .pg-btn:hover:not(:disabled) { border-color: #0f766e; color: #0f766e; transform: translateY(-1px); }
    .pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    /* Loading spinner */
    .spinner-wrap { display: none; text-align: center; padding: 16px; }
    .spinner-wrap.active { display: block; }
    .spinner {
        border: 3px solid #f3f3f3; border-top: 3px solid #0f766e;
        border-radius: 50%; width: 32px; height: 32px;
        animation: spin 0.9s linear infinite; margin: 0 auto 8px;
    }

    /* Notification */
    .notification {
        display: none; padding: 16px 20px;
        border-radius: 8px; margin-bottom: 18px;
        font-size: 0.9rem;
        animation: fadeDown 0.3s ease;
    }
    .notification.success { background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; }
    .notification.error   { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; }
    .notification .close-btn {
        float: right; background: none; border: none;
        font-size: 1.2rem; cursor: pointer; color: inherit; opacity: 0.6;
    }
    .notification .close-btn:hover { opacity: 1; }

    /* Table row stagger animation class added via JS */
    .row-animate {
        animation: tableRowIn 0.3s ease both;
    }

    /* Footer */
    footer {
        animation: fadeIn 0.4s 1s ease both;
    }

    /* ============================================================
       RESPONSIVE
    ============================================================ */

    /* ---- Preview table ---- */
    .preview-section {
        display: none;
        margin: 18px 0;
        animation: fadeUp 0.4s ease both;
    }
    .preview-section.active { display: block; }
    .preview-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 10px;
    }
    .preview-header h5 {
        font-size: 0.9rem; font-weight: 700; color: #1f2937;
        display: flex; align-items: center; gap: 6px;
    }
    .preview-header h5 i { color: #0f766e; }
    .preview-header .preview-count {
        font-size: 0.78rem; background: #f0fdfa; color: #0f766e;
        padding: 3px 10px; border-radius: 20px; font-weight: 600;
    }
    .preview-table-wrap {
        overflow-x: auto; border-radius: 8px;
        border: 1px solid #e5e7eb; max-height: 300px; overflow-y: auto;
    }
    .preview-table {
        width: 100%; border-collapse: collapse;
        font-size: 0.82rem; min-width: 500px;
    }
    .preview-table thead { position: sticky; top: 0; z-index: 1; }
    .preview-table thead th {
        padding: 9px 12px; background: #f0fdfa; color: #0f766e;
        font-weight: 700; text-align: left; font-size: 0.76rem;
        text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;
    }
    .preview-table tbody td {
        padding: 8px 12px; border-bottom: 1px solid #f0f0f0; color: #374151;
    }
    .preview-table tbody tr:hover td { background: #f0fdf9; }
    .preview-table .status-pp { color: #2563eb; font-weight: 700; }
    .preview-table .status-p  { color: #16a34a; font-weight: 700; }
    .preview-table .status-a  { color: #dc2626; font-weight: 700; }
    .preview-table .status-l  { color: #d97706; font-weight: 700; }
    .preview-row-error td { background: #fef2f2 !important; }
    .preview-row-error .error-msg { color: #dc2626; font-size: 0.75rem; font-weight: 600; }
    @media (max-width: 1100px) {
        .page-body { grid-template-columns: 1fr 1.2fr !important; gap: 18px !important; }
        .edit-panel, .history-panel { padding: 22px !important; }
    }

    @media (max-width: 900px) {
        .page-body { grid-template-columns: 1fr !important; gap: 16px !important; }
        .edit-panel { height: auto !important; }
        .upload-drop-zone { min-height: 140px !important; }
        /* On single col, both panels slide up instead of left/right */
        .edit-panel   { animation: fadeUp 0.5s 0.35s cubic-bezier(0.22,1,0.36,1) both !important; }
        .history-panel{ animation: fadeUp 0.5s 0.48s cubic-bezier(0.22,1,0.36,1) both !important; }
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed !important;
            left: 0 !important; top: 0 !important;
            transform: translateX(-100%) !important;
            height: 100vh !important;
            z-index: 200 !important;
            animation: none !important;
        }
        .sidebar.open { transform: translateX(0) !important; box-shadow: 8px 0 32px rgba(0,0,0,0.35) !important; }
        .sidebar-close { display: flex !important; }
        .hamburger-btn { display: flex !important; }
        .container { flex-direction: column !important; }
        .main { padding: 0 !important; width: 100% !important; min-width: 0 !important; overflow-x: hidden !important; }
        .topbar { padding: 12px 14px !important; border-radius: 8px !important; margin: 10px !important; width: calc(100% - 20px) !important; box-sizing: border-box !important; }
        .topbar h3 { font-size: 0.85rem !important; position: static !important; transform: none !important; flex: 1; text-align: center; }
        .topbar-right .logout { display: none !important; }
        .page-body { display: flex !important; flex-direction: column !important; gap: 14px !important; padding: 0 10px 10px !important; width: 100% !important; box-sizing: border-box !important; }
        .edit-panel, .history-panel { width: 100% !important; box-sizing: border-box !important; padding: 16px !important; border-radius: 10px !important; margin: 0 !important; }
        .edit-panel-header { flex-wrap: wrap !important; gap: 8px !important; }
        .edit-panel-header h4 { font-size: 0.88rem !important; }
        .sample-link { font-size: 0.78rem !important; padding: 5px 10px !important; }
        .instruction-box { font-size: 0.79rem !important; padding: 10px 11px !important; }
        .upload-drop-zone { min-height: 120px !important; padding: 20px 14px !important; margin-bottom: 14px !important; }
        .upload-drop-zone i { font-size: 1.8rem !important; margin-bottom: 8px !important; }
        .upload-drop-zone p  { font-size: 0.85rem !important; }
        .upload-drop-zone span { font-size: 0.77rem !important; }
        .upload-process-btn { font-size: 0.87rem !important; padding: 11px !important; }
        .history-header h4 { font-size: 0.9rem !important; }
        .tab-bar { flex-wrap: wrap !important; gap: 8px !important; }
        .tab-btn  { font-size: 0.8rem !important; padding: 7px 13px !important; }
        .table-controls { flex-direction: column !important; align-items: stretch !important; gap: 8px !important; }
        .show-entries { font-size: 0.8rem !important; }
        .search-box { width: 100% !important; }
        .search-box label { font-size: 0.8rem !important; flex-shrink: 0; }
        .search-box input  { flex: 1 !important; width: 100% !important; font-size: 0.8rem !important; min-width: 0 !important; }
        .history-table-wrap { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; width: 100% !important; }
        .history-table { font-size: 0.76rem !important; min-width: 460px !important; }
        .history-table thead th, .history-table tbody td { padding: 8px 9px !important; }
        .pagination-row { flex-wrap: wrap !important; gap: 8px !important; }
        #showingText { font-size: 0.78rem !important; }
        .pg-btn { padding: 6px 13px !important; font-size: 0.79rem !important; }

    }

    @media (max-width: 480px) {
        .topbar { margin: 8px !important; width: calc(100% - 16px) !important; }
        .topbar h3 { font-size: 0.78rem !important; }
        .page-body { padding: 0 8px 8px !important; gap: 12px !important; }
        .edit-panel, .history-panel { padding: 13px !important; }
        .upload-drop-zone { min-height: 105px !important; padding: 16px 10px !important; }
        .upload-drop-zone i { font-size: 1.6rem !important; }
        .history-table thead th:first-child,
        .history-table tbody td:first-child { display: none !important; }
        .history-table { font-size: 0.71rem !important; min-width: 360px !important; }
        .history-table thead th, .history-table tbody td { padding: 7px 7px !important; }
        .tab-btn { font-size: 0.75rem !important; padding: 6px 10px !important; }
        .pg-btn  { padding: 5px 10px !important; font-size: 0.75rem !important; }
    }
    </style>
<link rel="stylesheet" href="assets/responsive.css">
</head>
<body>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container">

    <!-- ===================================================
         SIDEBAR
    =================================================== -->
    <aside class="sidebar" id="sidebar">

        <button class="sidebar-close" id="sidebarClose">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </div>

        <ul>
            <li>
                <a href="user_dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="user_attendance.php">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Upload Attendance
                </a>
            </li>
            <li class="active">
                <a href="update_attendance.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    Update Attendance
                </a>
            </li>

        </ul>

    </aside>

    <!-- ===================================================
         MAIN
    =================================================== -->
    <main class="main">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" id="hamburgerBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <h3>Security Attendance and Billing Portal</h3>
            <div class="topbar-right">
                <div class="user-icon">
                    <a href="user_profile.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="8" r="4"/>
                        </svg>
                    </a>
                </div>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </header>

        <!-- Notification -->
        <div id="notification" class="notification"></div>

        <!-- Page Body -->
        <div class="page-body">

            <!-- ===== LEFT: Edit Upload Panel ===== -->
            <div class="edit-panel">

                <div class="edit-panel-header">
                    <h4><i class="fa-solid fa-pen-to-square"></i> Edit Attendance</h4>
                    <a href="user/update_sample.xlsx" download class="sample-link">
                        <i class="fa-solid fa-file-csv"></i> Sample Excel
                    </a>
                </div>

                <div class="instruction-box">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>
                        <strong>Instructions:</strong> Download the sample file, fill in your data using the
                        same columns and same format, and upload. <strong>All fields are mandatory.</strong>
                    </span>
                </div>

                <!-- Drop Zone -->
                <div class="upload-drop-zone" id="dropZone" onclick="document.getElementById('csvFile').click()">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p id="dropText">Select Excel / CSV File</p>
                    <span id="dropSubText">Click to browse your computer</span>
                    <input type="file" id="csvFile" accept=".xlsx,.xls,.csv" />
                </div>

                <!-- Preview table -->
                <div class="preview-section" id="previewSection">
                    <div class="preview-header">
                        <h5><i class="fa-solid fa-table-list"></i> File Preview</h5>
                        <span class="preview-count" id="previewCount">0 rows</span>
                    </div>
                    <div class="preview-table-wrap">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Emp Code</th>
                                    <th>Emp Name</th>
                                    <th>Attendance Date</th>
                                    <th>New Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Spinner -->
                <div class="spinner-wrap" id="spinnerWrap">
                    <div class="spinner"></div>
                    <p style="font-size:0.85rem;color:#6b7280;">Processing file...</p>
                </div>

                <button class="upload-process-btn" id="uploadBtn" onclick="processFile()">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    Upload &amp; Process File
                </button>
            </div>

            <!-- ===== RIGHT: History Panel ===== -->
            <div class="history-panel">

                <div class="history-header">
                    <h4><i class="fa-solid fa-clock-rotate-left"></i> Attendance Edit Request History</h4>
                </div>

                <!-- Tabs -->
                <div class="tab-bar">
                    <button class="tab-btn pending active-tab" id="tabPending" onclick="switchTab('pending')">
                        <i class="fa-solid fa-clock"></i> Pending
                        <span class="tab-count" id="pendingCount">0</span>
                    </button>
                    <button class="tab-btn processed" id="tabProcessed" onclick="switchTab('processed')">
                        <i class="fa-solid fa-circle-check"></i> Processed
                    </button>
                </div>

                <!-- Table controls -->
                <div class="table-controls">
                    <div class="show-entries">
                        Show
                        <select id="pageSize" onchange="renderTable()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        entries
                    </div>
                    <div class="search-box">
                        <label>Search:</label>
                        <input type="text" id="searchInput" placeholder="Search..." oninput="renderTable()">
                    </div>
                </div>

                <!-- Table -->
                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>ESIC NO <span class="sort-icon">▲▼</span></th>
                                <th>Name <span class="sort-icon">▲▼</span></th>
                                <th>Date <span class="sort-icon">▲▼</span></th>
                                <th>Change <span class="sort-icon">▲▼</span></th>
                                <th>Reason <span class="sort-icon">▲▼</span></th>
                                <th>Status <span class="sort-icon">▲▼</span></th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="6" class="no-data">No data available in table</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-row">
                    <span id="showingText">Showing 0 to 0 of 0 entries</span>
                    <div class="pg-btns">
                        <button class="pg-btn" id="prevBtn" onclick="changePage(-1)">Previous</button>
                        <button class="pg-btn" id="nextBtn" onclick="changePage(1)">Next</button>
                    </div>
                </div>

            </div>
        </div>



    </main>
</div>

<script>
    /* ── Mobile sidebar ── */
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger && hamburger.addEventListener('click', openSidebar);
    closeBtn  && closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

    /* ── Notification helpers ── */
    const notifEl = document.getElementById('notification');
    function showNotif(type, msg) {
        notifEl.className = 'notification ' + type;
        notifEl.innerHTML = `<button class="close-btn" onclick="hideNotif()">&times;</button>${msg}`;
        notifEl.style.display = 'block';
        notifEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function hideNotif() { notifEl.style.display = 'none'; }

    /* ── File selection ── */
    const csvFile  = document.getElementById('csvFile');
    const dropText = document.getElementById('dropText');
    const dropSub  = document.getElementById('dropSubText');
    const dropZone = document.getElementById('dropZone');

    csvFile.addEventListener('change', function () {
        if (this.files.length) {
            dropText.textContent = this.files[0].name;
            dropSub.textContent  = 'File selected — click Upload & Process to continue';
            dropZone.style.borderColor = '#0f766e';
            dropZone.style.background  = '#f0fdfa';
            parseAndPreview(this.files[0]);
        }
    });

    /* ── Drag-and-drop support ── */
    ['dragenter','dragover'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.style.borderColor = '#0f766e';
            dropZone.style.background  = '#f0fdfa';
        });
    });
    dropZone.addEventListener('dragleave', () => {
        dropZone.style.borderColor = '#cbd5e1';
        dropZone.style.background  = '#f9fafb';
    });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        const validExts = ['.csv', '.xlsx', '.xls'];
        if (file && validExts.some(ext => file.name.toLowerCase().endsWith(ext))) {
            const dt = new DataTransfer();
            dt.items.add(file);
            csvFile.files = dt.files;
            dropText.textContent = file.name;
            dropSub.textContent  = 'File selected — click Upload & Process to continue';
            dropZone.style.borderColor = '#0f766e';
            dropZone.style.background  = '#f0fdfa';
            parseAndPreview(file);
        }
    });

    /* ── Excel preview parser ── */
    function parseAndPreview(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

                const previewSection = document.getElementById('previewSection');
                const tbody = document.getElementById('previewTableBody');
                const countEl = document.getElementById('previewCount');

                if (rows.length <= 1) {
                    previewSection.classList.remove('active');
                    return;
                }

                const dataRows = rows.slice(1).filter(r => r.some(c => c !== null && c !== undefined && String(c).trim() !== ''));
                countEl.textContent = dataRows.length + ' row' + (dataRows.length !== 1 ? 's' : '');

                const statusLabels = { 'p': 'Present', 'a': 'Absent', 'pp': 'Present With Extra', 'l': 'Leave' };

                tbody.innerHTML = dataRows.map((row, i) => {
                    const empcode = String(row[0] ?? '').trim();
                    const empname = String(row[1] ?? '').trim();
                    const dateVal = row[2] ?? '';
                    const statusRaw = String(row[3] ?? '').trim().toLowerCase().replace(/[^a-z]/g, '');
                    const reason  = String(row[4] ?? '').trim();

                    let dateStr = dateVal;
                    if (typeof dateVal === 'number') {
                        const d = XLSX.SSF.parse_date_code(dateVal);
                        if (d) dateStr = `${d.y}-${String(d.m).padStart(2,'0')}-${String(d.d).padStart(2,'0')}`;
                    }

                    const statusCls = statusRaw === 'pp' ? 'status-pp' : statusRaw === 'p' ? 'status-p' : statusRaw === 'a' ? 'status-a' : statusRaw === 'l' ? 'status-l' : '';
                    const statusLabel = statusLabels[statusRaw] || String(row[3] ?? '');

                    let hasError = !empcode || !empname || !dateStr || !statusRaw || !reason;

                    return `<tr class="${hasError ? 'preview-row-error' : ''}">
                        <td>${i + 1}</td>
                        <td style="font-weight:600;">${empcode || '<span class="error-msg">Missing</span>'}</td>
                        <td>${empname || '<span class="error-msg">Missing</span>'}</td>
                        <td style="white-space:nowrap;">${dateStr || '<span class="error-msg">Missing</span>'}</td>
                        <td class="${statusCls}">${statusLabel || '<span class="error-msg">Missing</span>'}</td>
                        <td>${reason || '<span class="error-msg">Missing</span>'}</td>
                    </tr>`;
                }).join('');

                previewSection.classList.add('active');
            } catch (err) {
                console.error('Preview parse error:', err);
            }
        };
        reader.readAsArrayBuffer(file);
    }

    /* ── Upload & process ── */
    function processFile() {
        if (!csvFile.files.length) {
            Swal.fire({ icon: 'warning', title: 'No File Selected', text: 'Please select an Excel or CSV file first.' });
            return;
        }

        const previewRows = document.querySelectorAll('#previewTableBody tr');
        if (previewRows.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Empty File', text: 'The selected file has no data rows.' });
            return;
        }

        Swal.fire({
            title: 'Submit Attendance Requests?',
            html: `<p>You are about to submit <strong>${previewRows.length} row(s)</strong> for admin approval.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0f766e',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fa-solid fa-paper-plane"></i> Submit',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            const btn     = document.getElementById('uploadBtn');
            const spinner = document.getElementById('spinnerWrap');

            btn.disabled = true;
            spinner.classList.add('active');

            const formData = new FormData();
            formData.append('edit_file', csvFile.files[0]);

            fetch('process_edit_attendance.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    spinner.classList.remove('active');
                    btn.disabled = false;
                    if (data.success) {
                        let errHtml = '';
                        if (data.errors && data.errors.length) {
                            errHtml = '<br><div style="text-align:left;max-height:150px;overflow-y:auto;font-size:.82rem;margin-top:10px;padding:10px;background:#fff8f0;border-radius:6px;border:1px solid #fed7aa;">' +
                                '<strong style="color:#92400e;">Warnings:</strong><br>' + data.errors.join('<br>') + '</div>';
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            html: `<p>${data.message}</p>${errHtml}`,
                            confirmButtonColor: '#0f766e'
                        });
                        document.getElementById('previewSection').classList.remove('active');
                        csvFile.value = '';
                        dropText.textContent = 'Select Excel / CSV File';
                        dropSub.textContent = 'Click to browse your computer';
                        dropZone.style.borderColor = '#cbd5e1';
                        dropZone.style.background = '#f9fafb';
                        loadHistory();
                    } else {
                        let errHtml = '';
                        if (data.errors && data.errors.length) {
                            errHtml = '<br><div style="text-align:left;max-height:200px;overflow-y:auto;font-size:.82rem;margin-top:10px;padding:10px;background:#fef2f2;border-radius:6px;border:1px solid #fca5a5;">' +
                                '<strong style="color:#991b1b;">Errors:</strong><br>' + data.errors.join('<br>') + '</div>';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            html: `<p>${data.message}</p>${errHtml}`,
                            confirmButtonColor: '#dc2626'
                        });
                    }
                })
                .catch(err => {
                    spinner.classList.remove('active');
                    btn.disabled = false;
                    Swal.fire({ icon: 'error', title: 'Upload Error', text: err.message });
                });
        });
    }

    /* ── Tab state ── */
    let currentTab  = 'pending';
    let currentPage = 1;
    let allRows     = [];

    function switchTab(tab) {
        currentTab  = tab;
        currentPage = 1;
        document.getElementById('tabPending').classList.toggle('active-tab',   tab === 'pending');
        document.getElementById('tabProcessed').classList.toggle('active-tab', tab === 'processed');
        loadHistory();
    }

    /* ── Status helpers ── */
    function statusColor(s) {
        const map = {
            'Present':            { bg: '#16a34a', color: '#fff', icon: 'fa-user-check' },
            'Present With Extra': { bg: '#2563eb', color: '#fff', icon: 'fa-user-plus'  },
            'Leave':              { bg: '#d97706', color: '#fff', icon: 'fa-calendar-xmark' },
            'Absent':             { bg: '#dc2626', color: '#fff', icon: 'fa-user-xmark'  },
        };
        return map[s] || { bg: '#6b7280', color: '#fff', icon: 'fa-circle' };
    }

    function statusPill(s) {
        if (!s || s === 'N/A') return '<span style="color:#9ca3af;font-size:0.78rem;">N/A</span>';
        const c = statusColor(s);
        return `<span style="display:inline-flex;align-items:center;gap:5px;background:${c.bg};color:${c.color};
                    padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;white-space:nowrap;">
                    <i class="fa-solid ${c.icon}" style="font-size:0.72rem;"></i>${s}
                </span>`;
    }

    function changeCell(from, to) {
        return `<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    ${statusPill(from)}
                    <i class="fa-solid fa-arrow-right" style="color:#9ca3af;font-size:0.75rem;"></i>
                    ${statusPill(to)}
                </div>`;
    }

    /* ── Load history from database ── */
    function loadHistory() {
        fetch('fetch_edit_requests.php?tab=' + currentTab)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    allRows = data.data.map(r => ({
                        esic_no:       r.empcode,
                        employee_name: r.empname,
                        date:          r.attendance_date,
                        from_status:   r.current_status || 'N/A',
                        to_status:     r.new_status_name || r.new_status,
                        reason:        r.reason_for_update,
                        status:        r.status ? r.status.toUpperCase() : 'PENDING'
                    }));
                    document.getElementById('pendingCount').textContent = data.pending_count;
                    currentPage = 1;
                    renderTable();
                }
            })
            .catch(err => console.error('Error loading history:', err));
    }

    /* ── Render table with row stagger ── */
    function renderTable() {
        const search   = document.getElementById('searchInput').value.toLowerCase();
        const pageSize = parseInt(document.getElementById('pageSize').value);
        const tbody    = document.getElementById('historyTableBody');

        let filtered = allRows.filter(r =>
            Object.values(r).some(v => String(v).toLowerCase().includes(search))
        );

        const total = filtered.length;
        const start = (currentPage - 1) * pageSize;
        const end   = Math.min(start + pageSize, total);
        const paged = filtered.slice(start, end);

        if (!paged.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="no-data">No data available in table</td></tr>`;
        } else {
            tbody.innerHTML = paged.map((r, idx) => {
                const statusStyle = r.status === 'APPROVED'
                    ? 'background:#d1fae5;color:#065f46;'
                    : r.status === 'REJECTED'
                    ? 'background:#fee2e2;color:#991b1b;'
                    : 'background:#dbeafe;color:#1d4ed8;';
                return `
                <tr class="row-animate" style="animation-delay:${idx * 55}ms">
                    <td style="font-weight:600;">${r.esic_no ?? ''}</td>
                    <td style="font-weight:500;">${r.employee_name ?? ''}</td>
                    <td style="white-space:nowrap;">${r.date ?? ''}</td>
                    <td>${changeCell(r.from_status, r.to_status)}</td>
                    <td>${r.reason ?? ''}</td>
                    <td><span style="display:inline-block;padding:4px 12px;border-radius:20px;
                        font-size:0.78rem;font-weight:700;${statusStyle}">${r.status ?? ''}</span></td>
                </tr>`;
            }).join('');
        }

        document.getElementById('showingText').textContent =
            total ? `Showing ${start + 1} to ${end} of ${total} entries` : 'Showing 0 to 0 of 0 entries';

        document.getElementById('prevBtn').disabled = currentPage <= 1;
        document.getElementById('nextBtn').disabled = end >= total;
    }

    function changePage(dir) {
        currentPage += dir;
        renderTable();
    }

    /* Initial load */
    loadHistory();
</script>
<?php include 'chatbot/chatbot_widget.php'; ?>
</body>
</html>