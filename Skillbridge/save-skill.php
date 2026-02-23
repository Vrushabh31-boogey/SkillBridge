<?php 
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo 'Not authorized';
    exit();
}

$student_id = $_SESSION['student_id'];
$skill = $_POST['skill'];

// Use prepared statement to prevent SQL injection
$stmt = mysqli_prepare($conn, "INSERT INTO skills(student_id, skill_name) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "is", $student_id, $skill);

if (mysqli_stmt_execute($stmt)) {
    echo 'Skill saved';
} else {
    echo 'Error saving skill';
}

mysqli_stmt_close($stmt);
?>