<?php
session_start();

$error = "";
$success = "";

// 🔒 मास्टर सिक्युरिटी की व्याख्या (तुम्ही तुमच्या गरजेनुसार ही की बदलू शकता)
define('MASTER_KEY', 'SUPER_SECRET_ERP_KEY_2026');

// 🔑 १. लॉगिन हँडलर इंजिन
if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    // सेशन्स किंवा फाईल/डेटाबेसमधून क्रेडेंशियल्स पडताळणे (जर बदलले असतील तर)
    $saved_username = isset($_SESSION['current_admin_user']) ? $_SESSION['current_admin_user'] : 'Tirupati';
    $saved_password = isset($_SESSION['current_admin_pass']) ? $_SESSION['current_admin_pass'] : 'Tirupati@123';

    if($username === $saved_username && $password === $saved_password){
        $_SESSION['admin'] = $username;
        $success = "Successfully Login. Redirecting...";

        echo "
        <script>
        setTimeout(function(){
            window.location='admin/dashboard.php';
        },1500);
        </script>
        ";
    } else {
        $error = "Invalid Username or Password";
    }
}

// 🛡️ २. क्रेडेंशियल्स रिकव्हरी आणि रिसेट इंजिन (Forgot Handler)
if(isset($_POST['reset_credentials'])){
    $input_key = $_POST['security_key'];
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];

    if($input_key === MASTER_KEY){
        // नवीन युझरनेम आणि पासवर्ड सेशन्स/सिस्टीममध्ये परमनंटली राईट करणे
        $_SESSION['current_admin_user'] = $new_username;
        $_SESSION['current_admin_pass'] = $new_password;
        
        $success = "Credentials updated successfully! Use new details to login.";
    } else {
        $error = "SECURITY CRISIS: Invalid Master Security Key.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Attendance ERP Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(-45deg,#0f172a,#1e3a8a,#2563eb,#7c3aed);
    background-size:400% 400%;
    animation:bgMove 12s ease infinite;
    overflow:hidden;
}

@keyframes bgMove{
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

.circle{
    position:absolute;
    border-radius:50%;
    background:rgba(255,255,255,0.06);
    backdrop-filter:blur(15px);
    z-index: 1;
}

.c1{ width:250px; height:250px; top:50px; left:80px; }
.c2{ width:180px; height:180px; bottom:80px; right:100px; }
.c3{ width:120px; height:120px; top:200px; right:250px; }

.login-box{
    width:100%;
    max-width:450px;
    padding:20px;
    z-index:2;
    animation:slideUp .8s ease;
}

@keyframes slideUp{
    from{ opacity:0; transform:translateY(40px); }
    to{ opacity:1; transform:translateY(0); }
}

.card{
    background:rgba(255,255,255,0.12);
    backdrop-filter:blur(25px);
    border:1px solid rgba(255,255,255,0.2);
    border-radius:30px;
    overflow:hidden;
    transition:.4s;
    box-shadow:0 20px 50px rgba(0,0,0,.35);
}

.card:hover{
    transform:translateY(-5px);
}

.card-header{
    background:transparent;
    border:none;
    text-align:center;
    color:white;
    padding:30px 30px 10px 30px;
}

.logo{
    font-size:70px;
    margin-bottom:10px;
    filter: drop-shadow(0 0 15px rgba(0,219,222,0.6));
}

.card-body{
    padding:35px;
}

.input-group-text{
    background:rgba(255,255,255,.18);
    border:none;
    color:white;
}

.form-control{
    height:55px;
    border:none;
    background:rgba(255,255,255,.12);
    color:white;
    transition:.3s;
}

.form-control::placeholder{
    color:rgba(255,255,255,.65);
}

.form-control:focus{
    background:rgba(255,255,255,.2);
    color:white;
    border:1px solid #00dbde;
    box-shadow:0 0 15px rgba(0,219,222,.5);
}

.btn-login{
    width:100%;
    height:55px;
    border:none;
    border-radius:15px;
    background:linear-gradient(45deg,#00dbde,#fc00ff);
    color:white;
    font-size:18px;
    font-weight:bold;
    transition:.4s;
}

.btn-login:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 25px rgba(252,0,255,.4);
}

.forgot-link-wrapper {
    text-align: right;
    margin-top: -5px;
    margin-bottom: 20px;
}

.forgot-link {
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: color 0.3s;
}

.forgot-link:hover {
    color: #00dbde;
    text-shadow: 0 0 8px rgba(0,219,222,0.5);
}

.auto-hide{
    animation:fadeOut 5s forwards;
}

@keyframes fadeOut{
    0%{opacity:1;}
    85%{opacity:1;}
    100%{opacity:0; display:none;}
}

.footer{
    text-align:center;
    color:white;
    margin-top:15px;
    font-size:14px;
    opacity: 0.8;
}

/* 🎇 PREMIUM CYBER MODAL DESIGN */
.cyber-modal {
    background: rgba(15, 23, 42, 0.85) !important;
    backdrop-filter: blur(30px) !important;
    border: 1px solid rgba(252, 0, 255, 0.3) !important;
    border-radius: 25px !important;
    color: white;
    box-shadow: 0 0 40px rgba(252, 0, 255, 0.2);
}

.cyber-modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.1) !important;
    padding: 25px;
}

.cyber-modal-footer {
    border-top: 1px solid rgba(255,255,255,0.1) !important;
}

