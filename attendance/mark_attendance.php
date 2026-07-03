<?php
/**
 * Enterprise Attendance Management System
 * Core Controller: Granular Row Locking with Live New-Student Discovery Engine
 */

include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

// अचूक इंडियन टाइमझोन सेट करा
date_default_timezone_set('Asia/Kolkata');
$today_date = date("Y-m-d");

// Anti-CSRF टोकन जनरेशन
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// फिल्टर पॅरामीटर्स सॅनिटायझेशन
$branch = isset($_GET['branch']) ? mysqli_real_escape_string($conn, trim($_GET['branch'])) : '';
$sem    = isset($_GET['sem']) ? mysqli_real_escape_string($conn, trim($_GET['sem'])) : '';

// तारीख युझर कीबोर्डने बदलू शकतो
$date   = (!empty($_GET['date'])) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : $today_date; 
$time   = isset($_GET['time']) ? mysqli_real_escape_string($conn, trim($_GET['time'])) : date("H:i");

$dataset = [];
$step2 = false;
$is_any_student_marked = false;
$show_success_overlay = false; // नवीन ट्रॅकर

if (isset($_GET['next_step']) && !empty($branch) && !empty($sem)) {
    $step2 = true;
    
    preg_match('/\d+/', $sem, $matches);
    $sem_digit = isset($matches[0]) ? $matches[0] : $sem;
    
    $branch_param = $branch;
    $sem_param_1  = "%" . $sem . "%";
    $sem_param_2  = "%" . $sem_digit . "%";
    
    $sql = "SELECT s.student_id, s.name AS student_name, s.branch, s.sem AS semester,
                   a.status AS saved_status, a.attendance_date, a.attendance_time, a.id AS attendance_id
            FROM students s
            LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = ?
            WHERE s.branch = ? AND (s.sem LIKE ? OR s.sem LIKE ?)
            ORDER BY s.name ASC";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $date, $branch_param, $sem_param_1, $sem_param_2);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['saved_status'])) {
            $is_any_student_marked = true;
        } else {
            $row['attendance_date'] = $date;
            $row['attendance_time'] = $time;
            $row['saved_status'] = '';
        }
        $dataset[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// सबमिशन मॅनेजर (Insert / Update Controller)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_master_attendance'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Exception: CSRF Token Validation Failed.");
    }

    $final_date = mysqli_real_escape_string($conn, $_POST['final_date']); 
    $final_time = mysqli_real_escape_string($conn, $_POST['final_time']);
    $final_branch = mysqli_real_escape_string($conn, $_POST['final_branch']);
    $final_semester = mysqli_real_escape_string($conn, $_POST['final_semester']);
    $attendance_data = isset($_POST['status']) ? $_POST['status'] : [];

    if (!empty($attendance_data)) {
        mysqli_begin_transaction($conn);
        try {
            foreach ($attendance_data as $stu_id => $status_value) {
                $stu_id = mysqli_real_escape_string($conn, $stu_id);
                $status_value = mysqli_real_escape_string($conn, $status_value);

                $check_stmt = mysqli_prepare($conn, "SELECT id FROM attendance WHERE student_id=? AND attendance_date=?");
                mysqli_stmt_bind_param($check_stmt, "ss", $stu_id, $final_date);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if (mysqli_stmt_num_rows($check_stmt) == 0) {
                    $ins_stmt = mysqli_prepare($conn, "INSERT INTO attendance (student_id, attendance_date, attendance_time, status, branch, semester) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($ins_stmt, "ssssss", $stu_id, $final_date, $final_time, $status_value, $final_branch, $final_semester);
                    mysqli_stmt_execute($ins_stmt);
                    mysqli_stmt_close($ins_stmt);
                } else {
                    $upd_stmt = mysqli_prepare($conn, "UPDATE attendance SET status=?, attendance_time=?, branch=?, semester=? WHERE student_id=? AND attendance_date=?");
                    mysqli_stmt_bind_param($upd_stmt, "ssssss", $status_value, $final_time, $final_branch, $final_semester, $stu_id, $final_date);
                    mysqli_stmt_execute($upd_stmt);
                    mysqli_stmt_close($upd_stmt);
                }
                mysqli_stmt_close($check_stmt);
            }
            mysqli_commit($conn);
            $show_success_overlay = true; // अलर्ट ऐवजी ट्रॅकर ऑन केला
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "<script>alert('Transaction Failed: Data integrity compromised.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Live Attendance Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            background-attachment: fixed;
            min-height: 100vh;
            color: #f8fafc;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .premium-panel {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6);
        }
        .filter-zone {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 16px;
        }
        .form-label-glowing {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #38bdf8 !important;
            font-weight: 800 !important;
            text-shadow: 0 0 12px rgba(56, 189, 248, 0.7);
        }
        .glowing-filter-input {
            background-color: rgba(15, 23, 42, 0.8) !important;
            border: 2px solid #38bdf8 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 1.05rem !important;
            letter-spacing: 0.5px;
            border-radius: 12px !important;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.35), inset 0 0 10px rgba(56, 189, 248, 0.1) !important;
            transition: all 0.3s ease-in-out;
        }
        .glowing-filter-input:focus {
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 25px rgba(14, 165, 233, 0.7), inset 0 0 15px rgba(14, 165, 233, 0.2) !important;
            transform: scale(1.01);
        }
        .glowing-filter-input option {
            background-color: #0f172a !important;
            color: #fff !important;
            font-weight: 600;
        }
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) drop-shadow(0px 0px 4px #38bdf8);
            cursor: pointer;
        }
        .btn-check-present:checked + .label-present {
            background-color: #10b981 !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            border-color: #10b981 !important;
        }
        .btn-check-absent:checked + .label-absent {
            background-color: #ef4444 !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
            border-color: #ef4444 !important;
        }
        .pill-btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 8px 18px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .individual-row-locked {
            position: relative;
        }
        .individual-row-locked::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: transparent;
            z-index: 100;
            cursor: not-allowed;
        }
        .premium-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 10000;
            display: none;
            padding: 18px 32px;
            border-radius: 16px;
            color: white;
            font-weight: 600;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .table-custom th {
            background-color: rgba(15, 23, 42, 0.7) !important;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            padding: 18px;
        }
        .table-custom td {
            background-color: transparent !important;
            color: #cbd5e1;
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .highlight-meta-box {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(14, 165, 233, 0.25);
            padding: 10px 14px;
            border-radius: 12px;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.05), 0 4px 12px rgba(0,0,0,0.25);
        }
        .highlight-time-box {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(30, 41, 59, 0.6));
            border: 1px solid rgba(245, 158, 11, 0.25);
            padding: 10px 14px;
            border-radius: 12px;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.05), 0 4px 12px rgba(0,0,0,0.25);
        }
        .text-neon-cyan { color: #38bdf8 !important; text-shadow: 0 0 10px rgba(56, 189, 248, 0.2); }
        .text-neon-amber { color: #fbbf24 !important; text-shadow: 0 0 10px rgba(251, 191, 36, 0.2); }
        .text-neon-orange { color: #f97316 !important; text-shadow: 0 0 10px rgba(249, 115, 22, 0.3); }

        /* 🎆 HIGH-END FULL SCREEN SUCCESS OVERLAY & ANIMATION */
        .success-fullscreen-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(5, 9, 20, 0.92); backdrop-filter: blur(20px);
            z-index: 99999; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }
        .success-circle-wrapper {
            width: 140px; height: 140px; position: relative;
            display: flex; justify-content: center; align-items: center;
        }
        .svg-checkmark {
            width: 130px; height: 130px; border-radius: 50%;
            display: block; stroke-width: 4; stroke: #10b981;
            stroke-miterlimit: 10; box-shadow: inset 0px 0px 0px #10b981;
            animation: fillCheckmark .4s ease-in-out .4s forwards, scaleCheckmark .3s ease-in-out .9s forwards;
        }
        .svg-checkmark-circle {
            stroke-dasharray: 166; stroke-dashoffset: 166; stroke-width: 4;
            stroke-miterlimit: 10; stroke: #10b981; fill: none;
            animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .svg-checkmark-check {
            transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48;
            animation: strokeCheckmark 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
        }
        @keyframes strokeCircle { 100% { stroke-dashoffset: 0; } }
        @keyframes strokeCheckmark { 100% { stroke-dashoffset: 0; } }
        @keyframes fillCheckmark { 100% { box-shadow: inset 0px 0px 0px 80px rgba(16, 185, 129, 0.15); border: 2px solid #10b981; } }
        @keyframes scaleCheckmark { 0%, 100% { transform: none; } 50% { transform: scale3d(1.1, 1.1, 1); } }
        
        .success-neon-text {
            font-size: 1.8rem; font-weight: 800; color: #10b981;
            letter-spacing: 1px; text-transform: uppercase;
            text-shadow: 0 0 20px rgba(16, 185, 129, 0.6); margin-top: 25px;
        }
        .success-neon-subtext {
            color: #94a3b8; font-size: 0.95rem; font-weight: 500; margin-top: 8px;
        }
    </style>
</head>
<body>

<?php if($show_success_overlay): ?>
<div class="success-fullscreen-overlay animate__animated animate__fadeIn">
    <div class="success-circle-wrapper">
        <svg class="svg-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="svg-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="svg-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>
    </div>
    <div class="success-neon-text animate__animated animate__zoomIn animate__delay-1s">
        <i class="bi bi-shield-fill-check"></i> Attendance Marked Successfully
    </div>
    <div class="success-neon-subtext animate__animated animate__fadeIn animate__delay-1s font-monospace">
        REAL-TIME CORE SYNC COMPLETION RUNNING...
    </div>
</div>
<script>
    // कोणत्याही बटणावर क्लिक न करता २ सेकंदात आपोआप गायब होईल आणि रीडायरेक्ट होईल
    setTimeout(function() {
        window.location.href = 'mark_attendance.php';
    }, 2200);
</script>
<?php endif; ?>

<div id="liveToast" class="premium-toast animate__animated"></div>

<div class="container-fluid p-4 p-md-5">
    <div class="card premium-panel p-4 p-md-5">
        
        <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
            <div>
                <h2 class="h3 mb-1 fw-bold text-white"><i class="bi bi-cpu-fill text-info me-2"></i>Live Attendance Matrix</h2>
                <p class="text-muted small mb-0">System Roster Engine Management Dashboard.</p>
            </div>
            <div class="badge bg-warning bg-opacity-10 text-neon-amber border border-warning border-opacity-25 px-3 py-2 rounded-pill font-monospace">
                <i class="bi bi-calendar-event me-1"></i> DYNAMIC ROSTER MODE
            </div>
        </div>

        <form method="GET" action="" class="row g-3 p-4 filter-zone align-items-end">
            <div class="col-md-2">
                <label class="form-label form-label-glowing"><i class="bi bi-calendar-check me-1"></i>Session Date</label>
                <input type="date" name="date" class="form-control glowing-filter-input" value="<?php echo $date; ?>" <?php if($step2) echo 'readonly style="opacity: 0.85;"'; ?>>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-glowing"><i class="bi bi-clock me-1"></i>Session Time</label>
                <input type="time" name="time" class="form-control glowing-filter-input" value="<?php echo $time; ?>" <?php if($step2) echo 'readonly style="opacity: 0.85;"'; ?>>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-glowing"><i class="bi bi-diagram-3 me-1"></i>Academic Branch</label>
                <select name="branch" class="form-select glowing-filter-input" required <?php if($step2) echo 'disabled style="opacity: 0.85;"'; ?>>
                    <option value="">-- Choose Branch --</option>
                    <option value="Computer Science" <?php if($branch == 'Computer Science') echo 'selected'; ?>>Computer Science</option>
                    <option value="Information Technology" <?php if($branch == 'Information Technology') echo 'selected'; ?>>Information Technology</option>
                    <option value="Mechanical" <?php if($branch == 'Mechanical') echo 'selected'; ?>>Mechanical</option>
                    <option value="Civil" <?php if($branch == 'Civil') echo 'selected'; ?>>Civil</option>
                     <option value="Electrical" <?php if($branch == 'Electrical') echo 'selected'; ?>>Electrical</option>
                   <option value="Electronics" <?php if($branch == 'Electronics') echo 'selected'; ?>>Electronics</option>
                </select>
                <?php if($step2): ?><input type="hidden" name="branch" value="<?php echo $branch; ?>"><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-glowing"><i class="bi bi-layers me-1"></i>Semester</label>
                <select name="sem" class="form-select glowing-filter-input" required <?php if($step2) echo 'disabled style="opacity: 0.85;"'; ?>>
                    <option value="">-- Choose Semester --</option>
                    <?php for($i=1; $i<=6; $i++) { $sem_val = "Sem $i"; ?>
                        <option value="<?php echo $sem_val; ?>" <?php if($sem == $sem_val) echo 'selected'; ?>><?php echo $sem_val; ?></option>
                    <?php } ?>
                </select>
                <?php if($step2): ?><input type="hidden" name="sem" value="<?php echo $sem; ?>"><?php endif; ?>
            </div>
            <div class="col-md-2">
                <?php if(!$step2): ?>
                    <button type="submit" name="next_step" value="1" class="btn btn-primary w-100 h-48 rounded-3 fw-bold shadow-sm">
                        Next Block <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                <?php else: ?>
                    <a href="mark_attendance.php" class="btn btn-outline-secondary w-100 h-48 d-flex align-items-center justify-content-center fw-bold rounded-3">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filter
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if($step2): ?>
            <?php if(!empty($dataset)): ?>
                <form method="POST" action="" class="mt-5 animate__animated animate__fadeInUp" id="masterAttendanceForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="final_date" value="<?php echo $date; ?>">
                    <input type="hidden" name="final_time" value="<?php echo $time; ?>">
                    <input type="hidden" name="final_branch" value="<?php echo $branch; ?>">
                    <input type="hidden" name="final_semester" value="<?php echo $sem; ?>">

                    <div class="alert bg-dark bg-opacity-30 border border-secondary border-opacity-10 rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="badge bg-info text-dark px-3 py-2 me-2 rounded-pill fw-semibold font-monospace"><?php echo $branch; ?></span>
                            <span class="badge bg-primary text-white px-3 py-2 me-2 rounded-pill fw-semibold font-monospace"><?php echo $sem; ?></span>
                            
                            <?php if($is_any_student_marked): ?>
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold animate__animated animate__pulse animate__infinite" id="lockStatusBadge"><i class="bi bi-exclamation-circle-fill me-1"></i> SUB-ROSTER ACTIVE (NEW ENTRIES DETECTED)</span>
                            <?php else: ?>
                                <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold"><i class="bi bi-unlock-fill me-1"></i> FRESH ROSTER OPEN</span>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <?php if($is_any_student_marked): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning fw-bold px-3 py-2 rounded-3" id="unlockMatrixBtn">
                                    <i class="bi bi-shield-lock-fill me-1"></i> Force Unlock All Rows
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive rounded-4 shadow-lg">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Branch / Sem Metadata</th>
                                    <th>Log Date & Time</th>
                                    <th class="text-center" style="width: 280px;">Allocation Engine (Status)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($dataset as $row): 
                                    $saved_status = $row['saved_status'];
                                    $is_row_new_student = empty($saved_status); 
                                ?>
                                    <tr>
                                        <td><span class="font-monospace text-info fw-bold">#<?php echo htmlspecialchars($row['student_id']); ?></span></td>
                                        
                                        <td>
                                            <span class="fw-bold text-white fs-6"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                            
                                            <?php if($is_row_new_student): ?>
                                                <span class="ms-2 badge bg-warning text-dark border border-warning py-1 font-monospace rounded-pill animate__animated animate__flash animate__infinite">NEW UNMARKED</span>
                                            <?php else: ?>
                                                <?php if($saved_status == 'Present'): ?>
                                                    <span class="ms-2 badge bg-success bg-opacity-20 text-success border border-success border-opacity-50 py-1 font-monospace rounded-pill">P-LOGGED</span>
                                                <?php elseif($saved_status == 'Absent'): ?>
                                                    <span class="ms-2 badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-50 py-1 font-monospace rounded-pill">A-LOGGED</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <div class="highlight-meta-box d-inline-block">
                                                <div class="small text-secondary fw-semibold">Branch: <span class="text-neon-cyan fw-bold"><?php echo htmlspecialchars($row['branch']); ?></span></div>
                                                <div class="small text-secondary fw-semibold mt-1">Semester: <span class="text-neon-cyan fw-bold"><?php echo htmlspecialchars($row['semester']); ?></span></div>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="highlight-time-box d-inline-block font-monospace">
                                                <div class="small text-neon-amber fw-bold"><i class="bi bi-calendar3 me-1"></i><?php echo date("d-M-Y", strtotime($row['attendance_date'])); ?></div>
                                                <div class="small text-neon-orange fw-bold mt-1" style="font-size: 0.8rem;"><i class="bi bi-clock me-1"></i><?php echo date("h:i A", strtotime($row['attendance_time'])); ?></div>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="d-flex justify-content-center gap-2 class-row-container <?php if(!$is_row_new_student) echo 'individual-row-locked'; ?>">
                                                
                                                <input type="radio" class="btn-check btn-check-present att-trigger-pills" 
                                                       name="status[<?php echo $row['student_id']; ?>]" 
                                                       id="pres_<?php echo $row['student_id']; ?>" 
                                                       value="Present" autocomplete="off" required
                                                       data-student-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                                       <?php if($saved_status == 'Present') echo 'checked'; ?>
                                                       <?php if(!$is_row_new_student) echo 'disabled'; ?>>
                                                <label class="btn pill-btn label-present btn-sm w-50 d-flex align-items-center justify-content-center gap-1" for="pres_<?php echo $row['student_id']; ?>">
                                                    <i class="bi bi-shield-check"></i> Present
                                                </label>

                                                <input type="radio" class="btn-check btn-check-absent att-trigger-pills" 
                                                       name="status[<?php echo $row['student_id']; ?>]" 
                                                       id="abs_<?php echo $row['student_id']; ?>" 
                                                       value="Absent" autocomplete="off" required
                                                       data-student-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                                       <?php if($saved_status == 'Absent') echo 'checked'; ?>
                                                       <?php if(!$is_row_new_student) echo 'disabled'; ?>>
                                                <label class="btn pill-btn label-absent btn-sm w-50 d-flex align-items-center justify-content-center gap-1" for="abs_<?php echo $row['student_id']; ?>">
                                                    <i class="bi bi-shield-minus"></i> Absent
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" id="submitMasterBtn" name="submit_master_attendance" class="btn btn-success btn-lg px-5 py-3 rounded-3 fw-bold shadow-lg text-uppercase font-monospace small" style="letter-spacing: 0.5px;">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Commit Roster Records
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center empty-state-card mt-5">
                    <i class="bi bi-folder-x text-warning display-3"></i>
                    <h4 class="text-white fw-bold mt-3">No Students Registered under Selected Roster</h4>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<script>
const toastElement = document.getElementById('liveToast');

function showPremiumToast(message, type) {
    if(type === 'success') {
        toastElement.style.background = 'rgba(16, 185, 129, 0.25)';
        toastElement.style.borderColor = 'rgba(16, 185, 129, 0.4)';
        toastElement.style.boxShadow = '0 20px 40px rgba(16, 185, 129, 0.15)';
    } else if(type === 'error') {
        toastElement.style.background = 'rgba(239, 68, 68, 0.25)';
        toastElement.style.borderColor = 'rgba(239, 68, 68, 0.4)';
        toastElement.style.boxShadow = '0 20px 40px rgba(239, 68, 68, 0.15)';
    } else {
        toastElement.style.background = 'rgba(245, 158, 11, 0.25)';
        toastElement.style.borderColor = 'rgba(245, 158, 11, 0.4)';
        toastElement.style.boxShadow = '0 20px 40px rgba(245, 158, 11, 0.15)';
    }
    
    toastElement.innerHTML = message;
    toastElement.style.display = 'block';
    toastElement.classList.remove('animate__fadeOutDown');
    toastElement.classList.add('animate__fadeInUp');

    setTimeout(() => {
        toastElement.classList.remove('animate__fadeInUp');
        toastElement.classList.add('animate__fadeOutDown');
        setTimeout(() => { toastElement.style.display = 'none'; }, 400);
    }, 3500);
}

document.addEventListener('click', function(e) {
    const shield = e.target.closest('.individual-row-locked');
    if (shield) {
        e.preventDefault();
        showPremiumToast(`<i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i> <span style="color:#e2e8f0;">Student status already logged! Please click <b>'Force Unlock All Rows'</b> to rewrite data.</span>`, 'warning');
    }
});

const unlockBtn = document.getElementById('unlockMatrixBtn');
if(unlockBtn) {
    unlockBtn.addEventListener('click', function() {
        document.querySelectorAll('.att-trigger-pills').forEach(radio => {
            radio.removeAttribute('disabled');
        });
        document.querySelectorAll('.class-row-container').forEach(shield => {
            shield.classList.remove('individual-row-locked');
        });
        
        const badge = document.getElementById('lockStatusBadge');
        if(badge) {
            badge.className = "badge bg-success text-white px-3 py-2 rounded-pill fw-bold";
            badge.innerHTML = `<i class="bi bi-unlock-fill me-1"></i> ALL ROSTER ROWS FORCE UNLOCKED`;
        }

        this.innerHTML = `<i class="bi bi-shield-check-fill me-1"></i> Global Roster Open`;
        this.className = "btn btn-sm btn-success fw-bold px-3 py-2 rounded-3 animate__animated animate__rubberBand";
        
        showPremiumToast(`<i class="bi bi-unlock-fill text-success me-2 fs-5"></i> <span style="color:#e2e8f0;">Matrix unlocked. Existing row structures are now editable.</span>`, 'success');
    });
}

document.querySelectorAll('.att-trigger-pills').forEach(radioPill => {
    radioPill.addEventListener('change', function() {
        if (this.hasAttribute('disabled')) return;
        const name = this.getAttribute('data-student-name');
        const selection = this.value;
        
        if(selection === 'Present') {
            showPremiumToast(`<i class="bi bi-check-circle-fill text-success me-2 fs-5"></i> <span style="color:#e2e8f0;"><b>${name}</b> status dynamically shifted to</span> <span class="badge bg-success font-monospace ms-1">Present</span>`, 'success');
        } else {
            showPremiumToast(`<i class="bi bi-exclamation-octagon-fill text-danger me-2 fs-5"></i> <span style="color:#e2e8f0;"><b>${name}</b> status dynamically shifted to</span> <span class="badge bg-danger font-monospace ms-1">Absent</span>`, 'error');
        }
    });
});
</script>

</body>
</html>
<?php include '../includes/footer.php'; ?>