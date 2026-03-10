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

        /* SIDEBAR */
        .sidebar { background:linear-gradient(180deg,#0f766e 0%,#0a5c55 100%); color:white; padding:0; box-shadow:4px 0 24px rgba(13,95,88,0.35); position:sticky; top:0; height:100vh; overflow-y:auto; z-index:100; display:flex; flex-direction:column; }
        .sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,0.12); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:1rem; align-items:center; justify-content:center; z-index:2; }
        .sidebar-logo { padding:1.4rem 1.5rem 1.2rem; border-bottom:1px solid rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .mcl-logo-box { background:white; padding:10px 20px; border-radius:10px; font-size:1.5rem; font-weight:900; color:#0f766e; letter-spacing:2px; }
        .sidebar-nav { list-style:none; padding:1rem 0; flex:1; }
        .sidebar-nav li { margin:0.25rem 1rem; }
        .nav-link { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1.1rem; color:rgba(255,255,255,0.88); text-decoration:none; border-radius:12px; transition:all 0.2s; font-weight:500; font-size:0.95rem; cursor:pointer; }
        .nav-link:hover { background:rgba(255,255,255,0.15); color:#fff; }
        .nav-link.active { background:rgba(255,255,255,0.22); color:#fff; font-weight:600; }
        .nav-link i { font-size:1.05rem; width:22px; text-align:center; opacity:0.9; }
        .logout-link { color:rgba(255,255,255,0.75) !important; }
        .logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#fca5a5 !important; }

        /* MAIN */
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

        /* FILTER */
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

        /* WORKFLOW */
        .workflow-section { background:white; border-radius:14px; padding:1.5rem 2rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; gap:1.2rem; }
        .workflow-title { display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; font-weight:600; color:#374151; }
        .workflow-title i { color:#0f766e; }
        .workflow-steps { display:flex; align-items:center; justify-content:center; }
        .workflow-step { display:flex; flex-direction:column; align-items:center; gap:0.5rem; }
        .step-card { width:115px; height:115px; border-radius:14px; border:2px solid #e5e7eb; background:#f9fafb; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.35rem; position:relative; padding-top:18px; transition:all 0.2s; }
        .step-avatar { width:54px; height:54px; border-radius:50%; background:#d1d5db; display:flex; align-items:center; justify-content:center; }
        .step-avatar i { font-size:1.55rem; color:white; }
        .step-label { font-size:0.78rem; font-weight:700; color:#6b7280; text-transform:uppercase; }
        .step-sub   { font-size:0.65rem; color:#9ca3af; }
        .step-check { position:absolute; top:-11px; left:50%; transform:translateX(-50%); width:22px; height:22px; border-radius:50%; background:#16a34a; color:white; display:flex; align-items:center; justify-content:center; font-size:0.65rem; border:2px solid white; box-shadow:0 1px 4px rgba(0,0,0,0.15); }
        .workflow-step.approved .step-card  { border-color:#86efac; background:#f0fdf4; }
        .workflow-step.approved .step-avatar { background:#16a34a; }
        .workflow-step.approved .step-label  { color:#15803d; }
        .workflow-step.current  .step-card  { border-color:#fca5a5; background:#fef2f2; box-shadow:0 0 0 3px rgba(220,38,38,0.12); }
        .workflow-step.current  .step-avatar { background:#dc2626; }
        .workflow-step.current  .step-label  { color:#b91c1c; }
        .workflow-step.pending  .step-avatar { background:#9ca3af; }
        .workflow-step.pending  .step-label  { color:#9ca3af; }
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

        /* ── UPLOAD COMPARE SECTION ── */
        .upload-compare-section {
            background: white;
            border-radius: 14px;
            border: 1.5px solid #e5e7eb;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 1.25rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .upload-compare-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1f2937;
        }
        .upload-compare-title i { color: #0f766e; font-size: 0.95rem; }
        .upload-compare-body {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .upload-file-input-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        /* Custom file input button */
        .custom-file-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.48rem 1.1rem;
            border-radius: 7px;
            border: 1.5px solid #d1d5db;
            background: #f9fafb;
            font-size: 0.84rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .custom-file-btn:hover {
            border-color: #0f766e;
            color: #0f766e;
            background: #f0fdf4;
        }
        #fileInput { display: none; }
        .file-name-label {
            font-size: 0.84rem;
            color: #6b7280;
            min-width: 110px;
        }
        .upload-hint {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            color: #6b7280;
        }
        .upload-hint i { color: #0f766e; font-size: 0.78rem; }

        /* Compare result area */
        .compare-result-bar {
            display: none;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 600;
            animation: fadeUp 0.3s ease;
        }
        .compare-result-bar.match    { background: #f0fdf4; border: 1.5px solid #86efac; color: #15803d; }
        .compare-result-bar.mismatch { background: #fef2f2; border: 1.5px solid #fca5a5; color: #b91c1c; }
        .compare-result-bar.show     { display: flex; }

        /* Compare difference table */
        .compare-diff-table-wrapper { display: none; overflow-x: auto; border-radius: 10px; border: 1px solid #e5e7eb; margin-top: 0.5rem; }
        .compare-diff-table-wrapper.show { display: block; animation: fadeUp 0.3s ease; }
        .compare-diff-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        .compare-diff-table thead th { background: linear-gradient(135deg,#0f766e,#0d5f58); color: white; font-weight: 700; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.3px; padding: 0.65rem 0.85rem; text-align: left; white-space: nowrap; }
        .compare-diff-table tbody td { padding: 0.6rem 0.85rem; border-bottom: 1px solid #f0f0f0; color: #1f2937; white-space: nowrap; }
        .compare-diff-table tbody tr:last-child td { border-bottom: none; }
        .compare-diff-table tbody tr:nth-child(even) { background: #fafafa; }
        .diff-cell-mismatch { background: #fef2f2 !important; color: #b91c1c; font-weight: 700; }
        .diff-cell-match    { color: #15803d; font-weight: 600; }
        .diff-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.15rem 0.55rem; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .diff-badge.ok  { background: #dcfce7; color: #15803d; }
        .diff-badge.err { background: #fee2e2; color: #b91c1c; }

        /* CARD */
        .card { background:white; border-radius:14px; padding:1.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.08); border:1px solid #e5e7eb; }
        .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem; flex-wrap:wrap; gap:0.75rem; }
        .card-title { font-size:0.95rem; font-weight:700; color:#0f766e; display:flex; align-items:center; gap:0.5rem; }
        .table-toolbar { display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap; }
        .show-entries { display:flex; align-items:center; gap:0.4rem; font-size:0.86rem; color:#6b7280; }
        .show-entries select { padding:0.38rem 0.6rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.86rem; outline:none; }
        .export-buttons { display:flex; gap:0.45rem; }
        .btn-export { display:inline-flex; align-items:center; gap:0.35rem; padding:0.42rem 0.9rem; border-radius:7px; font-size:0.82rem; font-weight:700; cursor:pointer; border:none; transition:all 0.2s; }
        .btn-excel { background:#16a34a; color:white; } .btn-excel:hover { background:#15803d; }
        .btn-pdf   { background:#dc2626; color:white; } .btn-pdf:hover   { background:#b91c1c; }
        .search-input { padding:0.46rem 0.9rem 0.46rem 2.35rem; border:1.5px solid #e5e7eb; border-radius:7px; font-size:0.86rem; outline:none; width:190px; background:white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="%236b7280" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.7rem center; background-size:14px; }
        .search-input:focus { border-color:#0f766e; }

        /* BILLING TABLE */
        .billing-table-wrapper { overflow-x:auto; border-radius:10px; border:1px solid #e5e7eb; scrollbar-width:thin; scrollbar-color:#0f766e #f0f0f0; }
        .billing-table-wrapper::-webkit-scrollbar { height:5px; }
        .billing-table-wrapper::-webkit-scrollbar-thumb { background:#0f766e; border-radius:3px; }
        .billing-table { width:max-content; min-width:100%; border-collapse:separate; border-spacing:0; font-size:0.81rem; background:white; }
        .billing-table thead th { background:linear-gradient(135deg,#0f766e,#0d5f58); color:white; font-weight:700; font-size:0.73rem; text-transform:uppercase; letter-spacing:0.3px; padding:0.78rem 0.7rem; text-align:right; white-space:nowrap; }
        .billing-table thead th.th-left   { text-align:left; }
        .billing-table thead th.th-center { text-align:center; }
        .billing-table thead th:nth-child(1),
        .billing-table tbody td:nth-child(1),
        .billing-table tfoot td:nth-child(1) { position:sticky; left:0; z-index:5; min-width:54px; width:54px; text-align:center; }
        .billing-table thead th:nth-child(2),
        .billing-table tbody td:nth-child(2),
        .billing-table tfoot td:nth-child(2) { position:sticky; left:54px; z-index:5; min-width:240px; width:240px; text-align:left; }
        .billing-table thead th:nth-child(1),
        .billing-table thead th:nth-child(2) { z-index:7; }
        .billing-table tfoot td:nth-child(1),
        .billing-table tfoot td:nth-child(2) { z-index:6; }
        .billing-table tbody td:nth-child(1),
        .billing-table tbody td:nth-child(2) { background:white; }
        .billing-table tbody tr:nth-child(even) td:nth-child(1),
        .billing-table tbody tr:nth-child(even) td:nth-child(2) { background:#fafafa; }
        .billing-table tbody tr:hover td:nth-child(1),
        .billing-table tbody tr:hover td:nth-child(2) { background:#f0fdf4; }
        .billing-table tbody td:nth-child(2) { border-right:2px solid #e5e7eb !important; }
        .billing-table thead th.col-net,
        .billing-table tbody td.col-net,
        .billing-table tfoot td.col-net { position:sticky; right:0; }
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

        /* PAGINATION */
        .pagination { display:flex; align-items:center; justify-content:space-between; margin-top:1.1rem; flex-wrap:wrap; gap:0.6rem; }
        .pagination-info { font-size:0.82rem; color:#6b7280; }
        .pagination-btns { display:flex; align-items:center; gap:0.4rem; }
        .page-btn { padding:0.38rem 0.8rem; border-radius:7px; border:1.5px solid #e5e7eb; background:white; cursor:pointer; font-size:0.86rem; font-weight:600; color:#6b7280; transition:all 0.2s; }
        .page-btn:hover { border-color:#0f766e; color:#0f766e; }
        .page-btn.active { background:#0f766e; color:white; border-color:#0f766e; }
        .page-btn:disabled { opacity:0.4; cursor:not-allowed; }

        /* HIDDEN */
        .hidden { display:none !important; }

        /* SIDEBAR OVERLAY */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99; backdrop-filter:blur(2px); }
        .sidebar-overlay.active { display:block; }

        /* ── TABLET (≤1024px) ── */
        @media (max-width:1024px) {
            :root { --sidebar-width:240px; }
            .main-content { padding:1.5rem; gap:1.25rem; }
            .topbar h2 { font-size:1.15rem; }
            .filter-panel { padding:1.5rem 1.75rem; max-width:100%; }
            .card { padding:1.25rem; }
            .card-header { flex-direction:column; align-items:flex-start; }
            .table-toolbar { width:100%; justify-content:space-between; }
            .search-input { flex:1; min-width:120px; }
            .workflow-section { padding:1.25rem 1rem; }
            .step-card { width:100px; height:100px; }
            .step-avatar { width:46px; height:46px; }
            .step-avatar i { font-size:1.3rem; }
        }

        /* ── MOBILE (≤900px) ── */
        @media (max-width:900px) {
            .dashboard-layout { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; height:100vh; width:var(--sidebar-width); transform:translateX(-100%); transition:transform 0.3s; z-index:200; }
            .sidebar.open { transform:translateX(0); box-shadow:8px 0 32px rgba(0,0,0,0.3); }
            .sidebar-close { display:flex; }
            .hamburger-btn { display:flex; }
            .main-content { padding:1rem; gap:1rem; }
            .filter-panel { padding:1.25rem; border-radius:12px; }
            .filter-grid { grid-template-columns:1fr; }
            .btn-load-report { width:100%; justify-content:center; }
            .upload-compare-body { flex-direction:column; align-items:flex-start; gap:0.75rem; }
            .upload-file-input-wrapper { flex-wrap:wrap; }
            .file-name-label { width:100%; }
            .card-header { flex-direction:column; align-items:flex-start; gap:0.6rem; }
            .table-toolbar { flex-direction:column; align-items:flex-start; width:100%; gap:0.5rem; }
            .show-entries { width:100%; }
            .export-buttons { width:100%; }
            .btn-export { flex:1; justify-content:center; }
            .search-input { width:100%; box-sizing:border-box; }
            .workflow-section { padding:1.1rem; gap:0.9rem; }
            .workflow-steps { flex-wrap:wrap; gap:0.5rem; justify-content:center; }
            .workflow-arrow { padding:0 0.6rem; }
            .step-card { width:95px; height:95px; }
            .step-avatar { width:42px; height:42px; }
            .step-avatar i { font-size:1.15rem; }
            .step-label { font-size:0.72rem; }
            .approval-comments { border-radius:8px; }
            .comment-header { flex-direction:column; align-items:flex-start; gap:0.2rem; }
            .topbar h2 { font-size:1rem; }
            .role-badge { display:none; }
            .pagination { flex-direction:column; align-items:flex-start; gap:0.5rem; }
            .pagination-btns { flex-wrap:wrap; }
        }

        /* ── SMALL MOBILE (≤480px) ── */
        @media (max-width:480px) {
            .main-content { padding:0.75rem; gap:0.75rem; }
            .topbar { padding:0.75rem 1rem; border-radius:10px; }
            .topbar h2 { font-size:0.88rem; }
            .header-icon { width:34px; height:34px; font-size:0.88rem; }
            .user-icon { width:34px; height:34px; }
            .filter-panel { padding:1rem; border-radius:10px; }
            .month-display-input { font-size:0.88rem; padding:0.7rem 0.85rem; }
            .btn-load-report { padding:0.75rem 1.25rem; font-size:0.88rem; }
            .workflow-section { padding:0.9rem 0.75rem; border-radius:10px; }
            .workflow-steps { gap:0.3rem; }
            .workflow-arrow { padding:0 0.4rem; font-size:0.9rem; margin-bottom:1.2rem; }
            .step-card { width:82px; height:88px; border-radius:10px; }
            .step-avatar { width:36px; height:36px; }
            .step-avatar i { font-size:1rem; }
            .step-label { font-size:0.65rem; }
            .step-sub { font-size:0.58rem; }
            .step-check { width:18px; height:18px; font-size:0.55rem; }
            .btn-approval { font-size:0.78rem; padding:0.45rem 1rem; width:100%; justify-content:center; }
            .card { padding:0.85rem; border-radius:10px; }
            .card-title { font-size:0.82rem; }
            .show-entries select { font-size:0.8rem; }
            .btn-export { font-size:0.78rem; padding:0.42rem 0.7rem; }
            .upload-compare-section { padding:1rem; border-radius:10px; }
            .upload-compare-title { font-size:0.82rem; }
            .custom-file-btn { font-size:0.8rem; padding:0.45rem 0.85rem; width:100%; justify-content:center; }
            .upload-hint { font-size:0.72rem; }
            .billing-table { font-size:0.74rem; }
            .billing-table thead th { padding:0.6rem 0.5rem; font-size:0.65rem; }
            .billing-table tbody td { padding:0.5rem 0.5rem; }
            .billing-table tfoot td { padding:0.6rem 0.5rem; font-size:0.74rem; }
            .billing-table thead th:nth-child(2),
            .billing-table tbody td:nth-child(2),
            .billing-table tfoot td:nth-child(2) { min-width:160px; width:160px; }
            .page-btn { padding:0.32rem 0.6rem; font-size:0.78rem; }
            .pagination-info { font-size:0.75rem; }
            .compare-diff-table { font-size:0.72rem; }
            .compare-diff-table thead th { padding:0.5rem 0.6rem; font-size:0.65rem; }
            .compare-diff-table tbody td { padding:0.45rem 0.6rem; }
            .mcl-logo-box { font-size:1.2rem; padding:8px 14px; }
            .nav-link { font-size:0.88rem; padding:0.75rem 0.9rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="sidebar-logo">
            <div class="mcl-logo-box">
                <img src="../assets/logo/images.png" alt="MCL Logo">
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a class="nav-link" onclick="alert('Dashboard')"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
            <li><a class="nav-link" onclick="alert('Monthly Attendance')"><i class="fa-solid fa-calendar-days"></i><span>Monthly Attendance</span></a></li>
            <li><a class="nav-link active"><i class="fa-solid fa-calendar-check"></i><span>Monthly LPP</span></a></li>
            <li><a class="nav-link" onclick="alert('Details Monthly LPP')"><i class="fa-solid fa-list-check"></i><span>Details Monthly LPP</span></a></li>
            <li><a class="nav-link" onclick="alert('VV Statement')"><i class="fa-solid fa-file-invoice"></i><span>VV Statement</span></a></li>
            <li><a class="nav-link logout-link" onclick="alert('Logout')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- MAIN -->
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

        <!-- FILTER PANEL -->
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

        <!-- REPORT SECTION (hidden until Load Data clicked) -->
        <div id="reportSection" class="hidden">

            <!-- WORKFLOW -->
            <div class="workflow-section">
                <div class="workflow-title"><i class="fa-solid fa-sitemap"></i> LPP Report Approval Workflow</div>
                <div class="workflow-steps">
                    <div class="workflow-step approved" style="position:relative;">
                        <div class="step-card" style="border-color:#f59e0b; background:linear-gradient(135deg,#fffbeb,#fef3c7); box-shadow:0 0 0 4px rgba(245,158,11,0.2), 0 4px 18px rgba(245,158,11,0.25);">
                            <span class="step-check" style="background:#f59e0b; border-color:#fef3c7;"><i class="fa-solid fa-check"></i></span>
                            <div class="step-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fa-solid fa-user-tie"></i></div>
                            <span class="step-label" style="color:#92400e; font-weight:800;">SDHOD</span>
                            <span class="step-sub" style="color:#b45309; font-weight:600;">Forwarded</span>
                        </div>
                        <span style="position:absolute;bottom:-22px;left:50%;transform:translateX(-50%);font-size:0.68rem;font-weight:700;color:#d97706;white-space:nowrap;background:#fef3c7;border:1.5px solid #f59e0b;border-radius:20px;padding:0.1rem 0.6rem;">● Active</span>
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

            <!-- ── UPLOAD EXCEL/CSV TO COMPARE VALUES ── -->
            <div class="upload-compare-section" style="margin-top:1.5rem;">
                <div class="upload-compare-title">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    Upload Excel/CSV to Compare Values
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
                        File must have exactly 13 columns and a "Grand Total" row
                    </div>
                </div>

                <!-- Result feedback bar -->
                <div class="compare-result-bar" id="compareResultBar">
                    <i class="fa-solid fa-circle-check" id="compareResultIcon"></i>
                    <span id="compareResultText"></span>
                </div>

                <!-- Difference table (shown when mismatches found) -->
                <div class="compare-diff-table-wrapper" id="compareDiffWrapper">
                    <table class="compare-diff-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Site Name</th>
                                <th>Field</th>
                                <th>System Value</th>
                                <th>Uploaded Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="compareDiffBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- BILLING TABLE CARD -->
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
                        <tbody id="billingBody">
                        </tbody>
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

            <!-- FINAL APPROVE REPORT SECTION -->
            <div style="margin-top:1.5rem;background:white;border-radius:14px;border:2px solid #0f766e;box-shadow:0 2px 12px rgba(15,118,110,0.10);padding:1.5rem 1.75rem;">
                <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.3rem;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#0f766e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid fa-paper-plane" style="color:white;font-size:0.95rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1rem;font-weight:800;color:#1f2937;">Final Approve REPORT</div>
                        <div style="font-size:0.78rem;color:#6b7280;margin-top:0.05rem;">All data validated. You can now forward this report.</div>
                    </div>
                </div>
                <div style="margin-top:1.1rem;">
                    <label style="font-size:0.82rem;font-weight:700;color:#374151;display:block;margin-bottom:0.45rem;">Add Comments / Remarks (Optional)</label>
                    <textarea id="forwardRemarks" rows="3" placeholder="Enter your comments or remarks here..." style="width:100%;padding:0.75rem 1rem;border:1.5px solid #93c5fd;border-radius:9px;font-size:0.88rem;color:#1f2937;font-family:inherit;resize:vertical;outline:none;transition:border-color 0.2s;background:#f9fafb;" onfocus="this.style.borderColor='#0f766e';this.style.background='white';" onblur="this.style.borderColor='#93c5fd';this.style.background='#f9fafb';"></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;margin-top:0.85rem;">
                    <button onclick="handleForwardReport()" style="display:inline-flex;align-items:center;gap:0.55rem;padding:0.72rem 1.75rem;border-radius:9px;background:linear-gradient(135deg,#0f766e,#0d5f58);color:white;border:none;font-size:0.9rem;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(15,118,110,0.3);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='';">
                        <i class="fa-solid fa-share-from-square"></i> Forward Report
                    </button>
                </div>
            </div>

        </div>

    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
/* ── DEMO DATA ── */
const demoData = [
    { site:'MAHANADI COAL FIELD(001)',                    bes:'1234567890', dar:'987654321', amount:247985, gst:44637, gross:292622, ittds:25,   sgst:2480, cgst:2480, ret:12399, bonus:0, net:275238 },
    { site:'MAHANADI COAL FIELD BHUBANE SHWAR(002)',      bes:'1234567890', dar:'987654321', amount:189500, gst:34110, gross:223610, ittds:20,   sgst:1895, cgst:1895, ret:9475,  bonus:0, net:210325 },
    { site:'MAHANADI COAL FIELD SAMBHAL PUR(003)',        bes:'1234567890', dar:'987654321', amount:215300, gst:38754, gross:254054, ittds:22,   sgst:2153, cgst:2153, ret:10765, bonus:0, net:238961 },
    { site:'MAHANADI COAL FIELDS LIMITED(004)',           bes:'1234567890', dar:'987654321', amount:178900, gst:32202, gross:211102, ittds:18,   sgst:1789, cgst:1789, ret:8945,  bonus:0, net:198561 },
    { site:'MAHANADI COALFIELD LIMITED (NSCH COLLEGE)(007)', bes:'1234567890', dar:'987654321', amount:132000, gst:23760, gross:155760, ittds:13, sgst:1320, cgst:1320, ret:6600, bonus:0, net:146507 },
    { site:'MAHANADI COALFIELD LIMITED (NSCH HOSPITAL Z)(008)', bes:'1234567890', dar:'987654321', amount:145600, gst:26208, gross:171808, ittds:15, sgst:1456, cgst:1456, ret:7280, bonus:0, net:161601 },
    { site:'MAHANADI COALFIELD LIMITED (HINGULA GM)(010)',bes:'1234567890', dar:'987654321', amount:198400, gst:35712, gross:234112, ittds:20,   sgst:1984, cgst:1984, ret:9920,  bonus:0, net:220204 },
    { site:'MAHANADI COALFIELD LIMITED (BALANDA, OCP)(011)', bes:'1234567890', dar:'987654321', amount:167200, gst:30096, gross:197296, ittds:17, sgst:1672, cgst:1672, ret:8360, bonus:0, net:185575 },
    { site:'MAHANADI COALFIELD LIMITED (BALARAM OCP)(012)', bes:'1234567890', dar:'987654321', amount:223400, gst:40212, gross:263612, ittds:22, sgst:2234, cgst:2234, ret:11170, bonus:0, net:247952 },
    { site:'MAHANADI COALFIELD LIMITED (HINGULA OCP)(013)', bes:'1234567890', dar:'987654321', amount:191700, gst:34506, gross:226206, ittds:19, sgst:1917, cgst:1917, ret:9585, bonus:0, net:212768 },
    { site:'MAHANADI COALFIELD LIMITED (LINGARAJ OCP)(014)', bes:'1234567890', dar:'987654321', amount:205800, gst:37044, gross:242844, ittds:21, sgst:2058, cgst:2058, ret:10290, bonus:0, net:228417 },
    { site:'MAHANADI COALFIELD LIMITED (JAGANNATH OCP)(015)', bes:'1234567890', dar:'987654321', amount:178500, gst:32130, gross:210630, ittds:18, sgst:1785, cgst:1785, ret:8925, bonus:0, net:198117 },
    { site:'MCL HQ BURLA(016)',                           bes:'1234567890', dar:'987654321', amount:312000, gst:56160, gross:368160, ittds:31,   sgst:3120, cgst:3120, ret:15600, bonus:0, net:346289 },
    { site:'MCL REGIONAL OFFICE SAMBALPUR(017)',          bes:'1234567890', dar:'987654321', amount:267500, gst:48150, gross:315650, ittds:27,   sgst:2675, cgst:2675, ret:13375, bonus:0, net:296898 },
    { site:'MCL TALCHER COALFIELD(018)',                  bes:'1234567890', dar:'987654321', amount:289600, gst:52128, gross:341728, ittds:29,   sgst:2896, cgst:2896, ret:14480, bonus:0, net:321427 },
    { site:'MCL IB VALLEY COALFIELD(019)',                bes:'1234567890', dar:'987654321', amount:234100, gst:42138, gross:276238, ittds:23,   sgst:2341, cgst:2341, ret:11705, bonus:0, net:259828 },
    { site:'MAHANADI COALFIELD ANANTA OCP(020)',          bes:'1234567890', dar:'987654321', amount:156300, gst:28134, gross:184434, ittds:16,   sgst:1563, cgst:1563, ret:7815,  bonus:0, net:173477 },
    { site:'MAHANADI COALFIELD BHARATPUR OCP(021)',       bes:'1234567890', dar:'987654321', amount:143200, gst:25776, gross:168976, ittds:14,   sgst:1432, cgst:1432, ret:7160,  bonus:0, net:158938 },
    { site:'MAHANADI COALFIELD KULDA OCP(022)',           bes:'1234567890', dar:'987654321', amount:187400, gst:33732, gross:221132, ittds:19,   sgst:1874, cgst:1874, ret:9370,  bonus:0, net:207995 },
    { site:'MAHANADI COALFIELD LAJKURA OCP(023)',         bes:'1234567890', dar:'987654321', amount:162800, gst:29304, gross:192104, ittds:16,   sgst:1628, cgst:1628, ret:8140,  bonus:0, net:180692 },
    { site:'MCL DEPOT SAMBALPUR(024)',                    bes:'1234567890', dar:'987654321', amount:98700,  gst:17766, gross:116466, ittds:10,   sgst:987,  cgst:987,  ret:4935,  bonus:0, net:109547 },
    { site:'MCL DEPOT TALCHER(025)',                      bes:'1234567890', dar:'987654321', amount:104500, gst:18810, gross:123310, ittds:10,   sgst:1045, cgst:1045, ret:5225,  bonus:0, net:115985 },
    { site:'MCL HOSPITAL BURLA(026)',                     bes:'1234567890', dar:'987654321', amount:218900, gst:39402, gross:258302, ittds:22,   sgst:2189, cgst:2189, ret:10945, bonus:0, net:242957 },
    { site:'MCL SCHOOL BURLA(027)',                       bes:'1234567890', dar:'987654321', amount:87600,  gst:15768, gross:103368, ittds:9,    sgst:876,  cgst:876,  ret:4380,  bonus:0, net:97227 },
    { site:'MCL GUESTHOUSE BHUBANESWAR(028)',             bes:'1234567890', dar:'987654321', amount:76500,  gst:13770, gross:90270,  ittds:8,    sgst:765,  cgst:765,  ret:3825,  bonus:0, net:84907 },
    { site:'MAHANADI COALFIELD GARJANBAHAL(029)',         bes:'1234567890', dar:'987654321', amount:193200, gst:34776, gross:227976, ittds:19,   sgst:1932, cgst:1932, ret:9660,  bonus:0, net:214433 },
    { site:'MAHANADI COALFIELD NANDIRA WASHERY(030)',     bes:'1234567890', dar:'987654321', amount:145800, gst:26244, gross:172044, ittds:15,   sgst:1458, cgst:1458, ret:7290,  bonus:0, net:161823 },
    { site:'MAHANADI COALFIELD ORIENT MINE(031)',         bes:'1234567890', dar:'987654321', amount:178200, gst:32076, gross:210276, ittds:18,   sgst:1782, cgst:1782, ret:8910,  bonus:0, net:197784 },
    { site:'MCL TRAINING CENTRE SAMBALPUR(032)',          bes:'1234567890', dar:'987654321', amount:67400,  gst:12132, gross:79532,  ittds:7,    sgst:674,  cgst:674,  ret:3370,  bonus:0, net:74807 },
    { site:'MCL CENTRAL WORKSHOP BURLA(033)',             bes:'1234567890', dar:'987654321', amount:211500, gst:38070, gross:249570, ittds:21,   sgst:2115, cgst:2115, ret:10575, bonus:0, net:234744 },
    { site:'MAHANADI COALFIELD BHUBANESWARI OCP(034)',    bes:'1234567890', dar:'987654321', amount:256700, gst:46206, gross:302906, ittds:26,   sgst:2567, cgst:2567, ret:12835, bonus:0, net:284911 },
    { site:'MAHANADI COALFIELD LAKHANPUR OCP(035)',       bes:'1234567890', dar:'987654321', amount:183900, gst:33102, gross:217002, ittds:18,   sgst:1839, cgst:1839, ret:9195,  bonus:0, net:204111 },
    { site:'MCL OFFICE ANGUL(036)',                       bes:'1234567890', dar:'987654321', amount:92300,  gst:16614, gross:108914, ittds:9,    sgst:923,  cgst:923,  ret:4615,  bonus:0, net:102444 },
    { site:'MAHANADI COALFIELD DEULBERA OCP(037)',        bes:'1234567890', dar:'987654321', amount:168700, gst:30366, gross:199066, ittds:17,   sgst:1687, cgst:1687, ret:8435,  bonus:0, net:187240 },
    { site:'MCL RAJGANGPUR OFFICE(038)',                  bes:'1234567890', dar:'987654321', amount:115400, gst:20772, gross:136172, ittds:12,   sgst:1154, cgst:1154, ret:5770,  bonus:0, net:128082 },
    { site:'MAHANADI COALFIELD DERA OCP(039)',            bes:'1234567890', dar:'987654321', amount:199800, gst:35964, gross:235764, ittds:20,   sgst:1998, cgst:1998, ret:9990,  bonus:0, net:221758 },
];

/* ── SIDEBAR ── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('hamburgerBtn').addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('active'); });
document.getElementById('sidebarClose').addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

/* ── MONTH PICKER ── */
const shortMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const fullMonths  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const now = new Date();
let pickerYear  = now.getFullYear();
let pickerMonth = now.getMonth() + 1;

function renderPickerMonths() {
    const grid = document.getElementById('pickerMonths');
    grid.innerHTML = '';
    shortMonths.forEach((m, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'picker-month-btn' + (i+1 === pickerMonth ? ' active' : '');
        btn.textContent = m;
        btn.onclick = () => selectMonth(i+1);
        grid.appendChild(btn);
    });
    document.getElementById('pickerYearLabel').textContent = pickerYear;
}
function selectMonth(m) {
    pickerMonth = m;
    document.getElementById('monthDisplayText').textContent = fullMonths[m-1] + ', ' + pickerYear;
    closePicker();
}
function changeYear(d) { pickerYear += d; renderPickerMonths(); }
function togglePicker() {
    const popup = document.getElementById('monthPickerPopup');
    const input = document.getElementById('monthDisplayInput');
    const open  = popup.classList.contains('open');
    popup.classList.toggle('open', !open);
    input.classList.toggle('open', !open);
    if (!open) renderPickerMonths();
}
function closePicker() {
    document.getElementById('monthPickerPopup').classList.remove('open');
    document.getElementById('monthDisplayInput').classList.remove('open');
}
function clearPicker()  { pickerMonth = now.getMonth()+1; pickerYear = now.getFullYear(); selectMonth(pickerMonth); }
function setThisMonth() { clearPicker(); }
document.addEventListener('click', e => {
    const w = document.querySelector('.month-picker-wrapper');
    if (w && !w.contains(e.target)) closePicker();
});

/* ── LOAD REPORT ── */
function loadReport() {
    const section = document.getElementById('reportSection');
    section.classList.remove('hidden');
    buildTable();
    section.scrollIntoView({ behavior:'smooth', block:'start' });
}

/* ── TABLE BUILD ── */
function fmt(v) { return v === 0 ? '₹0' : '₹' + v.toLocaleString('en-IN'); }

function buildTable() {
    const tbody = document.getElementById('billingBody');
    tbody.innerHTML = '';
    let totals = { amount:0, gst:0, gross:0, ittds:0, sgst:0, cgst:0, ret:0, bonus:0, net:0 };

    demoData.forEach((r, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="sn-cell">${i+1}</td>
            <td class="site-name-cell">${r.site}</td>
            <td style="text-align:center;">${r.bes}</td>
            <td style="text-align:center;">${r.dar}</td>
            <td>${fmt(r.amount)}</td>
            <td>${fmt(r.gst)}</td>
            <td style="font-weight:600;">${fmt(r.gross)}</td>
            <td>${fmt(r.ittds)}</td>
            <td>${fmt(r.sgst)}</td>
            <td>${fmt(r.cgst)}</td>
            <td>${fmt(r.ret)}</td>
            <td>${fmt(r.bonus)}</td>
            <td class="col-net">${fmt(r.net)}</td>
        `;
        tbody.appendChild(tr);
        totals.amount += r.amount; totals.gst += r.gst; totals.gross += r.gross;
        totals.ittds  += r.ittds;  totals.sgst += r.sgst; totals.cgst += r.cgst;
        totals.ret    += r.ret;    totals.bonus += r.bonus; totals.net += r.net;
    });

    document.getElementById('gt-amount').textContent    = fmt(totals.amount);
    document.getElementById('gt-gst').textContent       = fmt(totals.gst);
    document.getElementById('gt-gross').textContent     = fmt(totals.gross);
    document.getElementById('gt-ittds').textContent     = fmt(totals.ittds);
    document.getElementById('gt-sgst').textContent      = fmt(totals.sgst);
    document.getElementById('gt-cgst').textContent      = fmt(totals.cgst);
    document.getElementById('gt-retention').textContent = fmt(totals.ret);
    document.getElementById('gt-bonus').textContent     = fmt(totals.bonus);
    document.getElementById('gt-net').textContent       = fmt(totals.net);

    initPagination();
}

/* ── COMMENTS TOGGLE ── */
function toggleComments() {
    const panel = document.getElementById('approvalComments');
    const open  = panel.classList.toggle('open');
    document.getElementById('toggleCommentsBtn').innerHTML = open
        ? '<i class="fa-solid fa-comments"></i> Hide Approval Comments <i id="commentChevron" class="fa-solid fa-chevron-up" style="font-size:0.7rem;"></i>'
        : '<i class="fa-solid fa-comments"></i> View Approval Comments <i id="commentChevron" class="fa-solid fa-chevron-down" style="font-size:0.7rem;"></i>';
}

/* ── UPLOAD & COMPARE ── */
function handleFileUpload(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('fileNameLabel').textContent = file.name;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            let rows = [];
            if (file.name.endsWith('.csv')) {
                // CSV parse
                const text = e.target.result;
                rows = text.trim().split('\n').map(line =>
                    line.split(',').map(c => c.replace(/^"|"$/g,'').trim())
                );
            } else {
                // XLSX parse
                const wb = XLSX.read(e.target.result, { type: 'array' });
                const ws = wb.Sheets[wb.SheetNames[0]];
                rows = XLSX.utils.sheet_to_json(ws, { header:1, defval:'' });
            }
            compareData(rows);
        } catch(err) {
            showResult(false, 'Could not parse file. Please check the format.');
        }
    };
    if (file.name.endsWith('.csv')) {
        reader.readAsText(file);
    } else {
        reader.readAsArrayBuffer(file);
    }
}

// Column mapping: col index → demoData field (0-based, skip SL NO and SITE NAME)
const colFields = ['amount','gst','gross','ittds','sgst','cgst','ret','bonus','net'];
const colLabels = ['Amount','GST (18%)','Gross Total','IT TDS','SGST','CGST','Retention','Bonus','Net Payment'];

function parseNum(v) {
    if (v === null || v === undefined || v === '') return null;
    const s = String(v).replace(/[₹,\s]/g,'');
    const n = parseFloat(s);
    return isNaN(n) ? null : n;
}

function compareData(rows) {
    if (!rows || rows.length < 2) {
        showResult(false, 'File appears to be empty or has too few rows.');
        return;
    }

    // Find header row (first row)
    // Data rows start from index 1; skip grand total row
    const dataRows = rows.slice(1).filter(r => {
        const siteName = String(r[1]||'').toLowerCase();
        return siteName && !siteName.includes('grand total') && !siteName.includes('total');
    });

    if (dataRows.length === 0) {
        showResult(false, 'No data rows found. Check your file format.');
        return;
    }

    const diffs = [];
    let totalChecked = 0;
    let mismatches = 0;

    dataRows.forEach((row, i) => {
        const sysRow = demoData[i];
        if (!sysRow) return;

        const uploadedSite = String(row[1]||'').trim();

        colFields.forEach((field, fi) => {
            const colIdx = fi + 4; // SL, SITE, BES, DAR = first 4 cols; numeric cols start at index 4
            const uploadedVal = parseNum(row[colIdx]);
            const sysVal = sysRow[field];
            totalChecked++;

            if (uploadedVal !== null && Math.abs(uploadedVal - sysVal) > 0.5) {
                mismatches++;
                diffs.push({
                    sn: i+1,
                    site: sysRow.site,
                    field: colLabels[fi],
                    sysVal,
                    uploadedVal,
                    match: false
                });
            }
        });
    });

    const resultBar = document.getElementById('compareResultBar');
    const diffWrapper = document.getElementById('compareDiffWrapper');
    const diffBody = document.getElementById('compareDiffBody');

    if (mismatches === 0) {
        showResult(true, `All ${totalChecked} values match perfectly between the uploaded file and system data.`);
        diffWrapper.classList.remove('show');
    } else {
        showResult(false, `Found ${mismatches} mismatch${mismatches>1?'es':''} out of ${totalChecked} values checked. See details below.`);
        // Populate diff table
        diffBody.innerHTML = '';
        diffs.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${d.sn}</td>
                <td>${d.site}</td>
                <td>${d.field}</td>
                <td class="diff-cell-match">₹${d.sysVal.toLocaleString('en-IN')}</td>
                <td class="diff-cell-mismatch">₹${d.uploadedVal.toLocaleString('en-IN')}</td>
                <td><span class="diff-badge err"><i class="fa-solid fa-xmark"></i> Mismatch</span></td>
            `;
            diffBody.appendChild(tr);
        });
        diffWrapper.classList.add('show');
    }
}

function showResult(isMatch, msg) {
    const bar = document.getElementById('compareResultBar');
    const icon = document.getElementById('compareResultIcon');
    const text = document.getElementById('compareResultText');
    bar.className = 'compare-result-bar show ' + (isMatch ? 'match' : 'mismatch');
    icon.className = isMatch ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
    text.textContent = msg;
}

/* ── PAGINATION ── */
let allRows  = [];
let pageSize = 10;
let curPage  = 1;

function initPagination() {
    const tbody = document.getElementById('billingBody');
    allRows = Array.from(tbody.querySelectorAll('tr'));
    curPage = 1;
    renderPage();
}
function renderPage() {
    const visible = allRows.filter(r => r.dataset.hidden !== 'true');
    const total   = visible.length;
    const pages   = Math.max(1, Math.ceil(total / pageSize));
    if (curPage > pages) curPage = pages;
    const start = (curPage-1) * pageSize, end = start + pageSize;
    allRows.forEach(r => r.style.display = 'none');
    visible.forEach((r, i) => { if (i >= start && i < end) r.style.display = ''; });

    const info = document.getElementById('paginationInfo');
    const s = total === 0 ? 0 : start+1, e = Math.min(end, total);
    info.textContent = `Showing ${s} to ${e} of ${total} entries`;

    const btns = document.getElementById('paginationBtns');
    btns.innerHTML = '';
    const addBtn = (label, page, active, disabled) => {
        const b = document.createElement('button');
        b.className = 'page-btn' + (active ? ' active' : '');
        b.textContent = label; b.disabled = disabled;
        if (!disabled) b.onclick = () => { curPage = page; renderPage(); };
        btns.appendChild(b);
    };
    addBtn('Previous', curPage-1, false, curPage===1);
    for (let p=1; p<=pages; p++) {
        if (p===1 || p===pages || (p>=curPage-2 && p<=curPage+2)) {
            addBtn(p, p, p===curPage, false);
        } else if (p===curPage-3 || p===curPage+3) {
            const d = document.createElement('span');
            d.textContent='…'; d.style.cssText='padding:0 0.25rem;color:#9ca3af;';
            btns.appendChild(d);
        }
    }
    addBtn('Next', curPage+1, false, curPage===pages);
}
function changePageSize(v) { pageSize = parseInt(v); curPage = 1; renderPage(); }

/* ── FORWARD REPORT ── */
function handleForwardReport() {
    const remarks = document.getElementById('forwardRemarks').value.trim();
    const msg = remarks ? `Report forwarded successfully!\n\nRemarks: "${remarks}"` : 'Report forwarded successfully!';
    alert(msg);
}

/* ── SEARCH ── */
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    allRows.forEach(r => { r.dataset.hidden = r.textContent.toLowerCase().includes(q) ? 'false' : 'true'; });
    curPage = 1;
    renderPage();
}
</script>
</body>
</html>