<?php
// कोणत्याही अनपेक्षित एरर/वार्निंगमुळे JSON खराब होऊ नये म्हणून आउटपुट बफरिंग सुरू करा
ob_start(); 

include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

// भारताच्या वेळेनुसार टाइमझोन सेट
date_default_timezone_set('Asia/Kolkata');

// ⚡ बॅकएंड AJAX रिक्वेस्ट प्रोसेसर
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['scanned_id'])) {
    ob_clean();
    header('Content-Type: application/json');

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed!"]);
        exit;
    }

    $stu_id = mysqli_real_escape_string($conn, trim($_POST['scanned_id']));
    $attendance_date = (!empty($_POST['custom_date'])) ? mysqli_real_escape_string($conn, $_POST['custom_date']) : date("Y-m-d");
    $time = date("H:i:s");
    
    // 🔍 १. 'students' टेबलचे कॉलम्स ऑटो-डिटेक्ट करणे
    $columns = [];
    $detect_cols = mysqli_query($conn, "SHOW COLUMNS FROM students");
    if ($detect_cols) {
        while ($c_row = mysqli_fetch_assoc($detect_cols)) {
            $columns[] = strtolower($c_row['Field']);
        }
    }

    $id_field = in_array('student_id', $columns) ? 'student_id' : (in_array('id', $columns) ? 'id' : 'id');
    $name_field = in_array('name', $columns) ? 'name' : 'name';
    $branch_field = in_array('branch', $columns) ? 'branch' : 'branch';
    $sem_field = in_array('semester', $columns) ? 'semester' : (in_array('sem', $columns) ? 'sem' : '');
    $roll_field = in_array('roll_no', $columns) ? 'roll_no' : (in_array('roll_number', $columns) ? 'roll_number' : $id_field);

    // 🔍 २. 'attendance' टेबलचे कॉलम्स ऑटो-डिटेक्ट करणे
    $att_columns = [];
    $detect_att_cols = mysqli_query($conn, "SHOW COLUMNS FROM attendance");
    if ($detect_att_cols) {
        while ($a_row = mysqli_fetch_assoc($detect_att_cols)) {
            $att_columns[] = strtolower($a_row['Field']);
        }
    }
    $att_id_field = in_array('student_id', $att_columns) ? 'student_id' : (in_array('id', $att_columns) ? 'id' : 'student_id');

    // सुरक्षित डायनामिक क्वेरी बिल्ड करणे
    $select_fields = array_filter([$name_field, $branch_field, $sem_field, ($roll_field !== $id_field ? $roll_field : null)]);
    $select_str = implode(", ", $select_fields) . ($roll_field !== $id_field ? "" : ", $id_field");
    
    if(!str_contains($select_str, $id_field)) {
        $select_str .= ", $id_field";
    }

    // Prepared Statement चा वापर करून सुरक्षित डेटा फॅचिंग
    $query_student = "SELECT $select_str FROM students WHERE $id_field = ?";
    $stmt_stud = mysqli_prepare($conn, $query_student);
    mysqli_stmt_bind_param($stmt_stud, "s", $stu_id);
    mysqli_stmt_execute($stmt_stud);
    $check_student = mysqli_stmt_get_result($stmt_stud);

    if (mysqli_num_rows($check_student) > 0) {
        $s_row = mysqli_fetch_assoc($check_student);
        $name = $s_row[$name_field] ?? 'N/A';
        $branch = $s_row[$branch_field] ?? 'N/A';
        $roll_no = $s_row[$roll_field] ?? $s_row[$id_field] ?? 'N/A';
        $semester = (!empty($sem_field) && isset($s_row[$sem_field])) ? $s_row[$sem_field] : 'N/A';
        
        // 🔄 डुप्लीकेट अटेंडेंस चेक (आजच्या तारखेसाठी)
        $query_att = "SELECT * FROM attendance WHERE $att_id_field = ? AND attendance_date = ?";
        $stmt_att = mysqli_prepare($conn, $query_att);
        mysqli_stmt_bind_param($stmt_att, "ss", $stu_id, $attendance_date);
        mysqli_stmt_execute($stmt_att);
        $check_att = mysqli_stmt_get_result($stmt_att);
        
        if (mysqli_num_rows($check_att) == 0) {
            
            // 📝 डेटाबेस ट्रान्झॅक्शन सुरू करा (डेटा सुरक्षिततेसाठी आणि सर्व ठिकाणी सिंक होण्यासाठी)
            mysqli_begin_transaction($conn);

            try {
                $insert_fields = ["$att_id_field", "attendance_date", "attendance_time", "status"];
                $insert_values = ["?", "?", "?", "'Present'"];
                $bind_params = [$stu_id, $attendance_date, $time];
                $types = "sss";

                if (in_array('branch', $att_columns)) { $insert_fields[] = 'branch'; $insert_values[] = "?"; $bind_params[] = $branch; $types .= "s"; }
                if (in_array('semester', $att_columns)) { $insert_fields[] = 'semester'; $insert_values[] = "?"; $bind_params[] = $semester; $types .= "s"; }
                if (in_array('roll_no', $att_columns)) { $insert_fields[] = 'roll_no'; $insert_values[] = "?"; $bind_params[] = $roll_no; $types .= "s"; }

                $fields_sql = implode(", ", $insert_fields);
                $values_sql = implode(", ", $insert_values);

                $ins_query = "INSERT INTO attendance ($fields_sql) VALUES ($values_sql)";
                $stmt_ins = mysqli_prepare($conn, $ins_query);
                
                mysqli_stmt_bind_param($stmt_ins, $types, ...$bind_params);
                $insert = mysqli_stmt_execute($stmt_ins);
                
                if ($insert) {
                    mysqli_commit($conn); // डेटाबेसमध्ये परमनंट सेव्ह करा
                    echo json_encode([
                        "status" => "success", 
                        "message" => "🚀 $name Attendance Marked Successfully! Report Synced.", 
                        "name" => $name, "branch" => $branch, "roll_no" => $roll_no, "semester" => $semester
                    ]);
                } else {
                    throw new Exception("Insert execution failed");
                }
            } catch (Exception $e) {
                mysqli_rollback($conn); // काही एरर आल्यास बदल मागे घ्या
                echo json_encode(["status" => "error", "message" => "Database Sync Failure: " . mysqli_error($conn)]);
            }
        } else {
            echo json_encode([
                "status" => "warning", 
                "message" => "⚠️ Already Checked-In for Date: $attendance_date.",
                "name" => $name, "branch" => $branch, "roll_no" => $roll_no, "semester" => $semester
            ]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Invalid QR Code! No student record found."]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enterprise QR Attendance Radar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <style>
        body { 
            background: radial-gradient(circle at top right, #0f172a, #020617); 
            min-height: 100vh; 
            color: #f8fafc; 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .glass-card { 
            background: rgba(15, 23, 42, 0.45); 
            backdrop-filter: blur(30px); 
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08); 
            border-radius: 28px; 
            box-shadow: 0 35px 70px -15px rgba(0,0,0,0.7); 
        }
        .scanner-wrapper { 
            position: relative; 
            width: 100%; 
            max-width: 420px; 
            margin: 0 auto; 
            border: 2px solid rgba(56, 189, 248, 0.5); 
            border-radius: 24px; 
            overflow: hidden; 
            box-shadow: 0 0 35px rgba(56, 189, 248, 0.25); 
            background: #000; 
            min-height: 280px; 
        }
        #reader { width: 100%; height: auto; background: #000; border: none !important; }
        #reader button { display: none !important; }
        
        /* 🚨 Live Premium Laser Scanning Line */
        .laser-line {
            position: absolute;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, transparent, #00f2fe, #4facfe, transparent);
            box-shadow: 0 0 18px #00f2fe, 0 0 8px #4facfe;
            animation: cyberLaser 2.5s linear infinite;
            z-index: 5;
            pointer-events: none;
        }
        @keyframes cyberLaser {
            0% { top: 0%; }
            50% { top: 100%; }
            100% { top: 0%; }
        }

        .info-panel { 
            background: rgba(2, 6, 23, 0.7) !important; 
            border: 1px solid rgba(16, 185, 129, 0.4); 
            border-radius: 20px; 
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.15); 
            display: none; 
        }
        .badge-label { font-size: 0.75rem; letter-spacing: 0.08em; color: #94a3b8 !important; font-weight: 700; }
        .upload-box { 
            background: rgba(56, 189, 248, 0.06); 
            border: 2px dashed rgba(56, 189, 248, 0.3); 
            border-radius: 16px; 
            padding: 18px; 
            transition: all 0.3s ease;
            cursor: pointer; 
        }
        .upload-box:hover {
            background: rgba(56, 189, 248, 0.12); 
            border-color: #38bdf8;
        }
        .camera-paused-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,6,23,0.95); display: none; flex-direction: column; align-items: center; justify-content: center; z-index: 10; }
        .form-control-cyber {
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #fff !important;
            border-radius: 12px;
        }
        .form-control-cyber:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
        }
    </style>
