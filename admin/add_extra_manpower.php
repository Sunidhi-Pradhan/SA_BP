<?php
session_start();
if (!isset($_SESSION["user"])) { header("Location: login.php"); exit; }

require "../config.php";

$success = "";
$error   = "";

echo "<pre>";
print_r($_FILES);
exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $site_code   = $_POST['site_code'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $count       = (int)($_POST['count'] ?? 0);
    $efile_no    = trim($_POST['efile_no'] ?? '');
    $uploaded_by = $_SESSION['user'];

    if (!$site_code || !$designation || !$count || !$efile_no) {
        $error = "Please fill all required fields.";
    } else {

        /* ========= Upload PDF ========= */

        $pdf_path = '';

        if (!empty($_FILES['pdf_doc']['name'])) {

            $upload_dir = "../uploads/pdf/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $pdf_name = time() . "_" . basename($_FILES['pdf_doc']['name']);
            $pdf_path = $upload_dir . $pdf_name;

            move_uploaded_file($_FILES['pdf_doc']['tmp_name'], $pdf_path);
        }


        /* ========= Read Excel / CSV + Check ESIC duplicates ========= */

        $employees = [];
        $esic_list = [];

        if (!empty($_FILES['csv_data']['tmp_name']) && !empty($_FILES['csv_data']['name'])) {

            $originalName = strtolower($_FILES['csv_data']['name']);
            $tmpPath      = $_FILES['csv_data']['tmp_name'];
            $ext          = pathinfo($originalName, PATHINFO_EXTENSION);

            // ── Route by file extension ──────────────────────────────
            if ($ext === 'csv') {

                // ── Plain CSV (fgetcsv) ──────────────────────────────
                $handle = fopen($tmpPath, "r");

                if ($handle !== FALSE) {

                    $headers = fgetcsv($handle);

                    if ($headers) {
                        // Strip UTF-8 BOM and whitespace
                        $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
                        $headers    = array_map('trim', $headers);
                    }

                    while (($row = fgetcsv($handle)) !== FALSE) {

                        if (count($row) !== count($headers)) continue;

                        $data = array_combine($headers, $row);
                        $esic = trim($data['ESIC_NO'] ?? '');

                        if (!$esic) continue;

                        if (in_array($esic, $esic_list)) {
                            $error = "Duplicate ESIC_NO found: " . $esic;
                            break;
                        }

                        $esic_list[] = $esic;
                        $employees[] = $data;
                    }

                    fclose($handle);
                }

            } elseif (in_array($ext, ['xlsx', 'xls'])) {

                // ── Excel via PhpSpreadsheet ─────────────────────────
                $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';

                if (!file_exists($autoloaderPath)) {
                    $error = "PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet";
                } else {

                    require_once $autoloaderPath;

                    try {
                        $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpPath);
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($tmpPath);
                        $sheet       = $spreadsheet->getActiveSheet();
                        $rows        = $sheet->toArray(null, true, true, false);

                        // First row = headers
                        $headers = array_map('trim', $rows[0] ?? []);

                        // Remove null/empty header columns
                        $headers = array_filter($headers, fn($h) => $h !== null && $h !== '');
                        $colCount = count($headers);

                        foreach (array_slice($rows, 1) as $row) {

                            // Trim to number of valid header columns
                            $row = array_slice($row, 0, $colCount);

                            // Skip fully empty rows
                            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                                continue;
                            }

                            // Pad row if shorter than headers
                            while (count($row) < $colCount) {
                                $row[] = '';
                            }

                            $data = array_combine(array_values($headers), $row);
                            $esic = trim((string)($data['ESIC_NO'] ?? ''));

                            if (!$esic) continue;

                            if (in_array($esic, $esic_list)) {
                                $error = "Duplicate ESIC_NO found: " . $esic;
                                break;
                            }

                            $esic_list[] = $esic;
                            $employees[] = $data;
                        }

                    } catch (\Exception $e) {
                        $error = "Failed to read Excel file: " . $e->getMessage();
                    }
                }

            } else {
                $error = "Unsupported file type. Please upload a .csv, .xlsx, or .xls file.";
            }
        }


        /* ========= Insert only if no error ========= */

        if (!$error) {

            $employee_json = json_encode($employees, JSON_UNESCAPED_UNICODE);

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO extra_manpower_assign
                    (site_code, designation, manpower_count, eoffice_file_no, supporting_pdf, employee_json, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $site_code,
                    $designation,
                    $count,
                    $efile_no,
                    $pdf_path,
                    $employee_json,
                    $uploaded_by
                ]);

                $success = "Extra manpower assigned successfully.";

            } catch (Exception $e) {

                $error = "Database error: " . $e->getMessage();

            }
        }
    }
}

