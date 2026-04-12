<?php
session_start();
require "../config.php";

/* ── AUTH ── */
if (!isset($_SESSION['user'])) { header("Location: ../login.php"); exit; }
$stmtU = $pdo->prepare("SELECT role, name FROM user WHERE id = ?");
$stmtU->execute([$_SESSION['user']]);
$user = $stmtU->fetch(PDO::FETCH_ASSOC);
$userId = $_SESSION['user'];
$userName = $user['name'] ?? 'SDHOD';

// Fetch sites for dropdown
$stmtSites = $pdo->query("SELECT SiteCode, SiteName FROM site_master ORDER BY SiteName");
$sites = $stmtSites->fetchAll(PDO::FETCH_ASSOC);

// Calculate Financial Years (e.g., from 2023 to current year + 1)
$currentYear = date('Y');
$currentMonth = date('n');
if ($currentMonth < 4) {
    // Before April, current FY is (currentYear-1) - currentYear
    $fyStart = $currentYear - 1;
} else {
    // April or later, current FY is currentYear - (currentYear+1)
    $fyStart = $currentYear;
}
$financialYears = [];
for ($i = $fyStart - 2; $i <= $fyStart + 1; $i++) {
    $financialYears[] = $i . '-' . ($i + 1);
}
// Default to current FY
$defaultFY = $fyStart . '-' . ($fyStart + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>VV Statement – SDHOD | Security Attendance and Billing Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ── BASE STYLES & LAYOUT ── */
* { margin:0; padding:0; box-sizing:border-box; }
:root { --primary:#0f766e; --primary-dark:#0d5f58; --sidebar-width:270px; --role:#b91c1c; --role-light:#fef2f2; --role-border:#fca5a5; }
html { scroll-behavior:smooth; }
body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; line-height:1.6; }
.dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }

/* ── SIDEBAR ── */
.sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; display:flex; flex-direction:column; }
.sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
.mcl-logo-box { background:white; padding:10px 14px; border-radius:10px; display:inline-flex; }
.mcl-logo-img { max-width:155px; height:auto; display:block; }
.sidebar-nav { list-style:none; padding:1rem 0; flex:1; display:flex; flex-direction:column; gap:0.25rem; }
.sidebar-nav li { margin:0 1rem; }
.nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; }
.nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
.nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
.nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
.logout-link { margin-top:auto; color:rgba(255,255,255,0.75) !important; }
.logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }

