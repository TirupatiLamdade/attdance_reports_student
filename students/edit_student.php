<?php
/**
 * Attendance ERP Pro - High-End Student Modifier Form
 * Security: Fully Protected with SQL Prepared Statements
 * UX: Premium Animated Green Success Feedback with Auto-Redirect
 */
include '../config/session.php';
include '../config/database.php';

// १. सुरक्षा: ID फक्त संख्या असावी (SQL Injection पासून १००% बचाव)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$show_success_overlay = false;

// Student चा डेटा डेटाबेसमधून सुरक्षितपणे आणणे
if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// POST UPDATE ACTION HANDLER
if (isset($_POST['update']) && $id > 0) {
    $name    = $_POST['name'];
    $gender  = $_POST['gender'];
    $email   = $_POST['email'];
    $contact = $_POST['contact'];
    $branch  = $_POST['branch'] ?? '';
    $sem     = $_POST['sem'] ?? '';

    // SQL Prepared Statement चा वापर करून डेटा अपडेट करणे
    $sql = "UPDATE students SET name=?, branch=?, sem=?, gender=?, email=?, contact=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssi", $name, $branch, $sem, $gender, $email, $contact, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $show_success_overlay = true; // अपडेट यशस्वी झाल्यावर ॲनिमेशन दाखवा
        } else {
            // Fallback: जर branch किंवा sem कॉलम टेबलात नसतील तर जुन्या पद्धतीने ट्राय करा
            $fallback_sql = "UPDATE students SET name=?, gender=?, email=?, contact=? WHERE id=?";
            $fallback_stmt = mysqli_prepare($conn, $fallback_sql);
            if ($fallback_stmt) {
                mysqli_stmt_bind_param($fallback_stmt, "ssssi", $name, $gender, $email, $contact, $id);
                if (mysqli_stmt_execute($fallback_stmt)) {
                    $show_success_overlay = true;
                }
                mysqli_stmt_close($fallback_stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- प्रिमियम गुगल फॉन्ट आणि ॲनिमेशन लायब्ररी -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<style>
    /* 全 GLOBAL COSMETIC RESETS FOR PROFESSIONAL LOOK */
    .form-main-viewport {
        background: radial-gradient(circle at 10% 20%, #0f172a 0%, #1e293b 90%) !important;
        font-family: 'Plus Jakarta Sans', sans-serif;
        min-height: calc(100vh - 70px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 50px 20px;
    }

    /* MODERN GLASSMORPHISM CARD DESIGN */
    .vertical-form-card {
        background: rgba(30, 41, 59, 0.7) !important;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        width: 100%;
        max-width: 500px;
        padding: 40px;
        transition: transform 0.3s ease;
    }

    .form-title {
        color: #ffffff !important;
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
    }

    .form-subtitle {
        color: #94a3b8 !important;
        font-size: 0.88rem;
    }

    .vertical-form-stack {
        display: flex;
        flex-direction: column;
        gap: 20px; 
        width: 100%;
    }

    .form-item-label {
        color: #cbd5e1 !important;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* PREMIUM DARK MODE INPUT FIELDS */
    .custom-input-node {
        color: #ffffff !important;
        font-weight: 500;
        font-size: 0.95rem;
        padding: 12px 16px;
        background: rgba(15, 23, 42, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 12px !important;
        transition: all 0.2s ease;
    }
    
    .custom-input-node:focus {
        background: #0f172a !important;
        border-color: #38bdf8 !important; /* Premium Cyan Focus Line */
        box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15) !important;
    }

    /* INPUT TYPE SELECT BACKGROUND CORRECTION */
    select.custom-input-node option {
        background-color: #1e293b;
        color: #fff;
    }

    .static-id-box {
        font-family: 'JetBrains Mono', monospace, sans-serif;
        color: #38bdf8 !important;
        font-weight: 700;
        background: rgba(56, 189, 248, 0.1);
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px dashed rgba(56, 189, 248, 0.3);
    }

    /* PREMIUM GLOWING INTERACTIVE BUTTON */
    .action-update-btn {
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        color: white !important;
        font-weight: 700;
        font-size: 1rem;
        padding: 14px;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .action-update-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.45);
        background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
    }

    /* ⚡ ULTRA-PREMIUM SUCCESS OVERLAY GRAPHICS (1.5 SECONDS SHOWCASE) */
    .update-fullscreen-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(7, 11, 22, 0.96); backdrop-filter: blur(20px);
        z-index: 99999; display: flex; flex-direction: column;
        justify-content: center; align-items: center;
    }

    .success-icon-wrapper {
        width: 120px; height: 120px; position: relative;
        display: flex; justify-content: center; align-items: center;
    }

    .svg-checkmark {
        width: 100px; height: 100px; border-radius: 50%;
        display: block; stroke-width: 4; stroke: #10b981; /* Premium Emerald Green */
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
    @keyframes fillCheckmark { 100% { box-shadow: inset 0px 0px 0px 80px rgba(16, 185, 129, 0.15); } }
    @keyframes scaleCheckmark { 0%, 100% { transform: none; } 50% { transform: scale3d(1.1, 1.1, 1); } }
    
    .update-neon-text {
        font-size: 1.7rem; font-weight: 800; color: #10b981;
        letter-spacing: 0.5px; text-shadow: 0 0 30px rgba(16, 185, 129, 0.4);
        margin-top: 30px;
    }

    .update-neon-subtext {
        color: #64748b; font-size: 0.85rem; font-weight: 600; margin-top: 10px;
        letter-spacing: 1px;
    }
</style>

<div class="form-main-viewport">

    <!-- डेटा यशस्वीरित्या सेव्ह झाल्यावर हा भाग एक्झिक्युट होईल (1.5 Sec Auto Timeout) -->
    <?php if($show_success_overlay): ?>
        <div class="update-fullscreen-overlay animate__animated animate__fadeIn">
            <div class="success-icon-wrapper">
                <svg class="svg-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="svg-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="svg-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <div class="update-neon-text animate__animated animate__zoomIn animate__delay-1s">
                Changes Saved Successfully
            </div>
            <div class="update-neon-subtext animate__animated animate__fadeIn animate__delay-1s font-monospace">
                SYNCHRONIZING DATABASE • REDIRECTING IN 1.5s
            </div>
        </div>

        <script>
            // बरोबर १५०० मिलिसेकंदांनी (१.५ सेकंद) ऑटोमॅटिकली मुख्य स्क्रीनवर जाईल
            setTimeout(function() {
                window.location.href = 'view_students.php';
            }, 1500);
        </script>
    <?php endif; ?>


    <!-- मुख्य एडिट फॉर्म रचना -->
    <?php if ($row) { ?>
        <div class="vertical-form-card animate__animated animate__fadeInUp">
            
            <div class="text-center mb-4">
                <h4 class="form-title m-0"><i class="bi bi-pencil-square text-info me-2"></i>Modify Student Profile</h4>
                <p class="form-subtitle mt-2 mb-0">Update credentials for real-time attendance accuracy</p>
            </div>

            <form method="POST" class="vertical-form-stack">
                
                <div>
                    <label class="form-item-label"><i class="bi bi-fingerprint text-info"></i> Student ID (Immutable)</label>
                    <div class="static-id-box"><?= htmlspecialchars($row['student_id'] ?? $row['id']); ?></div>
                </div>

                <div>
                    <label class="form-item-label"><i class="bi bi-person-fill text-muted"></i> Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($row['name']); ?>" class="form-control custom-input-node" required autocomplete="off">
                </div>

                <div>
                    <label class="form-item-label"><i class="bi bi-diagram-3-fill text-muted"></i> Academic Branch</label>
                    <input type="text" name="branch" value="<?= htmlspecialchars($row['branch'] ?? ''); ?>" class="form-control custom-input-node" placeholder="e.g. Computer Engineering">
                </div>

                <div>
                    <label class="form-item-label"><i class="bi bi-calendar3 text-muted"></i> Current Semester</label>
                    <input type="text" name="sem" value="<?= htmlspecialchars($row['sem'] ?? ''); ?>" class="form-control custom-input-node" placeholder="e.g. 6th Sem">
                </div>

                <div>
                    <label class="form-item-label"><i class="bi bi-gender-ambiguous text-muted"></i> Gender</label>
                    <select name="gender" class="form-select custom-input-node" required>
                        <option value="Male" <?php if($row['gender'] == "Male") echo "selected"; ?>>Male</option>
                        <option value="Female" <?php if($row['gender'] == "Female") echo "selected"; ?>>Female</option>
                    </select>
                </div>

                <div>
                    <label class="form-item-label"><i class="bi bi-envelope-fill text-muted"></i> Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($row['email']); ?>" class="form-control custom-input-node" required>
                </div>

                <div>
                    <label class="form-item-label"><i class="bi bi-telephone-fill text-muted"></i> Contact Number</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($row['contact']); ?>" class="form-control custom-input-node" required>
                </div>

                <div class="mt-3">
                    <button type="submit" name="update" class="btn action-update-btn w-100">
                        <i class="bi bi-check2-circle me-2"></i>Save Changes & Commit
                    </button>
                </div>

            </form>
        </div>
    <?php } else if(!$show_success_overlay) { ?>
        <div class="vertical-form-card text-center text-danger fw-bold bg-dark">
            <i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2 text-warning"></i> 
            Student record parameter missing or invalid.
        </div>
    <?php } ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/footer.php'; ?>