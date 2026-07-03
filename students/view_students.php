<?php
/**
 * Attendance ERP Pro - Ultimate Glowing Cyber Dark Build
 * Architecture: Database column 'sem' mapped correctly for students table, Multi-Parameter Search (Semester Filter Removed)
 */
include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

// Filter Nodes Sanitization
$search_query   = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$branch_filter  = isset($_GET['branch']) ? mysqli_real_escape_string($conn, trim($_GET['branch'])) : '';

$where_clause = " WHERE 1=1 ";

if (!empty($search_query)) {
    $where_clause .= " AND (student_id LIKE '%$search_query%' OR name LIKE '%$search_query%' OR contact LIKE '%$search_query%') ";
}
if (!empty($branch_filter)) {
    $where_clause .= " AND branch = '$branch_filter' ";
}

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM students $where_clause");
$total_students = mysqli_fetch_assoc($count_query)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Registry Management Matrix | ERP Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Rajdhani:wght@500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* 🌌 TRUE BLACK OBSIDIAN BACKDROP */
        body {
            background-color: #02040a !important;
            background-image: radial-gradient(circle at 50% 0%, #071126 0%, #010205 100%) !important;
            min-height: 100vh;
            color: #e2e8f0;
            font-family: 'Rajdhani', sans-serif;
            font-size: 16px;
            letter-spacing: 0.5px;
            padding: 30px 20px;
        }

        /* ✨ EXTREME NEON GLOW TEXT CONFIGURATIONS */
        .glow-title {
            font-family: 'Orbitron', sans-serif;
            color: #ffffff;
            text-shadow: 0 0 15px #00f2fe, 0 0 30px rgba(0, 242, 254, 0.5);
        }
        .glow-text-cyan {
            color: #00f2fe !important;
            text-shadow: 0 0 12px #00f2fe, 0 0 20px rgba(0, 242, 254, 0.4);
        }
        .glow-text-green {
            color: #00fe9b !important;
            text-shadow: 0 0 12px #00fe9b, 0 0 20px rgba(0, 254, 155, 0.4);
        }
        .glow-text-amber {
            color: #ffaa00 !important;
            text-shadow: 0 0 12px #ffaa00, 0 0 20px rgba(255, 170, 0, 0.4);
        }

        .header-panel {
            background: rgba(4, 8, 20, 0.95) !important;
            backdrop-filter: blur(20px);
            border: 1px solid #00f2fe !important;
            border-radius: 16px;
            box-shadow: 0 0 30px rgba(0, 242, 254, 0.2);
        }

        /* 🔎 FILTER COMPONENT */
        .filter-box-card {
            background: #040712 !important;
            border: 1px solid rgba(0, 242, 254, 0.2) !important;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
        }
        .search-input-sm, .select-input-sm {
            background: #010206 !important;
            border: 1px solid rgba(0, 242, 254, 0.3) !important;
            color: #ffffff !important;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 10px 14px;
            border-radius: 8px !important;
            transition: all 0.3s;
        }
        .search-input-sm:focus, .select-input-sm:focus {
            border-color: #00f2fe !important;
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.6) !important;
        }
        .form-select option {
            background-color: #02040a !important;
            color: #ffffff !important;
        }
        
        /* 🔘 PREMIUM ACTION BUTTONS */
        .btn-neon-cyan {
            background: linear-gradient(135deg, #00f2fe, #0072ff) !important;
            color: black !important; font-family: 'Orbitron', sans-serif; font-weight: 700; font-size: 0.85rem; border: none;
            padding: 11px 22px; border-radius: 8px; transition: all 0.3s;
            box-shadow: 0 0 15px #00f2fe;
        }
        .btn-neon-cyan:hover { transform: translateY(-2px); box-shadow: 0 0 25px #00f2fe; }

        .btn-neon-kill {
            background: rgba(255, 0, 85, 0.1) !important; border: 1px solid rgba(255, 0, 85, 0.4) !important;
            color: #ff0055 !important; padding: 10px 14px; border-radius: 8px; text-decoration: none; display: inline-flex;
            transition: all 0.3s;
        }
        .btn-neon-kill:hover { background: #ff0055 !important; color: white !important; box-shadow: 0 0 15px #ff0055; }

        .btn-global-action {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 10px 18px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .btn-global-action:disabled {
            background: #080d1a !important;
            border-color: rgba(255,255,255,0.02) !important;
            color: #2a354d !important;
            box-shadow: none !important;
            cursor: not-allowed;
        }

        /* 🟦 PURE JET-DARK TABLE ARCHITECTURE */
        .premium-card {
            background: #030612 !important;
            border: 1px solid rgba(0, 242, 254, 0.15) !important;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.9);
        }
        .table {
            border-collapse: separate !important;
            border-spacing: 0 12px !important; 
            background: transparent !important;
        }
        .table thead th {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: #8c9cb2 !important; 
            padding: 15px 20px;
            border: none !important;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* Base Look: Pitch Dark Rows */
        .table tbody tr {
            background: #060a17 !important; 
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .table tbody tr td {
            padding: 16px 20px;
            border-top: 1px solid rgba(0, 242, 254, 0.1) !important;
            border-bottom: 1px solid rgba(0, 242, 254, 0.1) !important;
            color: #d1d5db !important; 
            font-size: 1.05rem;
            font-weight: 600; 
            background: transparent !important;
        }
        .table tbody tr td:first-child { border-left: 1px solid rgba(0, 242, 254, 0.1) !important; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        .table tbody tr td:last-child { border-right: 1px solid rgba(0, 242, 254, 0.1) !important; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

        .text-id { 
            font-family: 'JetBrains Mono', monospace; 
            color: #00f2fe !important; 
            font-weight: 700 !important; 
            text-shadow: 0 0 10px #00f2fe;
        }

        .table tbody tr:hover {
            transform: scale(1.008) translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 242, 254, 0.2) !important;
            background: #0b132b !important; 
        }

        /* 🔥 HIGH-VOLTAGE CYBER GLOW ROW SELECTION */
        .table tbody tr.active-focused-row {
            background: rgba(0, 242, 254, 0.2) !important; 
            box-shadow: 0 0 25px #00f2fe !important;
        }
        .table tbody tr.active-focused-row td {
            color: #ffffff !important;
            border-top: 1px solid #00f2fe !important;
            border-bottom: 1px solid #00f2fe !important;
        }
        .table tbody tr.active-focused-row td:first-child { border-left: 1px solid #00f2fe !important; }
        .table tbody tr.active-focused-row td:last-child { border-right: 1px solid #00f2fe !important; }

        .table-inactive-blur tr:not(.active-focused-row) {
            opacity: 0.35;
        }

        /* Photo Avatar Frame */
        .photo-container {
            width: 44px; height: 44px; border-radius: 8px; overflow: hidden;
            border: 2px solid rgba(0, 242, 254, 0.3); transition: all 0.2s;
            display: inline-block; background: #010206;
        }
        .photo-container:hover { border-color: #00fe9b; transform: scale(1.1); box-shadow: 0 0 10px #00fe9b; }
        .student-avatar { width: 100%; height: 100%; object-fit: cover; }

        /* Inline Buttons Grid */
        .sys-btn {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; font-size: 0.95rem; color: #fff; border: none; transition: transform 0.2s;
        }
        .sys-btn:hover { transform: translateY(-2px); color: #fff; filter: brightness(1.3); }
        .btn-v { background: #0072ff; box-shadow: 0 0 8px rgba(0,114,255,0.4); } 
        .btn-e { background: #ea580c; box-shadow: 0 0 8px rgba(234,88,12,0.4); } 
        .btn-w { background: #00fe9b; color: black !important; font-weight: bold; box-shadow: 0 0 8px #00fe9b; } 
        .btn-d { background: #ff0055; box-shadow: 0 0 8px rgba(255,0,85,0.4); }

        .zoom-backdrop { backdrop-filter: blur(15px); background: rgba(2, 3, 6, 0.9); }
        .zoom-card { background: #050914; border: 2px solid #00f2fe; border-radius: 20px; box-shadow: 0 0 40px #00f2fe; }
    </style>
</head>
<body onclick="clearRowFocus()">

<div class="container-fluid max-w-7xl mx-auto">

    <div class="header-panel p-4 mb-4 d-flex flex-column lg-flex-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h2 class="fw-bold m-0 glow-title"><i class="bi bi-cpu-fill text-info me-2"></i>Enterprise Student Registry</h2>
            <p class="text-secondary mb-0 small" style="font-size:14px;">Live Counter Matrix: <b class="glow-text-green"><?= $total_students; ?> Synchronized Nodes</b></p>
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <a href="add_student.php" class="btn btn-success btn-global-action px-3 py-2 text-dark shadow" style="background:#00fe9b !important; box-shadow:0 0 12px #00fe9b; border:none;">
                <i class="bi bi-person-plus-fill me-2"></i> Add Student
            </a>
            <button id="globalEditBtn" onclick="executeGlobalAction('edit')" class="btn btn-warning btn-global-action text-dark shadow" style="background:#ffaa00 !important; box-shadow:0 0 12px #ffaa00; border:none;" disabled>
                <i class="bi bi-pencil-square me-2"></i> Edit Student
            </button>
            <button id="globalShowBtn" onclick="executeGlobalAction('profile')" class="btn btn-info btn-global-action text-dark shadow" style="background:#00f2fe !important; box-shadow:0 0 12px #00f2fe; border:none;" disabled>
                <i class="bi bi-person-bounding-box me-2"></i> Show Profile
            </button>
        </div>
    </div>

    <div class="filter-box-card">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-lg-6 col-md-12">
                <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Search Parameter (ID / Name / Contact)</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-info" style="border-color: rgba(0,242,254,0.2) !important;"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control search-input-sm" placeholder="Search by Student ID, full name or mobile..." value="<?= htmlspecialchars($search_query); ?>">
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Department Branch</label>
                <select name="branch" class="form-select select-input-sm">
                    <option value="">-- All Branches --</option>
                    <option value="Computer Science" <?= $branch_filter == 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Information Technology" <?= $branch_filter == 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Mechanical" <?= $branch_filter == 'Mechanical' ? 'selected' : ''; ?>>Mechanical</option>
                    <option value="Electrical" <?= $branch_filter == 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                    <option value="Civil" <?= $branch_filter == 'Civil' ? 'selected' : ''; ?>>Civil</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-6 d-flex gap-2">
                <button class="btn btn-neon-cyan w-100" type="submit"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-neon-kill" title="Clear Filters"><i class="bi bi-x-circle-fill"></i></a>
            </div>
        </form>
    </div>

    <div class="premium-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px;">Avatar</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Branch</th>
                        <th class="text-center">Semester</th>
                        <th>Email Address</th>
                        <th>Contact No</th>
                        <th class="text-end" style="min-width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableDataBody">

                <?php
                $students = mysqli_query($conn, "SELECT * FROM students $where_clause ORDER BY id DESC");
                
                if (mysqli_num_rows($students) == 0) {
                    echo '<tr><td colspan="8" class="text-center py-5 fw-bold text-danger bg-dark rounded-3 border border-danger border-opacity-25" style="text-shadow: 0 0 10px rgba(255,0,85,0.3);"><i class="bi bi-shield-slash me-2"></i> SYSTEM NULL RESULT: No database element matches active filters.</td></tr>';
                }

                while($row = mysqli_fetch_assoc($students)) {
                    $db_id   = $row['id'];
                    $name    = htmlspecialchars($row['name']);
                    $st_id   = htmlspecialchars($row['student_id']);
                    $branch  = htmlspecialchars($row['branch'] ?? 'N/A');
                    $sem_display = htmlspecialchars($row['sem'] ?? '-');
                    $email   = htmlspecialchars($row['email']);
                    $phone   = htmlspecialchars($row['contact'] ?? '');

                    $photo_db = $row['photo'] ?? '';
                    $photo_src = "";
                    if (!empty($photo_db)) {
                        if (file_exists("../uploads/photos/" . $photo_db)) $photo_src = "../uploads/photos/" . $photo_db;
                        elseif (file_exists("uploads/photos/" . $photo_db)) $photo_src = "uploads/photos/" . $photo_db;
                    }
                    if (empty($photo_src)) {
                        $photo_src = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=00f2fe&color=000&bold=true&size=128";
                    }
                ?>

                <tr data-id="<?= $db_id; ?>" onclick="focusTargetRow(this, <?= $db_id; ?>, event)">
                    <td class="text-center">
                        <div class="photo-container" onclick="triggerZoom('<?= $photo_src; ?>', '<?= $name; ?>', event)">
                            <img src="<?= $photo_src; ?>" class="student-avatar" alt="Avatar">
                        </div>
                    </td>
                    <td><span class="text-id"><?= $st_id; ?></span></td>
                    <td class="fw-bold text-white" style="font-size:16px;"><?= $name; ?></td>
                    <td><span class="badge bg-dark text-info border border-info border-opacity-25 px-3 py-2 rounded-2"><?= $branch; ?></span></td>
                    <td class="text-center fw-bold glow-text-amber" style="font-size:16px;">Semester <?= $sem_display; ?></td>
                    <td class="text-secondary font-monospace" style="font-size:14px;"><?= $email; ?></td>
                    <td class="glow-text-green font-monospace"><?= $phone ? $phone : '—'; ?></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1" onclick="event.stopPropagation();">
                            <a href="student_profile.php?id=<?= $db_id; ?>" class="sys-btn btn-v" title="View Profile"><i class="bi bi-eye-fill"></i></a>
                            <a href="edit_student.php?id=<?= $db_id; ?>" class="sys-btn btn-e" title="Edit Properties"><i class="bi bi-pencil-fill"></i></a>
                            
                            <?php if(!empty($phone)) { 
                                $message = "🎓 *Attendance Desk*\n\nHello *{$name}*,\nYour profile architecture framework has been verified successfully.\n\nDate: ".date('d-m-Y');
                                $wa_url = "https://wa.me/91".$phone."?text=".urlencode($message);
                            ?>
                                <a href="<?= $wa_url; ?>" target="_blank" class="sys-btn btn-w" title="WhatsApp Gateway"><i class="bi bi-whatsapp"></i></a>
                            <?php } ?>

                            <a href="delete_student.php?id=<?= $db_id; ?>" class="sys-btn btn-d" title="Delete Drop" onclick="return confirm('Erase <?= $name; ?> definitively?')">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade zoom-backdrop" id="photoZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content zoom-card text-white text-center p-3">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold glow-text-cyan" id="zoomTitle">Database Image Node</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img src="" id="zoomImageTarget" class="img-fluid rounded-3 border border-secondary" style="max-height: 500px; width: 100%; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let selectedStudentId = null;

function triggerZoom(imgUrl, studentName, event) {
    event.stopPropagation(); 
    document.getElementById('zoomImageTarget').src = imgUrl;
    document.getElementById('zoomTitle').innerText = studentName;
    var zoomInstance = new bootstrap.Modal(document.getElementById('photoZoomModal'));
    zoomInstance.show();
}

function focusTargetRow(rowElement, studentId, event) {
    event.stopPropagation(); 
    var tbody = document.getElementById('tableDataBody');
    var rows = tbody.getElementsByTagName('tr');
    
    for (var i = 0; i < rows.length; i++) {
        rows[i].classList.remove('active-focused-row');
    }
    
    rowElement.classList.add('active-focused-row');
    tbody.classList.add('table-inactive-blur');
    
    selectedStudentId = studentId;
    document.getElementById('globalEditBtn').disabled = false;
    document.getElementById('globalShowBtn').disabled = false;
}

function clearRowFocus() {
    var tbody = document.getElementById('tableDataBody');
    if(tbody) {
        tbody.classList.remove('table-inactive-blur');
        var rows = tbody.getElementsByTagName('tr');
        for (var i = 0; i < rows.length; i++) {
            rows[i].classList.remove('active-focused-row');
        }
    }
    selectedStudentId = null;
    document.getElementById('globalEditBtn').disabled = true;
    document.getElementById('globalShowBtn').disabled = true;
}

function executeGlobalAction(type) {
    if(!selectedStudentId) return;
    
    if(type === 'edit') {
        window.location.href = "edit_student.php?id=" + selectedStudentId;
    } else if(type === 'profile') {
        window.location.href = "student_profile.php?id=" + selectedStudentId;
    }
}
</script>

</body>
</html>
<?php include '../includes/footer.php'; ?>