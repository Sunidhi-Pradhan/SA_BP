<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly LPP – Security Billing Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root { --primary:#0f766e; --primary-dark:#0d5f58; --sidebar-width:270px; }
        html { scroll-behavior:smooth; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f5f5; color:#333; line-height:1.6; }
        .dashboard-layout { display:grid; grid-template-columns:var(--sidebar-width) 1fr; min-height:100vh; }
        .sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; display:flex; flex-direction:column; }
        .sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,0.12); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:1rem; align-items:center; justify-content:center; z-index:2; }
        .sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .mcl-logo-box { background:white; padding:8px 14px; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .mcl-logo-img { max-width:140px; height:auto; display:block; }
        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li { margin:0.25rem 1rem; }
        .nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; cursor:pointer; }
        .nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }
        .main-content { padding:2rem; overflow-y:auto; display:flex; flex-direction:column; gap:1.5rem; min-width:0; }
        .topbar { display:flex; justify-content:space-between; align-items:center; background:white; border-radius:14px; padding:1rem 1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .hamburger-btn { display:none; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px; width:38px; height:38px; align-items:center; justify-content:center; cursor:pointer; color:#0f766e; font-size:1rem; }
        .topbar h2 { font-size:1.4rem; font-weight:700; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .header-icon { width:40px; height:40px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; color:#6b7280; font-size:1rem; border:1px solid #e5e7eb; }
        .header-icon .badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:0.65rem; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-icon { width:40px; height:40px; border-radius:50%; background:#0f766e; display:flex; align-items:center; justify-content:center; cursor:pointer; }
        .user-icon svg { width:20px; height:20px; stroke:white; }
        .role-badge { display:inline-flex; align-items:center; gap:0.4rem; background:#f0fdf4; color:#15803d; border:1.5px solid #86efac; border-radius:20px; padding:0.3rem 0.9rem; font-size:0.82rem; font-weight:700; letter-spacing:0.5px; }
        .filter-panel { background:white; border-radius:18px; border:1px solid #e5e7eb; box-shadow:0 4px 24px rgba(0,0,0,0.08); padding:2rem 2.5rem 1.75rem; max-width:780px; width:100%; margin:0 auto; }
        .filter-grid { display:grid; grid-template-columns:1fr auto; gap:1.25rem; align-items:end; }
        .filter-field { display:flex; flex-direction:column; gap:0.45rem; }
        .filter-label { display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; }
        .filter-label i { color:#0f766e; }
        .month-picker-wrapper { position:relative; }
        .month-display-input { width:100%; padding:0.8rem 1rem; border:1.5px solid #e5e7eb; border-radius:10px; font-size:0.95rem; color:#1f2937; background:#f9fafb; outline:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; transition:border-color 0.2s; }
        .month-display-input:hover,.month-display-input.open { border-color:#0f766e; background:white; }
        .month-picker-popup { display:none; position:absolute; top:calc(100% + 8px); left:0; background:white; border:1.5px solid #e5e7eb; border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,0.12); padding:1.2rem; z-index:200; min-width:290px; }
        .month-picker-popup.open { display:block; animation:fadeUp 0.2s ease; }
        @keyframes fadeUp { 0%{transform:translateY(8px);opacity:0} 100%{transform:translateY(0);opacity:1} }
        .picker-year { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem; }
        .picker-year-label { font-size:1rem; font-weight:700; color:#1f2937; }
        .picker-year-btn { background:none; border:1.5px solid #e5e7eb; border-radius:8px; width:30px; height:30px; cursor:pointer; color:#374151; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .picker-year-btn:hover { border-color:#0f766e; color:#0f766e; }
        .picker-months { display:grid; grid-template-columns:repeat(4,1fr); gap:0.5rem; }
        .picker-month-btn { padding:0.5rem; border-radius:8px; border:1.5px solid #e5e7eb; background:white; font-size:0.82rem; font-weight:600; color:#374151; cursor:pointer; transition:all 0.2s; text-align:center; }
        .picker-month-btn:hover { border-color:#0f766e; color:#0f766e; background:#f0fdf4; }
        .picker-month-btn.active { background:#0f766e; color:white; border-color:#0f766e; }
        .picker-footer { display:flex; justify-content:space-between; margin-top:0.6rem; padding-top:0.6rem; border-top:1px solid #f0f0f0; font-size:0.8rem; }
        .picker-footer a { color:#6b7280; cursor:pointer; }
        .picker-footer a:hover { color:#0f766e; }
        .picker-this-month { color:#0f766e !important; font-weight:600; }
        .btn-load-report { display:inline-flex; align-items:center; gap:0.6rem; padding:0.85rem 2rem; border-radius:10px; background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; border:none; font-size:0.95rem; font-weight:700; cursor:pointer; white-space:nowrap; transition:all 0.2s; box-shadow:0 4px 14px rgba(15,118,110,0.3); letter-spacing:0.3px; text-transform:uppercase; }
        .btn-load-report:hover { background:linear-gradient(135deg,#0d5f58,#0a4f49); transform:translateY(-1px); }
        .workflow-section { background:white; border-radius:14px; padding:1.5rem 2rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; gap:1.2rem; }
        .workflow-title { display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; font-weight:600; color:#374151; }
        .workflow-title i { color:#0f766e; }
        .workflow-steps { display:flex; align-items:center; justify-content:center; }
        .workflow-step { display:flex; flex-direction:column; align-items:center; gap:0.5rem; }
        .step-card { width:115px; height:115px; border-radius:14px; border:2px solid #e5e7eb; background:#f9fafb; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.35rem; position:relative; padding-top:18px; transition:all 0.2s; }
        .step-avatar { width:54px; height:54px; border-radius:50%; background:#d1d5db; display:flex; align-items:center; justify-content:center; }
        .step-avatar i { font-size:1.55rem; color:white; }
        .step-label { font-size:0.78rem; font-weight:700; color:#6b7280; text-transform:uppercase; }
        .step-sub { font-size:0.65rem; color:#9ca3af; }
        .step-check { position:absolute; top:-11px; left:50%; transform:translateX(-50%); width:22px; height:22px; border-radius:50%; background:#16a34a; color:white; display:flex; align-items:center; justify-content:center; font-size:0.65rem; border:2px solid white; box-shadow:0 1px 4px rgba(0,0,0,0.15); }
        .workflow-step.approved .step-card { border-color:#86efac; background:#f0fdf4; }
        .workflow-step.approved .step-avatar { background:#16a34a; }
        .workflow-step.approved .step-label { color:#15803d; }
        .workflow-step.pending .step-avatar { background:#9ca3af; }
        .workflow-step.pending .step-label { color:#9ca3af; }
        .workflow-arrow { display:flex; align-items:center; padding:0 1.4rem; color:#9ca3af; font-size:1.1rem; margin-bottom:1.5rem; }
        .btn-approval { display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1.4rem; border-radius:8px; background:#fef2f2; color:#b91c1c; border:1.5px solid #fca5a5; font-size:0.84rem; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .btn-approval:hover { background:#fee2e2; }
        .approval-comments { display:none; width:100%; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
        .approval-comments.open { display:block; animation:fadeUp 0.3s ease; }
        .comment-item { padding:0.9rem 1.25rem; border-bottom:1px solid #f0f0f0; background:white; }
        .comment-item:last-child { border-bottom:none; }
        .comment-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem; }
        .comment-role { font-size:0.85rem; font-weight:700; color:#1f2937; }
        .comment-role span { color:#0f766e; margin-left:0.3rem; }
        .comment-time { font-size:0.75rem; color:#9ca3af; display:flex; align-items:center; gap:0.3rem; }
        .comment-text { font-size:0.84rem; color:#4b5563; background:#f9fafb; border-radius:8px; padding:0.5rem 0.85rem; border-left:3px solid #fca5a5; font-style:italic; }
        /* ── UPLOAD COMPARE ── */
        .upload-compare-section { background:white; border-radius:14px; border:1.5px solid #e5e7eb; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:1.25rem 1.75rem; display:flex; flex-direction:column; gap:0.85rem; }
        .upload-compare-title { display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; font-weight:700; color:#1f2937; }
        .upload-compare-title i { color:#0f766e; font-size:0.95rem; }
        .upload-compare-body { display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; }
        .upload-file-input-wrapper { display:flex; align-items:center; gap:0.75rem; }
        .custom-file-btn { display:inline-flex; align-items:center; gap:0.4rem; padding:0.48rem 1.1rem; border-radius:7px; border:1.5px solid #d1d5db; background:#f9fafb; font-size:0.84rem; font-weight:600; color:#374151; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
        .custom-file-btn:hover { border-color:#0f766e; color:#0f766e; background:#f0fdf4; }
        #fileInput { display:none; }
        .file-name-label { font-size:0.84rem; color:#6b7280; min-width:110px; }
        .upload-hint { display:flex; align-items:center; gap:0.35rem; font-size:0.78rem; color:#6b7280; }
        .upload-hint i { color:#0f766e; font-size:0.78rem; }
        .compare-result-bar { display:none; align-items:center; gap:0.75rem; padding:0.75rem 1.1rem; border-radius:10px; font-size:0.88rem; font-weight:600; animation:fadeUp 0.3s ease; }
        .compare-result-bar.match { background:#f0fdf4; border:2px solid #86efac; color:#15803d; }
        .compare-result-bar.mismatch { background:#fef2f2; border:2px solid #fca5a5; color:#b91c1c; }
        .compare-result-bar.show { display:flex; }

        /* ── MISMATCH DETAIL TABLE ── */
        .mismatch-section { display:none; background:white; border-radius:14px; border:2px solid #fca5a5; box-shadow:0 4px 18px rgba(220,38,38,0.10); overflow:hidden; animation:fadeUp 0.35s ease; }
        .mismatch-section.show { display:block; }
        .mismatch-header { display:flex; align-items:center; justify-content:space-between; padding:0.9rem 1.25rem; background:linear-gradient(135deg,#dc2626,#b91c1c); }
        .mismatch-header-left { display:flex; align-items:center; gap:0.6rem; color:white; font-size:0.9rem; font-weight:700; }
        .mismatch-count-badge { background:rgba(255,255,255,0.25); color:white; border-radius:20px; padding:0.15rem 0.65rem; font-size:0.78rem; font-weight:800; border:1.5px solid rgba(255,255,255,0.4); }
        .mismatch-table-wrap { overflow-x:auto; }
        .mismatch-table { width:100%; border-collapse:collapse; font-size:0.81rem; }
        .mismatch-table thead th { background:#fef2f2; color:#b91c1c; font-weight:700; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.65rem 1rem; text-align:left; border-bottom:2px solid #fca5a5; white-space:nowrap; }
        .mismatch-table thead th.th-right { text-align:right; }
        .mismatch-table tbody td { padding:0.62rem 1rem; border-bottom:1px solid #fef2f2; color:#1f2937; white-space:nowrap; vertical-align:middle; }
        .mismatch-table tbody tr:last-child td { border-bottom:none; }
        .mismatch-table tbody tr:hover { background:#fff5f5; }
        .sys-val  { color:#15803d; font-weight:700; text-align:right; }
        .up-val   { color:#b91c1c; font-weight:700; text-align:right; background:#fef2f2; }
        .col-badge { display:inline-flex; align-items:center; gap:0.3rem; padding:0.18rem 0.6rem; border-radius:20px; font-size:0.72rem; font-weight:700; background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
        .row-num  { color:#9ca3af; font-size:0.75rem; font-weight:600; text-align:center; }

        /* ── MAIN TABLE CELL HIGHLIGHT (mismatch in billing table) ── */
        .cell-mismatch { background:#fef2f2 !important; color:#b91c1c !important; font-weight:700 !important; position:relative; }
        .cell-mismatch::after { content:'!'; position:absolute; top:2px; right:3px; font-size:0.55rem; font-weight:900; color:#dc2626; }
        .row-has-mismatch td:nth-child(2) { border-left:3px solid #dc2626 !important; }

        /* ── FORWARD BUTTON STATES ── */
        .forward-btn-wrap { display:flex; justify-content:flex-end; align-items:center; gap:1rem; margin-top:0.85rem; flex-wrap:wrap; }
        .btn-forward { display:inline-flex; align-items:center; gap:0.55rem; padding:0.72rem 1.75rem; border-radius:9px; background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; border:none; font-size:0.9rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(15,118,110,0.3); transition:all 0.2s; }
        .btn-forward:hover:not(:disabled) { transform:translateY(-1px); background:linear-gradient(135deg,#0d5f58,#0a4f49); }
        .btn-forward:disabled { background:linear-gradient(135deg,#9ca3af,#6b7280); box-shadow:none; cursor:not-allowed; opacity:0.7; transform:none; }
        .forward-lock-msg { display:none; align-items:center; gap:0.45rem; font-size:0.82rem; font-weight:600; color:#b91c1c; background:#fef2f2; border:1.5px solid #fca5a5; border-radius:8px; padding:0.45rem 0.9rem; }
        .forward-lock-msg.show { display:flex; }
        .forward-ok-msg { display:none; align-items:center; gap:0.45rem; font-size:0.82rem; font-weight:600; color:#15803d; background:#f0fdf4; border:1.5px solid #86efac; border-radius:8px; padding:0.45rem 0.9rem; }
        .forward-ok-msg.show { display:flex; }
        .card { background:white; border-radius:14px; padding:1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem; flex-wrap:wrap; gap:0.75rem; }
        .card-title { font-size:0.95rem; font-weight:700; color:#0f766e; display:flex; align-items:center; gap:0.5rem; }
        .table-toolbar { display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap; }
        .show-entries { display:flex; align-items:center; gap:0.4rem; font-size:0.86rem; color:#6b7280; }
        .show-entries select { padding:0.38rem 0.6rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.86rem; outline:none; }
        .export-buttons { display:flex; gap:0.45rem; }
        .btn-export { display:inline-flex; align-items:center; gap:0.35rem; padding:0.42rem 0.9rem; border-radius:7px; font-size:0.82rem; font-weight:700; cursor:pointer; border:none; transition:all 0.2s; }
        .btn-excel { background:#16a34a; color:white; } .btn-excel:hover { background:#15803d; }
        .btn-pdf { background:#dc2626; color:white; } .btn-pdf:hover { background:#b91c1c; }
        .search-input { padding:0.46rem 0.9rem 0.46rem 2.35rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.86rem; outline:none; width:190px; background:white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.7rem center; background-size:14px; }
        .search-input:focus { border-color:#0f766e; }
        .billing-table-wrapper { overflow-x:auto; border-radius:10px; border:1px solid #e5e7eb; scrollbar-width:thin; scrollbar-color:#0f766e #f0f0f0; }
        .billing-table-wrapper::-webkit-scrollbar { height:5px; }
        .billing-table-wrapper::-webkit-scrollbar-thumb { background:#0f766e; border-radius:3px; }
        .billing-table { width:max-content; min-width:100%; border-collapse:separate; border-spacing:0; font-size:0.81rem; background:white; }
        .billing-table thead th { background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; font-weight:700; font-size:0.73rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.78rem 0.7rem; text-align:right; white-space:nowrap; }
        .billing-table thead th.th-left { text-align:left; }
        .billing-table thead th.th-center { text-align:center; }
        .billing-table thead th:nth-child(1),.billing-table tbody td:nth-child(1),.billing-table tfoot td:nth-child(1) { position:sticky; left:0; z-index:5; min-width:54px; width:54px; text-align:center; }
        .billing-table thead th:nth-child(2),.billing-table tbody td:nth-child(2),.billing-table tfoot td:nth-child(2) { position:sticky; left:54px; z-index:5; min-width:240px; width:240px; text-align:left; }
        .billing-table thead th:nth-child(1),.billing-table thead th:nth-child(2) { z-index:7; }
        .billing-table tfoot td:nth-child(1),.billing-table tfoot td:nth-child(2) { z-index:6; }
        .billing-table tbody td:nth-child(1),.billing-table tbody td:nth-child(2) { background:white; }
        .billing-table tbody tr:nth-child(even) td:nth-child(1),.billing-table tbody tr:nth-child(even) td:nth-child(2) { background:#fafafa; }
        .billing-table tbody tr:hover td:nth-child(1),.billing-table tbody tr:hover td:nth-child(2) { background:#f0fdf4; }
        .billing-table tbody td:nth-child(2) { border-right:2px solid #e5e7eb !important; }
        .billing-table thead th.col-net,.billing-table tbody td.col-net,.billing-table tfoot td.col-net { position:sticky; right:0; }
        .billing-table thead th.col-net { z-index:7; background:linear-gradient(135deg,#059669,#047857) !important; }
        .billing-table tfoot td.col-net { z-index:6; background:linear-gradient(135deg,#059669,#047857) !important; color:white !important; border-left:2px solid #047857 !important; }
        .billing-table tbody td.col-net { background:#d1fae5; color:#065f46; font-weight:700; border-left:2px solid #6ee7b7 !important; }
        .billing-table tbody tr:nth-child(even) td.col-net { background:#a7f3d0; }
        .billing-table tbody tr:hover td.col-net { background:#6ee7b7; }
        .billing-table tbody td { padding:0.65rem 0.7rem; border-bottom:1px solid #f0f0f0; text-align:right; color:#1f2937; vertical-align:middle; white-space:nowrap; }
        .billing-table tbody tr:hover { background:#f0fdf4; transition:background 0.15s; }
        .billing-table tbody tr:nth-child(even) { background:#fafafa; }
        .billing-table tbody tr:last-child td { border-bottom:none; }
        .sn-cell { color:#9ca3af !important; font-size:0.77rem; font-weight:600; }
        .site-name-cell { font-weight:600; color:#1f2937 !important; font-size:0.82rem; padding-left:0.9rem !important; }
        .billing-table tfoot td { background:linear-gradient(135deg,#f0fdf4,#dcfce7); font-weight:800; color:#065f46; padding:0.78rem 0.7rem; border-top:2px solid #6ee7b7; text-align:right; white-space:nowrap; font-size:0.82rem; }
        .billing-table tfoot td:nth-child(1) { background:linear-gradient(135deg,#f0fdf4,#dcfce7) !important; }
        .billing-table tfoot td:nth-child(2) { background:linear-gradient(135deg,#f0fdf4,#dcfce7) !important; border-right:2px solid #6ee7b7 !important; }
        .grand-label { font-size:0.82rem; font-weight:800; color:#065f46; }
        .pagination { display:flex; align-items:center; justify-content:space-between; margin-top:1.1rem; flex-wrap:wrap; gap:0.6rem; }
        .pagination-info { font-size:0.82rem; color:#6b7280; }
        .pagination-btns { display:flex; align-items:center; gap:0.4rem; }
        .page-btn { padding:0.38rem 0.8rem; border-radius:7px; border:1.5px solid #e5e7eb; background:white; cursor:pointer; font-size:0.86rem; font-weight:600; color:#6b7280; transition:all 0.2s; }
        .page-btn:hover { border-color:#0f766e; color:#0f766e; }
        .page-btn.active { background:#0f766e; color:white; border-color:#0f766e; }
        .page-btn:disabled { opacity:0.4; cursor:not-allowed; }
        .hidden { display:none !important; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }
        @media (max-width:900px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); transition:transform 0.3s; z-index:200; }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .main-content { padding:1rem; gap:1rem; }
            .filter-panel { padding:1.25rem; border-radius:12px; max-width:100%; }
            .filter-grid { grid-template-columns:1fr; }
            .btn-load-report { width:100%; justify-content:center; }
            .upload-compare-body { flex-direction:column; align-items:flex-start; gap:0.75rem; }
            .card-header { flex-direction:column; align-items:flex-start; gap:0.6rem; }
            .table-toolbar { flex-direction:column; align-items:flex-start; width:100%; gap:0.5rem; }
            .search-input { width:100%; box-sizing:border-box; }
            .role-badge { display:none; }
            .pagination { flex-direction:column; align-items:flex-start; gap:0.5rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <div class="mcl-logo-box">
                <img src="../assets/logo/images.png" alt="MCL Logo" class="mcl-logo-img" onerror="this.parentElement.innerHTML='<span style=&quot;font-size:1.4rem;font-weight:900;color:#0f766e;letter-spacing:2px;&quot;>&#9679; MCL</span>'">
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a href="monthlyatt.php" class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a href="monthlylpp.php" class="nav-link active"><i class="fa-solid fa-calendar-check"></i><span>Monthly LPP</span></a></li>
            <li><a href="details_monthly_lpp.php" class="nav-link"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a href="vvstatement.php" class="nav-link"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
            <h2>Security Billing Management Portal</h2>
            <div class="topbar-right">
                <span class="role-badge"><i class="fa-solid fa-user-tie"></i> ADMIN</span>
                <div class="header-icon"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
                <div class="user-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
                    </svg>
                </div>
            </div>
        </header>

        <div class="filter-panel">
            <div class="filter-grid">
                <div class="filter-field">
                    <label class="filter-label"><i class="fa-regular fa-calendar"></i> Select Billing Month &amp; Year</label>
                    <div class="month-picker-wrapper">
                        <div class="month-display-input" id="monthDisplayInput" onclick="togglePicker()">
                            <span id="monthDisplayText">March, 2026</span>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.72rem;color:#6b7280;"></i>
                        </div>
                        <div class="month-picker-popup" id="monthPickerPopup">
                            <div class="picker-year">
                                <button type="button" class="picker-year-btn" onclick="changeYear(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                                <span class="picker-year-label" id="pickerYearLabel">2026</span>
                                <button type="button" class="picker-year-btn" onclick="changeYear(1)"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="picker-months" id="pickerMonths"></div>
                            <div class="picker-footer">
                                <a onclick="clearPicker()">Clear</a>
                                <a class="picker-this-month" onclick="setThisMonth()">This month</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-field">
                    <label class="filter-label" style="visibility:hidden;">Load</label>
                    <button type="button" class="btn-load-report" onclick="loadReport()">
                        <i class="fa-solid fa-filter"></i> Load Data
                    </button>
                </div>
            </div>
        </div>

        <div id="reportSection" class="hidden">

            <div class="workflow-section">
                <div class="workflow-title"><i class="fa-solid fa-sitemap"></i> LPP Report Approval Workflow</div>
                <div class="workflow-steps">
                    <div class="workflow-step approved" style="position:relative;">
                        <div class="step-card" style="border-color:#f59e0b;background:linear-gradient(135deg,#fffbeb,#fef3c7);box-shadow:0 0 0 4px rgba(245,158,11,0.2),0 4px 18px rgba(245,158,11,0.25);">
                            <span class="step-check" style="background:#f59e0b;border-color:#fef3c7;"><i class="fa-solid fa-check"></i></span>
                            <div class="step-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fa-solid fa-user-tie"></i></div>
                            <span class="step-label" style="color:#92400e;font-weight:800;">SDHOD</span>
                            <span class="step-sub" style="color:#b45309;font-weight:600;">Forwarded</span>
                        </div>
                        <span style="position:absolute;bottom:-22px;left:50%;transform:translateX(-50%);font-size:0.68rem;font-weight:700;color:#d97706;white-space:nowrap;background:#fef3c7;border:1.5px solid #f59e0b;border-radius:20px;padding:0.1rem 0.6rem;">&#9679; Active</span>
                    </div>
                    <div class="workflow-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                    <div class="workflow-step pending">
                        <div class="step-card">
                            <div class="step-avatar"><i class="fa-solid fa-landmark"></i></div>
                            <span class="step-label">FINANCE</span>
                            <span class="step-sub">Pending</span>
                        </div>
                    </div>
                </div>
                <button class="btn-approval" id="toggleCommentsBtn" onclick="toggleComments()">
                    <i class="fa-solid fa-comments"></i> View Approval Comments
                    <i class="fa-solid fa-chevron-down" id="commentChevron" style="font-size:0.7rem;"></i>
                </button>
                <div id="approvalComments" class="approval-comments">
                    <div class="comment-item">
                        <div class="comment-header">
                            <div class="comment-role">SDHOD :<span>Rajesh Kumar</span></div>
                            <div class="comment-time"><i class="fa-regular fa-clock" style="font-size:0.68rem;"></i> 2026-03-02 10:30:00</div>
                        </div>
                        <div class="comment-text">"LPP report verified and forwarded for finance approval."</div>
                    </div>
                    <div class="comment-item">
                        <div class="comment-header">
                            <div class="comment-role">FINANCE :<span>Priya Sharma</span></div>
                            <div class="comment-time"><i class="fa-regular fa-clock" style="font-size:0.68rem;"></i> 2026-03-04 14:15:00</div>
                        </div>
                        <div class="comment-text">"Reviewed and approved. Payment to be processed by end of month."</div>
                    </div>
                </div>
            </div>

            <!-- UPLOAD COMPARE -->
            <div class="upload-compare-section" style="margin-top:1.5rem;">
                <div class="upload-compare-title">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    Upload Excel / CSV to Validate &amp; Compare Values
                </div>
                <div class="upload-compare-body">
                    <div class="upload-file-input-wrapper">
                        <label class="custom-file-btn" for="fileInput">
                            <i class="fa-solid fa-folder-open"></i> Choose File
                        </label>
                        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" onchange="handleFileUpload(this)">
                        <span class="file-name-label" id="fileNameLabel">No file chosen</span>
                    </div>
                    <div class="upload-hint">
                        <i class="fa-solid fa-circle-info"></i>
                        Upload the Excel file — all values must match to enable Forward Report
                    </div>
                </div>
                <!-- Result feedback bar -->
                <div class="compare-result-bar" id="compareResultBar">
                    <i id="compareResultIcon"></i>
                    <span id="compareResultText"></span>
                </div>
            </div>

            <!-- MISMATCH DETAIL TABLE (shown below upload section when mismatches found) -->
            <div class="mismatch-section" id="mismatchSection">
                <div class="mismatch-header">
                    <div class="mismatch-header-left">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Mismatch Details
                        <span class="mismatch-count-badge" id="mismatchCountBadge">0 mismatches</span>
                    </div>
                    <span style="color:rgba(255,255,255,0.75);font-size:0.78rem;">Fix the highlighted cells in your Excel and re-upload</span>
                </div>
                <div class="mismatch-table-wrap">
                    <table class="mismatch-table">
                        <thead>
                            <tr>
                                <th style="text-align:center;width:50px;">#</th>
                                <th>Site Name</th>
                                <th>Mismatched Column</th>
                                <th class="th-right">System Value</th>
                                <th class="th-right">Your Uploaded Value</th>
                            </tr>
                        </thead>
                        <tbody id="mismatchBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-top:1.5rem;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fa-solid fa-table-list"></i>
                        System Generated LPP Billing Summary (Source: Master)
                    </div>
                    <div class="table-toolbar">
                        <div class="show-entries">
                            <label>Show</label>
                            <select id="pageSizeSelect" onchange="changePageSize(this.value)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <label>entries</label>
                        </div>
                        <div class="export-buttons">
                            <button class="btn-export btn-excel" onclick="alert('Excel export would download here.')"><i class="fa-solid fa-file-excel"></i> Excel</button>
                            <button class="btn-export btn-pdf" onclick="alert('PDF export would open here.')"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                        </div>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search" oninput="filterTable()">
                    </div>
                </div>
                <div class="billing-table-wrapper">
                    <table class="billing-table" id="billingTable">
                        <thead>
                            <tr>
                                <th class="th-center">SL NO</th>
                                <th class="th-left">SITE NAME</th>
                                <th class="th-center">BES NO</th>
                                <th class="th-center">DAR NO</th>
                                <th>AMOUNT</th>
                                <th>GST (18%)</th>
                                <th>GROSS TOTAL</th>
                                <th>IT TDS</th>
                                <th>SGST</th>
                                <th>CGST</th>
                                <th>RETENTION</th>
                                <th>BONUS</th>
                                <th class="col-net">NET PAYMENT</th>
                            </tr>
                        </thead>
                        <tbody id="billingBody"></tbody>
                        <tfoot>
                            <tr id="grandTotalRow">
                                <td></td>
                                <td class="grand-label"><i class="fa-solid fa-sigma" style="margin-right:0.3rem;"></i>GRAND TOTAL</td>
                                <td></td><td></td>
                                <td id="gt-amount"></td>
                                <td id="gt-gst"></td>
                                <td id="gt-gross"></td>
                                <td id="gt-ittds"></td>
                                <td id="gt-sgst"></td>
                                <td id="gt-cgst"></td>
                                <td id="gt-retention"></td>
                                <td id="gt-bonus"></td>
                                <td class="col-net" id="gt-net"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="pagination" id="paginationBar">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-btns" id="paginationBtns"></div>
                </div>
            </div>

            <!-- FINAL APPROVE -->
            <div style="margin-top:1.5rem;background:white;border-radius:14px;border:2px solid #0f766e;box-shadow:0 2px 12px rgba(15,118,110,0.10);padding:1.5rem 1.75rem;" id="forwardSection">
                <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.3rem;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#0f766e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid fa-paper-plane" style="color:white;font-size:0.95rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1rem;font-weight:800;color:#1f2937;">Final Approve REPORT</div>
                        <div style="font-size:0.78rem;color:#6b7280;margin-top:0.05rem;" id="forwardSubtitle">Upload and validate the Excel file before forwarding this report.</div>
                    </div>
                </div>
                <div style="margin-top:1.1rem;">
                    <label style="font-size:0.82rem;font-weight:700;color:#374151;display:block;margin-bottom:0.45rem;">Add Comments / Remarks (Optional)</label>
                    <textarea id="forwardRemarks" rows="3" placeholder="Enter your comments or remarks here..." style="width:100%;padding:0.75rem 1rem;border:1.5px solid #93c5fd;border-radius:9px;font-size:0.88rem;color:#1f2937;font-family:inherit;resize:vertical;outline:none;transition:border-color 0.2s;background:#f9fafb;" onfocus="this.style.borderColor='#0f766e';this.style.background='white';" onblur="this.style.borderColor='#93c5fd';this.style.background='#f9fafb';"></textarea>
                </div>
                <div class="forward-btn-wrap">
                    <div class="forward-lock-msg" id="forwardLockMsg">
                        <i class="fa-solid fa-lock"></i> Fix all mismatches in the uploaded file to enable forwarding
                    </div>
                    <div class="forward-ok-msg" id="forwardOkMsg">
                        <i class="fa-solid fa-circle-check"></i> All values verified — ready to forward
                    </div>
                    <button class="btn-forward" id="forwardBtn" onclick="handleForwardReport()" disabled>
                        <i class="fa-solid fa-lock" id="forwardBtnIcon"></i>
                        <span id="forwardBtnText">Forward Report</span>
                    </button>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
// ── DUMMY DATA extracted from screenshot ──────────────────────────────────────
// Screenshot shows 36 total entries (4 pages of 10).
// Rows 1-9 all show ₹0. Row 10 (HINGULA OCP 013) has real values.
// Grand Total row: Amount ₹8,57,546 | GST ₹1,54,358 | Gross ₹10,11,904
//                  IT TDS ₹786 | SGST ₹8,576 | CGST ₹8,576 | Ret ₹42,877 | Bonus ₹0 | Net ₹9,51,789
const demoData = [
  // Rows 1–9: all zero (as shown in screenshot)
  { site:"MAHANADI COAL FIELD(001)",                           bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COAL FIELD BHUBANESWAR(002)",               bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COAL FIELD SAMBALPUR(003)",                 bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COAL FIELDS LIMITED(004)",                  bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COALFIELD LIMITED (NSCH COLLEGE)(007)",     bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COALFIELD LIMITED (NSCH HOSPITAL Z)(008)",  bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COALFIELD LIMITED, (HINGULA GM)(010)",      bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COALFIELD LIMITED, (BALANDA, OCP)(011)",    bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  { site:"MAHANADI COALFIELD LIMITED, (BALARAM OCP)(012)",     bes:"1234567890", dar:"987654321", amount:0,      gst:0,     gross:0,      ittds:0,  sgst:0,    cgst:0,    ret:0,     bonus:0, net:0      },
  // Row 10: real values from screenshot
  { site:"MAHANADI COALFIELD LIMITED, (HINGULA OCP)(013)",     bes:"1234567890", dar:"987654321", amount:247985, gst:44637, gross:292622, ittds:25, sgst:2480, cgst:2480, ret:12399, bonus:0, net:275238 },
  // Rows 11–36: realistic dummy values (distributed to approach grand totals)
  { site:"MAHANADI COALFIELD LIMITED, (LILARI OCP)(014)",      bes:"1234567890", dar:"987654321", amount:28500,  gst:5130,  gross:33630,  ittds:30, sgst:285,  cgst:285,  ret:1425,  bonus:0, net:31605  },
  { site:"MAHANADI COALFIELD LIMITED, (JAGANNATH OCP)(015)",   bes:"1234567890", dar:"987654321", amount:32000,  gst:5760,  gross:37760,  ittds:32, sgst:320,  cgst:320,  ret:1600,  bonus:0, net:35488  },
  { site:"MAHANADI COALFIELD LIMITED, (ANANTA OCP)(016)",      bes:"1234567890", dar:"987654321", amount:27500,  gst:4950,  gross:32450,  ittds:28, sgst:275,  cgst:275,  ret:1375,  bonus:0, net:30497  },
  { site:"MAHANADI COALFIELD LIMITED, (BHUBANESWARI OCP)(017)",bes:"1234567890", dar:"987654321", amount:35000,  gst:6300,  gross:41300,  ittds:35, sgst:350,  cgst:350,  ret:1750,  bonus:0, net:38815  },
  { site:"MAHANADI COALFIELD LIMITED, (KULDA OCP)(018)",       bes:"1234567890", dar:"987654321", amount:24000,  gst:4320,  gross:28320,  ittds:24, sgst:240,  cgst:240,  ret:1200,  bonus:0, net:26616  },
  { site:"MAHANADI COALFIELD LIMITED, (LAKHANPUR)(019)",       bes:"1234567890", dar:"987654321", amount:29000,  gst:5220,  gross:34220,  ittds:29, sgst:290,  cgst:290,  ret:1450,  bonus:0, net:32161  },
  { site:"MAHANADI COALFIELD LIMITED, (BELPAHAR)(020)",        bes:"1234567890", dar:"987654321", amount:26500,  gst:4770,  gross:31270,  ittds:27, sgst:265,  cgst:265,  ret:1325,  bonus:0, net:29388  },
  { site:"MAHANADI COALFIELD LIMITED, (ORIENT OCP)(021)",      bes:"1234567890", dar:"987654321", amount:31000,  gst:5580,  gross:36580,  ittds:31, sgst:310,  cgst:310,  ret:1550,  bonus:0, net:34379  },
  { site:"MAHANADI COALFIELD LIMITED, (DERA OCP)(022)",        bes:"1234567890", dar:"987654321", amount:22000,  gst:3960,  gross:25960,  ittds:22, sgst:220,  cgst:220,  ret:1100,  bonus:0, net:24398  },
  { site:"MAHANADI COALFIELD LIMITED, (GARJANBAHAL)(023)",     bes:"1234567890", dar:"987654321", amount:25000,  gst:4500,  gross:29500,  ittds:25, sgst:250,  cgst:250,  ret:1250,  bonus:0, net:27725  },
  { site:"MAHANADI COALFIELD LIMITED, (IB VALLEY)(024)",       bes:"1234567890", dar:"987654321", amount:33000,  gst:5940,  gross:38940,  ittds:33, sgst:330,  cgst:330,  ret:1650,  bonus:0, net:36597  },
  { site:"MAHANADI COALFIELD LIMITED, (BHARATPUR OCP)(025)",   bes:"1234567890", dar:"987654321", amount:20000,  gst:3600,  gross:23600,  ittds:20, sgst:200,  cgst:200,  ret:1000,  bonus:0, net:22180  },
  { site:"MAHANADI COALFIELD LIMITED, (LINGARAJ OCP)(026)",    bes:"1234567890", dar:"987654321", amount:18500,  gst:3330,  gross:21830,  ittds:19, sgst:185,  cgst:185,  ret:925,   bonus:0, net:20516  },
  { site:"MAHANADI COALFIELD LIMITED, (BHAWANIPATNA)(027)",    bes:"1234567890", dar:"987654321", amount:21500,  gst:3870,  gross:25370,  ittds:22, sgst:215,  cgst:215,  ret:1075,  bonus:0, net:23843  },
  { site:"MAHANADI COALFIELD LIMITED, (KALINGA OCP)(028)",     bes:"1234567890", dar:"987654321", amount:19000,  gst:3420,  gross:22420,  ittds:19, sgst:190,  cgst:190,  ret:950,   bonus:0, net:21071  },
  { site:"MAHANADI COALFIELD LIMITED, (SAMALESWARI)(029)",     bes:"1234567890", dar:"987654321", amount:23500,  gst:4230,  gross:27730,  ittds:24, sgst:235,  cgst:235,  ret:1175,  bonus:0, net:26061  },
  { site:"MAHANADI COALFIELD LIMITED, (BANDHABAHAL)(030)",     bes:"1234567890", dar:"987654321", amount:16000,  gst:2880,  gross:18880,  ittds:16, sgst:160,  cgst:160,  ret:800,   bonus:0, net:17744  },
  { site:"MAHANADI COALFIELD LIMITED, (HIRAKHAND OCP)(031)",   bes:"1234567890", dar:"987654321", amount:17500,  gst:3150,  gross:20650,  ittds:18, sgst:175,  cgst:175,  ret:875,   bonus:0, net:19407  },
  { site:"MAHANADI COALFIELD LIMITED, (SIARMAL OCP)(032)",     bes:"1234567890", dar:"987654321", amount:14500,  gst:2610,  gross:17110,  ittds:15, sgst:145,  cgst:145,  ret:725,   bonus:0, net:16080  },
  { site:"MAHANADI COALFIELD LIMITED, (MANOHARPUR)(033)",      bes:"1234567890", dar:"987654321", amount:15000,  gst:2700,  gross:17700,  ittds:15, sgst:150,  cgst:150,  ret:750,   bonus:0, net:16635  },
  { site:"MAHANADI COALFIELD LIMITED, (RAJGANGPUR)(034)",      bes:"1234567890", dar:"987654321", amount:12000,  gst:2160,  gross:14160,  ittds:12, sgst:120,  cgst:120,  ret:600,   bonus:0, net:13308  },
  { site:"MAHANADI COALFIELD LIMITED, (SUNDERGARH)(035)",      bes:"1234567890", dar:"987654321", amount:13500,  gst:2430,  gross:15930,  ittds:14, sgst:135,  cgst:135,  ret:675,   bonus:0, net:14971  },
  { site:"MAHANADI COALFIELD LIMITED, (JHARSUGUDA)(036)",      bes:"1234567890", dar:"987654321", amount:11000,  gst:1980,  gross:12980,  ittds:11, sgst:110,  cgst:110,  ret:550,   bonus:0, net:12199  },
  { site:"MAHANADI COALFIELD LIMITED, (ROURKELA)(037)",        bes:"1234567890", dar:"987654321", amount:10561,  gst:1901,  gross:12462,  ittds:11, sgst:106,  cgst:106,  ret:528,   bonus:0, net:11711  },
  { site:"MAHANADI COALFIELD LIMITED, (BURLA AREA)(038)",      bes:"1234567890", dar:"987654321", amount:9500,   gst:1710,  gross:11210,  ittds:10, sgst:95,   cgst:95,   ret:475,   bonus:0, net:10535  }
];

/* ── SIDEBAR ── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('hamburgerBtn').addEventListener('click',()=>{ sidebar.classList.add('open'); overlay.classList.add('active'); });
document.getElementById('sidebarClose').addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });

/* ── MONTH PICKER ── */
const shortMonths=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const fullMonths=['January','February','March','April','May','June','July','August','September','October','November','December'];
const now=new Date();
let pickerYear=now.getFullYear(), pickerMonth=now.getMonth()+1;

function renderPickerMonths(){
    const grid=document.getElementById('pickerMonths'); grid.innerHTML='';
    shortMonths.forEach((m,i)=>{ const btn=document.createElement('button'); btn.type='button'; btn.className='picker-month-btn'+(i+1===pickerMonth?' active':''); btn.textContent=m; btn.onclick=()=>selectMonth(i+1); grid.appendChild(btn); });
    document.getElementById('pickerYearLabel').textContent=pickerYear;
}
function selectMonth(m){ pickerMonth=m; document.getElementById('monthDisplayText').textContent=fullMonths[m-1]+', '+pickerYear; closePicker(); }
function changeYear(d){ pickerYear+=d; renderPickerMonths(); }
function togglePicker(){ const p=document.getElementById('monthPickerPopup'),i=document.getElementById('monthDisplayInput'),o=p.classList.contains('open'); p.classList.toggle('open',!o); i.classList.toggle('open',!o); if(!o) renderPickerMonths(); }
function closePicker(){ document.getElementById('monthPickerPopup').classList.remove('open'); document.getElementById('monthDisplayInput').classList.remove('open'); }
function clearPicker(){ pickerMonth=now.getMonth()+1; pickerYear=now.getFullYear(); selectMonth(pickerMonth); }
function setThisMonth(){ clearPicker(); }
document.addEventListener('click',e=>{ const w=document.querySelector('.month-picker-wrapper'); if(w&&!w.contains(e.target)) closePicker(); });

/* ── LOAD REPORT ── */
function loadReport(){
    const section=document.getElementById('reportSection');
    section.classList.remove('hidden');
    buildTable();
    section.scrollIntoView({behavior:'smooth',block:'start'});
}

/* ── TABLE BUILD ── */
function fmt(v){ return v===0?'&#8377;0':'&#8377;'+v.toLocaleString('en-IN'); }

function buildTable(){
    const tbody=document.getElementById('billingBody'); tbody.innerHTML='';
    let t={amount:0,gst:0,gross:0,ittds:0,sgst:0,cgst:0,ret:0,bonus:0,net:0};
    demoData.forEach((r,i)=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td class="sn-cell">${i+1}</td><td class="site-name-cell">${r.site}</td><td style="text-align:center;">${r.bes}</td><td style="text-align:center;">${r.dar}</td><td>${fmt(r.amount)}</td><td>${fmt(r.gst)}</td><td style="font-weight:600;">${fmt(r.gross)}</td><td>${fmt(r.ittds)}</td><td>${fmt(r.sgst)}</td><td>${fmt(r.cgst)}</td><td>${fmt(r.ret)}</td><td>${fmt(r.bonus)}</td><td class="col-net">${fmt(r.net)}</td>`;
        tbody.appendChild(tr);
        t.amount+=r.amount; t.gst+=r.gst; t.gross+=r.gross; t.ittds+=r.ittds; t.sgst+=r.sgst; t.cgst+=r.cgst; t.ret+=r.ret; t.bonus+=r.bonus; t.net+=r.net;
    });
    document.getElementById('gt-amount').innerHTML=fmt(t.amount);
    document.getElementById('gt-gst').innerHTML=fmt(t.gst);
    document.getElementById('gt-gross').innerHTML=fmt(t.gross);
    document.getElementById('gt-ittds').innerHTML=fmt(t.ittds);
    document.getElementById('gt-sgst').innerHTML=fmt(t.sgst);
    document.getElementById('gt-cgst').innerHTML=fmt(t.cgst);
    document.getElementById('gt-retention').innerHTML=fmt(t.ret);
    document.getElementById('gt-bonus').innerHTML=fmt(t.bonus);
    document.getElementById('gt-net').innerHTML=fmt(t.net);
    initPagination();
}

/* ── COMMENTS TOGGLE ── */
function toggleComments(){
    const panel=document.getElementById('approvalComments'),open=panel.classList.toggle('open');
    document.getElementById('toggleCommentsBtn').innerHTML=open
        ?'<i class="fa-solid fa-comments"></i> Hide Approval Comments <i class="fa-solid fa-chevron-up" style="font-size:0.7rem;"></i>'
        :'<i class="fa-solid fa-comments"></i> View Approval Comments <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;"></i>';
}

/* ── UPLOAD & COMPARE ── */
// col index in demoData → { field, label, tableColIndex (1-based in billing table) }
// Billing table cols: 1=SL, 2=SITE, 3=BES, 4=DAR, 5=AMT, 6=GST, 7=GROSS, 8=ITTDS, 9=SGST, 10=CGST, 11=RET, 12=BONUS, 13=NET
const COL_MAP = [
    { field:'amount', label:'Amount',      tCol:5  },
    { field:'gst',    label:'GST (18%)',   tCol:6  },
    { field:'gross',  label:'Gross Total', tCol:7  },
    { field:'ittds',  label:'IT TDS',      tCol:8  },
    { field:'sgst',   label:'SGST',        tCol:9  },
    { field:'cgst',   label:'CGST',        tCol:10 },
    { field:'ret',    label:'Retention',   tCol:11 },
    { field:'bonus',  label:'Bonus',       tCol:12 },
    { field:'net',    label:'Net Payment', tCol:13 },
];

function handleFileUpload(input){
    const file = input.files[0]; if(!file) return;
    document.getElementById('fileNameLabel').textContent = file.name;
    // Reset highlights
    clearTableHighlights();
    const reader = new FileReader();
    reader.onload = function(e){
        try{
            let rows = [];
            if(file.name.toLowerCase().endsWith('.csv')){
                rows = e.target.result.trim().split('\n').map(l=>l.split(',').map(c=>c.replace(/^"|"$/g,'').trim()));
            } else {
                const wb = XLSX.read(e.target.result,{type:'array'});
                const ws = wb.Sheets[wb.SheetNames[0]];
                rows = XLSX.utils.sheet_to_json(ws,{header:1,defval:''});
            }
            compareData(rows);
        }catch(err){ showResult(false,'Could not parse file. Please check the format.'); }
    };
    if(file.name.toLowerCase().endsWith('.csv')) reader.readAsText(file);
    else reader.readAsArrayBuffer(file);
}

function parseNum(v){
    if(v===null||v===undefined||v==='') return null;
    const s = String(v).replace(/[₹,\s]/g,'');
    const n = parseFloat(s);
    return isNaN(n) ? null : n;
}

function clearTableHighlights(){
    document.querySelectorAll('#billingBody tr').forEach(tr=>{
        tr.classList.remove('row-has-mismatch');
        tr.querySelectorAll('td').forEach(td=>td.classList.remove('cell-mismatch'));
    });
}

function compareData(rows){
    if(!rows||rows.length<2){ showResult(false,'File is empty or has too few rows.'); return; }

    // Skip title row if present (row[0] often has the portal title), find header row
    // Data rows: skip blank, header-like, and grand-total rows
    const dataRows = rows.filter(r=>{
        const s = String(r[1]||'').toLowerCase().trim();
        return s && s !== 'site name' && !s.includes('grand total') && !s.includes('total');
    });

    if(dataRows.length===0){ showResult(false,'No data rows found. Check your file format.'); return; }

    clearTableHighlights();
    const tbody = document.getElementById('billingBody');
    const tableRows = Array.from(tbody.querySelectorAll('tr')); // all rows (including hidden)

    const mismatches = []; // { rowIdx, site, field, label, tCol, sysVal, uploadedVal }
    let totalChecked = 0;

    dataRows.forEach((row, i)=>{
        const sysRow = demoData[i]; if(!sysRow) return;

        // Excel col layout: col0=SL, col1=SITE, col2=BES, col3=DAR, col4=AMT ... col12=NET
        COL_MAP.forEach((c, fi)=>{
            const excelCol = fi + 4; // 0-based
            const uploadedVal = parseNum(row[excelCol]);
            const sysVal = sysRow[c.field];
            totalChecked++;
            if(uploadedVal !== null && Math.abs(uploadedVal - sysVal) > 0.5){
                mismatches.push({ rowIdx:i, site:sysRow.site, field:c.field, label:c.label, tCol:c.tCol, sysVal, uploadedVal });
            }
        });
    });

    // ── Highlight cells in main billing table ──────────────────────────────
    const mismatchRowSet = new Set(mismatches.map(m=>m.rowIdx));
    mismatches.forEach(m=>{
        const tr = tableRows[m.rowIdx]; if(!tr) return;
        tr.classList.add('row-has-mismatch');
        const td = tr.querySelectorAll('td')[m.tCol - 1]; // tCol is 1-based
        if(td) td.classList.add('cell-mismatch');
    });

    // ── Show mismatch detail table ────────────────────────────────────────
    const mismatchSection = document.getElementById('mismatchSection');
    const mismatchBody    = document.getElementById('mismatchBody');
    mismatchBody.innerHTML = '';

    if(mismatches.length === 0){
        showResult(true, `All ${totalChecked} values verified — uploaded file matches system data perfectly.`);
        mismatchSection.classList.remove('show');
        setForwardEnabled(true);
        // scroll billing table into view so user sees all green
        document.getElementById('billingTable').scrollIntoView({behavior:'smooth', block:'nearest'});
    } else {
        showResult(false, `${mismatches.length} mismatch${mismatches.length>1?'es':''} found across ${mismatchRowSet.size} row${mismatchRowSet.size>1?'s':''}. Fix and re-upload to enable forwarding.`);
        document.getElementById('mismatchCountBadge').textContent = `${mismatches.length} mismatch${mismatches.length>1?'es':''}`;

        mismatches.forEach((m, idx)=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="row-num">${m.rowIdx+1}</td>
                <td style="font-weight:600;color:#1f2937;max-width:260px;overflow:hidden;text-overflow:ellipsis;">${m.site}</td>
                <td><span class="col-badge"><i class="fa-solid fa-arrow-right-arrow-left" style="font-size:0.65rem;"></i> ${m.label}</span></td>
                <td class="sys-val">&#8377;${m.sysVal.toLocaleString('en-IN')}</td>
                <td class="up-val">&#8377;${m.uploadedVal.toLocaleString('en-IN')}</td>
            `;
            mismatchBody.appendChild(tr);
        });

        mismatchSection.classList.add('show');
        setForwardEnabled(false);
        // Scroll to mismatch section
        setTimeout(()=>mismatchSection.scrollIntoView({behavior:'smooth', block:'start'}), 100);
        // Also jump billing table to first mismatched row (navigate to correct page)
        if(mismatches.length > 0){
            const firstMismatchRow = mismatches[0].rowIdx;
            const targetPage = Math.floor(firstMismatchRow / pageSize) + 1;
            curPage = targetPage;
            renderPage();
        }
    }
}

function showResult(isMatch, msg){
    const bar  = document.getElementById('compareResultBar');
    const icon = document.getElementById('compareResultIcon');
    const text = document.getElementById('compareResultText');
    bar.className = 'compare-result-bar show ' + (isMatch ? 'match' : 'mismatch');
    icon.className = isMatch ? 'fa-solid fa-circle-check fa-lg' : 'fa-solid fa-circle-xmark fa-lg';
    text.textContent = msg;
}

function setForwardEnabled(enabled){
    const btn      = document.getElementById('forwardBtn');
    const lockMsg  = document.getElementById('forwardLockMsg');
    const okMsg    = document.getElementById('forwardOkMsg');
    const subtitle = document.getElementById('forwardSubtitle');
    const icon     = document.getElementById('forwardBtnIcon');
    const txt      = document.getElementById('forwardBtnText');

    btn.disabled = !enabled;
    lockMsg.classList.toggle('show', !enabled);
    okMsg.classList.toggle('show', enabled);

    if(enabled){
        icon.className = 'fa-solid fa-share-from-square';
        txt.textContent = 'Forward Report';
        subtitle.textContent = 'All data validated. You can now forward this report.';
    } else {
        icon.className = 'fa-solid fa-lock';
        txt.textContent = 'Forward Report';
        subtitle.textContent = 'Fix all mismatches in the uploaded file before forwarding.';
    }
}

/* ── FORWARD REPORT ── */
function handleForwardReport(){
    const r = document.getElementById('forwardRemarks').value.trim();
    const msg = r ? `Report forwarded successfully!\n\nRemarks: "${r}"` : 'Report forwarded successfully!';
    alert(msg);
}

/* ── PAGINATION ── */
let allRows=[],pageSize=10,curPage=1;
function initPagination(){ allRows=Array.from(document.getElementById('billingBody').querySelectorAll('tr')); curPage=1; renderPage(); }
function renderPage(){
    const visible=allRows.filter(r=>r.dataset.hidden!=='true'),total=visible.length,pages=Math.max(1,Math.ceil(total/pageSize));
    if(curPage>pages) curPage=pages;
    const start=(curPage-1)*pageSize,end=start+pageSize;
    allRows.forEach(r=>r.style.display='none');
    visible.forEach((r,i)=>{ if(i>=start&&i<end) r.style.display=''; });
    const s=total===0?0:start+1,e=Math.min(end,total);
    document.getElementById('paginationInfo').textContent=`Showing ${s} to ${e} of ${total} entries`;
    const btns=document.getElementById('paginationBtns'); btns.innerHTML='';
    const addBtn=(label,page,active,disabled)=>{ const b=document.createElement('button'); b.className='page-btn'+(active?' active':''); b.textContent=label; b.disabled=disabled; if(!disabled) b.onclick=()=>{ curPage=page; renderPage(); }; btns.appendChild(b); };
    addBtn('Previous',curPage-1,false,curPage===1);
    for(let p=1;p<=pages;p++){ if(p===1||p===pages||(p>=curPage-2&&p<=curPage+2)) addBtn(p,p,p===curPage,false); else if(p===curPage-3||p===curPage+3){ const d=document.createElement('span'); d.textContent='…'; d.style.cssText='padding:0 0.25rem;color:#9ca3af;'; btns.appendChild(d); } }
    addBtn('Next',curPage+1,false,curPage===pages);
}
function changePageSize(v){ pageSize=parseInt(v); curPage=1; renderPage(); }

/* ── SEARCH ── */
function filterTable(){ const q=document.getElementById('searchInput').value.toLowerCase(); allRows.forEach(r=>{ r.dataset.hidden=r.textContent.toLowerCase().includes(q)?'false':'true'; }); curPage=1; renderPage(); }
</script>
</body>
</html>