<?php
// 1. डेटाबेस आणि टेम्पलेट फाइल्स इंक्लुड करणे
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

// फिल्टर व्हॅल्यूज मिळवणे (GET द्वारे)
$selected_sem  = isset($_GET['sem']) ? mysqli_real_escape_string($conn, $_GET['sem']) : '';
$scope         = isset($_GET['scope']) ? mysqli_real_escape_string($conn, $_GET['scope']) : 'all_days'; // डिफॉल्ट All Days
$specific_date = isset($_GET['specific_date']) ? mysqli_real_escape_string($conn, $_GET['specific_date']) : '';

// 2. SQL Query चे फिल्टर लॉजिक सेट करणे
$where_clauses = ["1=1"];

if (!empty($specific_date)) {
    $where_clauses[] = "a.attendance_date = '$specific_date'";
} else {
    if (!empty($selected_sem)) {
        $where_clauses[] = "(s.sem = '$selected_sem' OR s.sem LIKE '%$selected_sem%')";
    }
    if ($scope === 'daily') {
        $today = date('Y-m-d');
        $where_clauses[] = "a.attendance_date = '$today'";
    }
}

$final_where = implode(" AND ", $where_clauses);

// मुख्य SQL Query
$query = "SELECT 
            s.branch,
            COUNT(DISTINCT s.student_id) as total_students,
            COUNT(a.id) as total_attendance_logs,
            SUM(CASE WHEN LOWER(TRIM(a.status)) = 'present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN LOWER(TRIM(a.status)) = 'absent' THEN 1 ELSE 0 END) as total_absent
          FROM students s
          LEFT JOIN attendance a ON s.student_id = a.student_id AND $final_where
          WHERE 1=1
          GROUP BY s.branch";

$result = mysqli_query($conn, $query);

$analytics_data = [];
$highest_present_rate = -1;
$king_branch = "";

$standard_branches = ['Computer Science', 'Information Technology', 'Mechanical', 'Electrical', 'Civil', 'Electronics'];

// डिफॉल्ट रिकामी स्ट्रक्चर तयार करणे
foreach($standard_branches as $br) {
    $analytics_data[$br] = [
        'branch' => $br, 'total_students' => 0, 'total_attendance_logs' => 0, 'total_present' => 0, 'total_absent' => 0, 'present_rate' => 0, 'absent_rate' => 0
    ];
}

while ($row = mysqli_fetch_assoc($result)) {
    if(in_array($row['branch'], $standard_branches)) {
        $total_logs = (int)$row['total_attendance_logs'];
        $present = (int)$row['total_present'];
        
        $present_rate = ($total_logs > 0) ? round(($present / $total_logs) * 100, 1) : 0;
        $absent_rate = ($total_logs > 0) ? (100 - $present_rate) : 0;
        
        $row['present_rate'] = $present_rate;
        $row['absent_rate'] = $absent_rate;
        
        $analytics_data[$row['branch']] = $row;
        
        if ($present_rate > $highest_present_rate && $row['total_students'] > 0 && $total_logs > 0) {
            $highest_present_rate = $present_rate;
            $king_branch = $row['branch'];
        }
    }
}

// 📊 ग्राफ्स सॉर्टिंग लॉजिक (Highest First)
$present_sorted = $analytics_data;
usort($present_sorted, function($a, $b) { return $b['present_rate'] <=> $a['present_rate']; });

$absent_sorted = $analytics_data;
usort($absent_sorted, function($a, $b) { return $b['absent_rate'] <=> $a['absent_rate']; });

$students_sorted = $analytics_data;
usort($students_sorted, function($a, $b) { return $b['total_students'] <=> $a['total_students']; });
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Rajdhani:wght@500;600;700&display=swap');

    .analytics-body {
        background: #060913;
        background: radial-gradient(circle at 50% 0%, #15152b 0%, #05070f 100%);
        min-height: 100vh;
        padding: 40px 20px;
        color: #ffffff;
        font-family: 'Rajdhani', sans-serif;
        font-size: 16px;
        letter-spacing: 0.5px;
    }
    
    /* 🌟 हेडिंग आणि टायटल्ससाठी ग्लो इफेक्ट */
    .glow-text-primary {
        font-family: 'Orbitron', sans-serif;
        color: #ffffff;
        text-shadow: 0 0 10px rgba(99, 102, 241, 0.6), 0 0 20px rgba(99, 102, 241, 0.4);
    }
    .glow-text-success {
        font-family: 'Orbitron', sans-serif;
        color: #10b981 !important;
        text-shadow: 0 0 12px rgba(16, 185, 129, 0.7);
    }
    .glow-text-danger {
        font-family: 'Orbitron', sans-serif;
        color: #ef4444 !important;
        text-shadow: 0 0 12px rgba(239, 68, 68, 0.7);
    }
    .glow-text-info {
        font-family: 'Orbitron', sans-serif;
        color: #06b6d4 !important;
        text-shadow: 0 0 12px rgba(6, 182, 212, 0.7);
    }
    .text-light-custom {
        color: #cbd5e1 !important; /* स्पष्ट वाचता येणारा पांढरा/चंदेरी रंग */
        font-weight: 500;
    }

    .filter-card {
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 35px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
    }
    .branch-card {
        background: rgba(20, 27, 45, 0.6);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 25px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .branch-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.25);
        border-color: rgba(99, 102, 241, 0.6);
    }
    .king-card {
        border: 2px solid #f59e0b !important;
        background: linear-gradient(135deg, rgba(20, 27, 45, 0.8) 0%, rgba(245, 158, 11, 0.1) 100%);
        box-shadow: 0 0 35px rgba(245, 158, 11, 0.25);
    }
    .king-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #000000;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 11px;
        font-family: 'Orbitron', sans-serif;
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.6);
    }
    .chart-box {
        position: relative;
        max-width: 130px;
        margin: 0 auto;
    }
    .stat-label {
        font-size: 13px;
        color: #e2e8f0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-value {
        font-size: 15px;
        font-weight: 700;
        font-family: 'Orbitron', sans-serif;
    }
    .progress-custom {
        background-color: rgba(255, 255, 255, 0.07);
        height: 10px;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 5px;
        border: 1px solid rgba(255,255,255,0.03);
    }
    .form-select-custom, .form-control-custom {
        background-color: #0a0e1a !important;
        border: 1px solid rgba(99, 102, 241, 0.3) !important;
        color: #ffffff !important;
        font-weight: 600;
    }
    .form-select-custom:focus, .form-control-custom:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.4) !important;
    }
    .btn-apply {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: white;
        font-weight: 700;
        font-family: 'Orbitron', sans-serif;
        letter-spacing: 1px;
        border: none;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        transition: all 0.3s;
    }
    .btn-apply:hover { 
        background: linear-gradient(135deg, #4f46e5, #4338ca);
        box-shadow: 0 4px 25px rgba(99, 102, 241, 0.7);
        transform: scale(1.02);
    }
    
    .nav-pills-custom .nav-link {
        color: #cbd5e1;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-weight: 700;
        font-size: 14px;
        font-family: 'Orbitron', sans-serif;
    }
    .nav-pills-custom .nav-link.active {
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        color: white !important;
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        border-color: #6366f1;
    }

    /* 💎 प्रिमियम मास्टर ग्राफ कार्ड्स स्टाईल्स */
    .master-chart-card {
        background: rgba(16, 24, 48, 0.65);
        backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 30px;
        margin-bottom: 40px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
        position: relative;
    }
    .master-chart-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 6px; height: 100%;
        border-radius: 24px 0 0 24px;
    }
    .card-present { border-left: 1px solid rgba(16, 185, 129, 0.3); }
    .card-present::before { background: #10b981; box-shadow: 0 0 15px #10b981; }
    
    .card-absent { border-left: 1px solid rgba(239, 68, 68, 0.3); }
    .card-absent::before { background: #ef4444; box-shadow: 0 0 15px #ef4444; }
    
    .card-volume { border-left: 1px solid rgba(6, 182, 212, 0.3); }
    .card-volume::before { background: #06b6d4; box-shadow: 0 0 15px #06b6d4; }

    .chart-container-custom {
        position: relative; 
        height: 340px; 
        width: 100%;
        background: rgba(7, 11, 21, 0.6);
        padding: 20px;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.04);
    }
</style>

<div class="analytics-body">
    <div class="container-fluid">
        
        <div class="mb-4">
            <h2 class="fw-bold mb-1 glow-text-primary"><i class="bi bi-grid-1x2-fill me-2"></i>Live Attendance Analytics</h2>
            <p class="text-light-custom mb-0" style="font-size: 18px;">Real-time performance index metrics per department branch.</p>
        </div>
        
        <div class="filter-card">
            <form method="GET" action="" id="filterForm" class="row g-3 align-items-center">
                <div class="col-xl-3 col-md-4">
                    <label class="small text-light-custom fw-bold d-block mb-2">Analysis Scope Mode:</label>
                    <div class="nav nav-pills nav-pills-custom gap-2">
                        <button type="button" class="nav-link w-50 <?php echo ($scope === 'daily') ? 'active' : ''; ?>" onclick="setScope('daily')"><i class="bi bi-calendar2-day me-1"></i>Daily</button>
                        <button type="button" class="nav-link w-50 <?php echo ($scope === 'all_days') ? 'active' : ''; ?>" onclick="setScope('all_days')"><i class="bi bi-calendar2-check me-1"></i>All Days</button>
                    </div>
                    <input type="hidden" name="scope" id="scope_input" value="<?php echo $scope; ?>">
                </div>

                <div class="col-xl-3 col-md-4">
                    <label class="small text-light-custom fw-bold mb-2 d-block">Academic Semester:</label>
                    <select name="sem" id="sem_filter" class="form-select form-select-custom rounded-3" <?php echo !empty($specific_date) ? 'disabled' : ''; ?>>
                        <option value="">-- All Semesters --</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($selected_sem == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-xl-3 col-md-4">
                    <label class="small fw-bold mb-2 d-block text-warning" style="text-shadow: 0 0 8px rgba(245,158,11,0.4);"><i class="bi bi-funnel-fill me-1"></i>Particular Day Filter (Independent):</label>
                    <input type="date" name="specific_date" id="specific_date" class="form-control form-control-custom rounded-3" value="<?php echo $specific_date; ?>" onchange="toggleFilters(this.value)">
                </div>

                <div class="col-xl-3 col-md-12 d-flex align-items-end h-100" style="padding-top: 28px;">
                    <button type="submit" class="btn btn-apply w-100 rounded-3 py-2"><i class="bi bi-filter me-1"></i>Apply Selected Filters</button>
                </div>
            </form>
        </div>

        <div class="row g-4 mb-5">
            <?php 
            foreach ($standard_branches as $branch_name): 
                $b_data = isset($analytics_data[$branch_name]) ? $analytics_data[$branch_name] : [
                    'total_students' => 0, 'total_attendance_logs' => 0, 'total_present' => 0, 'total_absent' => 0, 'present_rate' => 0, 'absent_rate' => 0
                ];
                
                $is_king = ($branch_name === $king_branch);
                $total_logs = $b_data['total_attendance_logs'];
                $p_bar_width = ($total_logs > 0) ? ($b_data['total_present'] / $total_logs) * 100 : 0;
                $a_bar_width = ($total_logs > 0) ? ($b_data['total_absent'] / $total_logs) * 100 : 0;
            ?>
                <div class="col-xl-4 col-md-6">
                    <div class="branch-card <?php echo $is_king ? 'king-card' : ''; ?>">
                        
                        <?php if($is_king): ?>
                            <span class="king-badge"><i class="bi bi-crown-fill me-1"></i> KING BRANCH</span>
                        <?php endif; ?>

                        <h4 class="fw-bold mb-4 text-truncate" style="max-width: 75%; font-family: 'Orbitron', sans-serif; color: #ffffff; letter-spacing: 1px;">
                            <?php echo $branch_name; ?>
                        </h4>

                        <div class="row align-items-center">
                            <div class="col-5 border-end border-secondary border-opacity-50">
                                <div class="chart-box">
                                    <canvas id="chart-<?php echo str_replace(' ', '-', $branch_name); ?>"></canvas>
                                </div>
                            </div>
                            
                            <div class="col-7 ps-3">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="stat-label">Total Students</span>
                                        <span class="stat-value text-info" style="text-shadow: 0 0 8px rgba(6,182,212,0.5);"><?php echo $b_data['total_students']; ?></span>
                                    </div>
                                    <div class="progress-custom">
                                        <div class="bg-info" style="width: <?php echo $b_data['total_students'] > 0 ? '100%' : '0%'; ?>; height: 100%; box-shadow: 0 0 10px #06b6d4;"></div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="stat-label">Present Factor</span>
                                        <span class="stat-value text-success" style="text-shadow: 0 0 8px rgba(16,185,129,0.5);"><?php echo $b_data['present_rate']; ?>%</span>
                                    </div>
                                    <div class="progress-custom">
                                        <div class="bg-success" style="width: <?php echo $p_bar_width; ?>%; height: 100%; box-shadow: 0 0 10px #10b981;"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="stat-label">Absent Factor</span>
                                        <span class="stat-value text-danger" style="text-shadow: 0 0 8px rgba(239,68,68,0.5);"><?php echo $b_data['absent_rate']; ?>%</span>
                                    </div>
                                    <div class="progress-custom">
                                        <div class="bg-danger" style="width: <?php echo $a_bar_width; ?>%; height: 100%; box-shadow: 0 0 10px #ef4444;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="my-5 text-center">
            <h4 class="fw-bold text-uppercase tracking-wider" style="color: #6366f1; font-family: 'Orbitron', sans-serif; font-size: 16px; text-shadow: 0 0 10px rgba(99,102,241,0.5);">Institutional Performance Leaderboards</h4>
            <div style="width: 100px; height: 3px; background: linear-gradient(90deg, transparent, #6366f1, transparent); margin: 12px auto; border-radius: 10px;"></div>
        </div>

        <div class="row">
            <div class="col-12">
                
                <div class="master-chart-card card-present">
                    <h5 class="fw-bold mb-1 glow-text-success"><i class="bi bi-shield-check me-2"></i>Attendance Ranking (Highest Attendance First)</h5>
                    <p class="text-light-custom small mb-3">Departments sorted by prsent days.</p>
                    <div class="chart-container-custom">
                        <canvas id="presentRankChart"></canvas>
                    </div>
                </div>

                <div class="master-chart-card card-absent">
                    <h5 class="fw-bold mb-1 glow-text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Critical Absence Index (Highest Absenteeism First)</h5>
                    <p class="text-light-custom small mb-3">Departments sorted by Absent days.</p>
                    <div class="chart-container-custom">
                        <canvas id="absentRankChart"></canvas>
                    </div>
                </div>

                <div class="master-chart-card card-volume">
                    <h5 class="fw-bold mb-1 glow-text-info"><i class="bi bi-diagram-3-fill me-2"></i>Student Volume Distribution (Highest Enrollment First)</h5>
                    <p class="text-light-custom small mb-3">Showing total student headcount metrics.</p>
                    <div class="chart-container-custom">
                        <canvas id="studentVolumeChart"></canvas>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function setScope(val) {
    document.getElementById('scope_input').value = val;
    document.querySelectorAll('.nav-pills-custom .nav-link').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
}

function toggleFilters(dateVal) {
    const semFilter = document.getElementById('sem_filter');
    const scopeBtns = document.querySelectorAll('.nav-pills-custom button');
    
    if(dateVal !== "") {
        semFilter.disabled = true;
        scopeBtns.forEach(btn => btn.disabled = true);
    } else {
        semFilter.disabled = false;
        scopeBtns.forEach(btn => btn.disabled = false);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    toggleFilters(document.getElementById('specific_date').value);

    // ६ वैयक्तिक डोनट चार्ट्स कॉन्फिगरेशन
    <?php foreach ($standard_branches as $branch_name): 
        $b_data = isset($analytics_data[$branch_name]) ? $analytics_data[$branch_name] : [
            'present_rate' => 0, 'absent_rate' => 0, 'total_students' => 0, 'total_attendance_logs' => 0
        ];
        $chart_id = "chart-" . str_replace(' ', '-', $branch_name);
        
        $has_data = ($b_data['total_students'] > 0 && $b_data['total_attendance_logs'] > 0) ? true : false;
        $p_val = $has_data ? $b_data['present_rate'] : 0;
        $a_val = $has_data ? $b_data['absent_rate'] : 100;
        $p_color = $has_data ? '#10b981' : '#475569';
    ?>
    
    new Chart(document.getElementById('<?php echo $chart_id; ?>').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Present %', 'Absent %'],
            datasets: [{
                data: [<?php echo $p_val; ?>, <?php echo $a_val; ?>],
                backgroundColor: ['<?php echo $p_color; ?>', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '80%', 
            plugins: { legend: { display: false } }
        }
    });
    <?php endforeach; ?>


    // 🛠️ ग्राफ्ससाठी प्रिमियम ग्लोइंग आणि स्पष्ट फॉन्ट ऑप्शन्स 
    const getMasterOptions = (suffix) => ({
        indexAxis: 'y', 
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0a0f1d',
                titleColor: '#ffffff',
                titleFont: { family: 'Orbitron', size: 13, weight: 'bold' },
                bodyColor: '#e2e8f0',
                bodyFont: { family: 'Rajdhani', size: 14, weight: '600' },
                borderColor: '#6366f1',
                borderWidth: 1,
                padding: 12,
                boxPadding: 6,
                callbacks: {
                    label: function(context) {
                        return ' Data Index: ' + context.parsed.x + suffix;
                    }
                }
            }
        },
        scales: {
            x: { 
                grid: { color: 'rgba(255, 255, 255, 0.06)', drawBorder: false }, 
                ticks: { color: '#94a3b8', font: { family: 'Orbitron', size: 11, weight: '500' } } 
            },
            y: { 
                grid: { display: false }, 
                ticks: { color: '#ffffff', font: { family: 'Rajdhani', weight: '700', size: 14 } } 
            }
        }
    });

    // प्रिमियम निऑन ग्रॅडियंट्स बनवणे
    const ctx1 = document.getElementById('presentRankChart').getContext('2d');
    const gradPresent = ctx1.createLinearGradient(0, 0, ctx1.canvas.width, 0);
    gradPresent.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradPresent.addColorStop(1, '#10b981');

    const ctx2 = document.getElementById('absentRankChart').getContext('2d');
    const gradAbsent = ctx2.createLinearGradient(0, 0, ctx2.canvas.width, 0);
    gradAbsent.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
    gradAbsent.addColorStop(1, '#ef4444');

    const ctx3 = document.getElementById('studentVolumeChart').getContext('2d');
    const gradVolume = ctx3.createLinearGradient(0, 0, ctx3.canvas.width, 0);
    gradVolume.addColorStop(0, 'rgba(6, 182, 212, 0.2)');
    gradVolume.addColorStop(1, '#06b6d4');

    // ग्राफ १: Present Rate Chart
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($present_sorted, 'branch')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($present_sorted, 'present_rate')); ?>,
                backgroundColor: gradPresent,
                borderRadius: 8,
                barThickness: 22,
                borderWidth: 1,
                borderColor: '#10b981'
            }]
        },
        options: getMasterOptions('%')
    });

    // ग्राफ २: Absent Rate Chart
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($absent_sorted, 'branch')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($absent_sorted, 'absent_rate')); ?>,
                backgroundColor: gradAbsent,
                borderRadius: 8,
                barThickness: 22,
                borderWidth: 1,
                borderColor: '#ef4444'
            }]
        },
        options: getMasterOptions('%')
    });

    // ग्राफ ३: Student Volume Chart
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($students_sorted, 'branch')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($students_sorted, 'total_students')); ?>,
                backgroundColor: gradVolume,
                borderRadius: 8,
                barThickness: 22,
                borderWidth: 1,
                borderColor: '#06b6d4'
            }]
        },
        options: getMasterOptions(' Students')
    });
});
</script>

<?php 
include '../includes/footer.php'; 
?>