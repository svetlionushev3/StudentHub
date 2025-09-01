<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    die("Access denied.");
}

$submission_id = $_POST['submission_id'] ?? null;
$is_public = isset($_POST['is_public']) ? 'TRUE' : 'FALSE';

$egn = $_SESSION['student_egn'];
$result = pg_query_params($con, "SELECT id FROM students WHERE egn = $1", [$egn]);
$student = pg_fetch_assoc($result);

if (!$submission_id || !$student) {
    die("Invalid request.");
}

pg_query_params($con, "
    UPDATE homework_submissions 
    SET is_public = $1 
    WHERE id = $2 AND student_id = $3
", [$is_public, $submission_id, $student['id']]);

header("Location: ".$_SERVER['HTTP_REFERER']);
exit;
