<?php
session_start();
header('Content-Type: application/json');
include("connection.php");

file_put_contents('debug.log', date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n", FILE_APPEND);


if (!isset($_SESSION['teacher_email'])) {
    echo json_encode(["success" => false, "error" => "Not authenticated"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

$course_id   = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if ($course_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid course ID"]);
    exit;
}

$query = "UPDATE courses SET description = $1 WHERE id = $2";
$result = pg_query_params($con, $query, [$description, $course_id]);

if ($result) {
    $query2 = "SELECT description FROM courses WHERE id = $1";
    $res2 = pg_query_params($con, $query2, [$course_id]);
    if ($res2 && $row = pg_fetch_assoc($res2)) {
        echo json_encode(["success" => true, "description" => $row['description']]);
    } else {
        echo json_encode(["success" => true, "description" => $description]);
    }
} else {
    echo json_encode(["success" => false, "error" => pg_last_error($con)]);
}

?>
