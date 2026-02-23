<?php 
session_start();
require 'db.php';

$email = $_POST['email'];
$password = $_POST['password'];

// Use prepared statement to prevent SQL injection
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['student_id'] = $user['id'];
    $_SESSION['student_name'] = $user['name'];
    header('Location: student-dashboard.php');
    exit();
} else {
    echo "<link rel='stylesheet' href='style.css'>";
    echo "<div class='center-page'><div class='card'><h2>Login Failed</h2><p>Invalid email or password.</p><a href='student-login.html'>Try Again</a></div></div>";
}
?>