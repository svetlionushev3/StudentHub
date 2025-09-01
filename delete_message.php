<?php
session_start();
include("connection.php"); 

header('Content-Type: application/json');

if (!isset($_SESSION['student_egn'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$egn = $_SESSION['student_egn'];
$queryStudent = "SELECT * FROM students WHERE egn = $1";
$resultStudent = pg_query_params($con, $queryStudent, [$egn]);
$student = pg_fetch_assoc($resultStudent);
if (!$student) {
    echo json_encode(['success' => false, 'error' => 'Student not found']);
    exit;
}

if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
    exit;
}

$message_id = (int)$_POST['message_id'];
$queryCheck = "SELECT * FROM course_chat WHERE id = $1 AND student_id = $2";
$resultCheck = pg_query_params($con, $queryCheck, [$message_id, $student['id']]);

if (pg_num_rows($resultCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Message not found or permission denied']);
    exit;
}

$queryDelete = "DELETE FROM course_chat WHERE id = $1";
$resultDelete = pg_query_params($con, $queryDelete, [$message_id]);

if ($resultDelete) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
}
?>
