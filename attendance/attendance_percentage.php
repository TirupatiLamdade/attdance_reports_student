<?php
/**
 * Ultra-Premium Executive Analytics Dashboard
 * Architecture: Cyber-Glow Tier-1 UI, Failure-Safe Engine
 */

include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

// फिल्टर इनपुट आणि सुरक्षित सॅनिटायझेशन
$branch = isset($_GET['branch']) ? mysqli_real_escape_string($conn, trim($_GET['branch'])) : '';
$sem    = isset($_GET['sem']) ? mysqli_real_escape_string($conn, trim($_GET['sem'])) : '';

$total_students = 0;
$show_circle = false;
$error_message = "";

// डेटाबेस फेचिंग लॉजिक (Fail-Safe)
if (!empty($branch) && !empty($sem)) {
    $show_circle = true;
    
    // SQL Query - Direct Structure Match
    $sql = "SELECT COUNT(*) AS total FROM students WHERE branch = ? AND sem = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $branch, $sem);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $total_students = $row['total'];
            }
        } else {
            $error_message = "डेटा सर्व्हरवरून फेच करताना त्रुटी आली. कृपया नेटवर्क तपासा.";
            $show_circle = false;
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "क्वेरी एक्झिक्युशन इंजिनमध्ये तांत्रिक अडचण आली आहे.";
        $show_circle = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Roster Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #070a12;
            --card-glass: rgba(13, 20, 35, 0.6);
            --border-glass: rgba(255, 255, 255, 0.08);
            --neon-cyan: #06b6d4;
            --neon-purple: #d946ef;
            --neon-glow-cyan: rgba(6, 182, 212, 0.8);
            --neon-glow-purple: rgba(217, 70, 239, 0.8);
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(6, 182, 212, 0.07) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(217, 70, 239, 0.08) 0%, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        /* 💎 EXECUTIVE GLASS PANEL */
        .executive-panel {
            background: var(--card-glass);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid var(--border-glass);
            border-radius: 32px;
            box-shadow: 0 50px 100px -30px rgba(0, 0, 0, 0.9), 
                        inset 0 1px 2px rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .executive-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, transparent, var(--neon-cyan), var(--neon-purple), transparent);
        }

        /* 🎛️ PREMIUM CONSOLE FILTERS */
        .premium-filter-zone {
            background: rgba(8, 12, 22, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 24px;
            padding: 24px;
            box-shadow: inset 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .filter-header-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ffffff;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .premium-select {
            background: rgba(5, 7, 12, 0.95) !important;
            border: 1px solid rgba(6, 182, 212, 0.3) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            border-radius: 14px !important;
            padding: 14px 18px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .premium-select:focus {
            border-color: var(--neon-cyan) !important;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4), inset 0 0 8px rgba(6, 182, 212, 0.2) !important;
            transform: translateY(-1px);
        }

        .premium-select option {
            background-color: #070a12 !important;
            color: #ffffff !important;
        }

        /* 🔮 DYNAMIC GIANT GLOWING ANALYTICS CORE */
        .circle-master-wrapper {
            position: relative;
            width: 350px;
            height: 350px;
            margin: 40px auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .neon-glowing-halo {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: padding-box, linear-gradient(135deg, var(--neon-cyan), #1d4ed8, var(--neon-purple));
            border: 5px solid transparent;
            box-shadow: 0 0 60px rgba(6, 182, 212, 0.3), 
                        0 0 100px rgba(217, 70, 239, 0.2),
                        inset 0 0 40px rgba(6, 182, 212, 0.2);
            animation: rhythmicPulse 5s infinite ease-in-out;
        }

        .matte-dark-core {
            width: 92%;
            height: 92%;
            background: radial-gradient(circle at 50% 50%, #0d1424 0%, #05080f 100%);
            border-radius: 50%;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.03);
            padding: 30px;
            box-shadow: inset 0 10px 40px rgba(0, 0, 0, 0.8);
        }

        .circle-main-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #38bdf8;
            font-weight: 800;
            margin-bottom: 4px;
            text-shadow: 0 0 12px rgba(56, 189, 248, 0.6);
        }

        .stat-counter-value {
            font-size: 6rem;
            font-weight: 800;
            line-height: 1;
            color: #ffffff;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.6), 
                         0 0 40px rgba(6, 182, 212, 0.4);
            letter-spacing: -3px;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .badge-pill-executive {
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid var(--neon-cyan);
            color: #22d3ee;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 100px;
            letter-spacing: 0.5px;
            max-width: 270px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.25);
            text-shadow: 0 0 8px rgba(34, 211, 238, 0.5);
        }

        .badge-pill-sub {
            background: rgba(217, 70, 239, 0.06);
            border: 1px solid var(--neon-purple);
            color: #f472b6;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 100px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            box-shadow: 0 0 15px rgba(217, 70, 239, 0.2);
            text-shadow: 0 0 8px rgba(244, 114, 182, 0.5);
        }

        .luxury-placeholder {
            border: 1px dashed rgba(6, 182, 212, 0.2);
            background: rgba(6, 182, 212, 0.01);
            border-radius: 24px;
            box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.5);
        }

        .text-glow-cyan {
            color: #22d3ee !important;
            text-shadow: 0 0 10px var(--neon-glow-cyan);
        }

        @keyframes rhythmicPulse {
            0% { transform: scale(1); box-shadow: 0 0 60px rgba(6, 182, 212, 0.3), 0 0 100px rgba(217, 70, 239, 0.2); }
            50% { transform: scale(1.02); box-shadow: 0 0 80px rgba(6, 182, 212, 0.5), 0 0 130px rgba(217, 70, 239, 0.4); }
            100% { transform: scale(1); box-shadow: 0 0 60px rgba(6, 182, 212, 0.3), 0 0 100px rgba(217, 70, 239, 0.2); }
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 95vh; padding: 20px;">
    <div class="card executive-panel p-4 p-md-5 w-100" style="max-width: 800px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
            <div>
                <h1 class="h4 fw-extrabold text-white m-0 text-uppercase tracking-wider text-glow-cyan" style="font-weight: 800;">Roster Analytics Suite</h1>
                <p class="text-muted small m-0" style="letter-spacing: 0.5px;">Cryptographic database extraction console</p>
            </div>
            <div class="p-2 rounded-3" style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.3); box-shadow: 0 0 15px rgba(6, 182, 212, 0.2);">
                <i class="bi bi-cpu text-glow-cyan fs-5"></i>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 p-3 mb-4" role="alert" style="box-shadow: 0 0 15px rgba(239, 68, 68, 0.15);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-terminal-x fs-5"></i>
                    <strong>Core System Alert:</strong> <?php echo $error_message; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="GET" action="" class="premium-filter-zone row g-4 align-items-center justify-content-center">
            <div class="col-md-6">
                <div class="filter-header-label">
                    <i class="bi bi-hdd-stack-fill text-glow-cyan"></i> Target Core Department
                </div>
                <select name="branch" class="form-select premium-select" onchange="this.form.submit()" required>
                    <option value="">Select Cluster Department...</option>
                    <option value="Computer Science" <?php if($branch == 'Computer Science') echo 'selected'; ?>>Computer Science</option>
                    <option value="Information Technology" <?php if($branch == 'Information Technology') echo 'selected'; ?>>Information Technology</option>
                    <option value="Electronics" <?php if($branch == 'Electronics') echo 'selected'; ?>>Electronics</option>
                    <option value="Mechanical" <?php if($branch == 'Mechanical') echo 'selected'; ?>>Mechanical</option>
                    <option value="Civil" <?php if($branch == 'Civil') echo 'selected'; ?>>Civil</option>
                    <option value="Electrical" <?php if($branch == 'Electrical') echo 'selected'; ?>>Electrical</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <div class="filter-header-label">
                    <i class="bi bi-hr text-glow-cyan"></i> Target Timeline Node
                </div>
                <select name="sem" class="form-select premium-select" onchange="this.form.submit()" required>
                    <option value="">Select Target Semester Node...</option>
                    <?php for($i=1; $i<=6; $i++) { $sem_val = "Sem $i"; ?>
                        <option value="<?php echo $sem_val; ?>" <?php if($sem == $sem_val) echo 'selected'; ?>><?php echo $sem_val; ?></option>
                    <?php } ?>
                </select>
            </div>
        </form>

        <?php if($show_circle): ?>
            <div class="mt-4 text-center animate__animated animate__zoomIn animate__faster">
                <div class="circle-master-wrapper">
                    <div class="neon-glowing-halo"></div>
                    <div class="matte-dark-core">
                        
                        <span class="circle-main-label">Total Students</span>
                        <span class="stat-counter-value"><?php echo $total_students; ?></span>
                        
                        <div class="badge-pill-executive mt-4">
                            <i class="bi bi-shield-check me-1"></i> <?php echo htmlspecialchars($branch); ?>
                        </div>
                        <div class="badge-pill-sub mt-2">
                            <i class="bi bi-git me-1"></i> <?php echo htmlspecialchars($sem); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif(empty($error_message)): ?>
            <div class="luxury-placeholder text-center mt-5 p-5 text-secondary animate__animated animate__fadeIn">
                <div class="mb-3">
                    <i class="bi bi-radar text-muted display-5 opacity-25 animate__animated animate__pulse animate__infinite"></i>
                </div>
                <h5 class="text-white opacity-75 fw-semibold small text-uppercase tracking-widest" style="letter-spacing: 2px;">Console Ready For Initialisation</h5>
                <p class="small text-muted mb-0 mx-auto" style="max-width: 380px;">Please feed valid branch parameters and academic timelines into the control node above to project live student aggregates.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
<?php include '../includes/footer.php'; ?>