</head>
<body>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="glass-card p-4 p-md-5 animate__animated animate__fadeIn">
                
                <!-- Header -->
                <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                    <span class="spinner-grow spinner-grow-sm text-info" role="status"></span>
                    <h3 class="fw-bold text-white m-0" style="letter-spacing: -0.5px;">Attendance Radar <span class="text-info">System</span></h3>
                </div>
                <p class="text-muted small mb-4">Hold the QR code steadily in front of the camera or upload a digital pass.</p>

                <!-- Date Config Box -->
                <div class="row justify-content-center mb-4">
                    <div class="col-sm-8 col-md-6">
                        <div class="p-2 rounded-3" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05);">
                            <label class="form-label small fw-bold text-uppercase text-info tracking-wider mb-1" style="font-size:11px;"><i class="bi bi-calendar-event me-1"></i> Operations Date</label>
                            <input type="date" id="attendance_date_filter" class="form-control form-control-cyber text-center fw-bold btn-sm">
                        </div>
                    </div>
                </div>

                <!-- Premium Scanner Container -->
                <div class="scanner-wrapper mb-4">
                    <div class="laser-line"></div> <!-- Neon Glowing Line -->
                    <div id="reader"></div>
                    <div id="camera-overlay" class="camera-paused-overlay">
                        <i class="bi bi-shield-lock-fill fs-2 text-info animate__animated animate__pulse animate__infinite mb-2"></i>
                        <span class="text-white small fw-bold text-uppercase tracking-wider">Secure Sync Lock</span>
                        <span class="text-muted" id="overlay-reason" style="font-size:11px;">Commiting Engine Log...</span>
                    </div>
                </div>

                <!-- Upload Section -->
                <div class="mb-4">
                    <div class="upload-box text-center" onclick="document.getElementById('qr_file_input').click()">
                        <i class="bi bi-qr-code text-info fs-4 d-block mb-1"></i>
                        <p class="m-0 small text-light fw-bold">Upload Digital Pass Image</p>
                        <input type="file" id="qr_file_input" accept="image/*" style="display: none;" onchange="scanUploadedImage(this)">
                    </div>
                </div>

                <!-- Live Logs Status -->
                <div id="logs" class="alert alert-secondary py-2 rounded-pill fw-bold mb-4" style="font-size:13px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1;">
                    System Status: Initializing Camera Engine...
                </div>

                <!-- Premium Student Stats Panel -->
                <div id="student-card" class="info-panel p-4 text-start animate__animated animate__zoomIn animate__faster mb-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-30 pb-2 mb-3">
                        <h6 class="text-success fw-bold m-0"><i class="bi bi-shield-check me-1"></i> TELEMETRY VERIFIED</h6>
                        <button onclick="clearTerminal()" class="btn btn-outline-danger btn-sm rounded-pill px-3" style="font-size:11px;">Skip Timer & Reset</button>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="p-3 rounded-3" style="background: rgba(56, 189, 248, 0.05); border-left: 4px solid #38bdf8;">
                                <span class="badge-label d-block text-uppercase mb-1">Student Identity Name</span>
                                <strong id="res-name" class="fs-4 text-white fw-bold tracking-wide">-</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 text-center" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                                <span class="badge-label d-block text-uppercase mb-1">ID / Roll Reference</span>
                                <strong id="res-roll" class="text-white fs-5 fw-bold">-</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 text-center" style="background: rgba(245, 158, 11, 0.03); border: 1px solid rgba(245, 158, 11, 0.1);">
                                <span class="badge-label d-block text-uppercase mb-1 text-warning">Semester</span>
                                <strong id="res-sem" class="text-warning fs-5 fw-bold">-</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="px-2">
                                <span class="badge-label d-block text-uppercase mb-1">Academic Branch</span>
                                <strong id="res-branch" class="text-info fs-6 bg-info bg-opacity-10 px-3 py-1.5 rounded border border-info border-opacity-25 d-inline-block">-</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center text-danger small fw-bold" id="timer-msg" style="font-size:12px;">
                        Radar paused. Auto-resume sequence in 5 seconds...
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
let autoClearTimeout = null;
let html5QrCode = null;
let activeCameraId = null;
let isProcessingLock = false; 