.cyber-btn-secondary {
    background: rgba(255,255,255,0.1);
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    padding: 10px 20px;
    transition: 0.3s;
}
.cyber-btn-secondary:hover {
    background: rgba(255,255,255,0.2);
    color: white;
}

.cyber-btn-primary {
    background: linear-gradient(45deg, #fc00ff, #00dbde);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 10px 25px;
    font-weight: 600;
    transition: 0.3s;
}
.cyber-btn-primary:hover {
    box-shadow: 0 0 15px rgba(0,219,222,0.5);
    transform: translateY(-2px);
}
</style>
</head>
<body>

<div class="circle c1"></div>
<div class="circle c2"></div>
<div class="circle c3"></div>

<div class="login-box">

    <div class="card">

        <div class="card-header">
            <div class="logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h2>Attendance ERP</h2>
            <p>Secure Admin Dashboard Access</p>
        </div>

        <div class="card-body">

            <?php if(!empty($success)){ ?>
                <div class="alert alert-success text-center auto-hide border-0 bg-success bg-opacity-25 text-white">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                </div>
            <?php } ?>

            <?php if(!empty($error)){ ?>
                <div class="alert alert-danger text-center auto-hide border-0 bg-danger bg-opacity-25 text-white">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="POST">

                <div class="input-group mb-3">
                    <span class="input-group-text">
                        <i class="bi bi-person-fill"></i>
                    </span>
                    <input type="text"
                           name="username"
                           class="form-control"
                           placeholder="Enter Username"
                           required>
                </div>

                <div class="input-group mb-2">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Enter Password"
                           required>
                    <span class="input-group-text"
                          onclick="togglePassword('password', 'eyeIcon')"
                          style="cursor:pointer;">
                        <i class="bi bi-eye-fill" id="eyeIcon"></i>
                    </span>
                </div>

                <!-- 🔗 FORGOT CREDENTIALS TRIGGER TRIGGER LINK -->
                <div class="forgot-link-wrapper">
                    <a href="#" class="forgot-link" data-bs-toggle="modal" data-bs-target="#forgotResetModal">
                        <i class="bi bi-shield-key-fill me-1"></i> Forgot Username / Password?
                    </a>
                </div>

                <button type="submit"
                        name="login"
                        class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Login
                </button>

            </form>

        </div>

    </div>

    <div class="footer">
        © 2026 Attendance ERP System
    </div>

</div>

<!-- 🎛️ FORGOT USERNAME & PASSWORD RESET MODAL -->
<div class="modal fade" id="forgotResetModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cyber-modal animate__animated animate__zoomIn animate__faster">
            <div class="modal-header cyber-modal-header">
                <h5 class="modal-title fw-bold text-info"><i class="bi bi-cpu-fill me-2"></i>System Key Override Console</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <p class="small text-white-50 mb-4">Enter a security key </p>
                    
                    <!-- १. सिक्युरिटी मास्टर की फील्ड -->
                    <div class="mb-3">
                        <label class="form-label text-info small fw-bold">MASTER SECURITY KEY</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-info border-0"><i class="bi bi-key-fill"></i></span>
                            <input type="password" name="security_key" id="security_key" class="form-control bg-dark text-white border-0" placeholder="Enter System Secret Key" required>
                            <span class="input-group-text bg-dark text-info border-0" onclick="togglePassword('security_key', 'keyEyeIcon')" style="cursor:pointer;">
                                <i class="bi bi-eye-fill" id="keyEyeIcon"></i>
                            </span>
                        </div>
                        <div class="form-text text-white-50 small" style="font-size:11px;">Default Config Key: <code>TiruaptiLamdade</code></div>
                    </div>

                    <hr class="border-secondary my-4">

                    <!-- २. नवीन युझरनेम -->
                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">NEW ADMIN USERNAME</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white-50 border-0"><i class="bi bi-person-plus-fill"></i></span>
                            <input type="text" name="new_username" class="form-control bg-dark text-white border-0" placeholder="Set New Username" required autocomplete="off">
                        </div>
                    </div>

                    <!-- ३. नवीन पासवर्ड -->
                    <div class="mb-2">
                        <label class="form-label text-white small fw-bold">NEW ADMIN PASSWORD</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white-50 border-0"><i class="bi bi-shield-lock-fill"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control bg-dark text-white border-0" placeholder="Set New Strong Password" required>
                            <span class="input-group-text bg-dark text-white-50 border-0" onclick="togglePassword('new_password', 'newPassEyeIcon')" style="cursor:pointer;">
                                <i class="bi bi-eye-fill" id="newPassEyeIcon"></i>
                            </span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer cyber-modal-footer p-3">
                    <button type="button" class="btn cyber-btn-secondary" data-bs-dismiss="modal">ABORT</button>
                    <button type="submit" name="reset_credentials" class="btn cyber-btn-primary">RESET NOW</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// पासवर्ड लपवण्यासाठी आणि दाखवण्यासाठी कॉमन ऑप्टिमाइज्ड फंक्शन
function togglePassword(fieldId, iconId){
    let passwordField = document.getElementById(fieldId);
    let iconField = document.getElementById(iconId);

    if(passwordField.type === "password"){
        passwordField.type = "text";
        iconField.className = "bi bi-eye-slash-fill";
    } else {
        passwordField.type = "password";
        iconField.className = "bi bi-eye-fill";
    }
}
</script>

</body>
</html>