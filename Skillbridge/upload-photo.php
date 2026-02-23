<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit();
}

$file = $_FILES['photo'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
    exit();
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File too large. Max 2MB allowed.']);
    exit();
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'student_' . $_SESSION['student_id'] . '_' . time() . '.' . $ext;
$upload_dir = __DIR__ . '/uploads/';
$upload_path = $upload_dir . $filename;

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file.']);
    exit();
}

// Delete old photo if exists
$student_id = $_SESSION['student_id'];
$result = mysqli_query($conn, "SELECT photo FROM students WHERE id = $student_id");
$row = mysqli_fetch_assoc($result);
if (!empty($row['photo']) && file_exists(__DIR__ . '/' . $row['photo'])) {
    unlink(__DIR__ . '/' . $row['photo']);
}

// Update DB
$photo_path = 'uploads/' . $filename;
$photo_path_escaped = mysqli_real_escape_string($conn, $photo_path);
mysqli_query($conn, "UPDATE students SET photo = '$photo_path_escaped' WHERE id = $student_id");

echo json_encode(['success' => true, 'path' => $photo_path]);
?>
