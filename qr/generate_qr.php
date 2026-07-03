<?php
include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

$qr_image = "";
$student_info = null;
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_id'])) {
    $stu_id = mysqli_real_escape_string($conn, trim($_POST['student_id']));
    
    // डेटाबेस से छात्र की जानकारी निकालना
    $query = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$stu_id'");
    
    if (mysqli_num_rows($query) > 0) {
        $student_info = mysqli_fetch_assoc($query);
        
        // 🚀 QR कोड जनरेटर API
        $qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($stu_id);
    } else {
        $error_msg = "Roll Number '$stu_id' Enter number is !not found database";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate & View Student QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #090d16, #111827); 
            color: #f3f4f6; 
            min-height: 100vh; 
        }
        .glass-card { 
            background: rgba(17, 24, 39, 0.85); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        /* 🌟 High Contrast Highlighted Info Panel */
        .info-panel {
            background: #030712 !important; /* Pitch Black background for maximum contrast */
            border: 2px solid #3b82f6; /* Electric Blue Border */
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
        }
        /* Highlight Labels */
        .badge-label {
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            color: #9ca3af !important; /* Slate gray for labels */
            font-weight: 700;
        }
        /* QR Container with Glow */
        .qr-box {
            background: #ffffff;
            padding: 14px;
            border-radius: 18px;
            display: inline-block;
            box-shadow: 0 0 25px rgba(255, 255, 255, 0.15);
            border: 3px solid #10b981; /* Neon green ring around QR */
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="glass-card p-4 p-md-5 animate__animated animate__fadeIn">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0 fw-bold text-info"><i class="bi bi-qr-code-scan"></i> QR Console Node</h3>
                    <button type="button" onclick="clearGenerator()" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-trash3 me-1"></i> Clear Console
                    </button>
                </div>
                <p class="text-muted small mb-4">Input student Roll Number to process database mapping and view QR matrix.</p>
                
                <?php if (!empty($error_msg)): ?>
                    <div id="error-block" class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4 text-start animate__animated animate__shakeX">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form id="qr-form" method="POST" action="">
                    <div class="mb-4 text-start">
                        <!-- यहाँ Label का नाम भी Target Roll Number कर दिया गया है -->
                        <label class="form-label small fw-bold text-uppercase tracking-wider text-secondary">Target Roll Number</label>
                        <input type="text" id="student_id_input" name="student_id" class="form-control form-control-lg bg-dark bg-opacity-50 text-white border-secondary rounded-3" placeholder="e.g. 24015" value="<?= isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-info btn-lg w-100 fw-bold text-uppercase rounded-3 shadow-sm">
                        <i class="bi bi-cpu-fill me-1"></i> Generate & View QR
                    </button>
                </form>

                <!-- 🔮 VIEW QR & STUDENT INFORMATION PANEL -->
                <?php if (!empty($qr_image) && $student_info): ?>
                    <div id="display-matrix" class="mt-5 p-4 info-panel text-start animate__animated animate__zoomIn animate__faster">
                        <h5 class="text-info fw-bold mb-4 border-bottom border-secondary border-opacity-40 pb-2 text-uppercase tracking-wide">
                            <i class="bi bi-terminal-fill me-2 text-warning"></i> Live Output Console
                        </h5>
                        
                        <div class="row align-items-center g-4">
                            <div class="col-md-7 order-2 order-md-1">
                                <!-- Name Box -->
                                <div class="mb-4 bg-secondary bg-opacity-10 p-3 rounded-3 border-start border-3 border-info">
                                    <span class="badge-label d-block text-uppercase mb-1">Student Name</span>
                                    <strong class="fs-3 text-white fw-black tracking-wide"><?= htmlspecialchars($student_info['name']); ?></strong>
                                </div>
                                
                                <!-- 2-Column High Contrast Layout (Old Roll_no Deleted, Token ID renamed to Roll Number) -->
                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="bg-dark p-3 rounded text-center border border-info border-opacity-40 h-100">
                                            <span class="badge-label d-block text-uppercase text-info mb-1">Roll Number</span>
                                            <strong class="text-white fs-4 fw-bold"><?= htmlspecialchars($student_info['student_id']); ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-dark p-3 rounded text-center border border-warning border-opacity-40 h-100">
                                            <span class="badge-label d-block text-uppercase text-warning mb-1">Semester</span>
                                            <strong class="text-warning fs-4 fw-black"><?= htmlspecialchars($student_info['semester'] ?? $student_info['sem'] ?? 'N/A'); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Department Highlight -->
                                <div class="mb-4 px-2">
                                    <span class="badge-label d-block text-uppercase mb-1">Core Department / Branch</span>
                                    <strong class="text-white fs-6 bg-info bg-opacity-10 px-3 py-1.5 rounded border border-info border-opacity-30 d-inline-block"><i class="bi bi-braces me-1"></i> <?= htmlspecialchars($student_info['branch']); ?></strong>
                                </div>
                                
                                <button onclick="downloadQR('<?= htmlspecialchars($student_info['student_id']); ?>')" class="btn btn-success btn-lg fw-bold px-4 py-2.5 rounded-pill w-100 w-md-auto shadow">
                                    <i class="bi bi-download me-2"></i> Download QR Node
                                </button>
                            </div>
                            
                            <!-- QR Preview Box -->
                            <div class="col-md-5 order-1 order-md-2 text-center">
                                <div class="qr-box">
                                    <img id="qr-image" src="<?= $qr_image; ?>" alt="Student QR Code" class="img-fluid" style="width: 175px; height: 175px;">
                                </div>
                                <div class="mt-3 text-success small fw-bold tracking-wider"><i class="bi bi-patch-check-fill"></i> MATRIX SYNCED</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function clearGenerator() {
    document.getElementById('student_id_input').value = "";
    let errorBlock = document.getElementById('error-block');
    if(errorBlock) errorBlock.style.display = "none";
    
    let displayMatrix = document.getElementById('display-matrix');
    if(displayMatrix) {
        displayMatrix.classList.remove('animate__zoomIn');
        displayMatrix.classList.add('animate__fadeOut');
        setTimeout(() => { displayMatrix.style.display = "none"; }, 400);
    }
}

function downloadQR(studentId) {
    let qrImgElement = document.getElementById('qr-image');
    if (!qrImgElement) return;

    let qrUrl = qrImgElement.src;
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Downloading...`;

    fetch(qrUrl)
        .then(response => response.blob())
        .then(blob => {
            let blobURL = URL.createObjectURL(blob);
            let downloadLink = document.createElement('a');
            downloadLink.href = blobURL;
            downloadLink.download = "RollNo_" + studentId + "_QR.png";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(blobURL);
            btn.disabled = false;
            btn.innerHTML = originalText;
        })
        .catch(error => {
            alert("Download failed.");
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}
</script>
</body>
</html>
<?php include '../includes/footer.php'; ?>