<?php
session_start();
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login_teacher.php");
    exit;
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    die("No course selected.");
}

$email = $_SESSION['teacher_email'];

$query = "SELECT * FROM teachers WHERE email = $1";
$result = pg_query_params($con, $query, [$email]);
$teacher = pg_fetch_assoc($result);
if (!$teacher) {
    die("Teacher not found.");
}

$check_query = "SELECT * FROM teacher_courses WHERE teacher_id = $1 AND course_id = $2";
$check_res = pg_query_params($con, $check_query, [$teacher['id'], $course_id]);
if (pg_num_rows($check_res) == 0) {
    die("You do not have access to this course.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';

    $update_query = "UPDATE courses SET description = $1 WHERE id = $2";
    $update_res = pg_query_params($con, $update_query, [$description, $course_id]);
    if ($update_res) {
        $message = "The description has been updated successfully.";
    } else {
        $message = "An error occurred while updating.";
    }
}

$course_query = "SELECT * FROM courses WHERE id = $1";
$course_result = pg_query_params($con, $course_query, [$course_id]);
$course = pg_fetch_assoc($course_result);
if (!$course) {
    die("Course not found.");
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Edit Course</title>
</head>
<body>
    <h2>Edit Course Description: <?= htmlspecialchars($course['course_name']) ?></h2>
    <?php if (!empty($message)) echo "<p>$message</p>"; ?>
    <form method="post">
        <label for="description">Description:</label><br>
        <textarea id="description" name="description" rows="5" cols="50"><?= htmlspecialchars($course['description']) ?></textarea><br><br>
        <button type="submit">Save</button>
    </form>
    <p><a href="dashboard.php">Back</a></p>
</body>
</html>
