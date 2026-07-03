<?php
/**
 * Enterprise Attendance Management System
 * Core Controller: Secure Admin Profile Panel & Advanced Data Purge Console
 */
include '../config/session.php';
include '../config/database.php';

// १. रीअल-टाइम डेटा काउंटर्स इंजिन
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
$total_students_registered = mysqli_fetch_assoc($count_query)['total'] ?? 0;

// २. फिल्टर पॅरामीटर्स सॅनिटायझेशन
$filter_branch = isset($_GET['purge_branch']) ? mysqli_real_escape_string($conn, trim($_GET['purge_branch'])) : '';
$filter_sem    = isset($_GET['purge_sem']) ? mysqli_real_escape_string($conn, trim($_GET['purge_sem'])) : '';

$where_clause = " WHERE 1=1 ";
if (!empty($filter_branch)) { $where_clause .= " AND branch = '$filter_branch' "; }
if (!empty($filter_sem))    { $where_clause .= " AND sem = '$filter_sem' "; }

// फिल्टर केलेल्या नोड्सचा अचूक काउंट
$filtered_count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM students $where_clause");
$filtered_students_count = mysqli_fetch_assoc($filtered_count_query)['total'] ?? 0;

$show_purge_overlay = false;
$purge_message = "";

// ३. प्रगत मास्टर डिलीट आणि निवडक डिलीट हँडलर
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['execute_purge'])) {
    $purge_type = $_POST['purge_type'];
    
    if ($purge_type === 'all') {
        // संपूर्ण विद्यार्थ्यांचा डेटा नष्ट करणे (Master Reset)
        if (mysqli_query($conn, "TRUNCATE TABLE students")) {
            $show_purge_overlay = true;
            $purge_message = "All student data has been deleted successfully!";
        }
    } else if ($purge_type === 'filtered' && (!empty($filter_branch) || !empty($filter_sem))) {
        // फक्त निवडलेल्या फिल्टरचा डेटा सुरक्षितपणे डिलीट करणे
        if (mysqli_query($conn, "DELETE FROM students $where_clause")) {
            $show_purge_overlay = true;
            $purge_message = "Data deleted successfully from this branch & semester!";
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Node | ERP Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Rajdhani:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #02040a !important;
            background-image: radial-gradient(circle at 50% 30%, #071126 0%, #010205 100%) !important;
            min-height: 100vh;
            color: #e2e8f0;
            font-family: 'Rajdhani', sans-serif;
            padding: 40px 20px;
        }

        .admin-matrix-card {
            background: rgba(4, 8, 20, 0.85) !important;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 2px solid #00f2fe !important;
            border-radius: 24px;
            box-shadow: 0 0 40px rgba(0, 242, 254, 0.15), inset 0 0 20px rgba(0, 242, 254, 0.05);
        }

        .matrix-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            letter-spacing: 2px;
            color: #ffffff;
            text-shadow: 0 0 15px rgba(0, 242, 254, 0.6);
        }
        
        .radar-icon {
            font-size: 2.5rem; color: #00f2fe;
            filter: drop-shadow(0 0 10px #00f2fe);
            animation: pulse-node 2s infinite alternate;
        }

        .metric-field {
            background: rgba(2, 4, 9, 0.9);
            border: 1px solid rgba(0, 242, 254, 0.15);
            border-radius: 12px; padding: 14px 18px;
        }

        .label-term {
            font-family: 'Orbitron', sans-serif; font-size: 0.75rem;
            font-weight: 700; color: #64748b; letter-spacing: 1.5px;
        }

        .value-string {
            font-family: 'JetBrains Mono', monospace; font-size: 1.15rem;
            font-weight: 700; color: #ffffff;
        }

        .badge-cyber-admin {
            background: rgba(255, 170, 0, 0.1) !important;
            border: 1px solid #ffaa00 !important; color: #ffaa00 !important;
            font-family: 'Orbitron', sans-serif; font-weight: 700;
        }

        .glowing-select-node {
            background: #010206 !important;
            border: 1px solid rgba(0, 242, 254, 0.3) !important;
            color: white !important; font-weight: 600; font-size: 0.95rem;
            border-radius: 8px !important; height: 44px;
        }
        .glowing-select-node:focus {
            border-color: #00f2fe !important;
            box-shadow: 0 0 12px rgba(0, 242, 254, 0.5) !important;
        }
        .glowing-select-node option { background-color: #040712; color: white; }

        .purge-console-box {
            border: 1px dashed rgba(255, 0, 85, 0.3);
            background: rgba(255, 0, 85, 0.02); border-radius: 16px; padding: 20px;
        }

        .btn-matrix-purge {
            font-family: 'Orbitron', sans-serif; font-weight: 700; font-size: 0.82rem;
            background: rgba(255, 0, 85, 0.15); border: 1px solid #ff0055; color: #ff0055;
            padding: 12px; border-radius: 10px; transition: all 0.3s;
            box-shadow: 0 0 10px rgba(255, 0, 85, 0.1);
        }
        .btn-matrix-purge:hover {
            background: #ff0055; color: black; font-weight: 900;
            box-shadow: 0 0 20px #ff0055; transform: translateY(-1px);
        }

        /* 🎆 CINEMATIC RED PURGE OVERLAY ARCHITECTURE */
        .purge-fullscreen-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(5, 7, 14, 0.97); backdrop-filter: blur(25px);
            z-index: 99999; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }
        .success-circle-wrapper {
            width: 140px; height: 140px; position: relative;
            display: flex; justify-content: center; align-items: center;
        }
        .svg-checkmark {
            width: 110px; height: 110px; border-radius: 50%;
            display: block; stroke-width: 4; stroke: #ff0055;
            stroke-miterlimit: 10; box-shadow: inset 0px 0px 0px #ff0055;
            animation: fillCheckmark .4s ease-in-out .4s forwards;
        }
        .svg-checkmark-circle {
            stroke-dasharray: 166; stroke-dashoffset: 166; stroke-width: 4;
            stroke-miterlimit: 10; stroke: #ff0055; fill: none;
            animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .svg-checkmark-check {
            transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48;
            animation: strokeCheckmark 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
        }
        @keyframes strokeCircle { 100% { stroke-dashoffset: 0; } }
        @keyframes strokeCheckmark { 100% { stroke-dashoffset: 0; } }
        @keyframes fillCheckmark { 100% { box-shadow: inset 0px 0px 0px 80px rgba(255, 0, 85, 0.15); border: 2px solid #ff0055; } }
        
        .purge-neon-text {
            font-size: 1.4rem; font-weight: 700; color: #ff0055;
            letter-spacing: 0.5px; text-shadow: 0 0 25px rgba(255, 0, 85, 0.6); margin-top: 30px;
            text-align: center; max-width: 80%;
        }

        @keyframes pulse-node { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(1.05); opacity: 1; } }
    </style>
</head>
<body>

<?php if($show_purge_overlay): ?>
<div class="purge-fullscreen-overlay animate__animated animate__fadeIn">
    <div class="success-circle-wrapper">
        <svg class="svg-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="svg-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="svg-checkmark-check" fill="none" d="M16 16 36 36 M36 16 16 36"/>
        </svg>
    </div>
    <div class="purge-neon-text animate__animated animate__zoomIn animate__delay-1s">
        <i class="bi bi-check-circle-fill"></i> <?= $purge_message; ?>
    </div>
    <div class="text-muted font-monospace small mt-2 animate__animated animate__fadeIn animate__delay-1s">
        Refreshing system profile...
    </div>
</div>
<script>
    setTimeout(function() {
        window.location.href = 'profile.php';
    }, 2000);
</script>
<?php endif; ?>

<div class="container d-flex justify-content-center align-items-center">
    <div class="card admin-matrix-card w-100 p-4 p-md-5 animate__animated animate__fadeIn" style="max-width: 600px;">
        
        <div class="text-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
            <div class="mb-2">
                <i class="bi bi-shield-lock-fill radar-icon"></i>
            </div>
            <h3 class="matrix-title m-0">SECURE ADMIN NODE</h3>
            <p class="text-secondary small mb-0 font-monospace" style="font-size: 13px;">ACCESS GRANTED // MANAGEMENT SECURITY ACTIVE</p>
        </div>

        <div class="d-flex flex-column gap-3 mb-4">
            <div class="metric-field">
                <div class="row align-items-center">
                    <div class="col-4"><span class="label-term"><i class="bi bi-terminal me-2 text-info"></i>Identity</span></div>
                    <div class="col-8 text-end"><span class="value-string text-info"><?= htmlspecialchars($_SESSION['admin'] ?? 'UNDEFINED'); ?></span></div>
                </div>
            </div>

            <div class="metric-field">
                <div class="row align-items-center">
                    <div class="col-4"><span class="label-term"><i class="bi bi-sliders me-2 text-warning"></i>Clearance</span></div>
                    <div class="col-8 text-end"><span class="badge badge-cyber-admin rounded-pill"><i class="bi bi-cpu-fill me-1"></i> Administrator</span></div>
                </div>
            </div>

            <div class="metric-field" style="border-color: rgba(0, 254, 155, 0.25);">
                <div class="row align-items-center">
                    <div class="col-5"><span class="label-term text-success"><i class="bi bi-database-fill-check me-2"></i>Total Nodes</span></div>
                    <div class="col-7 text-end">
                        <span class="value-string text-success font-monospace" style="text-shadow: 0 0 10px rgba(0, 254, 155, 0.5);">
                            <?= $total_students_registered; ?> Students Locked
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="label-term text-info mb-2"><i class="bi bi-funnel-fill"></i> Matrix Target Selector</h6>
        <form method="GET" action="" class="row g-2 mb-4">
            <div class="col-6">
                <select name="purge_branch" class="form-select glowing-select-node" onchange="this.form.submit()">
                    <option value="">-- All Branches --</option>
                    <option value="Computer Science" <?= $filter_branch == 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Information Technology" <?= $filter_branch == 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Mechanical" <?= $filter_branch == 'Mechanical' ? 'selected' : ''; ?>>Mechanical</option>
                    <option value="Civil" <?= $filter_branch == 'Civil' ? 'selected' : ''; ?>>Civil</option>
                    <option value="Electrical" <?= $filter_branch == 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                    <option value="Electronics" <?= $filter_branch == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                </select>
            </div>
            <div class="col-6">
                <select name="purge_sem" class="form-select glowing-select-node" onchange="this.form.submit()">
                    <option value="">-- All Semesters --</option>
                    <?php for($i=1; $i<=6; $i++): $s_val = "Sem $i"; ?>
                        <option value="<?= $s_val; ?>" <?= $filter_sem == $s_val ? 'selected' : ''; ?>><?= $s_val; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>

        <div class="purge-console-box">
            <h6 class="label-term text-danger mb-3"><i class="bi bi-cone-striped"></i> Danger Zone / Database Liquidation</h6>
            
            <div class="d-flex flex-column gap-2">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete data for this branch and semester?')">
                    <input type="hidden" name="purge_type" value="filtered">
                    <button type="submit" name="execute_purge" class="btn btn-matrix-purge w-100" <?= ($filtered_students_count == 0 || (empty($filter_branch) && empty($filter_sem))) ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : ''; ?>>
                        <i class="bi bi-trash-fill me-1"></i> Purge Selected Matrix (<?= $filtered_students_count; ?> Matches)
                    </button>
                </form>

                <form method="POST" action="" onsubmit="return confirm('💣 DANGER: This will delete ALL student records from the database. Proceed?')">
                    <input type="hidden" name="purge_type" value="all">
                    <button type="submit" name="execute_purge" class="btn btn-matrix-purge w-100" style="background: rgba(255,0,85,0.25); font-weight:900;" <?= ($total_students_registered == 0) ? 'disabled' : ''; ?>>
                        <i class="bi bi-shield-fire me-1"></i> Wipe Central Database (Wipe All Data)
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-4 pt-3 text-center border-top border-secondary border-opacity-10">
            <a href="dashboard.php" class="btn w-100 py-2 fw-bold text-dark font-monospace" style="background: #00f2fe; border: none; font-family:'Orbitron', sans-serif; font-size: 13px; box-shadow: 0 0 15px rgba(0,242,254,0.4); border-radius: 10px;">
                <i class="bi bi-speedometer2 me-1"></i> RETURN TO DASHBOARD
            </a>
        </div>

    </div>
</div>

</body>
</html>

<?php include '../includes/footer.php'; ?>