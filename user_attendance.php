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
    <title>Upload Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/useratt.css">
    <style>
        /* Notification Styles */
        .notification {
            display: none;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            animation: slideDown 0.3s ease-out;
        }

        .notification.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .notification.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .notification h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }

        .notification .stats {
            font-size: 1rem;
            margin: 10px 0;
        }

        .notification .stats b {
            font-weight: 600;
        }

        .notification .error-log {
            margin-top: 15px;
            padding: 10px;
            background: rgba(0,0,0,0.05);
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
        }

        .notification .error-log pre {
            margin: 0;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }

        .notification .close-btn {
            float: right;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .notification .close-btn:hover {
            opacity: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn.submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .logo img {
            max-width: 140px;   /* adjust as needed */
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 5px;
            }
            
    </style>
</head>

<body>

    <div class="container">

        <!-- Sidebar -->
        <aside class="sidebar">
        <h2 class="logo">
            <img src="assets/logo/images.png" alt="MCL Logo">
        </h2>          
        <ul>
            <li><a href="user_dashboard.php">Dashboard</a></li>
            <li class="active"><a href="user_attendance.php">Upload Attendance</a></li>
        </ul>
        </aside>

        <!-- Main Content -->
        <main class="main">

            <!-- Top Bar -->
            <header class="topbar">
                <h3>Security Management Portal</h3>
                <button class="logout">Logout</button>
            </header>

            <!-- Notification Area -->
            <div id="notification" class="notification"></div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="loading-spinner">
                <div class="spinner"></div>
                <p>Uploading attendance data...</p>
            </div>

            <!-- Upload Card -->
            <section class="card upload-card">

        <form 
            method="POST" 
            action="upload_attendance.php" 
            enctype="multipart/form-data"
            id="attendanceForm"
        >

            <h3 class="upload-title">
                Bulk Attendance Upload
                <span>Excel / CSV File</span>
            </h3>

            <div class="upload-box">
                <input 
                    type="file"
                    id="fileUpload"
                    name="attendance_file"
                    accept=".csv,.xls,.xlsx"
                    required
                >

                <label for="fileUpload" id="uploadLabel">
                    <strong>Drag & Drop</strong>
                    <span>or click to upload file</span>
                </label>
            </div>

            <div class="upload-buttons">
                <button type="button" class="btn green">
                    <a href="Attendance.xlsx" target="_blank" class="excel-link">
                        Excel list
                    </a>
                </button>

                <button type="button" class="btn yellow">
                    <a href="demoAttendance.xlsx" target="_blank" class="excel-link">
                        Excel Demo List
                    </a>
                </button>
            </div>

        </form>

        </section>

        <!-- Preview Section - Separate Div -->
        <section class="card preview-card" id="previewCard" style="display: none;">
            <div id="previewContainer"></div>

            <!-- Upload Button -->
            <div id="uploadButtonContainer" style="display: none; text-align: center; margin-top: 30px;">
                <button type="button" id="submitBtn" class="btn submit-btn">
                    Upload Attendance
                </button>
            </div>
        </section>


            <footer>
                © 2026 MCL — All Rights Reserved
            </footer>

        </main>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <script>
        const fileInput = document.getElementById("fileUpload");
        const uploadLabel = document.getElementById("uploadLabel");
        const previewContainer = document.getElementById("previewContainer");
        const uploadButtonContainer = document.getElementById("uploadButtonContainer");
        const previewCard = document.getElementById("previewCard");
        const submitBtn = document.getElementById("submitBtn");
        const notification = document.getElementById("notification");
        const loadingSpinner = document.getElementById("loadingSpinner");
        const attendanceForm = document.getElementById("attendanceForm");

        /* ----------------------------------
        Excel serial → correct date (UTC safe)
        ----------------------------------- */
        function excelSerialToDate(serial) {
            const utcDays = Math.floor(serial - 25569);
            const utcSeconds = utcDays * 86400;
            const date = new Date(utcSeconds * 1000);

            const year  = date.getUTCFullYear();
            const month = String(date.getUTCMonth() + 1).padStart(2, "0");
            const day   = String(date.getUTCDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        }

        /* ----------------------------------
        File change handler
        ----------------------------------- */
        fileInput.addEventListener("change", function () {

            if (!this.files.length) return;

            const file = this.files[0];

            // UI text
            uploadLabel.innerHTML = `
                <strong>Selected File</strong>
                <div class="upload-filename">${file.name}</div>
            `;

            // Clear old preview and notification
            previewContainer.innerHTML = "";
            uploadButtonContainer.style.display = "none";
            previewCard.style.display = "none";
            hideNotification();

            const reader = new FileReader();

            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: "array" });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

                renderPreview(rows);
                
                // Show preview card and upload button after preview is rendered
                previewCard.style.display = "block";
                uploadButtonContainer.style.display = "block";
            };

            reader.readAsArrayBuffer(file);
        });

        /* ----------------------------------
        Render Preview
        ----------------------------------- */
        function renderPreview(rows) {
            if (!rows.length) return;

            let html = `
                <div class="preview-section">
                    <h4 class="preview-title">Preview (First 10 Rows)</h4>
                    <div class="preview-table-wrapper">
                        <table class="preview-table">
                            <thead>
            `;

            // Render header row
            if (rows[0]) {
                html += "<tr>";
                rows[0].forEach(cell => {
                    html += `<th>${cell ?? ""}</th>`;
                });
                html += "</tr>";
            }

            html += "</thead><tbody>";

            // Render data rows (skip header, show next 9 rows)
            rows.slice(1, 10).forEach((row) => {
                html += "<tr>";

                row.forEach((cell, colIndex) => {
                    let value = cell ?? "";

                    // Column 3 (index 3) is ATTENDANCE DATE
                    if (colIndex === 3 && typeof value === "number") {
                        value = excelSerialToDate(value);
                    }

                    html += `<td>${value}</td>`;
                });

                html += "</tr>";
            });

            html += "</tbody></table></div></div>";

            previewContainer.innerHTML = html;
        }

        /* ----------------------------------
        Show Notification
        ----------------------------------- */
        function showNotification(type, title, message) {
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <button class="close-btn" onclick="hideNotification()">&times;</button>
                <h3>${title}</h3>
                <div class="message">${message}</div>
            `;
            notification.style.display = "block";
            
            // Scroll to notification
            notification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        /* ----------------------------------
        Hide Notification
        ----------------------------------- */
        function hideNotification() {
            notification.style.display = "none";
        }

        /* ----------------------------------
        AJAX Form Submission
        ----------------------------------- */
        submitBtn.addEventListener("click", function(e) {
            e.preventDefault();
            
            if (!fileInput.files.length) {
                showNotification('error', '❌ Error', 'Please select a file first.');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = "Uploading...";

            // Show loading spinner
            loadingSpinner.classList.add('active');
            hideNotification();

            // Create FormData
            const formData = new FormData(attendanceForm);

            // AJAX Request
            fetch('upload_attendance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Hide loading spinner
                loadingSpinner.classList.remove('active');
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = "Upload Attendance";

                // Parse the response
                parseAndDisplayResponse(data);

                // Reset form
                attendanceForm.reset();
                uploadLabel.innerHTML = `
                    <strong>Drag & Drop</strong>
                    <span>or click to upload file</span>
                `;
                previewCard.style.display = "none";
            })
            .catch(error => {
                // Hide loading spinner
                loadingSpinner.classList.remove('active');
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = "Upload Attendance";

                showNotification('error', '❌ Upload Failed', `Error: ${error.message}`);
            });
        });

        /* ----------------------------------
        Parse Response and Display
        ----------------------------------- */
        function parseAndDisplayResponse(htmlResponse) {
            // Create a temporary div to parse HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlResponse;

            // Extract information
            const h3 = tempDiv.querySelector('h3');
            const title = h3 ? h3.textContent : '✓ Upload Completed';
            
            // Extract processed and skipped numbers
            const processedMatch = htmlResponse.match(/Processed:\s*<b>(\d+)<\/b>/);
            const skippedMatch = htmlResponse.match(/Skipped:\s*<b>(\d+)<\/b>/);
            
            const processed = processedMatch ? processedMatch[1] : '0';
            const skipped = skippedMatch ? skippedMatch[1] : '0';

            // Extract error log if exists
            const preElement = tempDiv.querySelector('pre');
            const errorLog = preElement ? preElement.textContent : '';

            // Build message
            let message = `
                <div class="stats">
                    Processed: <b>${processed}</b><br>
                    Skipped: <b>${skipped}</b>
                </div>
            `;

            if (errorLog) {
                message += `
                    <div class="error-log">
                        <strong>Error Details:</strong>
                        <pre>${errorLog}</pre>
                    </div>
                `;
            }

            // Determine type (success if processed > 0, error otherwise)
            const type = parseInt(processed) > 0 ? 'success' : 'error';

            showNotification(type, title, message);
        }
    </script>

</body>
</html>