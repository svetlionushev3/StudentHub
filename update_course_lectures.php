<?php
session_start();
header('Content-Type: application/json');
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$course_id = $_POST['course_id'] ?? null;
if (!$course_id) {
    echo json_encode(["success" => false, "error" => "No course id"]);
    exit;
}

if (!isset($_POST['lectures'])) {
    echo json_encode(["success" => false, "error" => "No lectures data"]);
    exit;
}

try {
    foreach ($_POST['lectures'] as $idx => $lecture) {
        $lecture_id  = $lecture['id'] ?? null;
        $description = $lecture['description'] ?? '';
        $delete      = isset($lecture['delete']) ? true : false;
        $filename    = null;

        if (isset($_FILES['lectures']['name'][$idx]['file']) && $_FILES['lectures']['error'][$idx]['file'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['lectures']['tmp_name'][$idx]['file'];
            $originalName = basename($_FILES['lectures']['name'][$idx]['file']);
            $targetDir = __DIR__ . "/uploads/course_lectures/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalName);
            move_uploaded_file($tmpName, $targetDir . $filename);
        }

        if ($lecture_id) {
            if ($delete) {
               pg_query_params($con, "DELETE FROM course_lectures WHERE id=$1 AND course_id=$2", [$lecture_id, $course_id]);

            } else {
                if ($filename) {
                    pg_query_params(
                        $con,
                        "UPDATE course_lectures SET description=$1, filename=$2 WHERE id=$3 AND course_id=$4",
                        [$description, $filename, $lecture_id, $course_id]
                    );
                } else {
                    pg_query_params(
                        $con,
                        "UPDATE course_lectures SET description=$1 WHERE id=$2 AND course_id=$3",
                        [$description, $lecture_id, $course_id]
                    );
                }
            }
        } else {
            
            if ($filename) {
                pg_query_params(
                    $con,
                    "INSERT INTO course_lectures (course_id, description, filename, uploaded_at) VALUES ($1,$2,$3,NOW())",
                    [$course_id, $description, $filename]
                );
            } else {
                pg_query_params(
                    $con,
                    "INSERT INTO course_lectures (course_id, description, uploaded_at) VALUES ($1,$2,NOW())",
                    [$course_id, $description]
                );
            }
        }
    }

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
