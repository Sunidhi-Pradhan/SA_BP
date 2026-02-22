<?php
session_start();
require "config.php";

require __DIR__ . "/employee_import/vendor/autoload.php";
use PhpOffice\PhpSpreadsheet\IOFactory;

/* ================= FILE CHECK ================= */
if (!isset($_FILES['employee_file']) || $_FILES['employee_file']['error'] !== UPLOAD_ERR_OK) {
    header("Location: employees.php?error=No file uploaded");
    exit;
}

$ext = strtolower(pathinfo($_FILES['employee_file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv','xls','xlsx'])) {
    header("Location: employees.php?error=Invalid file type");
    exit;
}

/* ================= LOAD FILE ================= */
$rows = IOFactory::load($_FILES['employee_file']['tmp_name'])
        ->getActiveSheet()->toArray();

$inserted = 0;
$updated  = 0;

/* ================= MAIN QUERY ================= */
$sql = "
INSERT INTO employee_master
(
    esic_no, site_code, reg_no, employee_name, rank, gender,
    dob, doj, aadhar_no, father_name, mob_no,
    ac_no, ifsc_code, bank_name, address
)
VALUES
(
    :esic_no, :site_code, :reg_no, :employee_name, :rank, :gender,
    :dob, :doj, :aadhar_no, :father_name, :mob_no,
    :ac_no, :ifsc_code, :bank_name, :address
)
ON DUPLICATE KEY UPDATE
    site_code = VALUES(site_code),
    reg_no = VALUES(reg_no),
    employee_name = VALUES(employee_name),
    rank = VALUES(rank),
    gender = VALUES(gender),
    dob = VALUES(dob),
    doj = VALUES(doj),
    aadhar_no = VALUES(aadhar_no),
    father_name = VALUES(father_name),
    mob_no = VALUES(mob_no),
    ac_no = VALUES(ac_no),
    ifsc_code = VALUES(ifsc_code),
    bank_name = VALUES(bank_name),
    address = VALUES(address)
";

$stmt = $pdo->prepare($sql);

/* ================= LOOP ================= */
foreach ($rows as $i => $r) {

    if ($i === 0) continue;            // header
    if (empty($r[0])) continue;        // ESIC must exist

    $esic = trim($r[0]);
    $city = trim($r[1]);

    /* ---------- SITE HANDLING (AUTO CREATE) ---------- */
    $siteQ = $pdo->prepare("SELECT SiteCode FROM site_master WHERE SiteName LIKE ? LIMIT 1");
    $siteQ->execute(['%' . strtoupper($city) . '%']);
    $site_code = $siteQ->fetchColumn();

    if (!$site_code) {
        // Generate new site code
        $site_code = str_pad(rand(1,999), 3, '0', STR_PAD_LEFT);
        $fullSiteName = strtoupper("MAHANADI COAL FIELD $city");

        $pdo->prepare(
            "INSERT INTO site_master (SiteCode, SiteName)
             VALUES (?, ?)"
        )->execute([$site_code, $fullSiteName]);
    }

    /* ---------- FIX AADHAAR ---------- */
    $aadhar = trim($r[8]);
    if (stripos($aadhar, 'E') !== false) {
        $aadhar = number_format((float)$aadhar, 0, '', '');
    }

    /* ---------- SAFE DATES ---------- */
    $dob = !empty($r[6]) ? date('Y-m-d', strtotime($r[6])) : null;
    $doj = !empty($r[7]) ? date('Y-m-d', strtotime($r[7])) : null;

    /* ---------- EXECUTE ---------- */
    $stmt->execute([
        ':esic_no'       => $esic,
        ':site_code'     => $site_code,
        ':reg_no'        => trim($r[2]),
        ':employee_name' => trim($r[3]),
        ':rank'          => trim($r[4]),
        ':gender'        => trim($r[5]),
        ':dob'           => $dob,
        ':doj'           => $doj,
        ':aadhar_no'     => $aadhar,
        ':father_name'   => trim($r[9]),
        ':mob_no'        => trim($r[10]),
        ':ac_no'         => trim($r[11]),
        ':ifsc_code'     => trim($r[12]),
        ':bank_name'     => trim($r[13]),
        ':address'       => trim($r[14])
    ]);

    if ($stmt->rowCount() === 1) {
        $inserted++;
    } else {
        $updated++;
    }
}

/* ================= REDIRECT ================= */
header("Location: employees.php?inserted=$inserted&updated=$updated");
exit;
