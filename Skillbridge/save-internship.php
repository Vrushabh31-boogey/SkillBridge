<?php 
session_start();
require 'db.php';

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    echo 'Not authorized';
    exit();
}

$company_id = $_SESSION['company_id'];
$title = $_POST['title'];
$description = $_POST['desc'];
$stipend = $_POST['stipend'];

// Use prepared statement to prevent SQL injection
$stmt = mysqli_prepare($conn, "INSERT INTO internships(company_id, title, description, stipend) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isss", $company_id, $title, $description, $stipend);

if (mysqli_stmt_execute($stmt)) {
    echo 'Internship posted';
} else {
    echo 'Error posting internship';
}

mysqli_stmt_close($stmt);
?>