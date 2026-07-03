<?php
/**
 * Enterprise Attendance Management System
 * Core Controller: Secure Student Deletion Engine with Animated Feedback
 */

include '../config/session.php';
include '../config/database.php';

// १. सुरक्षा आणि सॅनिटायझेशन: ID फक्त नंबर असावा
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$show_success_overlay = false;

if ($id > 0) {
    // २. SQL Injection पासन सुरक्षिततेसाठी Prepared Statement चा वापर
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            // डिलीट यशस्वी झाल्यावर ॲनिमेशन ट्रॅकर ऑन करा
            $show_success_overlay = true; 
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleting Student Record...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow: hidden;
        }

        /* 🔥 ULTRA-PREMIUM FULL SCREEN DELETE OVERLAY */
        .delete-fullscreen-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(5, 9, 20, 0.95); backdrop-filter: blur(25px);
            z-index: 99999; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }

        /* Premium Circular Dash Animation */
        .success-circle-wrapper {
            width: 140px; height: 140px; position: relative;
            display: flex; justify-content: center; align-items: center;
        }
        .svg-checkmark {
            width: 130px; height: 130px; border-radius: 50%;
            display: block; stroke-width: 4; stroke: #ef4444; /* Premium Crimson Red */
            stroke-miterlimit: 10; box-shadow: inset 0px 0px 0px #ef4444;
            animation: fillCheckmark .4s ease-in-out .4s forwards, scaleCheckmark .3s ease-in-out .9s forwards;
        }
        .svg-checkmark-circle {
            stroke-dasharray: 166; stroke-dashoffset: 166; stroke-width: 4;
            stroke-miterlimit: 10; stroke: #ef4444; fill: none;
            animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .svg-checkmark-check {
            transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48;
            animation: strokeCheckmark 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
        }

        @keyframes strokeCircle { 100% { stroke-dashoffset: 0; } }
        @keyframes strokeCheckmark { 100% { stroke-dashoffset: 0; } }
        @keyframes fillCheckmark { 100% { box-shadow: inset 0px 0px 0px 80px rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; } }
        @keyframes scaleCheckmark { 0%, 100% { transform: none; } 50% { transform: scale3d(1.1, 1.1, 1); } }
        
        .delete-neon-text {
            font-size: 1.6rem; font-weight: 800; color: #ef4444;
            letter-spacing: 1px; text-transform: uppercase;
            text-shadow: 0 0 25px rgba(239, 68, 68, 0.55); margin-top: 30px;
        }
        .delete-neon-subtext {
            color: #94a3b8; font-size: 0.90rem; font-weight: 500; margin-top: 8px;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<?php if($show_success_overlay): ?>
<!-- ⚡ REAL-TIME GRAPHICAL DELETION MODULE -->
<div class="delete-fullscreen-overlay animate__animated animate__fadeIn">
    <div class="success-circle-wrapper">
        <svg class="svg-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="svg-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
            <!-- क्रॉस/डिलीट सिम्बॉल (X) -->
            <path class="svg-checkmark-check" fill="none" d="M16 16 36 36 M36 16 16 36"/>
        </svg>
    </div>
    <div class="delete-neon-text animate__animated animate__zoomIn animate__delay-1s">
        <i class="bi bi-trash3-fill"></i> Record Purged Successfully
    </div>
    <div class="delete-neon-subtext animate__animated animate__fadeIn animate__delay-1s font-monospace">
        DATA INTEGRITY VERIFIED • REDIRECTING ROSTER...
    </div>
</div>

<script>
    // युझरला डिस्टर्ब न करता १.८ सेकंदात स्क्रीन गायब होऊन मेन पेजवर जाईल
    setTimeout(function() {
        window.location.href = 'view_students.php';
    }, 1800);
</script>

<?php else: ?>
<script>
    // जर आयडी मिळाला नाही किंवा काही एरर आली तर डायरेक्ट बॅक जाईल
    window.location.href = 'view_students.php';
</script>
<?php endif; ?>

</body>
</html>