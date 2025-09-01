<?php
header('Content-Type: application/json');
include 'connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['lecture_id'])) {
    echo json_encode(['success' => false, 'error' => 'No lecture ID provided']);
    exit;
}

$lecture_id = intval($data['lecture_id']);

$result = pg_query_params($con, 'DELETE FROM course_lectures WHERE id = $1', [$lecture_id]);

if ($result) {
    if (pg_affected_rows($result) > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Lecture not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
}
