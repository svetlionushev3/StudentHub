<?php
session_start();
include("connection.php");

header('Content-Type: application/json');

if (!isset($_SESSION['student_egn'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['course_id'], $input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$course_id = (int)$input['course_id'];
$message = trim($input['message']);
if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

$egn = $_SESSION['student_egn'];
$queryStudent = "SELECT id FROM students WHERE egn = $1";
$resultStudent = pg_query_params($con, $queryStudent, [$egn]);
$student = pg_fetch_assoc($resultStudent);


if (!$student) {
    echo json_encode(['success' => false, 'error' => 'Student not found']);
    exit;
}

$student_id = $student['id'];
$query = "INSERT INTO course_chat (course_id, student_id, message) VALUES ($1, $2, $3)";
$result = pg_query_params($con, $query, [$course_id, $student_id, $message]);
if (!$result) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
