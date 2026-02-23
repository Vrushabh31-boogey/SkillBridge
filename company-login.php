<?php 
session_start();
require 'db.php';

$email = $_POST['email'];
$password = $_POST['password'];

// Use prepared statement to prevent SQL injection
$stmt = mysqli_prepare($conn, "SELECT * FROM companies WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$company = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($company && password_verify($password, $company['password'])) {
    $_SESSION['company_id'] = $company['id'];
    $_SESSION['company_name'] = $company['company_name'];
    header('Location: company-dashboard.php');
    exit();
} else {
    echo "<link rel='stylesheet' href='style.css'>";
    echo "<div class='center-page'><div class='card'><h2>Login Failed</h2><p>Invalid email or password.</p><a href='company-login.html'>Try Again</a></div></div>";
}
?>