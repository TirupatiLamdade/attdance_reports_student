<?php

include '../config/session.php';
include '../config/database.php';
include '../includes/header.php';

$totalStudents =
mysqli_fetch_row(
mysqli_query(
$conn,
"SELECT COUNT(*) FROM students"))[0];

$totalAttendance =
mysqli_fetch_row(
mysqli_query(
$conn,
"SELECT COUNT(*) FROM attendance"))[0];

$todayAttendance =
mysqli_fetch_row(
mysqli_query(
$conn,

"SELECT COUNT(*)
FROM attendance
WHERE attendance_date=CURDATE()"))[0];
?>

<div class="container mt-4">

<h2>Analytics Dashboard</h2>

<div class="row">

<div class="col-md-4">

<div class="card bg-primary text-white">

<div class="card-body">

<h3>
<?php echo $totalStudents; ?>
</h3>

<p>Total Students</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-success text-white">

<div class="card-body">

<h3>
<?php echo $todayAttendance; ?>
</h3>

<p>Today's Attendance</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-warning">

<div class="card-body">

<h3>
<?php echo $totalAttendance; ?>
</h3>

<p>Total Attendanced Records</p>

</div>

</div>

</div>

</div>

</div>

<?php include '../includes/footer.php'; ?>