document.addEventListener("DOMContentLoaded", function() {
    const today = new Date();
    const yyyy = today.getFullYear();
    let mm = today.getMonth() + 1; 
    let dd = today.getDate();
    if (dd < 10) dd = '0' + dd;
    if (mm < 10) mm = '0' + mm;
    document.getElementById('attendance_date_filter').value = yyyy + '-' + mm + '-' + dd;
    
    initDirectCamera();
});

function sendAttendanceRequest(studentId) {
    let logBox = document.getElementById('logs');
    let studentCard = document.getElementById('student-card');
    let selectedDate = document.getElementById('attendance_date_filter').value;
    
    logBox.className = "alert alert-info py-2 rounded-pill fw-bold text-info bg-info bg-opacity-10 border border-info border-opacity-20";
    logBox.innerText = "Syncing with Core Ledger Database...";

    let formData = new FormData();
    formData.append('scanned_id', studentId);
    formData.append('custom_date', selectedDate);

    fetch('scan_qr.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error("Server HTTP Error: " + response.status);
        return response.text(); 
    })
    .then(rawText => {
        try {
            let data = JSON.parse(rawText); 
            if(autoClearTimeout) clearTimeout(autoClearTimeout);

            if(data.status === 'success' || data.status === 'warning') {
                if(data.status === 'success') {
                    logBox.style.cssText = "background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981;";
                    logBox.className = "alert py-2 rounded-pill fw-bold animate__animated animate__bounceIn";
                } else {
                    logBox.style.cssText = "background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); color: #f59e0b;";
                    logBox.className = "alert py-2 rounded-pill fw-bold animate__animated animate__shakeX";
                }
                
                document.getElementById('res-name').innerText = data.name;
                document.getElementById('res-branch').innerText = data.branch;
                document.getElementById('res-roll').innerText = data.roll_no;
                document.getElementById('res-sem').innerText = data.semester;
                
                studentCard.style.display = "block"; 
                logBox.innerText = data.message;

                // ⏱️ 5 सेकंडांचा ऑटो-रीस्टार्ट टाइमर लॉजिक
                autoClearTimeout = setTimeout(() => { clearTerminal(); }, 5000);
            } else {
                logBox.style.cssText = "background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;";
                logBox.className = "alert py-2 rounded-pill fw-bold animate__animated animate__shakeX";
                logBox.innerText = data.message;
                studentCard.style.display = "none";
                restartCameraHardware();
            }
        } catch (e) {
            logBox.style.cssText = "";
            logBox.className = "alert alert-danger py-3 rounded-4 text-start small text-wrap";
            logBox.innerHTML = "<div class='text-warning fw-bold mb-1'>Server Trace Error:</div><code class='text-white bg-black p-2 d-block rounded border border-danger' style='font-size:11px;'>" + rawText + "</code>";
            restartCameraHardware();
        }
    })
    .catch(error => {
        logBox.className = "alert alert-danger py-2 rounded-pill fw-bold";
        logBox.innerText = error.message;
        restartCameraHardware();
    });
}

