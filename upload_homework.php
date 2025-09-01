<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    header("Location: login_student.php");
    exit;
}

$egn = $_SESSION['student_egn'];

$queryStudent = "SELECT id FROM students WHERE egn = $1";
$resultStudent = pg_query_params($con, $queryStudent, [$egn]);
$student = pg_fetch_assoc($resultStudent);
if (!$student) {
    echo "Student not found..";
    exit;
}

$student_id = $student['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['homework_id']) || !is_numeric($_POST['homework_id'])) {
        die("Invalid homework.");
    }

    $homework_id = (int)$_POST['homework_id'];

    if (!isset($_FILES['homework_file']) || $_FILES['homework_file']['error'] !== UPLOAD_ERR_OK) {
        die("An error occurred while uploading the file..");
    }

    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];
    $fileName = $_FILES['homework_file']['name'];
    $fileTmp = $_FILES['homework_file']['tmp_name'];
    $fileSize = $_FILES['homework_file']['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowed)) {
        die("Invalid file type. Allowed are: " . implode(", ", $allowed));
    }

    if ($fileSize > 10 * 1024 * 1024) {
        die("The file is too large (max 10MB).");
    }

    $newFileName = uniqid('hw_') . "." . $fileExt;

    $uploadDir = __DIR__ . "/uploads/homeworks/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $destination)) {
        die("An error occurred while saving the file.");
    }

$queryInsert = "INSERT INTO homework_submissions (homework_id, student_id, file_path, submitted_at) VALUES ($1, $2, $3, NOW())";
$resultInsert = pg_query_params($con, $queryInsert, [$homework_id, $student_id, $newFileName]);



    if ($resultInsert) {
        header("Location: view_student.php?upload=success");
        exit;
    } else {
        die("An error occurred while saving to the database.");
    }
    
} else {
    die("Invalid request.");
}
