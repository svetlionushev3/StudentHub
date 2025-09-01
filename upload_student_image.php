<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    header("Location: login_student.php");
    exit;
}

$egn = $_SESSION['student_egn'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];

    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array(strtolower($ext), $allowed)) {
            $newFileName = uniqid() . "." . $ext;
            $destination = 'uploads/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                
                $update = "UPDATE students SET profile_picture = $1 WHERE egn = $2";
                pg_query_params($con, $update, [$newFileName, $egn]);
            }
        }
    }
}

header("Location: profile_student.php");
exit;