/* ── MAIN CONTENT ── */
.main-content { padding:2rem; overflow-y:auto; display:flex; flex-direction:column; gap:1.5rem; min-width:0; }
.topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
.topbar h2 { font-size:1.25rem; font-weight:700; color:#1f2937; }
.topbar-right { display:flex; align-items:center; gap:12px; }
.role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:var(--role-light); color:var(--role); border:1.5px solid var(--role-border); border-radius:20px; padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700; }
.user-icon { width:40px; height:40px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
.user-icon svg { width:20px; height:20px; stroke:white; }

/* ── VV STATEMENT FORM ── */
.vv-container { display:flex; flex-direction:column; align-items:center; justify-content:center; flex:1; margin-top:2rem; }
.vv-card { background:#f5f5f5; border-radius:16px; border:1px solid #d1d5db; padding:2.5rem; max-width:680px; width:100%; box-shadow:inset 0 2px 4px rgba(255,255,255,0.5), 0 8px 24px rgba(0,0,0,0.06); }
.vv-title { text-align:center; font-size:1.4rem; font-weight:800; color:#1f2937; margin-bottom:2rem; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
.form-group { display:flex; flex-direction:column; gap:0.5rem; }
.form-group.full-width { grid-column:span 2; }
.form-label { font-size:0.75rem; font-weight:800; color:#4b5563; text-transform:uppercase; letter-spacing:0.5px; }
.form-select { width:100%; padding:0.8rem 1rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; color:#1f2937; background:white; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 1rem center; background-size:1em; outline:none; }
.form-select:focus { border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,0.1); }
.btn-submit { display:flex; align-items:center; justify-content:center; gap:0.5rem; width:100%; padding:0.85rem; background:#1f2937; color:white; border:none; border-radius:24px; font-size:1rem; font-weight:700; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 12px rgba(31,41,55,0.3); }
.btn-submit:hover { background:#111827; transform:translateY(-1px); }
.btn-download { display:flex; align-items:center; justify-content:center; gap:0.5rem; width:max-content; margin:1.5rem auto 0; padding:0.75rem 1.5rem; background:#16a34a; color:white; border:none; border-radius:24px; font-size:0.9rem; font-weight:700; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 12px rgba(22,163,74,0.3); }
.btn-download:hover { background:#15803d; transform:translateY(-1px); }

/* Placeholder Result Area */
.result-area { display:none; margin-top:2rem; background:white; border-radius:14px; padding:1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; width:100%; }

</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="mcl-logo-box">
                <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img">
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthlyatt.php" class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="monthlylpp.php" class="nav-link"><i class="fa-solid fa-calendar-check"></i><span>Monthly LPP</span></a></li>
            <li><a href="details_monthly_lpp.php" class="nav-link"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a href="vvstatement.php" class="nav-link active"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h2>Security Attendance and Billing Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-tie"></i> SDHOD</span>
                <a href="profile.php" style="text-decoration:none;"><div class="user-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/></svg></div></a>
            </div>
        </header>

        <div class="vv-container">
            <div class="vv-card">
                <div class="vv-title">Financial Year VV Statement</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">FINANCIAL YEAR</label>
                        <select class="form-select" id="fySelect">
                            <?php foreach($financialYears as $fy): ?>
                                <option value="<?= $fy ?>" <?= $fy === $defaultFY ? 'selected' : '' ?>><?= $fy ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" id="siteSelect" value="ALL">
                    <input type="hidden" id="categorySelect" value="all">
                </div>

                <button class="btn-submit" onclick="fetchStatement()">
                    <i class="fa-solid fa-arrows-rotate"></i> Submit
                </button>

                <button class="btn-download" id="downloadBtn" style="display:none;" onclick="downloadExcel()">
                    <i class="fa-solid fa-file-excel"></i> Download Excel Report
                </button>
            </div>

        </div>
    </main>
</div>

<script>
let vvData = null; // cached data from last fetch

const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function getMonthsForFY(fy) {
    const [startYear] = fy.split('-').map(Number);
    const months = [];
    for (let m = 4; m <= 12; m++) months.push({ name: monthNames[m-1], year: startYear });
    for (let m = 1; m <= 3; m++)  months.push({ name: monthNames[m-1], year: startYear + 1 });
    return months;
}

// Populate month selector on page load & FY change
function populateMonths() {
    const fy = document.getElementById('fySelect').value;
    const months = getMonthsForFY(fy);
    let sel = document.getElementById('monthSelect');
    if (!sel) {
        // Create month selector
        const group = document.createElement('div');
        group.className = 'form-group';
        group.innerHTML = '<label class="form-label">SELECT MONTH</label><select class="form-select" id="monthSelect"></select>';
        document.querySelector('.form-grid').insertBefore(group, document.querySelector('.form-group.full-width'));
        sel = document.getElementById('monthSelect');
    }
    sel.innerHTML = months.map(m => `<option value="${m.name} ${m.year}">${m.name} ${m.year}</option>`).join('');

    // Default to current month if it exists in the list
    const now = new Date();
    const curLabel = monthNames[now.getMonth()] + ' ' + now.getFullYear();
    if ([...sel.options].find(o => o.value === curLabel)) sel.value = curLabel;
}
document.getElementById('fySelect').addEventListener('change', populateMonths);
document.addEventListener('DOMContentLoaded', populateMonths);

async function fetchStatement() {
    const fy    = document.getElementById('fySelect').value;
    const site  = document.getElementById('siteSelect').value;
    const cat   = document.getElementById('categorySelect').value;
    const month = document.getElementById('monthSelect')?.value || '';

    if (!month) { Swal.fire({icon:'warning',title:'Select Month',text:'Please select a month.',toast:true,position:'top-end',showConfirmButton:false,timer:3000}); return; }

    const btn = document.querySelector('.btn-submit');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
    btn.disabled = true;
    document.getElementById('downloadBtn').style.display = 'none';

    try {
        const url = `export_vv_statement.php?format=json&fy=${encodeURIComponent(fy)}&site=${encodeURIComponent(site)}&category=${encodeURIComponent(cat)}&month=${encodeURIComponent(month)}`;
        const res = await fetch(url);
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(pe) {
            console.error('Server response:', text);
            Swal.fire({icon:'error',title:'Server Error',text:text.substring(0,200),toast:true,position:'top-end',showConfirmButton:false,timer:5000});
            btn.innerHTML = originalHtml; btn.disabled = false; return;
        }

        if (data.error) {
            Swal.fire({icon:'error',title:'Database Error',text:data.error.substring(0,200),toast:true,position:'top-end',showConfirmButton:false,timer:5000});
            btn.innerHTML = originalHtml; btn.disabled = false; return;
        }

        vvData = data;

        if (data.count > 0) {
            document.getElementById('downloadBtn').style.display = 'flex';
            Swal.fire({
                icon: 'success',
                title: 'Data Loaded',
                text: `${data.count} employees found for ${month}.`,
                toast: true,position: 'top-end', showConfirmButton: false, timer: 3000
            });
        } else {
            document.getElementById('downloadBtn').style.display = 'none';
            Swal.fire({
                icon: 'info',
                title: 'No Records',
                text: `No employees found for ${month} with selected filters. Try "All Categories".`,
                toast: true, position: 'top-end', showConfirmButton: false, timer: 4000
            });
        }
    } catch(e) {
        console.error(e);
        Swal.fire({icon:'error',title:'Error',text:'Failed to fetch data: ' + e.message,toast:true,position:'top-end',showConfirmButton:false,timer:3000});
    }

    btn.innerHTML = originalHtml;
    btn.disabled = false;
}

function downloadExcel() {
    if (!vvData || !vvData.rows || vvData.rows.length === 0) {
        Swal.fire({icon:'warning',title:'No Data',text:'Please fetch data first.',toast:true,position:'top-end',showConfirmButton:false,timer:3000});
        return;
    }

    const wb = XLSX.utils.book_new();

    // Build rows array
    const header = [
        'SlNo', 'RegNo', 'ESIC No', 'CMPF Acc No', 'Aadhar No',
        'Name of the Employee', "Father's Name", 'Designation', 'Site Name',
        'Wages (Basic+VDA)', 'Actual Attendance', 'National Working Days',
        'Gross Wage Payment', 'National Wages',
        'PF Member (12%)', 'PF Employer (12%)',
        'Pension Member (7%)', 'Pension Employer (7%)',
        'Total PF', 'Total Pension', 'Total Deduction'
    ];

    // Title rows
    const aoa = [
        [vvData.title || 'Monthly VV Statement'],
        [`Area: MCL ${vvData.area || ''}`],
        [],
        header
    ];

    // Data rows
    vvData.rows.forEach(d => {
        aoa.push([
            d.slNo, d.regNo, d.esicNo, d.cmpfAccNo, d.aadharNo,
            d.employeeName, d.fatherName, d.designation, d.siteName,
            d.wages, d.actualAttendance, d.notionalDays,
            d.grossWage, d.notionalWages,
            d.pfMember12, d.pfEmployer12,
            d.pensionMember7, d.pensionEmployer7,
            d.totalPF, d.totalPension, d.totalDeduction
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(aoa);

    // Auto-width columns
    const colWidths = header.map((h, i) => {
        let max = h.length;
        vvData.rows.forEach(d => {
            const val = String(aoa[4] ? (aoa[aoa.length-1]?.[i] ?? '') : '');
            if (val.length > max) max = val.length;
        });
        return { wch: Math.min(max + 4, 30) };
    });
    ws['!cols'] = colWidths;

    // Merge title cells
    ws['!merges'] = [
        { s:{r:0,c:0}, e:{r:0,c:header.length-1} },
        { s:{r:1,c:0}, e:{r:1,c:header.length-1} }
    ];

    // Get month from selected dropdown for sheet name
    const month = document.getElementById('monthSelect')?.value || 'VV_Report';
    const sheetName = month.replace(/\s+/g, '_').substring(0, 31);
    XLSX.utils.book_append_sheet(wb, ws, sheetName);

    // Generate & Download
    const fy = document.getElementById('fySelect').value;
    const fileName = `VV_Report_${fy}_${sheetName}.xlsx`;
    XLSX.writeFile(wb, fileName);

    Swal.fire({
        icon: 'success',
        title: 'Downloaded!',
        text: `${fileName} saved successfully.`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}
</script>
</body>
</html>
