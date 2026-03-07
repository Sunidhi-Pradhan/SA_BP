<?php
session_start();
if (!isset($_SESSION["user"])) { header("Location: login.php"); exit; }

require "../config.php";

$success = "";
$error   = "";

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

        /* ========= Helper: normalize a header string ========= */
        // Converts "ESIC NO", "esic_no", "Esic No.", "  ESIC_NO  " → "ESIC_NO"
        function normalizeHeader($h) {
            $h = trim($h);
            // Remove UTF-8 BOM if present
            $h = ltrim($h, "\xEF\xBB\xBF");
            // Strip non-alphanumeric except spaces/underscores
            $h = preg_replace('/[^a-zA-Z0-9_ ]/', '', $h);
            // Replace spaces with underscore
            $h = preg_replace('/\s+/', '_', $h);
            // Uppercase
            return strtoupper(trim($h, '_'));
        }

        /* ========= Read Excel / CSV + Check ESIC duplicates ========= */
        $employees = [];
        $esic_list = [];

        if (!empty($_FILES['csv_data']['tmp_name']) && !empty($_FILES['csv_data']['name'])) {

            $originalName = strtolower(trim($_FILES['csv_data']['name']));
            $tmpPath      = $_FILES['csv_data']['tmp_name'];
            $ext          = pathinfo($originalName, PATHINFO_EXTENSION);

            // ── Route by file extension ──────────────────────────────
            if ($ext === 'csv') {

                // ── Plain CSV ────────────────────────────────────────
                $handle = fopen($tmpPath, "r");

                if ($handle !== false) {

                    $rawHeaders = fgetcsv($handle);

                    if ($rawHeaders) {
                        $headers = array_map('normalizeHeader', $rawHeaders);
                    } else {
                        $error = "CSV file appears to be empty or unreadable.";
                        $headers = [];
                    }

                    if (!$error) {
                        // Check ESIC_NO column exists
                        if (!in_array('ESIC_NO', $headers)) {
                            $error = "Column 'ESIC_NO' not found in CSV. Found columns: " . implode(', ', $headers);
                        }
                    }

                    if (!$error) {
                        $rowNum = 1;
                        while (($row = fgetcsv($handle)) !== false) {
                            $rowNum++;

                            // Skip completely empty rows
                            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                                continue;
                            }

                            // Pad or trim row to match header count
                            $colCount = count($headers);
                            while (count($row) < $colCount) $row[] = '';
                            $row = array_slice($row, 0, $colCount);

                            $data = array_combine($headers, $row);
                            $esic = trim((string)($data['ESIC_NO'] ?? ''));

                            if (!$esic) continue; // skip rows with no ESIC

                            if (in_array($esic, $esic_list)) {
                                $error = "Duplicate ESIC_NO found: " . $esic . " (row $rowNum)";
                                break;
                            }

                            $esic_list[] = $esic;
                            $employees[] = $data;
                        }
                    }

                    fclose($handle);
                } else {
                    $error = "Could not open the uploaded CSV file.";
                }

            } elseif (in_array($ext, ['xlsx', 'xls'])) {

                // ── Excel via PhpSpreadsheet ─────────────────────────
                $autoloaderPath = __DIR__ . '/../employee_import/vendor/autoload.php';

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

                        // Remove trailing completely-null rows from the end
                        while (!empty($rows) && empty(array_filter((array)end($rows), fn($v) => $v !== null && trim((string)$v) !== ''))) {
                            array_pop($rows);
                        }

                        if (empty($rows)) {
                            $error = "The Excel file appears to be empty.";
                        } else {

                            // First row = headers — normalize them
                            $rawHeaders = array_map(fn($h) => (string)($h ?? ''), $rows[0]);
                            $headers    = array_map('normalizeHeader', $rawHeaders);

                            // Remove empty header columns (keep track of valid indices)
                            $validIndices = [];
                            $cleanHeaders = [];
                            foreach ($headers as $i => $h) {
                                if ($h !== '') {
                                    $validIndices[] = $i;
                                    $cleanHeaders[] = $h;
                                }
                            }
                            $colCount = count($cleanHeaders);

                            // Check ESIC_NO column exists
                            if (!in_array('ESIC_NO', $cleanHeaders)) {
                                $error = "Column 'ESIC_NO' not found in Excel. Found columns: " . implode(', ', $cleanHeaders);
                            }

                            if (!$error) {
                                $rowNum = 1;
                                foreach (array_slice($rows, 1) as $rawRow) {
                                    $rowNum++;

                                    // Extract only valid-index columns
                                    $row = [];
                                    foreach ($validIndices as $idx) {
                                        $row[] = isset($rawRow[$idx]) ? trim((string)$rawRow[$idx]) : '';
                                    }

                                    // Skip fully empty rows
                                    if (empty(array_filter($row, fn($v) => $v !== ''))) {
                                        continue;
                                    }

                                    // Pad if shorter
                                    while (count($row) < $colCount) $row[] = '';

                                    $data = array_combine($cleanHeaders, $row);
                                    $esic = trim((string)($data['ESIC_NO'] ?? ''));

                                    if (!$esic) continue;

                                    if (in_array($esic, $esic_list)) {
                                        $error = "Duplicate ESIC_NO found: " . $esic . " (row $rowNum)";
                                        break;
                                    }

                                    $esic_list[] = $esic;
                                    $employees[] = $data;
                                }
                            }
                        }

                    } catch (\Exception $e) {
                        $error = "Failed to read Excel file: " . $e->getMessage();
                    }
                }

            } else {
                $error = "Unsupported file type '$ext'. Please upload a .csv, .xlsx, or .xls file.";
            }

            // If no error but also no employees parsed, warn the admin
            if (!$error && empty($employees)) {
                $error = "No employee records were found in the uploaded file. Please check the file content and ensure 'ESIC_NO' column exists with data.";
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

                $success = "Extra manpower assigned successfully. " . count($employees) . " employee record(s) saved.";

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

        .menu-btn {
            background:none; border:none; font-size:22px; cursor:pointer;
            color:var(--text); padding:6px 8px; border-radius:6px;
            display:none; align-items:center; justify-content:center;
            flex-shrink:0; transition:background .2s,transform .2s;
        }
        .menu-btn:hover { background:rgba(0,0,0,0.06); transform:rotate(90deg); }

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

        .drop-zone.pdf-zone { padding:14px 18px; }
        .drop-zone.pdf-zone .dz-icon {
            width:36px; height:36px; border-radius:8px;
            background:#fee2e2; color:#ef4444;
            display:flex; align-items:center; justify-content:center;
            font-size:1rem; flex-shrink:0;
        }
        .drop-zone.pdf-zone .dz-text { font-size:.87rem; color:var(--subtext); font-weight:500; }
        .drop-zone.pdf-zone .dz-text span { color:var(--teal); font-weight:700; }

        .drop-zone.csv-zone {
            flex-direction:column; align-items:center; justify-content:center;
            padding:40px 24px; gap:12px; min-height:160px; text-align:center;
        }
        .drop-zone.csv-zone .dz-icon-big { font-size:2.4rem; color:var(--teal); line-height:1; }
        .drop-zone.csv-zone .dz-title    { font-size:.92rem; font-weight:600; color:var(--text); }
        .drop-zone.csv-zone .dz-hint     { font-size:.78rem; color:var(--subtext); }

        .file-name {
            font-size:.8rem; color:var(--teal); font-weight:600;
            margin-top:4px; display:none; word-break:break-all;
        }
        .file-name.visible { display:block; }

        /* ===== COLUMN PREVIEW ===== */
        .col-preview {
            background: rgba(15,118,110,0.05);
            border: 1px solid rgba(15,118,110,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            display: none;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }
        .col-preview.visible { display: flex; }
        .col-preview-label {
            font-size: .68rem; font-weight: 700; color: var(--subtext);
            text-transform: uppercase; letter-spacing: .5px;
            width: 100%; margin-bottom: 2px;
        }
        .col-tag {
            font-size: .74rem; font-weight: 600;
            padding: 3px 9px; border-radius: 999px;
            background: var(--teal); color: #fff;
        }
        .col-tag.missing { background: #ef4444; }

        /* ===== SAMPLE CSV BTN ===== */
        .sample-btn {
            display:inline-flex; align-items:center; gap:.45rem;
            padding:.5rem 1.1rem; min-height:38px;
            background:var(--bg); color:#2563eb;
            border:1px solid var(--border); border-radius:8px;
            font-size:.82rem; font-weight:700;
            cursor:pointer; font-family:inherit; text-decoration:none;
            transition:background .2s,transform .15s,box-shadow .15s;
            align-self:center;
        }
        .sample-btn:hover { background:#eff6ff; transform:translateY(-1px); box-shadow:0 2px 8px rgba(37,99,235,.12); }

        /* ===== FORM FOOTER ===== */
        .form-footer {
            padding:16px 28px 22px;
            border-top:1px solid var(--border);
            display:flex; align-items:center; justify-content:flex-end; gap:.7rem;
            flex-wrap:wrap;
        }

        .btn-reset {
            display:flex; align-items:center; justify-content:center; gap:.4rem;
            padding:.65rem 1.3rem; min-height:44px;
            background:var(--bg); color:var(--subtext);
            border:1px solid var(--border); border-radius:10px;
            font-size:.88rem; font-weight:600;
            cursor:pointer; font-family:inherit;
            transition:background .2s,transform .15s;
        }
        .btn-reset:hover { background:var(--border); transform:translateY(-1px); }

        .btn-submit {
            display:flex; align-items:center; justify-content:center; gap:.45rem;
            padding:.68rem 1.8rem; min-height:44px;
            background:var(--teal); color:#fff;
            border:none; border-radius:10px;
            font-size:.9rem; font-weight:700;
            cursor:pointer; font-family:inherit;
            box-shadow:0 3px 12px rgba(15,118,110,.28);
            transition:background .2s,transform .15s,box-shadow .2s;
        }
        .btn-submit:hover { background:var(--teal-dark); transform:translateY(-2px); box-shadow:0 6px 20px rgba(15,118,110,.38); }
        .btn-submit:active { background:var(--teal-deep); transform:scale(.97); }

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
            opacity:0; pointer-events:none;
        }
        .btn-submit-float.show { opacity:1; pointer-events:auto; }
        .btn-submit-float:hover { background:var(--teal-dark); transform:translateY(-3px); }

        /* ===== RESPONSIVE ===== */
        @media (max-width:992px) {
            .sidebar { width:210px; min-width:210px; }
            .form-grid-4 { grid-template-columns:repeat(2,1fr); }
        }
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
            .page-content { padding:16px 12px 60px; gap:14px; }
            .form-body { padding:16px 16px 14px; }
            .form-grid-4 { grid-template-columns:1fr 1fr; gap:12px; }
            .form-grid-2 { grid-template-columns:1fr; gap:12px; margin-top:12px; }
            .form-footer { padding:12px 16px 16px; }
            .btn-reset, .btn-submit { flex:1; }
        }
        @media (max-width:480px) {
            .form-grid-4 { grid-template-columns:1fr; gap:10px; }
            .form-control { font-size:.85rem; min-height:42px; }
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
            <a href="#" class="menu">
                <span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <span>Basic Pay Update</span>
            </a>
            <a href="extra_manpower.php" class="menu active">
                <span class="icon"><i class="fa-solid fa-user-clock"></i></span>
                <span>Add Extra Manpower</span>
            </a>
            <a href="../unlock/unlock.php" class="menu">
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

            <!-- Page title -->
            <div class="page-title-row">
                <div class="page-title-icon"><i class="fa-solid fa-user-clock"></i></div>
                <div class="page-title-text">
                    <h2>Assign Extra Manpower</h2>
                    <p>Upload employee data with a valid CSV or Excel file containing an <strong>ESIC_NO</strong> column.</p>
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
                                <div class="upload-label">Upload Employee Data (Excel / CSV) <span class="req">*</span></div>
                                <div class="drop-zone csv-zone" id="csvZone">
                                    <input type="file" name="csv_data" accept=".csv,.xlsx,.xls" id="csvInput">
                                    <div class="dz-icon-big"><i class="fa-solid fa-upload"></i></div>
                                    <div class="dz-title">Upload Employee Master Excel / CSV</div>
                                    <div class="dz-hint">
                                        File must contain an <strong>ESIC_NO</strong> column.<br>
                                        Header variations like "esic no", "Esic_No", "ESIC NO" are all accepted.
                                    </div>
                                </div>
                                <div class="file-name" id="csvName"></div>

                                <!-- Column preview shown after file pick -->
                                <div class="col-preview" id="colPreview">
                                    <div class="col-preview-label">Detected columns in your file:</div>
                                    <!-- tags injected by JS -->
                                </div>
                            </div>

                            <!-- Sample CSV button -->
                            <a class="sample-btn" href="../assets/samplecsv.xlsx" download>
                                <i class="fa-solid fa-arrow-down-to-line"></i>
                                Download Sample CSV / Excel
                            </a>

                        </div><!-- /.upload-section -->

                    </div><!-- /.form-body -->

                    <div class="form-footer">
                        <button type="reset" class="btn-reset" id="resetBtn">
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

/* ── Normalize header (mirrors PHP logic) ── */
function normalizeHeader(h) {
    h = h.trim().replace(/^\uFEFF/, '');          // strip BOM
    h = h.replace(/[^a-zA-Z0-9_ ]/g, '');        // strip special chars
    h = h.replace(/\s+/g, '_');                   // spaces → underscore
    return h.toUpperCase().replace(/^_+|_+$/g,''); // uppercase, trim underscores
}

/* ── CSV column preview ── */
function previewCSVColumns(file) {
    const preview  = document.getElementById('colPreview');
    const ext      = file.name.split('.').pop().toLowerCase();

    // Only preview CSV client-side (Excel needs server)
    if (ext !== 'csv') {
        preview.innerHTML = '<div class="col-preview-label">Column preview available after upload (Excel file)</div>';
        preview.classList.add('visible');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const text    = e.target.result;
        const lines   = text.split(/\r?\n/);
        const rawCols = lines[0]?.split(',') || [];
        const cols    = rawCols.map(normalizeHeader).filter(Boolean);

        preview.innerHTML = '<div class="col-preview-label">Detected columns (' + cols.length + '):</div>';

        const hasEsic = cols.includes('ESIC_NO');

        cols.forEach(col => {
            const tag = document.createElement('span');
            tag.className = 'col-tag' + (col === 'ESIC_NO' ? '' : '');
            tag.textContent = col;
            preview.appendChild(tag);
        });

        if (!hasEsic) {
            const warn = document.createElement('div');
            warn.style.cssText = 'width:100%;font-size:.75rem;color:#ef4444;font-weight:700;margin-top:4px;';
            warn.innerHTML = '⚠️ ESIC_NO column not found! The file must have an ESIC_NO column.';
            preview.appendChild(warn);
        } else {
            const ok = document.createElement('div');
            ok.style.cssText = 'width:100%;font-size:.75rem;color:#059669;font-weight:700;margin-top:4px;';
            ok.innerHTML = '✓ ESIC_NO column detected.';
            preview.appendChild(ok);
        }

        preview.classList.add('visible');
    };
    reader.readAsText(file);
}

/* ── File input binding ── */
function bindFileInput(inputId, zoneId, nameId) {
    const input = document.getElementById(inputId);
    const zone  = document.getElementById(zoneId);
    const label = document.getElementById(nameId);

    function setFile(file) {
        if (!file) return;
        zone.classList.add('has-file');
        label.textContent = '📎 ' + file.name;
        label.classList.add('visible');
        if (inputId === 'csvInput') previewCSVColumns(file);
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

/* ── Reset ── */
document.getElementById('resetBtn').addEventListener('click', function() {
    ['pdfZone','csvZone'].forEach(id => document.getElementById(id).classList.remove('has-file','dragover'));
    ['pdfName','csvName'].forEach(id => { const el=document.getElementById(id); el.textContent=''; el.classList.remove('visible'); });
    const prev = document.getElementById('colPreview');
    prev.innerHTML = ''; prev.classList.remove('visible');
});

/* ── Floating submit ── */
const floatBtn   = document.getElementById('floatSubmit');
const formFooter = document.querySelector('.form-footer');
const io = new IntersectionObserver(entries => {
    floatBtn.classList.toggle('show', !entries[0].isIntersecting);
}, { threshold:0 });
io.observe(formFooter);

/* ── SweetAlert2 Confirm Submit ── */
function confirmSubmit() {
    const form = document.getElementById('empForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

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
                    <tr><td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📍 Site</td><td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${site}</td></tr>
                    <tr><td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">🎖️ Designation</td><td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${designation}</td></tr>
                    <tr><td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">👥 Manpower</td><td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${count}</td></tr>
                    <tr><td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📁 E-File No.</td><td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${efile}</td></tr>
                    <tr><td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📄 PDF Doc</td><td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${pdfFile}</td></tr>
                    <tr><td style="color:#6b7280;padding:4px 8px 4px 0;white-space:nowrap;font-weight:600;">📊 CSV/Excel</td><td style="font-weight:700;color:${isDark?'#e5e7eb':'#111827'}">${csvFile}</td></tr>
                </table>
                <p style="margin-top:14px;font-size:0.8rem;color:#6b7280;text-align:center;">Please review the details above before confirming.</p>
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
        customClass: { popup:'swal-popup-custom', confirmButton:'swal-confirm-btn', cancelButton:'swal-cancel-btn', title:'swal-title-custom' },
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Submitting...',
                html: 'Please wait while we process your request.',
                allowOutsideClick: false, allowEscapeKey: false,
                didOpen: () => { Swal.showLoading(); }
            });
            form.submit();
        }
    });
}

/* ── PHP success/error ── */
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
    timer: 5000,
    timerProgressBar: true,
});
<?php elseif ($error): ?>
Swal.fire({
    icon: 'error',
    title: 'Upload Error',
    html: `<div style="text-align:left;font-size:.9rem;line-height:1.7;"><?= addslashes(htmlspecialchars($error)) ?></div>`,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Fix &amp; Retry',
    background: document.body.classList.contains('dark') ? '#111827' : '#ffffff',
    color:      document.body.classList.contains('dark') ? '#e5e7eb' : '#111827',
});
<?php endif; ?>
</script>

<style>
.swal-popup-custom { border-radius:16px !important; padding:28px 24px !important; box-shadow:0 20px 60px rgba(0,0,0,0.18) !important; font-family:"Segoe UI",sans-serif !important; }
.swal-title-custom { font-size:1.2rem !important; font-weight:800 !important; }
.swal-confirm-btn, .swal-cancel-btn { border-radius:10px !important; font-size:.88rem !important; font-weight:700 !important; padding:10px 22px !important; display:inline-flex !important; align-items:center !important; gap:7px !important; }
.swal2-timer-progress-bar { background:#0f766e !important; }
</style>
</body>
</html>