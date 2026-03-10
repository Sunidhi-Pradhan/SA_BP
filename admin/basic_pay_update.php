<?php
session_start();
if (!isset($_SESSION["user"])) { header("Location: login.php"); exit; }
require "../config.php";

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $basic_vda   = trim($_POST['basic_vda']   ?? '');
    $site_code   = trim($_POST['site_code']   ?? '');
    $designation = trim($_POST['designation'] ?? '');

    if (!$basic_vda || !$site_code || !$designation) {
        $error = "Please fill all required fields.";
    } else {

        /* ========= Upload Supporting PDF ========= */
        $doc_path = '';

        if (!empty($_FILES['support_doc']['name'])) {
            $upload_dir = "../uploads/pdf/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext      = strtolower(pathinfo($_FILES['support_doc']['name'], PATHINFO_EXTENSION));
            $allowed  = ['pdf'];

            if (!in_array($ext, $allowed)) {
                $error = "Only PDF files are allowed for the supporting document.";
            } else {
                $doc_name = time() . "_" . basename($_FILES['support_doc']['name']);
                $doc_path = $upload_dir . $doc_name;
                move_uploaded_file($_FILES['support_doc']['tmp_name'], $doc_path);
            }
        }

        /* ========= Insert into emp_grade ========= */
        if (!$error) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO emp_grade (designation, SiteCode, basic_vda, supporting_doc, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $designation,
                    $site_code,
                    $basic_vda,
                    $doc_path,
                ]);

                $success = "Basic+VDA Pay updated successfully for {$designation} at site {$site_code}.";

            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

