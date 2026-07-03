<?php
include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

// ⏰ टाईमझोन आणि आजची तारीख सेटअप
date_default_timezone_set('Asia/Kolkata'); 
$today = date("Y-m-d");

// 🧼 इनपुट डेटा गेट, ट्रिम आणि सॅनिटायझेशन
$selected_branch   = isset($_GET['branch']) ? mysqli_real_escape_string($conn, trim($_GET['branch'])) : '';
$selected_semester = isset($_GET['semester']) ? mysqli_real_escape_string($conn, trim($_GET['semester'])) : '';
$search_student    = isset($_GET['search_student']) ? mysqli_real_escape_string($conn, trim($_GET['search_student'])) : '';

// 🚀 FIXED: 'Information Technology' ब्रांच लिस्टमध्ये आणि लॉजिकमध्ये ऍड केली आहे
$standard_branches = ['Computer Science', 'Information Technology', 'Mechanical', 'Electrical', 'Civil', 'Electronics'];

// 🔍 फिल्टर कंडीशन्स एरेज
$student_conditions = [];
$attendance_conditions = [];

// ⚡ STRICT TODAY LOGIC: पूर्ण पेज फक्त आजच्याचेंट फिल्टर्ड डेटाला दाखवेल
$recent_logs_where = " WHERE a.attendance_date = '$today' ";

// नियम १: ब्रांच फिल्टर
if (!empty($selected_branch)) {
    $student_conditions[] = "branch LIKE '%$selected_branch%'";
    $attendance_conditions[] = "student_id IN (SELECT student_id FROM students WHERE branch LIKE '%$selected_branch%')";
    $recent_logs_where .= " AND s.branch LIKE '%$selected_branch%' ";
}

// 🔥 नियम २: सेमिस्टर फिल्टर
$grid_sem_student_append = "";
if (!empty($selected_semester)) {
    $sem_digit = preg_replace('/[^0-9]/', '', $selected_semester);
    
    if (!empty($sem_digit)) {
        $sem_match_plain = "(sem = '$selected_semester' OR sem = '$sem_digit' OR sem LIKE '%$sem_digit%')";
        $sem_match_alias = "(s.sem = '$selected_semester' OR s.sem = '$sem_digit' OR s.sem LIKE '%$sem_digit%')";
    } else {
        $sem_match_plain = "(sem = '$selected_semester' OR sem LIKE '%$selected_semester%')";
        $sem_match_alias = "(s.sem = '$selected_semester' OR s.sem LIKE '%$selected_semester%')";
    }
    
    $student_conditions[] = $sem_match_plain;
    $attendance_conditions[] = "student_id IN (SELECT student_id FROM students WHERE $sem_match_plain)";
    $recent_logs_where .= " AND $sem_match_alias ";
    
    $grid_sem_student_append = " AND " . $sem_match_plain;
}

// नियम ३: स्टूडेंट ID किंवा नाव सर्च फिल्टर
if (!empty($search_student)) {
    $student_conditions[] = "(student_id LIKE '%$search_student%' OR name LIKE '%$search_student%')";
    $attendance_conditions[] = "student_id IN (SELECT student_id FROM students WHERE student_id LIKE '%$search_student%' OR name LIKE '%$search_student%')";
    $recent_logs_where .= " AND (a.student_id LIKE '%$search_student%' OR s.name LIKE '%$search_student%') ";
}

// क्लॉज स्ट्रिंग्स एकत्र जोडणे
$student_where_clause = !empty($student_conditions) ? " WHERE " . implode(" AND ", $student_conditions) : "";
$attendance_where_clause = !empty($attendance_conditions) ? " AND " . implode(" AND ", $attendance_conditions) : "";

// सांख्यिकी डेटा फेच करणे
$total_students_query = mysqli_query($conn, "SELECT COUNT(*) as total_students FROM students $student_where_clause");
$total_students_row = mysqli_fetch_assoc($total_students_query);
$total_students = $total_students_row['total_students'] ?? 0;

