<?php
/**
 * Ultra-Premium Cyberpunk Attendance Analytics
 * Fail-Safe Edition: Anti-Blank Screen Guard Active
 * Module: Clear Filter Vector Engine & Dynamic Target Shaker
 * Scope: Extended with Electrical and Electronics Vectors
 * Language: English Enterprise Standards
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php'; 

date_default_timezone_set('Asia/Kolkata');

// 📅 Filters Initialization
$start_date     = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date       = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$filter_branch  = isset($_GET['filter_branch']) ? trim($_GET['filter_branch']) : '';
$search_student = isset($_GET['search_student']) ? trim($_GET['search_student']) : '';

// Flag to check if any active filter is applied
$is_filter_active = (!empty($start_date) || !empty($end_date) || !empty($filter_branch) || !empty($search_student));

$dataset = [];
$error_message = "";

try {
    if (!$conn) {
        throw new Exception("Database Connection Vector Terminated. Check database.php configuration.");
    }

    $query = "SELECT 
                s.student_id, 
                s.name AS student_name, 
                s.branch, 
                s.sem AS semester, 
                IFNULL(?, DATE(s.created_at)) AS baseline_start_date,
                IFNULL(?, CURRENT_DATE()) AS baseline_end_date,
                (DATEDIFF(IFNULL(?, CURRENT_DATE()), IFNULL(?, DATE(s.created_at))) + 1) AS total_working_days,
                COUNT(CASE WHEN a.status = 'Present' " . (!empty($start_date) && !empty($end_date) ? "AND a.attendance_date BETWEEN ? AND ?" : "") . " THEN 1 END) AS present_days,
                COUNT(CASE WHEN a.status = 'Absent' " . (!empty($start_date) && !empty($end_date) ? "AND a.attendance_date BETWEEN ? AND ?" : "") . " THEN 1 END) AS absent_days
              FROM students s
              LEFT JOIN attendance a ON s.student_id = a.student_id
              WHERE 1=1";

    $params = [];
    $types = "";

    $p_start = !empty($start_date) ? $start_date : null;
    $p_end   = !empty($end_date) ? $end_date : null;
    
    $params[] = $p_start; $params[] = $p_end;
    $params[] = $p_end;   $params[] = $p_start;
    $types .= "ssss";

    if (!empty($start_date) && !empty($end_date)) {
        $params[] = $start_date; $params[] = $end_date;
        $params[] = $start_date; $params[] = $end_date;
        $types .= "ssss";
    }

    if (!empty($filter_branch)) {
        $query .= " AND s.branch = ?";
        $params[] = $filter_branch;
        $types .= "s";
    }
    
    if (!empty($search_student)) {
        $query .= " AND s.student_id LIKE ?";
        $search_param = "%" . $search_student . "%";
        $params[] = $search_param;
        $types .= "s";
    }

    $query .= " GROUP BY s.student_id, s.name, s.branch, s.sem, s.created_at ORDER BY s.student_id ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $dataset[] = $row;
            }
        } else {
            throw new Exception("SQL Execution Fatal Failure: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        throw new Exception("Blueprint Compile Error (Check table or column name constraints): " . mysqli_error($conn));
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glow Analytics - Enterprise Attendance Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background-color: #060913 !important; 
            color: #a0aec0 !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .report-header-card {
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            border: 1px solid #374151;
            padding: 26px;
            border-radius: 14px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .report-title {
            color: #ffffff !important;
            font-size: 1.5rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }
        .panel-box-container {
            background-color: #0f1626;
            border: 1px solid #1e293b;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .field-structural-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .form-control-blueprint, .form-select-blueprint {
            background-color: #030712 !important;
            border: 1px solid #334155 !important;
            color: #f3f4f6 !important;
            height: 44px;
            border-radius: 8px;
            font-size: 0.88rem;
        }
        .form-control-blueprint:focus, .form-select-blueprint:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.25) !important;
        }
        .btn-blueprint-trigger {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%) !important;
            border: none !important;
            color: #ffffff !important;
            height: 44px;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        .btn-blueprint-trigger:hover { 
            transform: translateY(-1px);
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.4) !important;
        }
        .btn-blueprint-clear {
            background: linear-gradient(90deg, #374151 0%, #1f2937 100%) !important;
            border: 1px solid #4b5563 !important;
            color: #d1d5db !important;
            height: 44px;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        .btn-blueprint-clear:hover {
            background: #4b5563 !important;
            color: #ffffff !important;
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.1) !important;
        }
        .report-table-wrapper {
            background-color: #0b0f19; 
            border: 1px solid #1e293b;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .table-report-matrix th {
            background-color: #111726 !important;
            color: #94a3b8 !important;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 20px;
            border-bottom: 2px solid #1e293b;
        }
        .table-report-matrix td {
            padding: 15px 20px;
            border-bottom: 1px solid #161f30;
            background-color: #0e1424 !important; 
            color: #cbd5e1;
        }
        .table-report-matrix tbody tr:hover td {
            background-color: #151d30 !important;
            color: #ffffff;
        }
        
        /* ⚡ ULTRA CYBER TARGET HIGHLIGHTER STYLES */
        @keyframes cyberShake {
            0% { transform: translate(0, 0); }
            20% { transform: translate(-1px, 1px); }
            40% { transform: translate(-1px, -1px); }
            60% { transform: translate(1px, 1px); }
            80% { transform: translate(1px, -1px); }
            100% { transform: translate(0, 0); }
        }
        .cyber-targeted-row {
            animation: cyberShake 0.4s ease-in-out infinite alternate;
            box-shadow: inset 0 0 20px rgba(0, 242, 254, 0.2);
        }
        .cyber-targeted-row td {
            background-color: #0f243a !important;
            border-top: 1px solid #00f2fe !important;
            border-bottom: 1px solid #00f2fe !important;
            color: #ffffff !important;
        }
        .targeted-glow-text {
            color: #00f2fe !important;
            text-shadow: 0 0 15px #00f2fe, 0 0 25px #00f2fe !important;
            font-weight: 900 !important;
        }
        .glow-student-id {
            color: #00f2fe !important;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            text-shadow: 0 0 10px rgba(0, 242, 254, 0.5);
        }
        .glow-student-name {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.95rem;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.25);
        }
        .glow-percentage {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 1rem;
        }
        .badge-present-neon {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 700;
            text-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
        }
        .badge-absent-neon {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 700;
            text-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
        }
        .badge-total-neon {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 700;
            text-shadow: 0 0 8px rgba(59, 130, 246, 0.4);
        }
        .progress-bar-glow-wrapper {
            height: 6px;
            border-radius: 20px;
            background-color: #1e293b;
            overflow: hidden;
            margin-top: 6px;
        }
        .progress-bar-fill-neon {
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>

<div class="container-fluid p-4">

    <div class="report-header-card d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1 report-title"><i class="bi bi-cpu-fill text-primary me-2"></i>Live Attendance Matrix Hub</h2>
            <span class="text-muted small font-monospace">High-Contrast Glowing Metrics System Terminal</span>
        </div>
        <div class="bg-black px-3 py-2 border border-secondary" style="border-radius: 8px;">
            <div class="text-muted font-monospace" style="font-size: 0.58rem; font-weight: 700;">SYSTEM ENGINE ONLINE</div>
            <div class="text-info fw-bold font-monospace" style="font-size: 0.82rem;"><i class="bi bi-hdd-network me-1"></i><?php echo date("d-M-Y"); ?></div>
        </div>
    </div>

    <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger border-0 p-4 mb-4 shadow-lg" style="background-color: #110c14; color: #ff5555; border: 2px solid #ef4444; border-radius: 12px; box-shadow: 0 0 20px rgba(239, 68, 68, 0.25);">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-shield-slash-fill fs-4 me-2 text-danger"></i>
                <span class="fw-bold tracking-wider" style="font-size: 1.1rem; text-transform: uppercase;">CRITICAL COMPILATION FAULT DETECTED</span>
            </div>
            <hr style="border-color: rgba(239, 68, 68, 0.3);">
            <p class="mb-2 fw-medium text-white">System error intercepted. Screen guard protection is active. Review debug data stream below:</p>
            <div class="p-3 bg-black rounded border border-dark font-monospace text-warning" style="font-size: 0.88rem; white-space: pre-wrap; overflow-x: auto;"><?php echo htmlspecialchars($error_message); ?></div>
        </div>
    <?php endif; ?>

    <div class="panel-box-container">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="field-structural-label">Start Date VECTOR</label>
                <input type="date" name="start_date" class="form-control form-control-blueprint" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-2">
                <label class="field-structural-label">End Date VECTOR</label>
                <input type="date" name="end_date" class="form-control form-control-blueprint" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-3">
                <label class="field-structural-label">Branch Target Vector</label>
                <select name="filter_branch" class="form-select form-select-blueprint">
                    <option value="">-- All Departments --</option>
                    <option value="Computer Science" <?php echo ($filter_branch == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Information Technology" <?php echo ($filter_branch == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Electrical" <?php echo ($filter_branch == 'Electrical') ? 'selected' : ''; ?>>Electrical Engineering</option>
                    <option value="Electronics" <?php echo ($filter_branch == 'Electronics') ? 'selected' : ''; ?>>Electronics Engineering</option>
                    <option value="Mechanical" <?php echo ($filter_branch == 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                    <option value="Civil" <?php echo ($filter_branch == 'Civil') ? 'selected' : ''; ?>>Civil</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="field-structural-label" style="color: #00f2fe; text-shadow: 0 0 5px rgba(0,242,254,0.2);"><i class="bi bi-search me-1"></i>Filter by Student ID</label>
                <input type="text" name="search_student" class="form-control form-control-blueprint" style="border-color: rgba(0, 242, 254, 0.4) !important;" placeholder="Type exact Student ID to shake row..." value="<?php echo htmlspecialchars($search_student); ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-blueprint-trigger flex-grow-1" title="Execute Search Matrix"><i class="bi bi-lightning-fill me-1"></i> Run</button>
                <?php if($is_filter_active): ?>
                    <a href="?" class="btn btn-blueprint-clear d-flex align-items-center justify-content-center px-3" title="Clear Filters & Fetch All Data"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="report-table-wrapper">
        <table class="table table-report-matrix align-middle mb-0">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Identity Profile</th>
                    <th>Department Structural Scope</th>
                    <th>Tracking Operational Window</th>
                    <th class="text-center">Total Day</th>
                    <th class="text-center">Present Vector Sum</th>
                    <th class="text-center">Absent Vector Sum</th>
                    <th style="width: 220px;">Yield Ratio Summary</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(empty($error_message) && count($dataset) > 0) {
                    foreach($dataset as $row) {
                        $total_days   = intval($row['total_working_days']);
                        $present_days = intval($row['present_days']);
                        $absent_days  = intval($row['absent_days']);
                        
                        $percentage = ($total_days > 0) ? round(($present_days / $total_days) * 100, 1) : 0;
                        
                        if($percentage >= 75) { 
                            $glow_color = '#10b981'; 
                            $shadow_matrix = '0 0 12px rgba(16, 185, 129, 0.6)';
                        } elseif($percentage >= 50) { 
                            $glow_color = '#f59e0b'; 
                            $shadow_matrix = '0 0 12px rgba(245, 158, 11, 0.6)';
                        } else { 
                            $glow_color = '#ef4444'; 
                            $shadow_matrix = '0 0 12px rgba(239, 68, 68, 0.6)';
                        }
                        
                        $display_start = date("d-M-Y", strtotime($row['baseline_start_date']));
                        $display_end   = date("d-M-Y", strtotime($row['baseline_end_date']));

                        // High-Impact Row Selection Logic
                        $isTargeted = (!empty($search_student) && strtolower($row['student_id']) == strtolower($search_student));
                        $rowClass = $isTargeted ? 'class="cyber-targeted-row"' : '';
                        $idSpanClass = $isTargeted ? 'targeted-glow-text' : 'glow-student-id';
                ?>
                <tr <?php echo $rowClass; ?>>
                    <td class="<?php echo $idSpanClass; ?>"><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td class="glow-student-name"><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td>
                        <span class="badge bg-dark border border-secondary font-monospace" style="color: #e2e8f0; font-size: 0.75rem;"><?php echo htmlspecialchars($row['branch']); ?> &bull; SEM <?php echo htmlspecialchars($row['semester']); ?></span>
                    </td>
                    <td style="font-size: 0.78rem; color: #64748b; font-family: monospace;">
                        <div><i class="bi bi-calendar-plus text-success me-1"></i><?php echo $display_start; ?></div>
                        <div><i class="bi bi-calendar-minus text-danger me-1"></i><?php echo $display_end; ?></div>
                    </td>
                    <td class="text-center"><span class="badge-total-neon"><?php echo $total_days; ?> Days</span></td>
                    <td class="text-center"><span class="badge-present-neon"><?php echo $present_days; ?></span></td>
                    <td class="text-center"><span class="badge-absent-neon"><?php echo $absent_days; ?></span></td>
                    <td>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="glow-percentage" style="color: <?php echo $glow_color; ?>; text-shadow: <?php echo $shadow_matrix; ?>;">
                                <?php echo $percentage; ?>%
                            </span>
                        </div>
                        <div class="progress-bar-glow-wrapper">
                            <div class="progress-bar-fill-neon" style="width: <?php echo min($percentage, 100); ?>%; background-color: <?php echo $glow_color; ?>; box-shadow: <?php echo $shadow_matrix; ?>;"></div>
                        </div>
                    </td>
                </tr>
                <?php 
                    }
                } else { 
                ?>
                <tr>
                    <td colspan="8" class="text-center p-5 text-muted" style="background-color: #0e1424 !important;">
                        <i class="bi bi-database-exclamation display-5 d-block mb-3 text-warning"></i>
                        <span class="fw-bold text-white d-block mb-1">No Valid Operational Records Found</span>
                        Verify systemic parameters, adjust global vectors, or check database architecture logs.
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
<?php include '../includes/footer.php'; ?>