$sites = $pdo->query("SELECT SiteCode, SiteName FROM site_master ORDER BY SiteName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Basic Pay Update – Security Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css">
    <style>
        /* ===== RESET ===== */
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }

        /* ===== THEME ===== */
        :root {
            --bg:#f4f6f9; --card:#ffffff; --text:#111827;
            --subtext:#6b7280; --border:#e5e7eb;
            --teal:#0f766e; --teal-dark:#0d5f58; --teal-deep:#0a4f49;
        }
        body.dark {
            --bg:#0b1120; --card:#111827; --text:#e5e7eb;
            --subtext:#9ca3af; --border:#1f2937;
        }

        body.dark .sidebar { background:#0d1526; box-shadow:2px 0 12px rgba(0,0,0,.5); }
        body.dark .sidebar .menu:hover  { background:rgba(255,255,255,.06); }
        body.dark .sidebar .menu.active { background:rgba(255,255,255,.10); }
        body.dark .theme-btn { background:#1e293b; color:#fbbf24; border-color:#334155; }
        body.dark .theme-btn:hover { background:#293548; }
        body.dark .form-card {
            box-shadow:0 4px 20px rgba(15,118,110,.28),0 1px 4px rgba(16,185,129,.14);
            border-color:rgba(15,118,110,.28);
        }
        body.dark .drop-zone { background:rgba(15,118,110,.06); }
        body.dark .drop-zone:hover,
        body.dark .drop-zone.dragover { background:rgba(15,118,110,.12); border-color:var(--teal); }
        body.dark .pdf-hint { color:#6b7280; }

        body { background:var(--bg); color:var(--text); transition:background .3s,color .3s; overflow-x:hidden; }

        /* ===== LAYOUT ===== */
        .dashboard { display:flex; min-height:100vh; }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.5); z-index:998; backdrop-filter:blur(2px);
        }
        .sidebar-overlay.active { display:block; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width:240px; min-width:240px;
            background:var(--teal); color:#fff;
            padding:0; display:flex; flex-direction:column;
            box-shadow:2px 0 8px rgba(0,0,0,.12);
            flex-shrink:0; z-index:999; overflow-y:auto;
            position:relative; transition:transform .3s ease;
        }
        @media (max-width:768px) { .sidebar { -webkit-overflow-scrolling:touch; } }

        .logo { padding:20px 15px; margin-bottom:10px; }
        .logo img {
            max-width:160px; height:auto; display:block; margin:0 auto;
            background:#fff; border-radius:12px; padding:10px 16px;
            box-shadow:0 2px 8px rgba(0,0,0,.15);
        }

        nav { display:flex; flex-direction:column; padding:0 15px; flex:1; }
        .menu {
            display:flex; align-items:center; gap:12px;
            padding:12px 15px; border-radius:6px;
            color:rgba(255,255,255,.9); text-decoration:none;
            font-size:14px; font-weight:400;
            transition:all .25s ease; position:relative;
            margin-bottom:2px; white-space:nowrap;
        }
        .menu .icon {
            font-size:16px; width:20px; display:flex;
            align-items:center; justify-content:center;
            opacity:.95; flex-shrink:0; transition:transform .2s ease;color:#ffffff;
        }
        .menu:hover .icon { transform:scale(1.2); }
        .menu:hover  { background:rgba(255,255,255,.1); color:#fff; }
        .menu.active { background:rgba(255,255,255,.15); color:#fff; font-weight:500; }
        .menu.active::before {
            content:""; position:absolute; left:-15px; top:50%;
            transform:translateY(-50%); width:4px; height:70%;
            background:#fff; border-radius:0 4px 4px 0;
        }
        .menu.logout {
            margin-top:auto; margin-bottom:15px;
            border-top:1px solid rgba(255,255,255,.15); padding-top:15px;
        }

        /* ===== MAIN ===== */
        .main { flex:1; display:flex; flex-direction:column; min-width:0; overflow-x:hidden; }

        /* ===== HEADER ===== */
        header {
            display:flex; align-items:center; gap:14px;
            padding:0 25px; height:62px; background:var(--card);
            box-shadow:0 1px 4px rgba(0,0,0,.08);
            position:sticky; top:0; z-index:50;
            border-bottom:1px solid var(--border); flex-shrink:0;
            animation:headerDrop .4s ease both;
        }
        @keyframes headerDrop {
            from { transform:translateY(-100%); opacity:0; }
            to   { transform:translateY(0);     opacity:1; }
        }
        header h1 { font-size:1.5rem; font-weight:700; color:var(--text); flex:1; text-align:center; }

        .menu-btn {
            background:none; border:none; font-size:22px; cursor:pointer;
            color:var(--text); padding:6px 8px; border-radius:6px;
            display:none; align-items:center; justify-content:center;
            flex-shrink:0; transition:background .2s,transform .2s;
        }
        .menu-btn:hover { background:rgba(0,0,0,.06); transform:rotate(90deg); }

        .theme-btn {
            width:44px; height:44px; border-radius:12px;
            border:1px solid var(--border); background:var(--card);
            color:var(--subtext); font-size:16px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; transition:all .2s; box-shadow:0 1px 4px rgba(0,0,0,.07);
            -webkit-tap-highlight-color:transparent;
        }
        .theme-btn:hover { background:#f3f4f6; color:var(--text); transform:scale(1.08); }
        .theme-btn.active { background:#1e293b; color:#a5b4fc; border-color:#334155; }

        /* ===== PAGE CONTENT ===== */
        .page-content {
            padding:28px 28px 56px;
            display:flex; flex-direction:column; gap:20px;
            width:100%; min-width:0; box-sizing:border-box;
            animation:contentFadeUp .5s .15s ease both;
        }
        @keyframes contentFadeUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ===== PAGE TITLE ROW ===== */
        .page-title-row {
            display:flex; align-items:center; gap:14px;
            animation:fadeUp .4s .2s ease both; opacity:0;
        }
        .page-title-icon {
            width:46px; height:46px; border-radius:12px;
            background:rgba(15,118,110,.1); border:1px solid rgba(15,118,110,.2);
            display:flex; align-items:center; justify-content:center;
            color:var(--teal); font-size:1.1rem; flex-shrink:0;
        }
        body.dark .page-title-icon { background:rgba(15,118,110,.18); }
        .page-title-text h2 { font-size:1.25rem; font-weight:800; color:var(--text); }
        .page-title-text p  { font-size:.82rem; color:var(--subtext); margin-top:2px; }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(14px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ===== FORM CARD ===== */
        .form-card {
            background:var(--card); border-radius:16px;
            border:1px solid rgba(15,118,110,.15);
            box-shadow:0 4px 20px rgba(15,118,110,.12),0 1px 4px rgba(16,185,129,.08);
            overflow:hidden; transition:box-shadow .2s,border-color .2s;
            animation:fadeUp .45s .3s ease both; opacity:0;
        }
        .form-card:hover {
            box-shadow:0 8px 32px rgba(15,118,110,.2),0 2px 10px rgba(16,185,129,.12);
            border-color:rgba(16,185,129,.32);
        }

        .form-card-header {
            display:flex; align-items:center; gap:.5rem;
            padding:1rem 1.4rem; border-bottom:1px solid var(--border);
        }
        .form-card-header i { color:var(--teal); }
        .form-card-header .title { font-size:.92rem; font-weight:700; color:var(--text); }

        /* ===== FORM BODY ===== */
        .form-body { padding:26px 28px 22px; display:flex; flex-direction:column; gap:20px; }

        .fields-grid {
            display:grid;
            grid-template-columns:1fr 1fr 1fr;
            gap:16px;
            align-items:end;
        }

        .form-group { display:flex; flex-direction:column; gap:.35rem; }
        .form-label {
            font-size:.7rem; font-weight:700; color:var(--subtext);
            text-transform:uppercase; letter-spacing:.55px;
        }
        .form-label .req { color:#ef4444; margin-left:2px; }

        .form-control {
            padding:.68rem .95rem; min-height:44px;
            border:1px solid var(--border); border-radius:10px;
            font-size:.9rem; color:var(--text);
            background:var(--bg); font-family:inherit; outline:none; width:100%;
            transition:border-color .2s,box-shadow .2s,background .2s;
            -webkit-tap-highlight-color:transparent;
        }
        .form-control:focus {
            border-color:var(--teal);
            box-shadow:0 0 0 3px rgba(15,118,110,.12);
            background:var(--card);
        }
        select.form-control { cursor:pointer; }

        /* ===== SUPPORTING DOC SECTION ===== */
        .doc-section { display:flex; flex-direction:column; gap:8px; }
        .doc-label {
            font-size:.7rem; font-weight:700; color:var(--subtext);
            text-transform:uppercase; letter-spacing:.55px;
        }

        .drop-zone {
            border:2px dashed var(--border); border-radius:12px;
            background:rgba(15,118,110,.03);
            min-height:130px;
            display:flex; flex-direction:column;
            align-items:center; justify-content:center; gap:10px;
            cursor:pointer; text-align:center;
            transition:border-color .2s,background .2s,transform .15s;
            position:relative; overflow:hidden; padding:24px 20px;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color:var(--teal); background:rgba(15,118,110,.06); transform:translateY(-1px);
        }
        .drop-zone.has-file { border-color:var(--teal); border-style:solid; }
        .drop-zone input[type="file"] {
            position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
        }

        .dz-icon-cloud {
            width:52px; height:52px; border-radius:50%;
            background:rgba(15,118,110,.12); border:2px solid rgba(15,118,110,.2);
            display:flex; align-items:center; justify-content:center;
            color:var(--teal); font-size:1.3rem;
            transition:transform .2s,background .2s;
        }
        .drop-zone:hover .dz-icon-cloud,
        .drop-zone.dragover .dz-icon-cloud { transform:scale(1.1) translateY(-3px); background:rgba(15,118,110,.18); }

        .dz-main-text { font-size:.9rem; font-weight:600; color:var(--text); }
        .dz-main-text span { color:var(--teal); }
        .dz-sub-text  { font-size:.78rem; color:var(--subtext); }

        .pdf-hint {
            display:flex; align-items:center; gap:.35rem;
            font-size:.78rem; color:var(--subtext); margin-top:2px;
        }
        .pdf-hint i { color:#ef4444; font-size:.75rem; }

        .file-name {
            font-size:.8rem; color:var(--teal); font-weight:600;
            display:none; word-break:break-all; margin-top:2px;
        }
        .file-name.visible { display:block; }

        /* ===== FORM FOOTER ===== */
        .form-footer {
            padding:14px 28px 20px;
            border-top:1px solid var(--border);
            display:flex; align-items:center; justify-content:flex-end; gap:.7rem;
            flex-wrap:wrap;
        }

        .btn-reset {
            display:flex; align-items:center; justify-content:center; gap:.4rem;
            padding:.65rem 1.3rem; min-height:44px;
            background:var(--bg); color:var(--subtext);
            border:1px solid var(--border); border-radius:10px;
            font-size:.88rem; font-weight:600; cursor:pointer; font-family:inherit;
            transition:background .2s,transform .15s;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .btn-reset:hover { background:var(--border); transform:translateY(-1px); }
        .btn-reset:active { transform:scale(.97); }

        .btn-submit {
            display:flex; align-items:center; justify-content:center; gap:.45rem;
            padding:.68rem 2rem; min-height:44px;
            background:var(--teal); color:#fff;
            border:none; border-radius:50px;
            font-size:.9rem; font-weight:700; cursor:pointer; font-family:inherit;
            box-shadow:0 3px 12px rgba(15,118,110,.3);
            transition:background .2s,transform .15s,box-shadow .2s;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .btn-submit:hover { background:var(--teal-dark); transform:translateY(-2px); box-shadow:0 6px 20px rgba(15,118,110,.4); }
        .btn-submit:active { background:var(--teal-deep); transform:scale(.97); box-shadow:0 2px 8px rgba(15,118,110,.2); }

        /* Floating submit */
        .btn-submit-float {
            position:fixed; bottom:24px; right:24px; z-index:200;
            display:flex; align-items:center; justify-content:center; gap:.45rem;
            padding:.72rem 1.6rem; min-height:46px;
            background:var(--teal); color:#fff;
            border:none; border-radius:50px; font-size:.9rem; font-weight:700;
            cursor:pointer; font-family:inherit;
            box-shadow:0 4px 18px rgba(15,118,110,.4);
            transition:background .2s,transform .15s,box-shadow .2s,opacity .2s;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
            opacity:0; pointer-events:none;
        }
        .btn-submit-float.show { opacity:1; pointer-events:auto; }
        .btn-submit-float:hover { background:var(--teal-dark); transform:translateY(-3px); box-shadow:0 8px 26px rgba(15,118,110,.45); }
        .btn-submit-float:active { transform:scale(.96); }

        /* ===== RESPONSIVE ===== */
        @media (max-width:992px) {
            .sidebar { width:210px; min-width:210px; }
            .menu { font-size:13px; padding:11px 12px; }
            header h1 { font-size:1.3rem; }
            .page-content { padding:22px 20px 48px; }
            .form-body { padding:22px 22px 18px; }
            .form-footer { padding:12px 22px 18px; }
            .fields-grid { grid-template-columns:1fr 1fr; }
        }

        @media (max-width:768px) {
            .menu-btn { display:flex; }
            .sidebar {
                position:fixed; top:0; left:0; height:100vh;
                transform:translateX(-100%); animation:none;
                width:260px; min-width:260px;
                box-shadow:4px 0 20px rgba(0,0,0,.25);
            }
            .sidebar.open { transform:translateX(0); }
            header { padding:0 14px; height:56px; }
            header h1 { font-size:1.1rem; }
            .theme-btn { width:38px; height:38px; font-size:14px; }
            .page-content { padding:16px 12px 60px; gap:14px; }
            .form-body { padding:16px 16px 14px; gap:16px; }
            .fields-grid { grid-template-columns:1fr 1fr; gap:12px; }
            .form-footer { padding:12px 16px 16px; }
            .btn-reset  { flex:1; }
            .btn-submit { flex:1; }
            .btn-submit-float { bottom:16px; right:16px; }
            .drop-zone { min-height:110px; padding:18px 16px; gap:8px; }
            .dz-icon-cloud { width:44px; height:44px; font-size:1.1rem; }
        }

        @media (max-width:480px) {
            header { padding:0 10px; height:52px; }
            header h1 { font-size:.92rem; }
            .menu-btn { font-size:19px; }
            .theme-btn { width:34px; height:34px; font-size:13px; border-radius:8px; }
            .page-content { padding:12px 10px 60px; gap:12px; }
            .page-title-icon { width:40px; height:40px; font-size:1rem; }
            .page-title-text h2 { font-size:1.1rem; }
            .form-card { border-radius:12px; }
            .form-body { padding:14px 14px 12px; gap:14px; }
            .fields-grid { grid-template-columns:1fr; gap:10px; }
            .form-control { font-size:.85rem; min-height:42px; }
            .form-footer { padding:12px 14px 16px; gap:.5rem; }
            .btn-reset  { font-size:.83rem; min-height:42px; padding:.6rem .9rem; }
            .btn-submit { font-size:.83rem; min-height:42px; padding:.6rem 1.3rem; }
            .drop-zone { min-height:100px; padding:16px 12px; }
            .dz-main-text { font-size:.84rem; }
            .dz-sub-text  { font-size:.74rem; }
            .btn-submit-float { padding:.6rem 1.1rem; font-size:.82rem; min-height:42px; right:12px; bottom:12px; }
        }

        @media (max-width:360px) {
            header h1 { font-size:.82rem; }
            .page-content { padding:10px 8px 60px; }
            .form-body { padding:12px 12px 10px; }
            .form-footer { padding:10px 12px 14px; }
            .btn-reset, .btn-submit { font-size:.8rem; min-height:40px; }
        }

        /* ===== SWEETALERT2 CUSTOM ===== */
        .swal-popup-custom {
            border-radius: 16px !important;
            padding: 28px 24px !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18) !important;
            font-family: "Segoe UI", sans-serif !important;
        }
        .swal-title-custom { font-size: 1.2rem !important; font-weight: 800 !important; }
        .swal-confirm-btn, .swal-cancel-btn {
            border-radius: 10px !important;
            font-size: 0.88rem !important;
            font-weight: 700 !important;
            padding: 10px 22px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 7px !important;
        }
        .swal2-timer-progress-bar { background: #0f766e !important; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../assets/logo/images.png" alt="MCL Logo">
        </div>
        <nav>
            <a href="../dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="../user.php" class="menu">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <span>Add Users</span>
            </a>
            <a href="../employees.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-plus"></i></span>
                <span>Add Employee</span>
            </a>
            <a href="../admin/basic_pay_update.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>
            <a href="../admin/add_extra_manpower.php" class="menu">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>
            <a href="../unlock/unlock.php" class="menu">
                <span class="icon"><i class="fa-solid fa-lock-open"></i></span>
                <span>Unlock Attendance</span>
            </a>
            <a href="../admin/attendance_request.php" class="menu">
                <span class="icon"><i class="fa-solid fa-file-signature"></i></span>
                <span>Attendance Request</span>
            </a>
            <a href="../download_attendance/download_attendance.php" class="menu">
                <span class="icon"><i class="fa-solid fa-download"></i></span>
                <span>Download Attendance</span>
            </a>
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Wage Report</span>
            </a>
            <a href="../admin/monthly_attendance.php" class="menu">
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

    <!-- ===== MAIN ===== -->
    <main class="main">

        <header>
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1>Security Billing Portal</h1>
            <button class="theme-btn" id="themeToggle" title="Toggle dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <div class="page-content">

            <?php /* Success/error shown via SweetAlert2 below */ ?>

            <!-- Page title -->
            <div class="page-title-row">
                <div class="page-title-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="page-title-text">
                    <h2>Basic+VDA Pay Change</h2>
                    <p>Create New Basic+VDA for Employee</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa-solid fa-user-tie"></i>
                    <span class="title">Basic+VDA Amount</span>
                </div>

                <form method="POST" enctype="multipart/form-data" id="payForm">
                    <div class="form-body">

                        <!-- 3-column fields -->
                        <div class="fields-grid">

                            <div class="form-group">
                                <label class="form-label">Basic+VDA Amount <span class="req">*</span></label>
                                <div style="position:relative;">
    <span style="position:absolute;left:.95rem;top:50%;transform:translateY(-50%);
                 color:var(--subtext);font-size:.9rem;font-weight:600;pointer-events:none;">₹</span>
    <input
        type="number"
        class="form-control"
        name="basic_vda"
        placeholder="Enter amount"
        min="1"
        step="1"
        style="padding-left:1.85rem;"
        value="<?= htmlspecialchars($_POST['basic_vda'] ?? '') ?>"
        required
    >
</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Site <span class="req">*</span></label>
                                <select class="form-control" name="site_code" required>
                                    <option value="">-- Select Site --</option>
                                    <?php foreach ($sites as $s): ?>
                                    <option value="<?= htmlspecialchars($s['SiteCode']) ?>"
                                        <?= (isset($_POST['site_code']) && $_POST['site_code'] === $s['SiteCode']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['SiteName']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Designation <span class="req">*</span></label>
                                <select class="form-control" name="designation" required>
                                    <option value="">-- Select Designation --</option>
                                    <?php
                                    $designations = ['Security Guard','Security Supervisor','Gun Man','Head Guard','Fire Guard','Lady Guard','Dog Handler','Driver cum Guard'];
                                    foreach ($designations as $d): ?>
                                    <option <?= (isset($_POST['designation']) && $_POST['designation'] === $d) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div><!-- /.fields-grid -->

                        <!-- Supporting Document -->
                        <div class="doc-section">
                            <div class="doc-label">Supporting Document</div>
                            <div class="drop-zone" id="dropZone">
                                <input type="file" name="support_doc" accept=".pdf" id="fileInput">
                                <div class="dz-icon-cloud">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <div class="dz-main-text">Drop files here or <span>click to upload</span></div>
                                <div class="dz-sub-text">Optional · Supports PDF Only</div>
                            </div>
                            <div class="pdf-hint"><i class="fa-solid fa-file-pdf"></i> PDF Only</div>
                            <div class="file-name" id="fileName"></div>
                        </div>

                    </div><!-- /.form-body -->

                    <div class="form-footer">
                        <button type="reset" class="btn-reset" onclick="resetFile()">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </button>
                        <button type="button" class="btn-submit" onclick="confirmSubmit()">
                            <i class="fa-solid fa-circle-check"></i> Submit
                        </button>
                    </div>
                </form>
            </div><!-- /.form-card -->

        </div><!-- /.page-content -->
    </main>
</div>

<!-- Floating submit -->
<button class="btn-submit-float" id="floatSubmit" onclick="confirmSubmit()">
    <i class="fa-solid fa-circle-check"></i> Submit
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.all.min.js"></script>
<script>
/* ── Sidebar ── */
const menuBtn = document.getElementById('menuBtn');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');

const openSidebar  = () => { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow='hidden'; };
const closeSidebar = () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; };

menuBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar .menu').forEach(l =>
    l.addEventListener('click', () => { if (window.innerWidth<=768) closeSidebar(); })
);
window.addEventListener('resize', () => {
    if (window.innerWidth>768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; }
});

/* ── Theme ── */
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = themeToggle.querySelector('i');
function applyTheme(dark) {
    document.body.classList.toggle('dark', dark);
    themeToggle.classList.toggle('active', dark);
    themeIcon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
}
applyTheme(localStorage.getItem('theme') === 'dark');
themeToggle.addEventListener('click', () => {
    const d = !document.body.classList.contains('dark');
    applyTheme(d); localStorage.setItem('theme', d ? 'dark' : 'light');
});

/* ── File upload / drag-drop ── */
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('fileInput');
const fileNameEl = document.getElementById('fileName');

function setFile(file) {
    if (!file) return;
    dropZone.classList.add('has-file');
    fileNameEl.textContent = '📎 ' + file.name;
    fileNameEl.classList.add('visible');
}

fileInput.addEventListener('change', () => setFile(fileInput.files[0]));

dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('dragover');
    const f = e.dataTransfer.files[0];
    if (f) {
        try { const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files; } catch(ex){}
        setFile(f);
    }
});

function resetFile() {
    dropZone.classList.remove('has-file','dragover');
    fileNameEl.textContent = '';
    fileNameEl.classList.remove('visible');
}

/* ── Floating submit ── */
const floatBtn   = document.getElementById('floatSubmit');
const formFooter = document.querySelector('.form-footer');
new IntersectionObserver(entries => {
    floatBtn.classList.toggle('show', !entries[0].isIntersecting);
}, { threshold:0 }).observe(formFooter);

/* ── SweetAlert2 Confirm Submit ── */
function confirmSubmit() {
    const form = document.getElementById('payForm');

    // Run HTML5 native validation first
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const amount      = document.querySelector('[name="basic_vda"] option:checked')?.text  || '—';
    const site        = document.querySelector('[name="site_code"] option:checked')?.text  || '—';
    const designation = document.querySelector('[name="designation"] option:checked')?.text || '—';
    const docFile     = fileInput?.files[0]?.name || '<span style="color:#6b7280;font-style:italic;">No file selected (optional)</span>';
    const isDark      = document.body.classList.contains('dark');

    Swal.fire({
        title: 'Confirm Submission',
        html: `
            <div style="text-align:left;font-size:0.9rem;line-height:1.9;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">💰 Amount</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${amount}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📍 Site</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${site}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">🎖️ Designation</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${designation}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📄 Document</td>
                        <td style="font-weight:600;color:${isDark?'#e5e7eb':'#111827'}">${docFile}</td>
                    </tr>
                </table>
                <p style="margin-top:14px;font-size:0.8rem;color:#6b7280;text-align:center;">
                    Please review the details above before confirming.
                </p>
            </div>
        `,
        icon: 'question',
        iconColor: '#0f766e',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-circle-check"></i> Yes, Submit',
        cancelButtonText:  '<i class="fa-solid fa-xmark"></i> Cancel',
        confirmButtonColor: '#0f766e',
        cancelButtonColor:  '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        background: isDark ? '#111827' : '#ffffff',
        color:      isDark ? '#e5e7eb' : '#111827',
        customClass: {
            popup:         'swal-popup-custom',
            confirmButton: 'swal-confirm-btn',
            cancelButton:  'swal-cancel-btn',
            title:         'swal-title-custom',
        }
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Submitting...',
                html: 'Please wait while we save the data.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            form.submit();
        }
    });
}

/* ── SweetAlert2 for PHP success / error ── */
<?php if ($success): ?>
window.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: '<?= addslashes($success) ?>',
        confirmButtonColor: '#0f766e',
        confirmButtonText: 'Great!',
        background: document.body.classList.contains('dark') ? '#111827' : '#ffffff',
        color:      document.body.classList.contains('dark') ? '#e5e7eb' : '#111827',
        iconColor: '#0f766e',
        timer: 4000,
        timerProgressBar: true,
    });
});
<?php elseif ($error): ?>
window.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: 'error',
        title: 'Oops!',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Fix & Retry',
        background: document.body.classList.contains('dark') ? '#111827' : '#ffffff',
        color:      document.body.classList.contains('dark') ? '#e5e7eb' : '#111827',
    });
});
<?php endif; ?>
</script>
</body>
</html>