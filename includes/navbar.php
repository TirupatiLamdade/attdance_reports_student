<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

/* BACKGROUND */
body{
background:linear-gradient(135deg,#0f172a,#1e293b,#334155);
background-attachment:fixed;
}

/* GLASS NAVBAR */
.erp-navbar{
background:rgba(255,255,255,0.07);
backdrop-filter:blur(25px);
border-bottom:1px solid rgba(255,255,255,0.12);
box-shadow:0 10px 35px rgba(0,0,0,0.35);
padding:12px 18px;
}

/* BRAND */
.erp-navbar .navbar-brand{
color:#fff !important;
font-size:22px;
font-weight:800;
letter-spacing:1px;
display:flex;
align-items:center;
gap:8px;
}

/* NAV ITEMS */
.erp-navbar .nav-link{
color:#fff !important;
font-weight:500;
padding:9px 14px;
margin:2px;
border-radius:14px;
display:flex;
align-items:center;
gap:6px;
transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
position:relative;
overflow:hidden;
}

/* HOVER PREMIUM EFFECT */
.erp-navbar .nav-link:hover{
background:rgba(255,255,255,0.14);
transform:translateY(-3px);
box-shadow:0 6px 15px rgba(255,255,255,0.1);
}

/* CLICK GLOW EFFECT (ON CLICK) */
.erp-navbar .nav-link:active {
transform: translateY(-1px) scale(0.95);
box-shadow: 0 0 25px rgba(59, 130, 246, 0.75), inset 0 0 10px rgba(255, 255, 255, 0.5);
transition: all 0.05s ease;
}

/* ACTIVE BUTTON WITH PULSE GLOW */
.erp-navbar .nav-link.active{
background:linear-gradient(135deg,#2563eb,#7c3aed);
box-shadow:0 6px 20px rgba(124,58,237,0.6);
animation: activePulse 2s infinite alternate;
}

/* NAV CENTER ALIGN */
.navbar-nav{
gap:6px;
}

/* LOGOUT BUTTON PREMIUM */
.logout-btn{
background:linear-gradient(135deg,#ef4444,#dc2626);
border:none;
border-radius:14px;
padding:9px 16px;
color:white;
font-weight:700;
text-decoration:none;
display:flex;
align-items:center;
gap:6px;
transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
box-shadow:0 8px 20px rgba(220,38,38,0.35);
}

.logout-btn:hover{
transform:scale(1.07);
box-shadow:0 10px 25px rgba(239, 68, 68, 0.5);
}

/* LOGOUT CLICK GLOW EFFECT (ON CLICK) */
.logout-btn:active {
transform: scale(0.98);
box-shadow: 0 0 30px rgba(239, 68, 68, 0.9), inset 0 0 10px rgba(255, 255, 255, 0.3);
transition: all 0.05s ease;
}

/* ICON STYLE */
.nav-link i{
font-size:16px;
}

/* GLASS GLOW EFFECT */
.erp-navbar::after{
content:'';
position:absolute;
inset:0;
background:gradient(90deg,transparent,rgba(255,255,255,0.05),transparent);
opacity:0.6;
pointer-events:none;
}

/* KEYFRAMES FOR ACTIVE PULSE */
@keyframes activePulse {
0% {
box-shadow: 0 6px 20px rgba(124,58,237,0.5);
}
100% {
box-shadow: 0 6px 30px rgba(124, 58, 237, 0.9), 0 0 10px rgba(37, 99, 235, 0.4);
}
}

</style>

<nav class="navbar navbar-expand-lg navbar-dark erp-navbar">

<div class="container-fluid">

<a class="navbar-brand" href="../admin/dashboard.php">
🎓 Attendance ERP
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navbarNav">

<ul class="navbar-nav mx-auto">

<li class="nav-item">
<a class="nav-link active" href="../admin/dashboard.php"><i class="bi bi-house"></i>Dashboard</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../students/view_students.php"><i class="bi bi-people"></i>Students</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../attendance/mark_attendance.php"><i class="bi bi-check-circle"></i>Attendance</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../attendance/attendance_report.php"><i class="bi bi-bar-chart"></i>Reports</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../attendance/daily_report.php"><i class="bi bi-calendar-day"></i>Daily</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../attendance/monthly_report.php"><i class="bi bi-calendar-month"></i>Monthly</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../attendance/attendance_percentage.php"><i class="bi bi-percent"></i>Total</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../qr/generate_qr.php"><i class="bi bi-qr-code"></i>QR</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../qr/scan_qr.php"><i class="bi bi-camera"></i>Scan</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../reports/export_pdf.php"><i class="bi bi-file-pdf"></i>PDF</a>
</li>



<li class="nav-item">
<a class="nav-link" href="../reports/analytics.php"><i class="bi bi-graph-up"></i>Analytics</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../admin/profile.php"><i class="bi bi-person"></i>Profile</a>
</li>

</ul>

<a href="../logout.php" class="logout-btn">
<i class="bi bi-box-arrow-right"></i>Logout
</a>

</div>

</div>

</nav>