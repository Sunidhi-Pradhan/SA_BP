<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Master - CSV Import</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Excel reader -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            background: white;
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: #0f766e;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 0;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }

        /* .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        } */

        /* .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        } */

        .sidebar-header {
            padding: 30px 25px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.3px;
        }

        .sidebar-header h2 i {
            font-size: 22px;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 25px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            font-size: 15px;
            font-weight: 400;
            position: relative;
        }

        .menu:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 25px;
        }

        .menu.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
            font-weight: 500;
        }

        .menu .icon {
            width: 22px;
            text-align: center;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu.logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            color: rgba(255,255,255,0.9);
        }

        .menu.logout:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 25px;
            background: white;
        }

        .page {
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: none;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .card-header {
            background: #0f766e;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.3);
        }

        .header-left {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-demo {
            background: white;
            color: #0f766e;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .btn-demo:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .card-body {
            padding: 30px;
        }

        .alert {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .alert.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 5px solid #10b981;
        }
        .alert.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 5px solid #ef4444;
        }

        .instructions {
            background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #14b8a6;
            box-shadow: 0 2px 8px rgba(20, 184, 166, 0.08);
        }

        .instructions h4 {
            color: #0f766e;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .instructions h4::before {
            content: '📋';
            font-size: 16px;
        }

        .instructions ul {
            margin-left: 18px;
            margin-bottom: 8px;
            color: #115e59;
        }

        .instructions li {
            margin-bottom: 4px;
            font-size: 12px;
            line-height: 1.4;
        }

        .expected {
            background: white;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
            color: #334155;
            border: 2px solid #5eead4;
            line-height: 1.5;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .expected strong {
            color: #0f766e;
            font-size: 13px;
        }

        .upload-area {
            margin-top: 20px;
            width: 100%;
        }

        .drop-box {
            border: 3px dashed #99f6e4;
            border-radius: 16px;
            padding: 50px 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s ease;
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: inset 0 2px 10px rgba(20, 184, 166, 0.05);
        }

        .drop-box:hover {
            border-color: #14b8a6;
            background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(20, 184, 166, 0.15);
        }

        .dragover {
            border-color: #0f766e !important;
            background: linear-gradient(135deg, #99f6e4 0%, #5eead4 100%) !important;
            transform: scale(1.02);
            box-shadow: 0 15px 40px rgba(15, 118, 110, 0.25) !important;
        }

        .cloud {
            font-size: 56px;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 8px rgba(20, 184, 166, 0.2));
        }

        #dropText {
            font-size: 18px;
            font-weight: 700;
            color: #0f766e;
            margin-bottom: 8px;
        }

        #fileName {
            font-size: 14px;
            color: #14b8a6;
            font-weight: 500;
        }

        .btn-upload {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-upload:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-upload.active:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(15, 118, 110, 0.4);
        }

        /* PREVIEW TABLE */
        #previewSection {
            margin-top: 40px;
            display: none;
            background: #f0fdfa;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #99f6e4;
        }

        #previewSection h4 {
            color: #0f766e;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
        }

        #previewTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        #previewTable th,
        #previewTable td {
            border: 1px solid #99f6e4;
            padding: 14px 16px;
            text-align: left;
        }
        #previewTable th {
            background: linear-gradient(135deg, #99f6e4 0%, #5eead4 100%);
            font-weight: 700;
            color: #0f766e;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        #previewTable tr:hover {
            background: #f0fdfa;
        }

        #previewTable td {
            color: #334155;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                width: 75px;
            }

            .sidebar-header h2 span {
                display: none;
            }

            .menu span:not(.icon) {
                display: none;
            }

            .main-content {
                margin-left: 75px;
                padding: 20px;
            }

            .sidebar:hover {
                width: 280px;
            }

            .sidebar:hover .sidebar-header h2 span,
            .sidebar:hover .menu span:not(.icon) {
                display: inline;
            }

            .card-body {
                padding: 25px;
            }

            .drop-box {
                padding: 60px 30px;
                min-height: 280px;
            }
        }
        .logo img {
            max-width: 140px;   /* adjust as needed */
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 0px;
            }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2 class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </h2>
    </div>

    <a href="dashboard.php" class="menu">
        <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
        <span>Dashboard</span>
    </a>

    <a href="user.php" class="menu">
        <span class="icon"><i class="fa-solid fa-users"></i></span>
        <span>Add Users</span>
    </a>

    <a href="employees.php" class="menu active">
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

    <a href="#" class="menu">
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
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="page">
        <div class="card">

            <!-- HEADER -->
            <div class="card-header">
                <div class="header-left">
                    📄 Employee Master – CSV Import
                </div>
                <a href="demo_employee.csv" class="btn-demo">📥 Download Demo CSV</a>
            </div>

            <div class="card-body">

                <!-- ALERT -->
                <div id="alertBox"></div>

                <!-- INSTRUCTIONS -->
                <div class="instructions">
                    <h4>Import Instructions</h4>
                    <ul>
                        <li>📄 Upload only CSV or Excel file formats (.csv, .xls, .xlsx)</li>
                        <li>📋 The first row must contain column headers</li>
                        <li>🔁 Duplicate ESIC_NO entries will be automatically skipped</li>
                    </ul>

                    <p class="expected">
                        <strong>Expected Columns:</strong><br>
                        ESIC_NO, Site_Name, RegNo, Employee_Name, Rank, Gender,
                        DOB, DOJ, AADHAAR_NO, Father_Name, MOB_NO,
                        AC_NO, IFSC_CODE, Bank_Name, Address
                    </p>
                </div>

                <!-- UPLOAD -->
                <div class="upload-area">
                    <form action="import_employees.php" method="POST" enctype="multipart/form-data">

                        <input type="file"
                               name="employee_file"
                               id="fileInput"
                               accept=".csv,.xls,.xlsx"
                               hidden
                               required>

                        <div class="drop-box" id="dropBox">
                            <div class="cloud">☁️</div>
                            <p id="dropText">Drag & Drop Your File Here</p>
                            <span id="fileName">or click to browse your computer</span>
                        </div>

                        <button type="submit" class="btn-upload" id="uploadBtn" disabled>
                            ⬆️ Upload & Import Employees
                        </button>
                    </form>

                    <!-- PREVIEW -->
                    <div id="previewSection">
                        <h4>📋 File Preview (First 10 Rows)</h4>
                        <div style="overflow-x:auto; max-height:400px;">
                            <table id="previewTable"></table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
