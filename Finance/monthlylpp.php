<?php
session_start();
require "../config.php";

/* ── AUTH ── */
if (!isset($_SESSION['user'])) { header("Location: ../login.php"); exit; }
$stmtU = $pdo->prepare("SELECT role, name FROM user WHERE id = ?");
$stmtU->execute([$_SESSION['user']]);
$user = $stmtU->fetch(PDO::FETCH_ASSOC);
$userId = $_SESSION['user'];
$userName = $user['name'] ?? 'Finance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Monthly LPP – Finance | Security Attendance and Billing Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
:root { --primary:#0f766e; --primary-dark:#0d5f58; --sidebar-width:270px; --role:#2563eb; --role-light:#eff6ff; --role-border:#93c5fd; }
html { scroll-behavior:smooth; }
body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; line-height:1.6; }
.dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }
.sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; display:flex; flex-direction:column; }
.sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,0.12); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:1rem; z-index:2; }
.sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
.mcl-logo-img { max-width:155px; height:auto; display:block; background:white; padding:10px 14px; border-radius:10px; }
.sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
.sidebar-nav li { margin:0.25rem 1rem; }
.nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; }
.nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
.nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
.nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
.logout-link { color:rgba(255,255,255,0.75) !important; }
.logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }
.main-content { padding:2rem; overflow-y:auto; display:flex; flex-direction:column; gap:1.5rem; min-width:0; }
.topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
.hamburger-btn { display:none; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px; width:38px; height:38px; cursor:pointer; color:#0f766e; font-size:1rem; }
.topbar h2 { font-size:1.4rem; font-weight:700; color:#1f2937; }
.topbar-right { display:flex; align-items:center; gap:12px; }
.role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:var(--role-light); color:var(--role); border:1.5px solid var(--role-border); border-radius:20px; padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700; }
.header-icon { width:40px; height:40px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; color:#6b7280; font-size:1rem; border:1px solid #e5e7eb; }
.header-icon .badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:0.65rem; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
.user-icon { width:40px; height:40px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
.user-icon svg { width:20px; height:20px; stroke:white; }
.filter-panel { background:white; border-radius:18px; border:1px solid #e5e7eb; box-shadow:0 4px 24px rgba(0,0,0,0.08); padding:2rem 2.5rem 1.75rem; max-width:780px; width:100%; margin:0 auto; }
.filter-grid { display:grid; grid-template-columns:1fr auto; gap:1.25rem; align-items:end; }
.filter-field { display:flex; flex-direction:column; gap:0.45rem; }
.filter-label { display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; }
.filter-label i { color:#0f766e; }
.filter-input { width:100%; padding:0.8rem 1rem; border:1.5px solid #e5e7eb; border-radius:10px; font-size:0.95rem; color:#1f2937; background:#f9fafb; outline:none; cursor:pointer; }
.filter-input:focus { border-color:#0f766e; }
.btn-load { display:inline-flex; align-items:center; gap:0.6rem; padding:0.85rem 2rem; border-radius:10px; background:linear-gradient(135deg,#2563eb,#1d4ed8); color:white; border:none; font-size:0.95rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(37,99,235,0.3); text-transform:uppercase; }
.btn-load:hover { transform:translateY(-1px); }
.workflow-section { background:white; border-radius:14px; padding:1.5rem 2rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; gap:1.2rem; }
.workflow-title { display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; font-weight:600; color:#374151; }
.workflow-title i { color:#0f766e; }
.workflow-steps { display:flex; align-items:center; justify-content:center; }
.workflow-step { display:flex; flex-direction:column; align-items:center; gap:0.5rem; }
.step-card { width:115px; height:115px; border-radius:14px; border:2px solid #e5e7eb; background:#f9fafb; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.35rem; position:relative; padding-top:18px; }
.step-avatar { width:54px; height:54px; border-radius:50%; background:#d1d5db; display:flex; align-items:center; justify-content:center; }
.step-avatar i { font-size:1.55rem; color:white; }
.step-label { font-size:0.78rem; font-weight:700; color:#6b7280; text-transform:uppercase; }
.step-sub { font-size:0.65rem; color:#9ca3af; }
.step-check { position:absolute; top:-11px; left:50%; transform:translateX(-50%); width:22px; height:22px; border-radius:50%; background:#16a34a; color:white; display:flex; align-items:center; justify-content:center; font-size:0.65rem; border:2px solid white; }
.workflow-step.approved .step-card { border-color:#86efac; background:#f0fdf4; }
.workflow-step.approved .step-avatar { background:#16a34a; }
.workflow-step.approved .step-label { color:#15803d; }
.workflow-step.current .step-card { border-color:#93c5fd; background:#eff6ff; box-shadow:0 0 0 4px rgba(37,99,235,0.15); }
.workflow-step.current .step-avatar { background:linear-gradient(135deg,#2563eb,#1d4ed8); }
.workflow-step.current .step-label { color:#1d4ed8; font-weight:800; }
.workflow-step.pending .step-avatar { background:#9ca3af; }
.workflow-step.pending .step-label { color:#9ca3af; }
.workflow-arrow { display:flex; align-items:center; padding:0 1.4rem; color:#9ca3af; font-size:1.1rem; margin-bottom:1.5rem; }
.btn-approval { display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1.4rem; border-radius:8px; background:#eff6ff; color:#1d4ed8; border:1.5px solid #93c5fd; font-size:0.84rem; font-weight:600; cursor:pointer; }
.approval-comments { display:none; width:100%; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
.approval-comments.open { display:block; }
.comment-item { padding:0.9rem 1.25rem; border-bottom:1px solid #f0f0f0; background:white; }
.comment-item:last-child { border-bottom:none; }
.comment-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem; }
.comment-role { font-size:0.85rem; font-weight:700; color:#1f2937; }
.comment-time { font-size:0.75rem; color:#9ca3af; }
.comment-text { font-size:0.84rem; color:#4b5563; background:#f9fafb; border-radius:8px; padding:0.5rem 0.85rem; border-left:3px solid #93c5fd; font-style:italic; }
.card { background:white; border-radius:14px; padding:1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
.card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem; flex-wrap:wrap; gap:0.75rem; }
.card-title { font-size:0.95rem; font-weight:700; color:var(--role); display:flex; align-items:center; gap:0.5rem; }
.billing-table-wrapper { overflow-x:auto; border-radius:10px; border:1px solid #e5e7eb; }
.billing-table { width:max-content; min-width:100%; border-collapse:separate; border-spacing:0; font-size:0.81rem; background:white; }
.billing-table thead th { background:linear-gradient(135deg,#2563eb,#1d4ed8); color:white; font-weight:700; font-size:0.73rem; text-transform:uppercase; padding:0.78rem 0.7rem; text-align:right; white-space:nowrap; }
.billing-table thead th.th-left { text-align:left; }
.billing-table thead th.th-center { text-align:center; }
.billing-table tbody td { padding:0.65rem 0.7rem; border-bottom:1px solid #f0f0f0; text-align:right; color:#1f2937; vertical-align:middle; white-space:nowrap; }
.billing-table tbody tr:hover { background:#eff6ff; }
.billing-table tbody tr:nth-child(even) { background:#fafafa; }
.billing-table tfoot td { background:linear-gradient(135deg,#eff6ff,#dbeafe); font-weight:800; color:#1d4ed8; padding:0.78rem 0.7rem; border-top:2px solid #93c5fd; text-align:right; }
.sn-cell { color:#9ca3af !important; font-size:0.77rem; font-weight:600; text-align:center !important; }
.site-name-cell { font-weight:600; color:#1f2937 !important; font-size:0.82rem; text-align:left !important; }
.hidden { display:none !important; }
.sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; }
.sidebar-overlay.active { display:block; }
.no-data-msg { text-align:center; padding:4rem; color:#6b7280; }
.no-data-msg i { font-size:2.5rem; color:#d1d5db; display:block; margin-bottom:1rem; }
.no-data-msg .highlight { color:#d97706; font-weight:700; }
.approve-section { margin-top:1.5rem; background:white; border-radius:14px; border:2px solid #2563eb; box-shadow:0 2px 12px rgba(37,99,235,0.1); padding:1.5rem 1.75rem; }
.approve-section .section-head { display:flex; align-items:center; gap:0.65rem; margin-bottom:1rem; }
.approve-section .section-icon { width:34px; height:34px; border-radius:50%; background:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.approve-section .section-icon i { color:white; font-size:0.95rem; }
.approve-section .section-title { font-size:1rem; font-weight:800; color:#1f2937; }
.approve-section .section-subtitle { font-size:0.78rem; color:#6b7280; }
.btn-approve { display:inline-flex; align-items:center; gap:0.55rem; padding:0.85rem 2rem; border-radius:10px; background:linear-gradient(135deg,#2563eb,#1d4ed8); color:white; border:none; font-size:0.95rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(37,99,235,0.3); transition:all 0.2s; }
.btn-approve:hover { transform:translateY(-1px); }
.btn-approve:disabled { background:linear-gradient(135deg,#9ca3af,#6b7280); cursor:not-allowed; box-shadow:none; opacity:0.7; }
.already-done { margin-top:1.5rem; background:#f0fdf4; border:2px solid #86efac; border-radius:14px; padding:1.5rem; text-align:center; }
.already-done i { font-size:2rem; color:#16a34a; display:block; margin-bottom:0.5rem; }
.already-done .title { font-size:1.1rem; font-weight:800; color:#15803d; }
.already-done .sub { font-size:0.85rem; color:#6b7280; margin-top:0.3rem; }
@media (max-width:900px) {
    .dashboard-layout { grid-template-columns:1fr; }
    .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); transition:transform 0.3s; z-index:200; }
    .sidebar.open { transform:translateX(0); }
    .sidebar-close { display:flex; }
    .hamburger-btn { display:flex; }
    .main-content { padding:1rem; gap:1rem; }
    .filter-panel { padding:1.25rem; max-width:100%; }
    .filter-grid { grid-template-columns:1fr; }
    .btn-load { width:100%; justify-content:center; }
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo"><img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img"></div>
        <ul class="sidebar-nav">
            <li><a class="nav-link" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a class="nav-link active" href="monthlylpp.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Monthly LPP</span></a></li>
            <li><a class="nav-link logout-link" href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
            <h2>Security Attendance and Billing Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-landmark"></i> Finance</span>
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <a href="profile.php" style="text-decoration:none;"><div class="user-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/></svg></div></a>
            </div>
        </header>

        <div class="filter-panel">
            <div class="filter-grid">
                <div class="filter-field">
                    <label class="filter-label"><i class="fa-regular fa-calendar"></i> Select Billing Month & Year</label>
                    <input type="month" class="filter-input" id="billingMonth" value="<?= date('Y-m') ?>">
                </div>
                <div class="filter-field">
                    <label class="filter-label" style="visibility:hidden;">Load</label>
                    <button class="btn-load" onclick="loadData()"><i class="fa-solid fa-filter"></i> Load Data</button>
                </div>
            </div>
        </div>

        <!-- No Record -->
        <div id="noRecordCard" class="no-data-msg">
            <i class="fa-solid fa-folder-open"></i>
            <div style="font-size:1.2rem;font-weight:800;color:#1f2937;margin-bottom:0.75rem;">No Record Found</div>
            <div><span class="highlight">Waiting for SDHOD Action:</span> The monthly LPP has not been forwarded yet. Please check back once SDHOD completes verification.</div>
        </div>

        <!-- Data View -->
        <div id="dataView" class="hidden">
            <div class="workflow-section" id="workflowSection"></div>

            <div class="card" style="margin-top:1.5rem;">
                <div class="card-header"><div class="card-title"><i class="fa-solid fa-table-list"></i> LPP Billing Summary (Forwarded by SDHOD)</div></div>
                <div class="billing-table-wrapper">
                    <table class="billing-table" id="billingTable">
                        <thead><tr>
                            <th class="th-center">SL</th><th class="th-left">SITE NAME</th>
                            <th>SEC NO</th><th>DAK NO</th>
                            <th>EMPLOYEES</th><th>PRESENT</th><th>LEAVE</th><th>OT</th>
                            <th>NET PAY</th><th>GST (18%)</th><th>GROSS TOTAL</th>
                        </tr></thead>
                        <tbody id="billingBody"></tbody>
                        <tfoot><tr>
                            <td colspan="4" style="text-align:right;font-weight:800;"><i class="fa-solid fa-sigma" style="margin-right:0.3rem;"></i>GRAND TOTAL</td>
                            <td id="gt-emp"></td><td id="gt-present"></td><td id="gt-leave"></td><td id="gt-ot"></td>
                            <td id="gt-net"></td><td id="gt-gst"></td><td id="gt-gross"></td>
                        </tr></tfoot>
                    </table>
                </div>
            </div>

            <!-- Approve Section (visible only when current_step = FINANCE) -->
            <div class="approve-section" id="approveSection">
                <div class="section-head">
                    <div class="section-icon"><i class="fa-solid fa-gavel"></i></div>
                    <div><div class="section-title">Final Approval</div><div class="section-subtitle">Review the billing data and give final approval.</div></div>
                </div>
                <div style="margin-top:0.75rem;">
                    <label style="font-size:0.82rem;font-weight:700;color:#374151;display:block;margin-bottom:0.4rem;">Add Comments (Optional)</label>
                    <textarea id="approveComment" rows="3" placeholder="Enter your remarks..." style="width:100%;padding:0.75rem 1rem;border:1.5px solid #93c5fd;border-radius:9px;font-size:0.88rem;font-family:inherit;resize:vertical;outline:none;background:#f9fafb;"></textarea>
                </div>
                <div style="display:flex;justify-content:center;margin-top:1.25rem;">
                    <button class="btn-approve" id="approveBtn" onclick="handleApprove()">
                        <i class="fa-solid fa-circle-check"></i> Final Approve
                    </button>
                </div>
            </div>

            <!-- Already approved -->
            <div class="already-done hidden" id="alreadyDone">
                <i class="fa-solid fa-circle-check"></i>
                <div class="title">LPC Finally Approved</div>
                <div class="sub">The workflow is complete. Payment can be processed.</div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* Sidebar */
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebarOverlay');
document.getElementById('hamburgerBtn').addEventListener('click',()=>{sidebar.classList.add('open');overlay.classList.add('active');});
document.getElementById('sidebarClose').addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});

let tableData=[], lpcWorkflow=null;
const fmt=v=>v===0?'₹0.00':'₹'+v.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});
const monthNames=['January','February','March','April','May','June','July','August','September','October','November','December'];

function loadData(){
    const val=document.getElementById('billingMonth').value;
    if(!val){Swal.fire('Oops...', 'Please select a month.', 'warning');return;}
    const [y,m]=val.split('-');

    fetch('get_lpc_data.php?month='+parseInt(m)+'&year='+parseInt(y))
        .then(r=>r.json())
        .then(data=>{
            if(!data||!data.sites||data.sites.length===0){
                document.getElementById('noRecordCard').style.display='block';
                document.getElementById('dataView').classList.add('hidden');
                return;
            }
            tableData=data.sites;
            lpcWorkflow=data.workflow;
            document.getElementById('noRecordCard').style.display='none';
            document.getElementById('dataView').classList.remove('hidden');
            buildTable();
            buildWorkflow();
            handleApproveVisibility();
        })
        .catch(err=>{Swal.fire('Error', 'Error: '+err.message, 'error');});
}

function buildTable(){
    const tbody=document.getElementById('billingBody');tbody.innerHTML='';
    let t={emp:0,present:0,leave:0,ot:0,net:0,gst:0,gross:0};
    tableData.forEach((r,i)=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td class="sn-cell">${i+1}</td><td class="site-name-cell">${r.siteName}</td>
            <td style="text-align:center;">${r.sec_no||'-'}</td><td style="text-align:center;">${r.dak_no||'-'}</td>
            <td style="text-align:center;">${r.employees}</td><td style="text-align:center;">${r.present}</td>
            <td style="text-align:center;">${r.leave}</td><td style="text-align:center;">${r.overtime}</td>
            <td>${fmt(r.total_netpay)}</td><td>${fmt(r.gst_amount)}</td><td>${fmt(r.grand_total)}</td>`;
        tbody.appendChild(tr);
        t.emp+=r.employees;t.present+=r.present;t.leave+=r.leave;t.ot+=r.overtime;
        t.net+=r.total_netpay;t.gst+=r.gst_amount;t.gross+=r.grand_total;
    });
    document.getElementById('gt-emp').textContent=t.emp;
    document.getElementById('gt-present').textContent=t.present;
    document.getElementById('gt-leave').textContent=t.leave;
    document.getElementById('gt-ot').textContent=t.ot;
    document.getElementById('gt-net').innerHTML=fmt(t.net);
    document.getElementById('gt-gst').innerHTML=fmt(t.gst);
    document.getElementById('gt-gross').innerHTML=fmt(t.gross);
}

function buildWorkflow(){
    if(!lpcWorkflow)return;
    const ws=document.getElementById('workflowSection');
    const steps=lpcWorkflow.steps||[];
    let html=`<div class="workflow-title"><i class="fa-solid fa-sitemap"></i> LPC Approval Workflow</div><div class="workflow-steps">`;
    steps.forEach((s,i)=>{
        let cls='pending',sub='Pending',icon=s.Code==='FINANCE'?'fa-landmark':'fa-user-tie';
        if(s.status==='approved'){cls='approved';sub='Approved';}
        else if(lpcWorkflow.current_step===s.Code){cls='current';sub='Active';}
        html+=`<div class="workflow-step ${cls}"><div class="step-card">`;
        if(s.status==='approved')html+=`<span class="step-check"><i class="fa-solid fa-check"></i></span>`;
        html+=`<div class="step-avatar"><i class="fa-solid ${icon}"></i></div><span class="step-label">${s.Code}</span><span class="step-sub">${sub}</span></div></div>`;
        if(i<steps.length-1)html+=`<div class="workflow-arrow"><i class="fa-solid fa-arrow-right"></i></div>`;
    });
    html+=`</div>`;
    const approved=steps.filter(s=>s.status==='approved'&&s.comment);
    if(approved.length>0){
        html+=`<button class="btn-approval" onclick="document.getElementById('lpcComments').classList.toggle('open')"><i class="fa-solid fa-comments"></i> View Comments</button>`;
        html+=`<div id="lpcComments" class="approval-comments">`;
        approved.forEach(s=>{
            html+=`<div class="comment-item"><div class="comment-header"><div class="comment-role">${s.Code}</div><div class="comment-time">${s.acted_at||''}</div></div><div class="comment-text">"${s.comment}"</div></div>`;
        });
        html+=`</div>`;
    }
    ws.innerHTML=html;
}

function handleApproveVisibility(){
    const as=document.getElementById('approveSection');
    const ad=document.getElementById('alreadyDone');
    if(!lpcWorkflow){as.style.display='none';ad.classList.add('hidden');return;}
    if(lpcWorkflow.current_step==='FINANCE'){as.style.display='block';ad.classList.add('hidden');}
    else if(lpcWorkflow.current_step==='COMPLETE'){as.style.display='none';ad.classList.remove('hidden');}
    else {as.style.display='none';ad.classList.add('hidden');}
}

function handleApprove(){
    const comment=document.getElementById('approveComment').value.trim();
    const btn=document.getElementById('approveBtn');
    btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

    const val=document.getElementById('billingMonth').value;
    const [y,m]=val.split('-');

    const fd=new FormData();
    fd.append('lpc_month',parseInt(m));
    fd.append('lpc_year',parseInt(y));
    fd.append('comment',comment);

    fetch('approve_lpc.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                Swal.fire({ icon: 'success', title: 'Success!', text: 'LPC finally approved!', confirmButtonColor: '#2563eb' }).then(() => { loadData(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#2563eb' });
                btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-circle-check"></i> Final Approve';
            }
        })
        .catch(err=>{Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not connect to the server.', confirmButtonColor: '#2563eb' });btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-circle-check"></i> Final Approve';});
}
</script>
</body>
</html>
