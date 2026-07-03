<?php
/**
 * Attendance ERP Pro - Integrated Vertical Student Profile
 * Architecture: Wrapped between Header/Navbar and Footer, Center Aligned Viewport
 */
include '../config/session.php';
include '../config/database.php';

// Sanitize Incoming ID
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Fetch Student Element with verified 'sem' mapped column
$result = mysqli_query($conn, "SELECT * FROM students WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

// GLOBAL STRUCTURE INCLUDES
include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    .profile-main-viewport {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        min-height: calc(100vh - 140px); /* Adjusts dynamically between nav and footer */
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 15px;
    }

    /* 🟦 HIGH-END VERTICAL UNIFIED DIM WHITE PANEL */
    .vertical-profile-card {
        background: #f8fafc !important; /* Premium Dim White Background */
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.05);
        width: 100%;
        max-width: 460px; /* Calibrated compact corporate layout */
        padding: 35px 30px;
    }

    /* Executive Avatar Layout */
    .avatar-holder {
        width: 110px;
        height: 110px;
        border-radius: 50%; /* Rounded circular design for executive feel */
        overflow: hidden;
        border: 4px solid #ffffff;
        margin: 0 auto 20px auto; 
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
        background: #ffffff;
        transition: transform 0.3s ease;
    }
    .avatar-holder:hover {
        transform: scale(1.04);
    }
    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Ultra Bold Standout Student ID Field */
    .text-id-glow {
        font-family: 'Inter', sans-serif;
        color: #0284c7 !important; /* Custom Electric Corporate Blue */
        font-weight: 900 !important; /* Maximum Boldness Signature */
        font-size: 1.2rem;
        letter-spacing: 0.03em;
        background: #e0f2fe; /* Light blue tint box for premium contrast */
        padding: 6px 16px;
        border-radius: 8px;
        display: inline-block;
        border: 1px solid #bae6fd;
    }

    /* PURE VERTICAL FIELD STACKING */
    .vertical-stack {
        display: flex;
        flex-direction: column;
        gap: 14px; 
        width: 100%;
    }

    .item-label {
        color: #64748b !important; /* Professional Muted Slate Gray */
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .item-value {
        color: #0f172a !important; /* High Contrast Deep Black */
        font-weight: 600;
        font-size: 0.95rem;
        padding: 11px 14px;
        background: #f1f5f9; /* Soft corporate grey values divider */
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        word-break: break-word;
    }
</style>

<div class="profile-main-viewport">

    <?php if ($row) { 
        $name = htmlspecialchars($row['name']);
        $photo_src = !empty($row['photo']) ? "../uploads/photos/".$row['photo'] : "https://ui-avatars.com/api/?name=".urlencode($name)."&background=0284c7&color=fff&bold=true&size=110";
    ?>

        <div class="vertical-profile-card">
            
            <div class="avatar-holder text-center">
                <img src="<?= $photo_src; ?>" class="avatar-img" alt="Student Specimen">
            </div>

            <div class="vertical-stack">
                
                <div class="text-center mb-3">
                    <div class="item-label justify-content-center">Identification Token</div>
                    <div class="text-id-glow"><?= htmlspecialchars($row['student_id']); ?></div>
                </div>

                <div>
                    <div class="item-label"><i class="bi bi-person-fill text-secondary"></i> Full Name</div>
                    <div class="item-value" style="font-size: 1.05rem; font-weight: 700; color: #0f172a !important;"><?= $name; ?></div>
                </div>

                <div>
                    <div class="item-label"><i class="bi bi-diagram-3-fill text-secondary"></i> Academic Branch</div>
                    <div class="item-value"><?= htmlspecialchars($row['branch'] ?? '—'); ?></div>
                </div>

                <div>
                    <div class="item-label"><i class="bi bi-calendar3 text-secondary"></i> Current Semester</div>
                    <div class="item-value" style="color: #0284c7 !important; font-weight: 700;"><?= htmlspecialchars($row['sem'] ?? '—'); ?></div>
                </div>

                <div>
                    <div class="item-label"><i class="bi bi-gender-ambiguous text-secondary"></i> Gender</div>
                    <div class="item-value"><?= htmlspecialchars($row['gender'] ?? '—'); ?></div>
                </div>

                <div>
                    <div class="item-label"><i class="bi bi-envelope-fill text-secondary"></i> Email Address</div>
                    <div class="item-value" style="font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($row['email'] ?? '—'); ?></div>
                </div>

                <div>
                    <div class="item-label"><i class="bi bi-telephone-fill text-secondary"></i> Contact Number</div>
                    <div class="item-value" style="color: #16a34a !important; font-weight: 700;"><?= htmlspecialchars($row['contact'] ?? '—'); ?></div>
                </div>

            </div>
        </div>

    <?php } else { ?>
        <div class="vertical-profile-card text-center text-danger fw-bold bg-white">
            <i class="bi bi-shield-slash fs-4 d-block mb-2"></i> No active record associated with this identifier.
        </div>
    <?php } ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php 
// FOOTER ENCLOSURE INJECTION
include '../includes/footer.php'; 
?>