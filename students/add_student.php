<?php
include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = mysqli_real_escape_string($conn, trim($_POST['student_id']));
    $name       = mysqli_real_escape_string($conn, trim($_POST['name']));
    $gender     = mysqli_real_escape_string($conn, $_POST['gender']);
    $contact    = mysqli_real_escape_string($conn, trim($_POST['contact']));
    $branch     = mysqli_real_escape_string($conn, $_POST['branch']);
    $sem        = mysqli_real_escape_string($conn, $_POST['sem']);
    
    // 📧 EMAIL SANITIZATION: Remove spaces & convert capital letters to lowercase
    $raw_email  = trim($_POST['email']);
    $clean_email = str_replace(' ', '', $raw_email); // मधील सर्व स्पेस काढून टाकणे
    $clean_email = strtolower($clean_email);        // सर्व कॅपिटल अक्षरे स्मॉल करणे
    $email       = mysqli_real_escape_string($conn, $clean_email);
    
    $photo_name = "default.png";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_extensions)) {
            $photo_name = "IMG_" . $student_id . "_" . time() . "." . $ext;
            $target_dir = "../uploads/photos/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $photo_name);
        }
    }

    // 🔍 DUPLICATE VALIDATION MATRIX
    $check_id = mysqli_query($conn, "SELECT id FROM students WHERE student_id = '$student_id'");
    $check_email = mysqli_query($conn, "SELECT id FROM students WHERE email = '$email'");
    
    if (mysqli_num_rows($check_id) > 0) {
        $msg = "
        <div class='alert-premium alert-premium-danger animate__animated animate__zoomIn'>
            <div class='d-flex align-items-center gap-3'>
                <i class='bi bi-exclamation-octagon-fill fs-3 text-danger' style='filter: drop-shadow(0 0 8px #ef4444);'></i>
                <div>
                    <h6 class='mb-0 fw-bold text-white' style='font-family: \"Plus Jakarta Sans\", sans-serif;'>Duplicate Registry Detected</h6>
                    <small style='color: #fca5a5;'>Student ID #{$student_id} is already registered in the log matrix.</small>
                </div>
            </div>
        </div>";
    } elseif (mysqli_num_rows($check_email) > 0) {
        // ❌ DUPLICATE EMAIL BLOCK ERROR
        $msg = "
        <div class='alert-premium alert-premium-danger animate__animated animate__zoomIn'>
            <div class='d-flex align-items-center gap-3'>
                <i class='bi bi-envelope-exclamation-fill fs-3 text-danger' style='filter: drop-shadow(0 0 8px #ef4444);'></i>
                <div>
                    <h6 class='mb-0 fw-bold text-white' style='font-family: \"Plus Jakarta Sans\", sans-serif;'>Duplicate Email Forbidden</h6>
                    <small style='color: #fca5a5;'>The email vector <b>{$email}</b> already exists in our central directory.</small>
                </div>
            </div>
        </div>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO students (student_id, name, gender, email, contact, branch, sem, photo) 
            VALUES ('$student_id', '$name', '$gender', '$email', '$contact', '$branch', '$sem', '$photo_name')");
        
        if ($insert) {
            $msg = "
            <div class='alert-premium alert-premium-success animate__animated animate__zoomIn'>
                <div class='d-flex align-items-center gap-3'>
                    <i class='bi bi-patch-check-fill fs-3 text-success' style='filter: drop-shadow(0 0 8px #10b981);'></i>
                    <div>
                        <h6 class='mb-0 fw-bold text-white' style='font-family: \"Plus Jakarta Sans\", sans-serif;'>Onboarding Complete</h6>
                        <small style='color: #86efac;'>Profile for <b>{$name}</b> established successfully.</small>
                    </div>
                </div>
            </div>";
        } else {
            $msg = "
            <div class='alert-premium alert-premium-danger animate__animated animate__zoomIn'>
                <div class='d-flex align-items-center gap-3'>
                    <i class='bi bi-x-circle-fill fs-3 text-danger' style='filter: drop-shadow(0 0 8px #ef4444);'></i>
                    <div>
                        <h6 class='mb-0 fw-bold text-white' style='font-family: \"Plus Jakarta Sans\", sans-serif;'>Database Exception</h6>
                        <small style='color: #fca5a5;'>Error: " . mysqli_error($conn) . "</small>
                    </div>
                </div>
            </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System | Registry Core</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Plus+Jakarta+Sans:wght@600;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
            background: 
                radial-gradient(circle at 20% 20%, rgba(14, 165, 233, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(249, 115, 22, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.08) 0%, transparent 60%),
                linear-gradient(135deg, #040814 0%, #080d1a 50%, #02050c 100%);
            background-attachment: fixed;
        }

        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: 
                linear-gradient(rgba(14, 165, 233, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14, 165, 233, 0.04) 1px, transparent 1px);
            background-size: 30px 30px; pointer-events: none; z-index: 0;
        }

        body::after {
            content: ""; position: fixed; top: 0; left: 25%; width: 50%; height: 3px;
            background: linear-gradient(90deg, transparent, #0ea5e9, #f97316, transparent);
            box-shadow: 0 0 25px rgba(14, 165, 233, 0.8); z-index: 10;
        }
        
        .premium-card {
            background: rgba(5, 9, 20, 0.78); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 2px solid rgba(14, 165, 233, 0.35); border-radius: 32px;
            box-shadow: 
                0 30px 80px rgba(0, 0, 0, 0.8), 
                0 0 50px rgba(14, 165, 233, 0.1),
                inset 0 0 30px rgba(255, 255, 255, 0.02);
            position: relative; z-index: 1;
        }

        .system-title {
            font-family: 'Orbitron', sans-serif; font-weight: 900; letter-spacing: 2px;
            background: linear-gradient(135deg, #ffffff 30%, #38bdf8 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(56, 189, 248, 0.5);
        }

        .form-label-premium {
            font-family: 'Orbitron', sans-serif; color: #e2e8f0; font-size: 0.85rem; font-weight: 900; 
            letter-spacing: 2px; margin-bottom: 10px; display: block; text-shadow: 0 0 10px rgba(56, 189, 248, 0.4); 
        }

        .input-group-custom {
            position: relative; background: rgba(2, 4, 8, 0.95); border: 2.5px solid #0ea5e9; border-radius: 16px;
            box-shadow: 0 0 22px rgba(14, 165, 233, 0.45), inset 0 0 10px rgba(14, 165, 233, 0.15); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center;
        }

        .input-group-custom:focus-within {
            border-color: #f97316; box-shadow: 0 0 32px rgba(249, 115, 22, 0.85), inset 0 0 15px rgba(249, 115, 22, 0.3);
            background: #010204; transform: scale(1.02) translateY(-2px);
        }

        .input-icon {
            padding-left: 18px; color: #38bdf8; font-size: 1.35rem; display: flex; align-items: center;
            transition: all 0.3s ease; filter: drop-shadow(0 0 8px rgba(56, 189, 248, 0.6));
        }

        .input-group-custom:focus-within .input-icon {
            color: #f97316; filter: drop-shadow(0 0 12px rgba(249, 115, 22, 0.9));
        }

        .input-group-custom .form-control, 
        .input-group-custom .form-select {
            background: transparent !important; border: none !important; color: #ffffff !important;
            font-weight: 800 !important; font-size: 1.05rem; box-shadow: none !important;
            padding: 14px 15px 14px 12px; height: 54px; letter-spacing: 0.8px;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
        }

        .input-group-custom .form-control::placeholder { color: #475569 !important; font-weight: 700; }
        .input-group-custom .form-select option { background-color: #03050a; color: #fff; font-weight: 700; }

        .photo-upload-zone {
            border: 2.5px dashed #0ea5e9; background: rgba(2, 4, 8, 0.6); border-radius: 20px;
            padding: 42px 20px; text-align: center; cursor: pointer;
            box-shadow: 0 0 22px rgba(14, 165, 233, 0.3); transition: all 0.3s ease;
        }

        .photo-upload-zone:hover {
            border-color: #f97316; background: rgba(249, 115, 22, 0.05);
            box-shadow: 0 0 35px rgba(249, 115, 22, 0.5); transform: translateY(-2px);
        }

        #imagePreview {
            width: 110px; height: 110px; object-fit: cover; border-radius: 50%;
            border: 4px solid #f97316; box-shadow: 0 0 25px rgba(249, 115, 22, 0.7);
            display: none; margin: 0 auto 12px auto;
        }

        .btn-submit-premium {
            font-family: 'Orbitron', sans-serif; background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white; border: 1px solid rgba(255,255,255,0.2); height: 56px; border-radius: 16px;
            font-weight: 900; font-size: 1.1rem; letter-spacing: 1.5px; transition: all 0.3s ease;
            box-shadow: 0 0 25px rgba(14, 165, 233, 0.5);
        }

        .btn-submit-premium:hover {
            transform: translateY(-3px); box-shadow: 0 0 35px rgba(249, 115, 22, 0.8);
            background: linear-gradient(135deg, #f97316, #ea580c);
        }

        .btn-clear-premium {
            font-family: 'Orbitron', sans-serif; background: rgba(220, 38, 38, 0.15); color: #ef4444;
            border: 2.5px solid #dc2626; height: 56px; border-radius: 16px; font-weight: 900;
            font-size: 1.1rem; letter-spacing: 1.5px; transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.35);
        }

        .btn-clear-premium:hover {
            background: #dc2626; color: white; transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(220, 38, 38, 0.7);
        }

        .alert-premium { padding: 16px 24px; border-radius: 16px; margin-bottom: 30px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
        .alert-premium-success { background: rgba(16, 185, 129, 0.15); border-left: 6px solid #10b981; }
        .alert-premium-danger { background: rgba(239, 68, 68, 0.15); border-left: 6px solid #ef4444; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card premium-card p-4 p-md-5 mx-auto animate__animated animate__fadeIn" style="max-width: 780px;">
        
        <div class="d-flex align-items-center justify-content-between mb-5 pb-3 border-bottom border-secondary border-opacity-25">
            <div>
                <h2 class="h3 mb-2 system-title">ATTENDANCE ARCHIVE SYSTEM</h2>
                <p class="text-muted small mb-0" style="letter-spacing: 0.5px;">INITIALIZE CORE DATA LOG INDEX FOR NEW STUDENT MATRICES</p>
            </div>
            <div class="fs-1 text-info opacity-75" style="filter: drop-shadow(0 0 10px #0ea5e9);"><i class="bi bi-cpu-fill animate__animated animate__pulse animate__infinite"></i></div>
        </div>

        <?php echo $msg; ?>
        
        <form id="attendanceForm" method="POST" enctype="multipart/form-data" class="row g-4">
            
            <div class="col-md-6">
                <label class="form-label-premium">Student Unique ID</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-qr-code-scan"></i></span>
                    <input type="text" name="student_id" class="form-control" placeholder="e.g. STU-IDENTITY-101" required autocomplete="off">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label-premium">Full Name</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-person-bounding-box"></i></span>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required autocomplete="off">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label-premium">Gender</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-gender-ambiguous"></i></span>
                    <select name="gender" class="form-select" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label-premium">Branch / Stream</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-terminal-fill"></i></span>
                    <select name="branch" class="form-select" required>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Mechanical">Mechanical</option>
                        <option value="Civil">Civil</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label-premium">Semester</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                    <select name="sem" class="form-select" required>
                        <?php for($i=1; $i<=6; $i++) { echo "<option value='Sem $i'>Sem $i</option>"; } ?>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label-premium">Contact Number</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-phone-vibrate-fill"></i></span>
                    <input type="tel" name="contact" class="form-control" placeholder="9876543210" required pattern="[0-9]{10}">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label-premium">Email Address</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-envelope-at-fill"></i></span>
                    <input type="email" id="emailInput" name="email" class="form-control" placeholder="name@college.com" required oninput="this.value = this.value.replace(/\s+/g, '').toLowerCase()">
                </div>
            </div>

            <div class="col-md-12">
                <label class="form-label-premium">Student Profile Identity (Photo)</label>
                <div class="photo-upload-zone" onclick="document.getElementById('photoInput').click()">
                    <img id="imagePreview" src="#" alt="Preview">
                    <div id="uploadPrompt">
                        <i class="bi bi-cloud-lightning-rain-fill text-info fs-1 mb-2 d-block" style="filter: drop-shadow(0 0 8px #0ea5e9);"></i>
                        <span class="d-block fw-bold text-white small" style="font-family: 'Orbitron', sans-serif; letter-spacing: 1px;">UPLOAD BIOMETRIC PHOTO</span>
                        <span class="text-muted extra-small" style="font-size: 0.75rem;">PNG, JPG, JPEG, WEBP REGISTERED ONLY</span>
                    </div>
                    <input type="file" id="photoInput" name="photo" class="d-none" accept="image/*">
                </div>
            </div>

            <div class="col-md-8 mt-5">
                <button type="submit" class="btn btn-submit-premium w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-shield-check"></i> INITIALIZE PROFILE RECORD
                </button>
            </div>
            <div class="col-md-4 mt-md-5 mt-2">
                <button type="button" id="clearBtn" class="btn btn-clear-premium w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-trash3-fill"></i> DELETE LOG
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const photoInput = document.getElementById('photoInput');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const attendanceForm = document.getElementById('attendanceForm');
    const clearBtn = document.getElementById('clearBtn');

    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadPrompt.style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });

    clearBtn.addEventListener('click', function() {
        attendanceForm.reset(); 
        imagePreview.src = "#";
        imagePreview.style.display = 'none'; 
        uploadPrompt.style.display = 'block'; 
    });
</script>

</body>
</html>
<?php include '../includes/footer.php'; ?>