function scanUploadedImage(input) {
    if (!input.files || !input.files[0]) return;
    isProcessingLock = true;
    document.getElementById('logs').innerText = "Processing Matrix Asset...";
    document.getElementById('camera-overlay').style.display = "flex";

    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().then(() => { executeFileScan(input.files[0], input); }).catch(() => { executeFileScan(input.files[0], input); });
    } else {
        executeFileScan(input.files[0], input);
    }
}

function executeFileScan(file, input) {
    html5QrCode.scanFile(file, true)
    .then(decodedText => { sendAttendanceRequest(decodedText); input.value = ""; })
    .catch(err => {
        document.getElementById('logs').innerText = "Error: System failed to decode file pass.";
        input.value = "";
        restartCameraHardware();
    });
}

function clearTerminal() {
    if(autoClearTimeout) clearTimeout(autoClearTimeout);
    document.getElementById('res-name').innerText = "-";
    document.getElementById('res-branch').innerText = "-";
    document.getElementById('res-roll').innerText = "-";
    document.getElementById('res-sem').innerText = "-";
    document.getElementById('student-card').style.display = "none";
    document.getElementById('logs').style.cssText = "";
    document.getElementById('logs').className = "alert alert-secondary py-2 rounded-pill fw-bold";
    document.getElementById('logs').innerText = "System Status: Camera Scanning Active...";
    restartCameraHardware();
}

function restartCameraHardware() {
    document.getElementById('camera-overlay').style.display = "none";
    isProcessingLock = false;
    if (html5QrCode && !html5QrCode.isScanning && activeCameraId) {
        html5QrCode.start(
            activeCameraId, { fps: 24, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
            (decodedText) => {
                if (!isProcessingLock) {
                    isProcessingLock = true;
                    document.getElementById('camera-overlay').style.display = "flex";
                    if (html5QrCode.isScanning) {
                        html5QrCode.stop().then(() => { sendAttendanceRequest(decodedText); }).catch(() => { sendAttendanceRequest(decodedText); });
                    } else {
                        sendAttendanceRequest(decodedText);
                    }
                }
            }, () => {}
        ).catch(err => { document.getElementById('logs').innerText = "Camera Start Error."; });
    }
}

function initDirectCamera() {
    html5QrCode = new Html5Qrcode("reader");
    Html5Qrcode.getCameras().then(cameras => {
        if (cameras && cameras.length > 0) {
            activeCameraId = cameras[0].id;
            restartCameraHardware();
        } else {
            document.getElementById('logs').innerText = "No Camera Hardware Detected.";
        }
    }).catch(() => { document.getElementById('logs').innerText = "Camera Permission Access Denied."; });
}
</script>

</body>
</html>
<?php ob_end_flush(); ?>