$attendance_today_query = mysqli_query($conn, "
    SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_today,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_today
    FROM attendance 
    WHERE attendance_date = '$today' $attendance_where_clause
");
$attendance_today = mysqli_fetch_assoc($attendance_today_query);

$present = $attendance_today['present_today'] ?? 0;
$absent  = $attendance_today['absent_today'] ?? 0;
$rate    = ($total_students > 0) ? round(($present / $total_students) * 100) : 0;

if ($total_students <= 10) {
    $student_card_color = "text-danger-custom";
    $student_card_glow = "rgba(239, 68, 68, 0.25)";
    $student_icon_color = "text-danger";
} elseif ($total_students > 10 && $total_students <= 50) {
    $student_card_color = "text-warning-custom";
    $student_card_glow = "rgba(245, 158, 11, 0.25)";
    $student_icon_color = "text-warning";
} else {
    $student_card_color = "text-success-custom";
    $student_card_glow = "rgba(16, 185, 129, 0.25)";
    $student_icon_color = "text-success";
}

// LINE CHART DATA
$chart_labels = [];
$chart_data   = [];
$chart_query = mysqli_query($conn, "
    SELECT 
        attendance_date,
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
        COUNT(*) as total_count
    FROM attendance
    WHERE 1=1 $attendance_where_clause
    GROUP BY attendance_date
    ORDER BY attendance_date DESC
    LIMIT 7
");
if ($chart_query && mysqli_num_rows($chart_query) > 0) {
    while ($c_row = mysqli_fetch_assoc($chart_query)) {
        $chart_labels[] = date("d M", strtotime($c_row['attendance_date']));
        $p_rate = ($c_row['total_count'] > 0) ? round(($c_row['present_count'] / $c_row['total_count']) * 100) : 0;
        $chart_data[] = $p_rate;
    }
    $chart_labels = array_reverse($chart_labels);
    $chart_data   = array_reverse($chart_data);
} else {
    $chart_labels = ['No Data'];
    $chart_data   = [0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance ERP Dashboard Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-glow: rgba(99, 102, 241, 0.15);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --orange-neon: #ff5500;
            --cyber-blue: #00f3ff;
        }
        body {
            background: #04060a;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        body::before {
            content: ""; position: absolute; width: 600px; height: 600px; border-radius: 50%; z-index: 0;
            filter: blur(160px); opacity: 0.15; pointer-events: none;
            background: radial-gradient(circle, #4f46e5 0%, transparent 70%); top: -10%; left: -5%;
        }
        .container-fluid { position: relative; z-index: 1; }
        .banner {
            background: rgba(10, 15, 30, 0.6); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border); border-radius: 24px; padding: 32px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        .glass-card {
            background: rgba(10, 15, 30, 0.6); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border); border-radius: 20px; color: var(--text-main);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4); transition: all 0.3s ease;
        }
        .glass-card:hover { transform: translateY(-4px); border-color: rgba(99, 102, 241, 0.4); }
        .card-number { font-size: 40px; font-weight: 700; letter-spacing: -1px; margin-top: 8px; }
        
        .text-info-gradient { background: linear-gradient(135deg, #fff 30%, #94a3b8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .text-danger-custom { background: linear-gradient(135deg, #ff8a8a 30%, #ef4444 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .text-warning-custom { background: linear-gradient(135deg, #ffe17d 30%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .text-success-custom { background: linear-gradient(135deg, #a7f3d0 30%, #10b981 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        /* ADVANCED FILTERS PANEL */
        .premium-filter-panel {
            background: rgba(8, 10, 20, 0.95) !important;
            border: 2px solid var(--cyber-blue) !important;
            box-shadow: 0 0 30px rgba(0, 243, 255, 0.25), inset 0 0 15px rgba(0, 243, 255, 0.05) !important;
            border-radius: 20px;
            padding: 24px;
        }
        .filter-glow-heading {
            color: var(--cyber-blue) !important; font-weight: 800 !important; text-transform: uppercase; letter-spacing: 1px;
            text-shadow: 0 0 15px rgba(0, 243, 255, 0.6) !important;
        }
        .filter-glow-label {
            color: #b3f7ff !important; font-weight: 700 !important; font-size: 13px; text-transform: uppercase;
            text-shadow: 0 0 8px rgba(0, 243, 255, 0.2) !important;
        }
        .premium-glow-input {
            background: #040814 !important; border: 2px solid rgba(0, 243, 255, 0.3) !important;
            color: #ffffff !important; font-weight: 600 !important; border-radius: 12px; padding: 12px 16px;
        }
        .premium-glow-input:focus {
            border-color: var(--cyber-blue) !important;
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.5) !important;
            color: #fff !important;
        }
        .btn-cyber-glow {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%) !important; border: none !important;
            color: #fff !important; font-weight: 800 !important; text-transform: uppercase; border-radius: 12px; padding: 12px 24px;
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.4) !important; transition: all 0.25s ease;
        }
        .btn-cyber-glow:hover { transform: translateY(-2px); box-shadow: 0 0 25px var(--cyber-blue) !important; }
        .btn-reset-glow {
            background: rgba(255, 255, 255, 0.03) !important; border: 1px solid rgba(0, 243, 255, 0.3) !important;
            color: #8ecae6 !important; font-weight: 700 !important; border-radius: 12px; padding: 12px 20px;
        }

        /* LOGS TABLE CSS */
        .orange-neon-card {
            background: #020306 !important; 
            border: 2px solid var(--orange-neon) !important;
            box-shadow: 0 0 40px rgba(255, 85, 0, 0.25), inset 0 0 20px rgba(255, 85, 0, 0.05) !important;
            border-radius: 24px;
        }
        .orange-neon-title {
            color: #ff8833 !important; font-weight: 800 !important; text-transform: uppercase; letter-spacing: 1.5px;
            text-shadow: 0 0 20px var(--orange-neon) !important;
        }
        .orange-table {
            background: #05070c !important; 
            border-collapse: separate !important;
            border-spacing: 0 6px !important;
        }
        .orange-table th {
            background: #090d16 !important; 
            color: #ff9944 !important; font-weight: 800 !important; text-transform: uppercase; font-size: 13px;
            border-bottom: 3px solid var(--orange-neon) !important;
            text-shadow: 0 0 12px rgba(255, 85, 0, 0.5) !important; padding: 20px 16px !important;
        }
        
        .orange-title-badge {
            background: rgba(255, 85, 0, 0.15) !important;
            color: #ffaa66 !important;
            border: 1px solid var(--orange-neon) !important;
            padding: 6px 14px !important;
            border-radius: 8px !important;
            box-shadow: 0 0 15px rgba(255, 85, 0, 0.4) !important;
            display: inline-block;
            text-shadow: 0 0 8px var(--orange-neon) !important;
        }

        .orange-table td {
            background: #090d16 !important; 
            color: #e2e8f0 !important;
            border-bottom: 1px solid rgba(255, 85, 0, 0.1) !important; padding: 18px 16px !important;
            vertical-align: middle;
        }
        .orange-table tbody tr:hover td { background: #111827 !important; }

        .highlight-student-id { 
            color: #00ffcc !important; 
            font-weight: 800 !important; font-size: 15px; text-decoration: none; 
            text-shadow: 0 0 10px rgba(0, 255, 204, 0.6) !important;
        }
        .glowing-student-name { 
            color: #ffffff !important; 
            font-weight: 800 !important; font-size: 16px; letter-spacing: 0.3px;
            text-shadow: 0 0 12px rgba(255, 255, 255, 0.95) !important; 
        }
        .glowing-date-text { color: #38bdf8 !important; font-weight: 700 !important; text-shadow: 0 0 10px rgba(56, 189, 248, 0.8) !important; }
        .glowing-time-text { color: #a855f7 !important; font-weight: 700 !important; text-shadow: 0 0 10px rgba(168, 85, 247, 0.8) !important; }
        .glowing-branch-info { color: #fbbf24 !important; font-weight: 700 !important; text-shadow: 0 0 8px rgba(251, 191, 36, 0.5) !important; }
        .normal-sem-text { color: #cbd5e1 !important; font-weight: 600; font-size: 13px; display: block; margin-top: 2px; }

        .avatar-box { position: relative; width: 42px; height: 42px; display: inline-block; }
        .avatar-img { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; object-fit: cover; 
            border: 2px solid var(--orange-neon); box-shadow: 0 0 10px var(--orange-neon); z-index: 2;
        }
        .avatar-logo-placeholder { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; 
            background: linear-gradient(135deg, rgba(255, 85, 0, 0.2), #090d16); color: #ff9944; 
            border: 2px dashed rgba(255, 85, 0, 0.5); box-shadow: 0 0 12px rgba(255, 85, 0, 0.1); 
            display: flex; align-items: center; justify-content: center; z-index: 1; transition: all 0.3s ease-in-out;
        }
        .avatar-logo-placeholder svg { width: 20px; height: 20px; }
        .orange-table tbody tr:hover .avatar-logo-placeholder {
            border-style: solid; border-color: var(--orange-neon); color: #ffffff;
            background: linear-gradient(135deg, var(--orange-neon), #05070c); box-shadow: 0 0 15px var(--orange-neon); transform: scale(1.05);
        }

        .badge-present { background: rgba(16, 185, 129, 0.2) !important; color: #34d399 !important; border: 2px solid #10b981 !important; padding: 8px 18px !important; font-weight: 800 !important; box-shadow: 0 0 12px rgba(16, 185, 129, 0.4) !important; }
        .badge-absent { background: rgba(239, 68, 68, 0.2) !important; color: #f87171 !important; border: 2px solid #ef4444 !important; padding: 8px 18px !important; font-weight: 800 !important; box-shadow: 0 0 12px rgba(239, 68, 68, 0.4) !important; }

        .summary-table-container { border-radius: 20px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.08); }
        .summary-table { margin-bottom: 0; background: rgba(10, 15, 30, 0.6) !important; }
        .summary-table th { background: rgba(15, 25, 45, 0.9) !important; color: #a5b4fc !important; padding: 16px; }
        .summary-table td { padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); vertical-align: middle; }
        
        .btn-custom { border-radius: 14px; padding: 12px 24px; font-weight: 600; transition: all 0.25s ease; }
        .btn-primary-glow { background: #4f46e5; color: white; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); }
        .btn-success-glow { background: #10b981; color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-warning-glow { background: #f59e0b; color: #0f172a; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); }
    </style>
</head>
<body>

<div class="container-fluid p-4">

    <div class="banner mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-1 fw-bold">🎓 Attendance ERP Pro Dashboard</h1>
            <p class="text-muted mb-0">Welcome Back, Admin | Server Time: <strong class="text-white"><?php echo date('h:i A'); ?></strong></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark border border-secondary text-light px-3 py-2 fs-6 rounded-pill">
                <i class="bi bi-calendar3 text-info me-2"></i><?php echo date('l, d M Y'); ?>
            </span>
        </div>
    </div>

    <div class="mb-4 d-flex gap-3 flex-wrap">
        <a href="../students/add_student.php" class="btn btn-custom btn-primary-glow">
            <i class="bi bi-person-plus-fill me-2"></i>Add Student
        </a>
        <a href="../attendance/mark_attendance.php" class="btn btn-custom btn-success-glow">
            <i class="bi bi-check-circle-fill me-2"></i>Mark Attendance
        </a>
        <a href="../reports/analytics.php" class="btn btn-custom btn-warning-glow">
            <i class="bi bi-bar-chart-fill me-2"></i>Analytics Report
        </a>
    </div>

    <div class="card glass-card mb-4">
        <div class="card-body p-4">
            <h5 class="card-title mb-4 fw-bold text-white">
                <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Branch wise overal Student
                <?php if(!empty($selected_semester)): ?>
                    <span class="text-warning fs-6 fw-normal">(Semester for <?php echo htmlspecialchars($selected_semester); ?>)</span>
                <?php endif; ?>
            </h5>
            <div class="table-responsive summary-table-container">
                <table class="table table-dark summary-table text-center align-middle">
                    <thead>
                        <tr>
                            <th class="text-start"><i class="bi bi-journal-bookmark-fill me-2"></i>Branch Name</th>
                            <th><i class="bi bi-people me-2 text-info"></i>Total Registered Students</th>
                            <th><i class="bi bi-patch-check me-2 text-success"></i>Present Today</th>
                            <th><i class="bi bi-patch-exclamation me-2 text-danger"></i>Absent Today</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($standard_branches as $branch_item) {
                            $escaped_bi = mysqli_real_escape_string($conn, $branch_item);
                            
                            $branch_matrix_q = mysqli_query($conn, "
                                SELECT 
                                    (SELECT COUNT(*) FROM students WHERE branch LIKE '%$escaped_bi%' $grid_sem_student_append) as total_b_students,
                                    (SELECT COUNT(DISTINCT student_id) FROM attendance WHERE attendance_date = '$today' AND status = 'Present' AND student_id IN (SELECT student_id FROM students WHERE branch LIKE '%$escaped_bi%' $grid_sem_student_append)) as present_b_today,
                                    (SELECT COUNT(DISTINCT student_id) FROM attendance WHERE attendance_date = '$today' AND status = 'Absent' AND student_id IN (SELECT student_id FROM students WHERE branch LIKE '%$escaped_bi%' $grid_sem_student_append)) as absent_b_today
                            ");
                            $matrix_data = mysqli_fetch_assoc($branch_matrix_q);
                            $b_total = $matrix_data['total_b_students'] ?? 0;
                            $b_present = $matrix_data['present_b_today'] ?? 0;
                            $b_absent = $matrix_data['absent_b_today'] ?? 0;
                        ?>
                            <tr>
                                <td class="text-start fw-bold text-white">
                                    <i class="bi bi-folder2-open text-warning me-2"></i><?php echo $branch_item; ?>
                                </td>
                                <td class="fs-5 fw-semibold text-info"><?php echo $b_total; ?></td>
                                <td class="fs-5 fw-semibold text-success"><?php echo $b_present; ?></td>
                                <td class="fs-5 fw-semibold text-danger"><?php echo $b_absent; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card glass-card" style="box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 20px <?php echo $student_card_glow; ?>;">
                <div class="card-body text-center py-4">
                    <i class="bi bi-people-fill fs-1 <?php echo $student_icon_color; ?>"></i>
                    <div class="card-number <?php echo $student_card_color; ?>"><?php echo $total_students; ?></div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-0 mt-2">Filtered Total Students</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card glass-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-check-fill fs-1 text-success"></i>
                    <div class="card-number" style="color:#10b981;"><?php echo $present; ?></div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-0 mt-2">Present Today</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card glass-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-x-fill fs-1 text-danger"></i>
                    <div class="card-number" style="color:#ef4444;"><?php echo $absent; ?></div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-0 mt-2">Absent Today</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card glass-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-graph-up-arrow fs-1 text-warning"></i>
                    <div class="card-number" style="color:#f59e0b;"><?php echo $rate; ?>%</div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-0 mt-2">Attendance Rate</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="card glass-card mt-5">
        <div class="card-body p-4">
            <h5 class="card-title mb-4"><i class="bi bi-bar-chart-line-fill me-2 text-warning"></i>Attendance Analytics  <span class="text-warning fs-6 fw-normal">(Semester for <?php echo htmlspecialchars($selected_semester); ?>)</span></h5>
            <div style="position: relative; height:300px; width:100%">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <div id="filter-engine-section" class="card premium-filter-panel mt-5 mb-4">
        <h5 class="mb-4 filter-glow-heading">
            <i class="bi bi-sliders2-vertical me-2"></i> Advanced Attendance Filtering Engine
        </h5>
        <form method="GET" action="dashboard.php#filter-engine-section">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label filter-glow-label"><i class="bi bi-search me-1"></i> Search Student (ID / Name)</label>
                    <input type="text" name="search_student" class="form-control premium-glow-input" placeholder="Type Student Roll ID or Name..." value="<?php echo htmlspecialchars($search_student); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label filter-glow-label"><i class="bi bi-mortarboard-fill me-1"></i> Filter By Branch</label>
                    <select name="branch" class="form-select premium-glow-input">
                        <option value="">All Standard Branches</option>
                        <?php
                        foreach ($standard_branches as $branch_opt) {
                            $selected = ($selected_branch === $branch_opt) ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($branch_opt)."' $selected>".htmlspecialchars($branch_opt)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label filter-glow-label"><i class="bi bi-calendar-range-fill me-1"></i> Semester</label>
                    <select name="semester" class="form-select premium-glow-input">
                        <option value="">All Sem</option>
                        <?php
                        for($i=1; $i<=6; $i++) {
                            $selected = ($selected_semester === (string)$i) ? 'selected' : '';
                            echo "<option value='$i' $selected>Sem $i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-cyber-glow w-100 py-3">
                        <i class="bi bi-filter-circle-fill me-1"></i> Filter Logs
                    </button>
                    <?php if(!empty($search_student) || !empty($selected_branch) || !empty($selected_semester)): ?>
                        <a href="dashboard.php#filter-engine-section" class="btn btn-reset-glow py-3 px-3" title="Clear All Active Filters Setup Framework">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="card orange-neon-card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h5 class="card-title orange-neon-title mb-0">
                    <i class="bi bi-lightning-charge-fill me-2"></i> Recent Matching Logs Output Stream
                </h5>
                <?php if(!empty($search_student) || !empty($selected_branch) || !empty($selected_semester)): ?>
                    <span class="badge bg-danger text-white border border-danger p-2 px-3 rounded-pill fw-bold" style="box-shadow:0 0 10px rgba(239,68,68,0.4)">
                        Active Criteria Filtered Live Lookups
                    </span>
                <?php else: ?>
                    <span class="badge bg-info text-dark border border-info p-2 px-3 rounded-pill fw-bold">
                        Showing Today's Logs Automatically
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="table-responsive">
                <table class="table orange-table align-middle">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th><span class="orange-title-badge"><i class="bi bi-mortarboard-fill me-1"></i> Branch & Semester</span></th>
                            <th>Date</th>
                            <th>Time</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recent_logs_query = "
                            SELECT a.id, a.student_id, a.attendance_date, a.attendance_time, a.status,
                                   s.name, s.branch, s.sem, s.photo 
                            FROM attendance a
                            INNER JOIN students s ON TRIM(a.student_id) = TRIM(s.student_id)
                            $recent_logs_where
                            ORDER BY a.id DESC LIMIT 10
                        ";
                        
                        $recent_res = mysqli_query($conn, $recent_logs_query);
                        if($recent_res && mysqli_num_rows($recent_res) > 0) {
                            while($row = mysqli_fetch_assoc($recent_res)) {
                                $formatted_time = !empty($row['attendance_time']) ? date("h:i A", strtotime($row['attendance_time'])) : '--:--';
                                $formatted_date = !empty($row['attendance_date']) ? date("d-m-Y", strtotime($row['attendance_date'])) : 'N/A';
                                
                                $student_name = !empty($row['name']) ? htmlspecialchars($row['name']) : 'Unknown Student';
                                $student_branch = !empty($row['branch']) ? htmlspecialchars($row['branch']) : 'N/A';
                                $student_sem = !empty($row['sem']) ? htmlspecialchars($row['sem']) : '--';
                                
                                $student_avatar = '<div class="avatar-box">';
                                if (!empty($row['photo'])) {
                                    $photo_path = "../uploads/profiles/" . $row['photo'];
                                    $student_avatar .= '<img src="'.htmlspecialchars($photo_path).'" alt="Profile Avatar" class="avatar-img" onerror="this.remove();">';
                                }
                                $student_avatar .= '
                                    <div class="avatar-logo-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                ';
                                $student_avatar .= '</div>';
                        ?>
                        <tr>
                            <td>
                                <a href="../students/view_student.php?id=<?php echo urlencode($row['student_id']); ?>" class="highlight-student-id">
                                    #<?php echo htmlspecialchars($row['student_id']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php echo $student_avatar; ?>
                                    <div class="glowing-student-name"><?php echo $student_name; ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="glowing-branch-info"><?php echo $student_branch; ?></div>
                                <span class="normal-sem-text">Semester <?php echo $student_sem; ?></span>
                            </td>
                            <td class="glowing-date-text"><?php echo $formatted_date; ?></td>
                            <td class="glowing-time-text"><?php echo $formatted_time; ?></td>
                            <td class="text-end">
                                <?php if(trim(strtolower($row['status'])) == 'present'): ?>
                                    <span class="badge badge-present">PRESENT</span>
                                <?php else: ?>
                                    <span class="badge badge-absent">ABSENT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else { 
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted fw-bold">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-4"></i> No Matching Logs Found For Today
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Attendance Rate %',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f59e0b',
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    min: 0, max: 100,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });
</script>

</body>
</html>
<?php include '../includes/footer.php'; ?>