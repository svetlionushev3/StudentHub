<?php
session_start();
header('Content-Type: application/json');
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid course_id']);
    exit;
}

$sql = "SELECT id, description, filename
        FROM course_lectures
        WHERE course_id = $1
        ORDER BY uploaded_at ASC, id ASC";
$res = pg_query_params($con, $sql, [$course_id]);

if (!$res) {
    echo json_encode(['success' => false, 'error' => 'DataBase error']);
    exit;
}

$lectures = [];
while ($row = pg_fetch_assoc($res)) {
    $lectures[] = [
        'id' => (int)$row['id'],
        'description' => $row['description'] ?? '',
        'filename' => $row['filename'] ?? ''
    ];
}

echo json_encode(['success' => true, 'lectures' => $lectures]);
