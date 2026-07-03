<?php
/**
 * Enterprise Attendance Ledger & Auditing Console
 * Core Architecture: Optimized Cross-Table Match Engine
 * Upgraded Engine: Live Client-Side Dom-Glow Matrix & Name Discovery (Premium Glassmorphism Style)
 */

// 🛡️ 1. ब्लँक स्क्रीन येऊ नये म्हणून एरर रिपोर्टिंग पूर्णपणे चालू
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php'; 

date_default_timezone_set('Asia/Kolkata');

// 🧼 2. इनपुट सॅनिटायझेशन
$filter_date    = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$search_student = isset($_GET['search_student']) ? trim($_GET['search_student']) : '';
$filter_branch  = isset($_GET['filter_branch']) ? trim($_GET['filter_branch']) : '';
$filter_sem     = isset($_GET['filter_sem']) ? trim($_GET['filter_sem']) : '';

$total_records = 0;
$present_count = 0;
$absent_count  = 0;
$dataset       = [];
$error_message = ""; 

// अटेंडन्स शो करण्यासाठीची कंडिशन
$is_filter_valid = !empty($search_student) || (!empty($filter_date) && !empty($filter_branch) && !empty($filter_sem));

if ($is_filter_valid) {
    try {
        /**
         * ⚡ 3. ऑप्टिमाइझ्ड SQL क्वेरी (FIXED for 's.semester' error):
         * LEFT JOIN वापरला आहे जेणेकरून स्कॅनरचा डेटा गाळला जाणार नाही.
         * COALESCE वापरून attendance मधील semester किंवा students मधील sem मॅच केले जाईल.
         */
        $query = "SELECT a.id, a.student_id, s.name AS student_name, a.attendance_date, a.attendance_time, a.status, 
                         COALESCE(NULLIF(a.branch, ''), s.branch) AS branch, 
                         COALESCE(NULLIF(a.semester, ''), s.sem) AS semester 
                  FROM attendance a 
                  LEFT JOIN students s ON TRIM(a.student_id) = TRIM(s.student_id) 
                  WHERE 1=1";

        $params = [];
        $types = "";

        // नियम १: जर डायरेक्ट Student ID टाकला, तर थेट डेटा दाखवा (TRIM सह जेणेकरून स्कॅनरची स्पेस मॅच होईल)
        if (!empty($search_student)) {
            $query .= " AND TRIM(a.student_id) = ?";
            $params[] = $search_student;
            $types .= "s";
        } 
        // नियम २: जर ID नसेल, तर तारीख, ब्रांच आणि सेम मॅच करा
        else {
            $sem_param = "%" . $filter_sem . "%";
            $query .= " AND a.attendance_date = ? 
                        AND (COALESCE(NULLIF(a.branch, ''), s.branch) = ?) 
                        AND (COALESCE(NULLIF(a.semester, ''), s.sem) LIKE ?)";
            
            $params[] = $filter_date;
            $params[] = $filter_branch;
            $params[] = $sem_param;
            $types .= "sss";
        }

        $query .= " ORDER BY a.attendance_date DESC, a.attendance_time DESC";

        // 🚀 Execution Core (Prepared Statements)
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            if (!empty($types)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);

                if ($result) {
                    $total_records = mysqli_num_rows($result);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $dataset[] = $row;
                        if (strtolower(trim($row['status'])) === 'present') {
                            $present_count++;
                        } else {
                            $absent_count++;
                        }
                    }
                } else {
                    throw new Exception("डेटाबेस सिंक एरर: " . mysqli_error($conn));
                }
            } else {
                throw new Exception("क्वेरी रन करणे फेल: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("SQL स्ट्रक्चर चुकीचे आहे. कृपया डेटाबेस कॉलम्स तपासा: " . mysqli_error($conn));
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 🔍 डेटाबेसमधून रिअल ब्रांचेस ऑटो-लोड करणे + मॅन्युअल कोर ब्रांचेस लॉजिक
$branch_options = [
    'Computer Science',
    'Information Technology',
    'Mechanical',
    'Electrical',
    'Civil'
];

$get_branches = mysqli_query($conn, "SELECT DISTINCT branch FROM students WHERE branch IS NOT NULL AND branch != ''");
if($get_branches) {
    while($b_row = mysqli_fetch_assoc($get_branches)) {
        $db_branch = trim($b_row['branch']);
        if (!in_array($db_branch, $branch_options)) {
            $branch_options[] = $db_branch;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>High-End Cross Match Attendance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        /* 🌌 Ultra Modern Futuristic Glassmorphism Styling */
        body { 
            background: #060814 !important; 
            background: radial-gradient(circle at 50% 0%, #1e1b4b, #0a0f24, #02040a) fixed !important; 
            min-height: 100vh; 
            color: #f1f5f9 !important; 
            font-family: 'Inter', system-ui, sans-serif; 
        }
        
        .report-card { 
            background: rgba(15, 23, 42, 0.4) !important; 
            backdrop-filter: blur(25px) saturate(200%); 
            -webkit-backdrop-filter: blur(25px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.09); 
            border-radius: 24px; 
            box-shadow: 0 40px 100px -30px rgba(0, 0, 0, 0.8); 
            margin-top: 20px; 
        }

        /* 💎 Crystal Glass Input Boxes & Forms */
        .filter-box { 
            background: rgba(255, 255, 255, 0.02) !important; 
            backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            border-radius: 20px; 
            padding: 24px !important; 
        }

        .live-search-box {
            background: rgba(99, 102, 241, 0.05) !important;
            border: 1px dashed rgba(99, 102, 241, 0.3) !important;
            border-radius: 20px;
            padding: 20px !important;
        }

        .form-label { 
            font-size: 0.72rem; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            color: #38bdf8 !important; 
            font-weight: 700; 
            margin-bottom: 8px; 
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.2);
        }

        .form-control, .form-select { 
            background: rgba(15, 23, 42, 0.6) !important; 
            border: 1px solid rgba(255, 255, 255, 0.08) !important; 
            color: #ffffff !important; 
            height: 54px; 
            border-radius: 14px; 
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.4);
        }

        .form-control:focus, .form-select:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 18px rgba(99, 102, 241, 0.4), inset 0 2px 4px rgba(0,0,0,0.2) !important;
            color: #fff !important;
        }

        .input-group-custom-id .input-group-text {
            background: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-right: none !important;
            color: #38bdf8 !important;
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }
        .input-group-custom-id .form-control {
            border-top-left-radius: 0px !important;
            border-bottom-left-radius: 0px !important;
        }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        .form-select option { background-color: #0b0f19 !important; color: #ffffff !important; }

        .btn-premium-execute { 
            background: linear-gradient(135deg, #4f46e5, #818cf8) !important; 
            border: none !important; 
            color: white !important; 
            height: 54px; 
            font-weight: 700; 
            border-radius: 14px; 
            letter-spacing: 1px;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4); 
            transition: all 0.3s ease;
        }
        .btn-premium-execute:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.6);
        }

        .btn-premium-reset { 
            background: rgba(255, 255, 255, 0.04) !important; 
            border: 1px solid rgba(255, 255, 255, 0.08) !important; 
            color: #ffffff !important; 
            height: 54px; 
            width: 54px; 
            border-radius: 14px; 
        }

        .btn-action-view {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #38bdf8 !important;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* 📊 KPI CARDS */
        .glass-kpi-card { 
            background: rgba(10, 15, 30, 0.8) !important; 
            backdrop-filter: blur(25px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            border-radius: 20px; 
            padding: 22px 24px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6); 
        }
        
        .kpi-content-block { display: flex; flex-direction: column; gap: 4px; }
        .kpi-title { font-size: 0.82rem; text-transform: uppercase; color: #cbd5e1; font-weight: 700; }
        .kpi-value { font-size: 2.3rem; font-weight: 900; font-family: monospace; line-height: 1.1; }
        .kpi-neon-icon { font-size: 2.2rem; display: flex; padding: 10px; border-radius: 14px; }

        .kpi-card-total { border-left: 4px solid #3b82f6 !important; }
        .kpi-card-total .kpi-value { color: #3b82f6 !important; text-shadow: 0 0 20px rgba(59, 130, 246, 0.6); }
        .kpi-card-total .kpi-neon-icon { color: #3b82f6 !important; background: rgba(59, 130, 246, 0.1); }

        .kpi-card-present { border-left: 4px solid #10b981 !important; }
        .kpi-card-present .kpi-value { color: #10b981 !important; text-shadow: 0 0 20px rgba(16, 185, 129, 0.6); }
        .kpi-card-present .kpi-neon-icon { color: #10b981 !important; background: rgba(16, 185, 129, 0.1); }

        .kpi-card-absent { border-left: 4px solid #ef4444 !important; }
        .kpi-card-absent .kpi-value { color: #ef4444 !important; text-shadow: 0 0 20px rgba(239, 68, 68, 0.6); }
        .kpi-card-absent .kpi-neon-icon { color: #ef4444 !important; background: rgba(239, 68, 68, 0.1); }

        .kpi-card-ratio { border-left: 4px solid #8b5cf6 !important; }
        .kpi-card-ratio .kpi-value { color: #8b5cf6 !important; text-shadow: 0 0 20px rgba(139, 92, 246, 0.6); }
        .kpi-card-ratio .kpi-neon-icon { color: #8b5cf6 !important; background: rgba(139, 92, 246, 0.1); }

        /* 📋 TABLE MATRIX */
        .table-responsive { border-radius: 18px; border: 1px solid rgba(255, 255, 255, 0.08); background: #020408 !important; }
        .table-custom th { background-color: #090d16 !important; color: #a5b4fc !important; font-weight: 700; text-transform: uppercase; font-size: 0.72rem; padding: 18px; }
        .table-custom tbody tr { background-color: #030712 !important; cursor: pointer; transition: all 0.2s ease; }
        .table-custom tbody tr:hover { background-color: #0b0f19 !important; }
        .table-custom td { padding: 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.04); }

        .cell-rec-id { color: #64748b !important; }
        .cell-student-name { color: #ff9f43 !important; font-weight: 700; text-shadow: 0 0 12px rgba(255, 159, 67, 0.35); } 
        .cell-student-id { color: #00d2d3 !important; font-weight: 700; text-shadow: 0 0 12px rgba(0, 210, 211, 0.35); } 
        .cell-branch-text { color: #a855f7 !important; font-weight: 600; } 
        .cell-sem-text { color: #10b981 !important; font-weight: 600; } 
        .cell-date-text { color: #fbbf24 !important; } 
        .cell-time-text { color: #f43f5e !important; font-weight: 700; }

        .table-custom tbody tr.clicked-glow { background: linear-gradient(90deg, rgba(99, 102, 241, 0.2), #090d16) !important; box-shadow: inset 4px 0 0 #6366f1; }
        .matched-glow-cell { background: rgba(234, 179, 8, 0.3) !important; color: #ffffff !important; border-radius: 4px; padding: 2px 6px; border: 1px solid rgba(234, 179, 8, 0.5); }
        
        .student-block { max-width: 240px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; vertical-align: middle; }
        .status-badge-present { background: rgba(16, 185, 129, 0.12) !important; color: #34d399 !important; border: 1px solid rgba(16, 185, 129, 0.3); padding: 6px 16px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; }
        .status-badge-absent { background: rgba(239, 68, 68, 0.12) !important; color: #f87171 !important; border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 16px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; }
    </style>
</head>
<body>

<div class="container-fluid p-3 p-md-5">
    <div class="card report-card p-4 p-md-5 animate__animated animate__fadeIn">
        
        <div class="mb-5 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="h3 mb-1 fw-bold text-white"><i class="bi bi-cpu text-info me-2"></i>Cross Match Ledger Console</h2>
                <p class="text-muted small mb-0">Strict Verification Protocol: Enabled</p>
            </div>
            <span class="badge bg-danger px-3 py-2" style="border-radius: 8px; box-shadow: 0 0 15px rgba(239,68,68,0.3); font-size: 0.75rem;"><i class="bi bi-shield-fill-check me-1"></i> Double Secure Mode</span>
        </div>

        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger border-0 shadow-sm p-4 mb-5" style="background: rgba(239, 68, 68, 0.12); border-radius: 16px; color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
                <h5 class="fw-bold"><i class="bi bi-hdd-network-fill me-2"></i> Structural Alignment Deficit</h5>
                <p class="mb-0 small font-monospace"><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="glass-kpi-card kpi-card-total">
                    <div class="kpi-content-block">
                        <span class="kpi-title">Valid Alignment</span>
                        <span class="kpi-value"><?php echo $total_records; ?></span>
                    </div>
                    <div class="kpi-neon-icon"><i class="bi bi-database-check"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="glass-kpi-card kpi-card-present">
                    <div class="kpi-content-block">
                        <span class="kpi-title">Confirmed Present</span>
                        <span class="kpi-value"><?php echo $present_count; ?></span>
                    </div>
                    <div class="kpi-neon-icon"><i class="bi bi-check-circle-fill"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="glass-kpi-card kpi-card-absent">
                    <div class="kpi-content-block">
                        <span class="kpi-title">Confirmed Absent</span>
                        <span class="kpi-value"><?php echo $absent_count; ?></span>
                    </div>
                    <div class="kpi-neon-icon"><i class="bi bi-x-circle-fill"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="glass-kpi-card kpi-card-ratio">
                    <div class="kpi-content-block">
                        <span class="kpi-title">Attendance Ratio</span>
                        <span class="kpi-value"><?php echo ($total_records > 0) ? round(($present_count / $total_records) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="kpi-neon-icon"><i class="bi bi-pie-chart-fill"></i></div>
                </div>
            </div>
        </div>

        <form method="GET" action="" class="row g-3 filter-box align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label">Student ID (Bypass Logic)</label>
                <div class="input-group input-group-custom-id">
                    <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                    <input type="text" name="search_student" class="form-control" placeholder="Search Student ID..." value="<?php echo htmlspecialchars($search_student); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Attendance Date</label>
                <input type="date" name="filter_date" class="form-control" value="<?php echo htmlspecialchars($filter_date ?: date('Y-m-d')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Branch</label>
                <select name="filter_branch" class="form-select">
                    <option value="">-- Select Branch --</option>
                    <?php foreach($branch_options as $br): ?>
                        <option value="<?php echo htmlspecialchars($br); ?>" <?php echo ($filter_branch == $br) ? 'selected' : ''; ?>><?php echo htmlspecialchars($br); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Semester</label>
                <select name="filter_sem" class="form-select">
                    <option value="">All Semesters</option>
                    <?php for($i=1; $i<=6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($filter_sem == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-premium-execute w-100"><i class="bi bi-cpu-fill me-1"></i> EXECUTE</button>
                <?php if(!empty($filter_date) || !empty($search_student) || !empty($filter_branch) || !empty($filter_sem)): ?>
                    <a href="?" class="btn btn-premium-reset d-flex align-items-center justify-content-center"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if($is_filter_valid && $total_records > 0 && empty($error_message)): ?>
            <div class="row g-3 live-search-box align-items-end mb-4 animate__animated animate__fadeIn">
                <div class="col-md-4">
                    <label class="form-label" style="color: #eab308 !important;"><i class="bi bi-filter-square-fill me-1"></i> Specific Field Target</label>
                    <select id="liveTargetField" class="form-select" style="border-color: rgba(234, 179, 8, 0.3);">
                        <option value="all">Search All Columns</option>
                        <option value="name">Student Name</option>
                        <option value="id">Student ID</option>
                        <option value="branch">Branch</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label" style="color: #eab308 !important;"><i class="bi bi-search me-1"></i> Instant Dataset Matcher (Glow-on-Type)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-warning"><i class="bi bi-lightning-charge-fill"></i></span>
                        <input type="text" id="liveTableSearch" class="form-control" placeholder="Type name or ID here to find & instantly highlight..." style="border-color: rgba(234, 179, 8, 0.3);">
                    </div>
                </div>
            </div>

            <div class="table-responsive shadow-lg animate__animated animate__fadeInUp">
                <table class="table table-custom align-middle mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Rec ID</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Verified Branch</th>
                            <th>Verified Semester</th>
                            <th>Date</th>
                            <th>Time Log</th>
                            <th>Status Matrix</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach($dataset as $row) {
                            $display_date = !empty($row['attendance_date']) ? date("d-M-Y", strtotime($row['attendance_date'])) : 'N/A';
                            $display_time = !empty($row['attendance_time']) ? date("h:i A", strtotime($row['attendance_time'])) : '--:--';
                        ?>
                        <tr onclick="toggleRowGlow(this)">
                            <td class="cell-rec-id">#<?php echo $row['id']; ?></td>
                            <td class="cell-id cell-student-id"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td class="cell-name">
                                <div class="student-block">
                                    <span class="cell-student-name"><?php echo !empty($row['student_name']) ? htmlspecialchars($row['student_name']) : 'Unknown Student'; ?></span>
                                </div>
                            </td>
                            <td class="cell-branch">
                                <span class="cell-branch-text"><?php echo htmlspecialchars($row['branch'] ?: 'N/A'); ?></span>
                            </td>
                            <td class="cell-sem-text">
                                <?php echo !empty($row['semester']) ? 'Semester ' . htmlspecialchars($row['semester']) : 'N/A'; ?>
                            </td>
                            <td class="cell-date-text"><i class="bi bi-calendar-event me-2"></i><?php echo $display_date; ?></td>
                            <td class="cell-time-text"><i class="bi bi-clock me-2"></i><?php echo $display_time; ?></td>
                            <td>
                                <span class="<?php echo (strtolower(trim($row['status'])) == 'present') ? 'status-badge-present' : 'status-badge-absent'; ?>">
                                    <i class="bi <?php echo (strtolower(trim($row['status'])) == 'present') ? 'bi-patch-check-fill' : 'bi-patch-exclamation-fill'; ?> me-1"></i>
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-action-view" onclick="event.stopPropagation(); alert('Redirecting to profile of: <?php echo htmlspecialchars($row['student_name'] ?: 'Unknown Student'); ?>');">
                                    <i class="bi bi-eye-fill me-1"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php elseif($is_filter_valid && $total_records == 0 && empty($error_message)): ?>
            <div class="text-center p-5 animate__animated animate__fadeIn" style="border: 1px dashed rgba(239, 68, 68, 0.3); border-radius: 20px; background: rgba(239, 68, 68, 0.02);">
                <i class="bi bi-exclamation-triangle text-danger display-4 mb-3 d-block"></i>
                <h5 class="text-white fw-bold">No Records Found</h5>
                <p class="text-muted small mb-0">या फिल्टर मॅचिंगसाठी डेटाबेसमध्ये कोणतीही हजेरी नोंदवलेली नाही.</p>
            </div>
        <?php else: ?>
            <div class="text-center p-5 animate__animated animate__pulse" style="border: 1px dashed rgba(255,255,255,0.08); border-radius: 20px; background: rgba(255, 255, 255, 0.01);">
                <i class="bi bi-shield-lock-fill text-warning display-4 mb-3 d-block" style="filter: drop-shadow(0 0 15px rgba(245,158,11,0.4));"></i>
                <h5 class="text-white fw-bold">Awaiting Filter Alignment</h5>
                <p class="text-muted small mb-0">कृपया डेटा पाहण्यासाठी <b>Date</b>, <b>Branch</b>, आणि <b>Semester</b> हे पर्याय निवडून **EXECUTE** करा.</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
// १. क्लिक केल्यावर तो पर्टिक्युलर रो हायलाईट / ग्लो करणे
function toggleRowGlow(element) {
    element.classList.toggle('clicked-glow');
}

// २. इनपुट बॉक्समध्ये टाईप करताना लाइव्ह मॅच करून ठराविक टेक्स्ट ग्लो करणे
document.getElementById('liveTableSearch')?.addEventListener('input', function() {
    let searchValue = this.value.toLowerCase().trim();
    let targetField = document.getElementById('liveTargetField').value;
    let rows = document.querySelectorAll('#attendanceTable tbody tr');

    rows.forEach(row => {
        let matchFound = false;
        let cellsToSearch = [];

        let nameCell = row.querySelector('.cell-name');
        let idCell = row.querySelector('.cell-id');
        let branchCell = row.querySelector('.cell-branch');

        // री-स्टोअर ओरिजिनल स्ट्रक्चर
        if(nameCell) {
            let pureText = nameCell.getAttribute('data-orig') || (nameCell.querySelector('.cell-student-name') ? nameCell.querySelector('.cell-student-name').textContent : nameCell.textContent);
            if(!nameCell.getAttribute('data-orig')) nameCell.setAttribute('data-orig', pureText);
            nameCell.innerHTML = `<div class="student-block"><span class="cell-student-name">${pureText}</span></div>`;
        }
        if(idCell) {
            let pureId = idCell.getAttribute('data-orig') || idCell.textContent;
            if(!idCell.getAttribute('data-orig')) idCell.setAttribute('data-orig', pureId);
            idCell.innerHTML = pureId;
        }
        if(branchCell) {
            let pureBranch = branchCell.getAttribute('data-orig') || (branchCell.querySelector('.cell-branch-text') ? branchCell.querySelector('.cell-branch-text').textContent : branchCell.textContent);
            if(!branchCell.getAttribute('data-orig')) branchCell.setAttribute('data-orig', pureBranch);
            branchCell.innerHTML = `<span class="cell-branch-text">${pureBranch}</span>`;
        }

        // फिल्टर टार्गेट सेट करणे
        if (targetField === 'name' && nameCell) cellsToSearch.push(nameCell.querySelector('.cell-student-name'));
        else if (targetField === 'id' && idCell) cellsToSearch.push(idCell);
        else if (targetField === 'branch' && branchCell) cellsToSearch.push(branchCell.querySelector('.cell-branch-text'));
        else {
            if(nameCell) cellsToSearch.push(nameCell.querySelector('.cell-student-name'));
            if(idCell) cellsToSearch.push(idCell);
            if(branchCell) cellsToSearch.push(branchCell.querySelector('.cell-branch-text'));
        }

        if (searchValue === "") {
            row.style.display = "";
            return;
        }

        // मॅचिंग आणि ग्लो इफेक्ट जोडणे
        cellsToSearch.forEach(cell => {
            if(!cell) return;
            let originalText = cell.textContent;
            if (originalText.toLowerCase().includes(searchValue)) {
                matchFound = true;
                let regex = new RegExp(`(${searchValue})`, "gi");
                let newHTML = originalText.replace(regex, "<span class='matched-glow-cell'>$1</span>");
                cell.innerHTML = newHTML;
            }
        });

        if (matchFound) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>

</body>
</html>