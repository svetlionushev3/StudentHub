<?php
session_start();
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login_teacher.php");
    exit;
}

$email = $_SESSION['teacher_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];

    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array(strtolower($ext), $allowed)) {
            $newFileName = uniqid() . "." . $ext;
            $destination = 'uploads/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {

                $update = "UPDATE teachers SET profile_picture = $1 WHERE email = $2";
                pg_query_params($con, $update, [$newFileName, $email]);
            }
        }
    }
}

header("Location: profile_teacher.php");
exit;