const dropBox = document.getElementById("dropBox");
const fileInput = document.getElementById("fileInput");
const fileName = document.getElementById("fileName");
const dropText = document.getElementById("dropText");
const uploadBtn = document.getElementById("uploadBtn");
const previewSection = document.getElementById("previewSection");
const previewTable = document.getElementById("previewTable");

// Click upload
dropBox.addEventListener("click", () => fileInput.click());

// File selected
fileInput.addEventListener("change", () => {
    if (fileInput.files.length) loadFile(fileInput.files[0]);
});

// Drag & drop
dropBox.addEventListener("dragover", e => {
    e.preventDefault();
    dropBox.classList.add("dragover");
});
dropBox.addEventListener("dragleave", () => dropBox.classList.remove("dragover"));
dropBox.addEventListener("drop", e => {
    e.preventDefault();
    dropBox.classList.remove("dragover");
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        loadFile(e.dataTransfer.files[0]);
    }
});

// Load & preview
function loadFile(file) {
    fileName.innerText = "✅ Selected: " + file.name;
    dropText.innerText = "File Loaded Successfully!";
    uploadBtn.disabled = false;
    uploadBtn.classList.add("active");

    previewTable.innerHTML = "";
    previewSection.style.display = "none";

    const reader = new FileReader();
    reader.onload = e => {
        const data = new Uint8Array(e.target.result);
        const wb = XLSX.read(data, { type: "array" });
        const sheet = wb.Sheets[wb.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });
        renderPreview(rows);
    };
    reader.readAsArrayBuffer(file);
}

function renderPreview(rows) {
    if (!rows.length) return;
    previewSection.style.display = "block";

    rows.slice(0, 10).forEach((row, i) => {
        const tr = document.createElement("tr");
        row.forEach(cell => {
            const el = document.createElement(i === 0 ? "th" : "td");
            el.textContent = cell ?? "";
            tr.appendChild(el);
        });
        previewTable.appendChild(tr);
    });
}

// Alert handling
const params = new URLSearchParams(window.location.search);
const alertBox = document.getElementById("alertBox");

if (params.has("inserted") || params.has("skipped")) {
    alertBox.innerHTML = `
        <div class="alert success">
            ✅ Import Completed Successfully!<br>
            <strong>Inserted:</strong> ${params.get("inserted") || 0} employees |
            <strong>Skipped:</strong> ${params.get("skipped") || 0} duplicates
        </div>`;
}
if (params.has("error")) {
    alertBox.innerHTML = `<div class="alert error">❌ Error: ${params.get("error")}</div>`;
}
</script>

</body>
</html>