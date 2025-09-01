<?php
include("connection.php");
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id > 0) {
    $res = pg_query_params($con, "SELECT description FROM courses WHERE id=$1", [$course_id]);
    if ($res && $row = pg_fetch_assoc($res)) {
        echo json_encode(['description' => $row['description']]);
        exit;
    }
}
echo json_encode(['description' => '']);
?>
