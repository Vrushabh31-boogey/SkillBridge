<?php 
require 'db.php';

$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Use prepared statement to prevent SQL injection
$stmt = mysqli_prepare($conn, "INSERT INTO companies(company_name, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $name, $email, $password);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header('Location: company-login.html');
exit();
?>