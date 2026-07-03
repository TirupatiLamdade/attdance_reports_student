
<?php

$conn = mysqli_connect(
    "127.0.0.1",
    "root",
    "",
 "attendance_system",
    3306
);

if (!$conn)
{
    die("MySQL Connection Failed: " . mysqli_connect_error());
}
?>