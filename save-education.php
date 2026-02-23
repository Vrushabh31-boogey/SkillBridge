<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

require 'db.php';
header('Content-Type: application/json');

$student_id = $_SESSION['student_id'];
$edu_type   = mysqli_real_escape_string($conn, trim($_POST['edu_type'] ?? ''));
$inst_name  = mysqli_real_escape_string($conn, trim($_POST['inst_name'] ?? ''));

$allowed = ['School', 'College', 'Learning Purpose'];
if (!in_array($edu_type, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid type selected.']);
    exit();
}

// If not College, clear institution name
if ($edu_type !== 'College') {
    $inst_name = '';
}

// Check whether the columns exist first (in case ALTER TABLE hasn't been run yet)
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'edu_type'");
if (mysqli_num_rows($col_check) === 0) {
    // Columns missing — try to create them now automatically
    mysqli_query($conn, "ALTER TABLE students ADD COLUMN IF NOT EXISTS edu_type ENUM('School','College','Learning Purpose') DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE students ADD COLUMN IF NOT EXISTS inst_name VARCHAR(200) DEFAULT NULL");

    // Re-check
    $col_check2 = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'edu_type'");
    if (mysqli_num_rows($col_check2) === 0) {
        echo json_encode(['success' => false, 'error' => 'DB columns missing. Please run the ALTER TABLE in phpMyAdmin.']);
        exit();
    }
}

try {
    $result = mysqli_query($conn,
        "UPDATE students SET edu_type='$edu_type', inst_name='$inst_name' WHERE id=$student_id"
    );

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
