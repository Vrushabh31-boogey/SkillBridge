<?php 
session_start();
require 'db.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo 'Not authorized';
    exit();
}

$student_id = $_SESSION['student_id'];
$internship_id = intval($_POST['internship_id']);

// Check if already applied
$check_stmt = mysqli_prepare($conn, "SELECT id FROM applications WHERE student_id = ? AND internship_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $student_id, $internship_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo 'Already applied';
    exit();
}
mysqli_stmt_close($check_stmt);

// Insert application
$stmt = mysqli_prepare($conn, "INSERT INTO applications (student_id, internship_id) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "ii", $student_id, $internship_id);

if (mysqli_stmt_execute($stmt)) {
    echo 'Applied successfully';
} else {
    echo 'Error applying';
}

mysqli_stmt_close($stmt);
?>