/* Fetch sites */
$sites = $pdo->query("
    SELECT SiteCode, SiteName 
    FROM site_master 
    ORDER BY SiteName
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Extra Manpower – Security Billing Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css">
    <style>
        /* ===== RESET ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }

        /* ===== THEME VARIABLES ===== */
        :root {
            --bg:#f4f6f9; --card:#ffffff; --text:#111827;
            --subtext:#6b7280; --border:#e5e7eb;
            --teal:#0f766e; --teal-dark:#0d5f58; --teal-deep:#0a4f49;
            --radius-card:16px; --radius-input:10px;
        }
        body.dark {
            --bg:#0b1120; --card:#111827; --text:#e5e7eb;
            --subtext:#9ca3af; --border:#1f2937;
        }

        /* ===== DARK MODE — SIDEBAR ===== */
        body.dark .sidebar { background:#0d1526; box-shadow:2px 0 12px rgba(0,0,0,0.5); }
        body.dark .sidebar .menu:hover  { background:rgba(255,255,255,0.06); }
        body.dark .sidebar .menu.active { background:rgba(255,255,255,0.10); }
        body.dark .theme-btn { background:#1e293b; color:#fbbf24; border-color:#334155; }
        body.dark .theme-btn:hover { background:#293548; }
        body.dark .form-card {
            box-shadow:0 4px 20px rgba(15,118,110,0.28), 0 1px 4px rgba(16,185,129,0.14);
            border-color:rgba(15,118,110,0.28);
        }
        body.dark .drop-zone { background:rgba(15,118,110,0.06); }
        body.dark .drop-zone:hover, body.dark .drop-zone.dragover { background:rgba(15,118,110,0.12); border-color:var(--teal); }
        body.dark .sample-btn { background:#1e293b; color:#60a5fa; border-color:#334155; }
        body.dark .sample-btn:hover { background:#263347; }

        body {
            background:var(--bg); color:var(--text);
            transition:background .3s,color .3s; overflow-x:hidden;
        }

        /* ===== LAYOUT ===== */
        .dashboard { display:flex; min-height:100vh; }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.5); z-index:998;
            backdrop-filter:blur(2px);
        }
        .sidebar-overlay.active { display:block; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width:240px; min-width:240px;
            background:var(--teal); color:#fff;
            padding:0; display:flex; flex-direction:column;
            box-shadow:2px 0 8px rgba(0,0,0,0.12);
            flex-shrink:0; z-index:999; overflow-y:auto;
            position:relative; transition:transform .3s ease;
        }
        @media (max-width:768px) { .sidebar { overflow-y:auto; -webkit-overflow-scrolling:touch; } }

        .logo { padding:20px 15px; margin-bottom:10px; }
        .logo img {
            max-width:160px; height:auto; display:block;
            margin:0 auto; background:#fff; border-radius:12px;
            padding:10px 16px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
        }

        nav { display:flex; flex-direction:column; gap:0; padding:0 15px; flex:1; }
        .menu {
            display:flex; align-items:center; gap:12px;
            padding:12px 15px; border-radius:6px;
            color:rgba(255,255,255,0.9); text-decoration:none;
            font-size:14px; font-weight:400;
            transition:all .25s ease; position:relative;
            margin-bottom:2px; white-space:nowrap;
        }
        .menu .icon {
            font-size:16px; width:20px; display:flex;
            align-items:center; justify-content:center;
            opacity:.95; flex-shrink:0; transition:transform .2s ease;
        }
        .menu:hover .icon { transform:scale(1.2); }
        .menu:hover  { background:rgba(255,255,255,0.1); color:#fff; }
        .menu.active { background:rgba(255,255,255,0.15); color:#fff; font-weight:500; }
        .menu.active::before {
            content:""; position:absolute; left:-15px; top:50%;
            transform:translateY(-50%); width:4px; height:70%;
            background:#fff; border-radius:0 4px 4px 0;
        }
        .menu.logout {
            margin-top:auto; margin-bottom:15px;
            border-top:1px solid rgba(255,255,255,0.15); padding-top:15px;
        }

        /* ===== MAIN ===== */
        .main { flex:1; display:flex; flex-direction:column; min-width:0; overflow-x:hidden; }

        /* ===== HEADER ===== */
        header {
            display:flex; align-items:center; gap:14px;
            padding:0 25px; height:62px;
            background:var(--card);
            box-shadow:0 1px 4px rgba(0,0,0,0.08);
            position:sticky; top:0; z-index:50;
            border-bottom:1px solid var(--border);
            flex-shrink:0; animation:headerDrop .4s ease both;
        }
        @keyframes headerDrop {
            from { transform:translateY(-100%); opacity:0; }
            to   { transform:translateY(0);     opacity:1; }
        }
        header h1 { font-size:1.5rem; font-weight:700; color:var(--text); flex:1; text-align:center; }

        /* Hamburger */
        .menu-btn {
            background:none; border:none; font-size:22px; cursor:pointer;
            color:var(--text); padding:6px 8px; border-radius:6px;
            display:none; align-items:center; justify-content:center;
            flex-shrink:0; transition:background .2s,transform .2s;
        }
        .menu-btn:hover { background:rgba(0,0,0,0.06); transform:rotate(90deg); }

        /* Theme btn */
        .theme-btn {
            width:44px; height:44px; border-radius:12px;
            border:1px solid var(--border); background:var(--card);
            color:var(--subtext); font-size:16px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; transition:background .2s,color .2s,border-color .2s,transform .2s;
            box-shadow:0 1px 4px rgba(0,0,0,0.07);
        }
        .theme-btn:hover { background:#f3f4f6; color:var(--text); transform:scale(1.08); }
        .theme-btn.active { background:#1e293b; color:#a5b4fc; border-color:#334155; }

        /* ===== PAGE CONTENT ===== */
        .page-content {
            padding:28px 28px 48px;
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
            background:var(--card); border-radius:var(--radius-card);
            border:1px solid rgba(15,118,110,0.15);
            box-shadow:0 4px 20px rgba(15,118,110,0.12), 0 1px 4px rgba(16,185,129,0.08);
            overflow:hidden;
            transition:box-shadow .2s,border-color .2s;
            animation:fadeUp .45s .3s ease both; opacity:0;
        }
        .form-card:hover {
            box-shadow:0 8px 32px rgba(15,118,110,0.2), 0 2px 10px rgba(16,185,129,0.12);
            border-color:rgba(16,185,129,0.32);
        }

        .form-card-header {
            display:flex; align-items:center; gap:.5rem;
            padding:1rem 1.4rem; border-bottom:1px solid var(--border);
        }
        .form-card-header i { color:var(--teal); }
        .form-card-header .title { font-size:.92rem; font-weight:700; color:var(--text); }

        /* ===== FORM BODY ===== */
        .form-body { padding:24px 28px 20px; }

        /* Grid: 4 columns desktop, responsive */
        .form-grid-4 {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:16px;
        }
        .form-grid-2 {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
            margin-top:18px;
        }
        .span-full { grid-column:1/-1; }

        .form-group { display:flex; flex-direction:column; gap:.35rem; }
        .form-label {
            font-size:.7rem; font-weight:700; color:var(--subtext);
            text-transform:uppercase; letter-spacing:.55px;
        }
        .form-label .req { color:#ef4444; margin-left:2px; }

        .form-control {
            padding:.65rem .9rem; min-height:44px;
            border:1px solid var(--border); border-radius:var(--radius-input);
            font-size:.9rem; color:var(--text);
            background:var(--bg); font-family:inherit; outline:none;
            transition:border-color .2s,box-shadow .2s,background .2s;
            -webkit-tap-highlight-color:transparent;
            width:100%;
        }
        .form-control:focus {
            border-color:var(--teal);
            box-shadow:0 0 0 3px rgba(15,118,110,.12);
            background:var(--card);
        }
        select.form-control { cursor:pointer; }

        /* ===== UPLOAD ZONES ===== */
        .upload-section { margin-top:20px; display:flex; flex-direction:column; gap:16px; }

        .upload-group { display:flex; flex-direction:column; gap:.35rem; }
        .upload-label {
            font-size:.7rem; font-weight:700; color:var(--subtext);
            text-transform:uppercase; letter-spacing:.55px;
        }
        .upload-label .req { color:#ef4444; margin-left:2px; }

        .drop-zone {
            border:2px dashed var(--border); border-radius:12px;
            background:rgba(15,118,110,0.03);
            padding:18px 20px;
            display:flex; align-items:center; gap:14px;
            cursor:pointer; transition:border-color .2s,background .2s,transform .15s;
            position:relative; overflow:hidden;
            -webkit-tap-highlight-color:transparent;
            touch-action:manipulation;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color:var(--teal);
            background:rgba(15,118,110,0.06);
            transform:translateY(-1px);
        }
        .drop-zone.has-file { border-color:var(--teal); border-style:solid; }
        .drop-zone input[type="file"] {
            position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
        }

        /* PDF drop zone – compact single row */
        .drop-zone.pdf-zone { padding:14px 18px; }
        .drop-zone.pdf-zone .dz-icon {
            width:36px; height:36px; border-radius:8px;
            background:#fee2e2; color:#ef4444;
            display:flex; align-items:center; justify-content:center;
            font-size:1rem; flex-shrink:0;
        }
        .drop-zone.pdf-zone .dz-text { font-size:.87rem; color:var(--subtext); font-weight:500; }
        .drop-zone.pdf-zone .dz-text span { color:var(--teal); font-weight:700; }

        /* CSV/Excel drop zone – tall centred */
        .drop-zone.csv-zone {
            flex-direction:column; align-items:center; justify-content:center;
            padding:40px 24px; gap:12px; min-height:160px;
            text-align:center;
        }
        .drop-zone.csv-zone .dz-icon-big { font-size:2.4rem; color:var(--teal); line-height:1; }
        .drop-zone.csv-zone .dz-title    { font-size:.92rem; font-weight:600; color:var(--text); }
        .drop-zone.csv-zone .dz-hint     { font-size:.78rem; color:var(--subtext); }

        /* File name display */
        .file-name {
            font-size:.8rem; color:var(--teal); font-weight:600;
            margin-top:4px; display:none; word-break:break-all;
        }
        .file-name.visible { display:block; }

        /* ===== SAMPLE CSV BTN ===== */
        .sample-btn {
            display:inline-flex; align-items:center; gap:.45rem;
            padding:.5rem 1.1rem; min-height:38px;
            background:var(--bg); color:#2563eb;
            border:1px solid var(--border); border-radius:8px;
            font-size:.82rem; font-weight:700;
            cursor:pointer; font-family:inherit; text-decoration:none;
            transition:background .2s,transform .15s,box-shadow .15s;
            -webkit-tap-highlight-color:transparent;
            touch-action:manipulation;
            align-self:center;
        }
        .sample-btn:hover { background:#eff6ff; transform:translateY(-1px); box-shadow:0 2px 8px rgba(37,99,235,.12); }
        .sample-btn:active { transform:scale(.97); }

        /* ===== ALERT MESSAGES ===== */
        .alert {
            padding:.85rem 1.1rem; border-radius:10px;
            font-size:.87rem; font-weight:600;
            display:flex; align-items:center; gap:.6rem;
            animation:fadeUp .35s ease both;
        }
        .alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        body.dark .alert-success { background:#064e3b; color:#6ee7b7; border-color:#065f46; }
        body.dark .alert-error   { background:#450a0a; color:#f87171; border-color:#991b1b; }

        /* ===== FORM FOOTER ===== */
        .form-footer {
            padding:16px 28px 22px;
            border-top:1px solid var(--border);
            display:flex; align-items:center; justify-content:flex-end; gap:.7rem;
            flex-wrap:wrap;
        }

        /* Reset btn */
        .btn-reset {
            display:flex; align-items:center; justify-content:center; gap:.4rem;
            padding:.65rem 1.3rem; min-height:44px;
            background:var(--bg); color:var(--subtext);
            border:1px solid var(--border); border-radius:10px;
            font-size:.88rem; font-weight:600;
            cursor:pointer; font-family:inherit;
            transition:background .2s,transform .15s;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .btn-reset:hover { background:var(--border); transform:translateY(-1px); }
        .btn-reset:active { transform:scale(.97); }

        /* Submit btn */
        .btn-submit {
            display:flex; align-items:center; justify-content:center; gap:.45rem;
            padding:.68rem 1.8rem; min-height:44px;
            background:var(--teal); color:#fff;
            border:none; border-radius:10px;
            font-size:.9rem; font-weight:700;
            cursor:pointer; font-family:inherit;
            box-shadow:0 3px 12px rgba(15,118,110,.28);
            transition:background .2s,transform .15s,box-shadow .2s;
            -webkit-tap-highlight-color:transparent; touch-action:manipulation;
        }
        .btn-submit:hover { background:var(--teal-dark); transform:translateY(-2px); box-shadow:0 6px 20px rgba(15,118,110,.38); }
        .btn-submit:active { background:var(--teal-deep); transform:scale(.97) translateY(0); box-shadow:0 2px 8px rgba(15,118,110,.2); }

        /* Floating submit */
        .btn-submit-float {
            position:fixed; bottom:24px; right:24px; z-index:200;
            display:flex; align-items:center; justify-content:center; gap:.45rem;
            padding:.72rem 1.6rem; min-height:46px;
            background:var(--teal); color:#fff;
            border:none; border-radius:50px;
            font-size:.9rem; font-weight:700;
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

        /* Tablet */
        @media (max-width:992px) {
            .sidebar { width:210px; min-width:210px; }
            .menu { font-size:13px; padding:11px 12px; }
            header h1 { font-size:1.3rem; }
            .page-content { padding:22px 20px 40px; }
            .form-body { padding:20px 22px 16px; }
            .form-footer { padding:14px 22px 18px; }
            .form-grid-4 { grid-template-columns:repeat(2,1fr); }
        }

        /* Mobile ≤768px */
        @media (max-width:768px) {
            .menu-btn { display:flex; }
            .sidebar {
                position:fixed; top:0; left:0; height:100vh;
                transform:translateX(-100%); animation:none;
                width:260px; min-width:260px;
                box-shadow:4px 0 20px rgba(0,0,0,0.25);
            }
            .sidebar.open { transform:translateX(0); }
            header { padding:0 14px; height:56px; }
            header h1 { font-size:1.1rem; }
            .theme-btn { width:38px; height:38px; font-size:14px; }
            .page-content { padding:16px 12px 60px; gap:14px; }
            .form-body { padding:16px 16px 14px; }
            .form-grid-4 { grid-template-columns:1fr 1fr; gap:12px; }
            .form-grid-2 { grid-template-columns:1fr; gap:12px; margin-top:12px; }
            .form-footer { padding:12px 16px 16px; }
            .btn-reset  { flex:1; }
            .btn-submit { flex:1; }
            .btn-submit-float { bottom:16px; right:16px; padding:.65rem 1.3rem; font-size:.85rem; }
            .drop-zone.csv-zone { min-height:130px; padding:28px 16px; }
            .drop-zone.csv-zone .dz-icon-big { font-size:2rem; }
        }

        /* Mobile portrait ≤480px */
        @media (max-width:480px) {
            header { padding:0 10px; height:52px; }
            header h1 { font-size:.92rem; }
            .menu-btn { font-size:19px; }
            .theme-btn { width:34px; height:34px; font-size:13px; border-radius:8px; }
            .page-content { padding:12px 10px 60px; gap:12px; }
            .page-title-icon { width:40px; height:40px; font-size:1rem; }
            .page-title-text h2 { font-size:1.1rem; }
            .form-card { border-radius:12px; }
            .form-body { padding:14px 14px 12px; }
            .form-grid-4 { grid-template-columns:1fr; gap:10px; }
            .form-grid-2 { grid-template-columns:1fr; gap:10px; margin-top:10px; }
            .form-control { font-size:.85rem; min-height:42px; }
            .form-footer { padding:12px 14px 16px; gap:.5rem; }
            .btn-reset  { font-size:.83rem; min-height:42px; padding:.6rem .9rem; }
            .btn-submit { font-size:.83rem; min-height:42px; padding:.6rem 1.2rem; }
            .drop-zone.pdf-zone { padding:12px 14px; }
            .drop-zone.csv-zone { min-height:115px; padding:22px 12px; gap:8px; }
            .drop-zone.csv-zone .dz-icon-big { font-size:1.8rem; }
            .drop-zone.csv-zone .dz-title { font-size:.85rem; }
            .drop-zone.csv-zone .dz-hint  { font-size:.74rem; }
            .btn-submit-float { padding:.6rem 1.1rem; font-size:.82rem; min-height:42px; right:12px; bottom:12px; }
        }

        /* Very small ≤360px */
        @media (max-width:360px) {
            header h1 { font-size:.82rem; }
            .page-content { padding:10px 8px 60px; }
            .form-body { padding:12px 12px 10px; }
            .form-footer { padding:10px 12px 14px; }
            .btn-reset, .btn-submit { font-size:.8rem; min-height:40px; }
        }
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
            <a href="dashboard.php" class="menu">
                <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="user.php" class="menu">
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
            <a href="extra_manpower.php" class="menu active">
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
                <div class="page-title-icon"><i class="fa-solid fa-user-clock"></i></div>
                <div class="page-title-text">
                    <h2>Assign Extra Manpower</h2>
                    <p>Add extra manpower</p>
                </div>
            </div>

            <!-- Form card -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa-solid fa-user-plus"></i>
                    <span class="title">Assign Extra Manpower</span>
                </div>

                <form method="POST" enctype="multipart/form-data" id="empForm">
                    <div class="form-body">

                        <!-- Row 1: 4 fields -->
                        <div class="form-grid-4">

                            <div class="form-group">
                                <label class="form-label">Site Location <span class="req">*</span></label>
                                <select class="form-control" name="site_code" required>
                                    <option value="">Select Site</option>
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
                                    <option value="">Select Designation</option>
                                    <?php
                                    $designations = ['Security Guard','Security Supervisor','Gun Man','Fire Guard','Lady Guard','Dog Handler','Driver cum Guard'];
                                    foreach ($designations as $d):
                                    ?>
                                    <option <?= (isset($_POST['designation']) && $_POST['designation'] === $d) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Additional Manpower Count <span class="req">*</span></label>
                                <input class="form-control" type="number" name="count" min="1"
                                    value="<?= htmlspecialchars($_POST['count'] ?? '0') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">E-office File No. <span class="req">*</span></label>
                                <input class="form-control" type="text" name="efile_no"
                                    placeholder="File reference"
                                    value="<?= htmlspecialchars($_POST['efile_no'] ?? '') ?>" required>
                            </div>

                        </div><!-- /.form-grid-4 -->

                        <!-- Upload zones -->
                        <div class="upload-section">

                            <!-- PDF upload -->
                            <div class="upload-group">
                                <div class="upload-label">Supporting Doc (PDF) <span class="req">*</span></div>
                                <div class="drop-zone pdf-zone" id="pdfZone">
                                    <input type="file" name="pdf_doc" accept=".pdf" id="pdfInput">
                                    <div class="dz-icon"><i class="fa-solid fa-file-pdf"></i></div>
                                    <div class="dz-text"><span>Upload PDF Only</span> — or drag &amp; drop here</div>
                                </div>
                                <div class="file-name" id="pdfName"></div>
                            </div>

                            <!-- CSV/Excel upload -->
                            <div class="upload-group">
                                <div class="upload-label">Upload Employee Data In (Excel / CSV) <span class="req">*</span></div>
                                <div class="drop-zone csv-zone" id="csvZone">
                                    <input type="file" name="csv_data" accept=".csv,.xlsx,.xls" id="csvInput">
                                    <div class="dz-icon-big"><i class="fa-solid fa-upload"></i></div>
                                    <div class="dz-title">Upload Employee Master Excel / CSV</div>
                                    <div class="dz-hint">Upload employee data in the provided sample format (Excel/CSV)</div>
                                </div>
                                <div class="file-name" id="csvName"></div>
                            </div>

                            <!-- Sample CSV button -->
                            <a class="sample-btn" href="../assets/samplecsv.xlsx" download>
                                <i class="fa-solid fa-arrow-down-to-line"></i>
                                Sample CSV
                            </a>

                        </div><!-- /.upload-section -->

                    </div><!-- /.form-body -->

                    <div class="form-footer">
                        <button type="reset" class="btn-reset" onclick="resetFiles()">
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
document.querySelectorAll('.sidebar .menu').forEach(l => l.addEventListener('click', () => { if (window.innerWidth<=768) closeSidebar(); }));
window.addEventListener('resize', () => { if (window.innerWidth>768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; } });

/* ── Theme ── */
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = themeToggle.querySelector('i');
function applyTheme(dark) {
    document.body.classList.toggle('dark', dark);
    themeToggle.classList.toggle('active', dark);
    themeIcon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
}
applyTheme(localStorage.getItem('theme')==='dark');
themeToggle.addEventListener('click', () => {
    const d = !document.body.classList.contains('dark');
    applyTheme(d); localStorage.setItem('theme', d?'dark':'light');
});

/* ── File inputs ── */
function bindFileInput(inputId, zoneId, nameId) {
    const input = document.getElementById(inputId);
    const zone  = document.getElementById(zoneId);
    const label = document.getElementById(nameId);

    function setFile(file) {
        if (!file) return;
        zone.classList.add('has-file');
        label.textContent = '📎 ' + file.name;
        label.classList.add('visible');
    }

    input.addEventListener('change', () => setFile(input.files[0]));

    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('dragover');
        const dt = e.dataTransfer;
        if (dt.files.length) {
            try {
                const dtz = new DataTransfer();
                dtz.items.add(dt.files[0]);
                input.files = dtz.files;
            } catch(ex) {}
            setFile(dt.files[0]);
        }
    });
}

bindFileInput('pdfInput', 'pdfZone', 'pdfName');
bindFileInput('csvInput', 'csvZone', 'csvName');

function resetFiles() {
    ['pdfZone','csvZone'].forEach(id => { document.getElementById(id).classList.remove('has-file','dragover'); });
    ['pdfName','csvName'].forEach(id => { const el=document.getElementById(id); el.textContent=''; el.classList.remove('visible'); });
}

/* ── Floating submit button ── */
const floatBtn   = document.getElementById('floatSubmit');
const formFooter = document.querySelector('.form-footer');
const io = new IntersectionObserver(entries => {
    floatBtn.classList.toggle('show', !entries[0].isIntersecting);
}, { threshold:0 });
io.observe(formFooter);

/* ── SweetAlert2 Confirm Submit ── */
function confirmSubmit() {
    const form = document.getElementById('empForm');

    // Run native HTML5 validation first
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Collect summary values for the confirm dialog
    const site        = document.querySelector('[name="site_code"] option:checked')?.text  || '—';
    const designation = document.querySelector('[name="designation"] option:checked')?.text || '—';
    const count       = document.querySelector('[name="count"]')?.value   || '—';
    const efile       = document.querySelector('[name="efile_no"]')?.value || '—';
    const pdfFile     = document.getElementById('pdfInput')?.files[0]?.name  || '<span style="color:#ef4444">Not selected</span>';
    const csvFile     = document.getElementById('csvInput')?.files[0]?.name  || '<span style="color:#ef4444">Not selected</span>';

    const isDark = document.body.classList.contains('dark');

    Swal.fire({
        title: 'Confirm Submission',
        html: `
            <div style="text-align:left;font-size:0.9rem;line-height:1.9;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📍 Site</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${site}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">🎖️ Designation</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${designation}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">👥 Manpower</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${count}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📁 E-File No.</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${efile}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📄 PDF Doc</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${pdfFile}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📊 CSV/Excel</td>
                        <td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${csvFile}</td>
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
        },
        showClass: {
            popup: 'animate__animated animate__fadeInDown animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp animate__faster'
        }
    }).then(result => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Submitting...',
                html: 'Please wait while we process your request.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => { Swal.showLoading(); }
            });
            form.submit();
        }
    });
}

/* ── SweetAlert2 for PHP success/error messages ── */
<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '<?= addslashes($success) ?>',
    confirmButtonColor: '#0f766e',
    confirmButtonText: 'Great!',
    background: document.body.classList.contains('dark') ? '#111827' : '#ffffff',
    color:      document.body.classList.contains('dark') ? '#e5e7eb' : '#111827',
    iconColor: '#0f766e',
    timer: 4000,
    timerProgressBar: true,
});
<?php elseif ($error): ?>
Swal.fire({
    icon: 'error',
    title: 'Oops!',
    text: '<?= addslashes($error) ?>',
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Fix & Retry',
    background: document.body.classList.contains('dark') ? '#111827' : '#ffffff',
    color:      document.body.classList.contains('dark') ? '#e5e7eb' : '#111827',
});
<?php endif; ?>
</script>

<style>
/* ── SweetAlert2 custom tweaks ── */
.swal-popup-custom {
    border-radius: 16px !important;
    padding: 28px 24px !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18) !important;
    font-family: "Segoe UI", sans-serif !important;
}
.swal-title-custom {
    font-size: 1.2rem !important;
    font-weight: 800 !important;
}
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
</body>
</html>