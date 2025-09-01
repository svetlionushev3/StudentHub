<?php
session_start();
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login_teacher.php");
    exit;
}

$email = $_SESSION['teacher_email'];
$queryTeacher = "SELECT * FROM teachers WHERE email = $1";
$resultTeacher = pg_query_params($con, $queryTeacher, [$email]);
$teacher = pg_fetch_assoc($resultTeacher);

if (!$teacher) {
    echo "Teacher not found.";
    exit;
}

if (!isset($_GET['course_id'])) {
    echo "No course selected.";
    exit;
}

$course_id = (int)$_GET['course_id'];

$queryHomeworks = "SELECT * FROM homeworks WHERE course_id = $1 ORDER BY due_date";
$resultHomeworks = pg_query_params($con, $queryHomeworks, [$course_id]);
$homeworks = [];
while ($hw = pg_fetch_assoc($resultHomeworks)) {
    $homeworks[] = $hw;
}

$queryStudents = "
    SELECT s.*
    FROM students s
    JOIN student_courses sc ON sc.student_id = s.id
    WHERE sc.course_id = $1
    ORDER BY s.last_name, s.first_name
";
$resultStudents = pg_query_params($con, $queryStudents, [$course_id]);
$students = [];
while ($st = pg_fetch_assoc($resultStudents)) {
    $students[] = $st;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homework_id = (int)$_POST['homework_id'];
    $student_id = (int)$_POST['student_id'];
    $grade = trim($_POST['grade']);

    if ($grade === '') {

        $deleteQuery = "DELETE FROM homework_grades WHERE homework_id = $1 AND student_id = $2";
        pg_query_params($con, $deleteQuery, [$homework_id, $student_id]);
    } else {

        $checkQuery = "SELECT * FROM homework_grades WHERE homework_id = $1 AND student_id = $2";
        $resCheck = pg_query_params($con, $checkQuery, [$homework_id, $student_id]);

        if (pg_num_rows($resCheck) > 0) {

            $updateQuery = "UPDATE homework_grades SET grade = $1 WHERE homework_id = $2 AND student_id = $3";
            pg_query_params($con, $updateQuery, [$grade, $homework_id, $student_id]);
        } else {

            $insertQuery = "INSERT INTO homework_grades (homework_id, student_id, grade) VALUES ($1, $2, $3)";
            pg_query_params($con, $insertQuery, [$homework_id, $student_id, $grade]);
        }
    }

    header("Location: grade_homeworks.php?course_id=$course_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Homeworks grades</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #6a11cb;
            color: white;
        }
        input.grade-input {
            width: 50px;
            text-align: center;
        }
        form.inline-form {
            margin: 0;
        }
        a.download {
            color: #007bff;
            text-decoration: none;
        }
        a.download:hover {
            text-decoration: underline;
        }
        .btn-back {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php include("navbar_teacher.php"); ?>

<h2>Homework grades - Course ID: <?= htmlspecialchars($course_id) ?></h2>

<?php if (empty($homeworks)): ?>
    <h1>There is no homework added for this course.</h1>
      <a href="view_teacher.php"><button class="btn-back">Back</button></a>
<?php elseif (empty($students)): ?>
    <h1>There are no students enrolled in this course.</h1>
      <a href="view_teacher.php"><button class="btn-back">Back</button></a>
<?php else: ?>
    <div class="profile-container">
        <h1>Grades</h1>
        <a href="view_teacher.php"><button class="btn-back">Back</button></a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <?php foreach ($homeworks as $hw): ?>
                    <th><?= htmlspecialchars($hw['title']) ?><br><small>(Due date: <?= date("d.m.Y H:i", strtotime($hw['due_date'])) ?>)</small></th>
                    <th>File</th>
                <?php endforeach; ?>
            </tr>
        </thead>
       <tbody>
    <?php foreach ($students as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
            <?php foreach ($homeworks as $hw): ?>
                <?php
                $queryGrade = "SELECT grade FROM homework_grades WHERE homework_id = $1 AND student_id = $2";
                $resGrade = pg_query_params($con, $queryGrade, [$hw['id'], $student['id']]);
                $gradeRow = pg_fetch_assoc($resGrade);
                $gradeVal = $gradeRow ? $gradeRow['grade'] : '';

                $queryFile = "SELECT file_path, submitted_at FROM homework_submissions WHERE homework_id = $1 AND student_id = $2 LIMIT 1";
                $resFile = pg_query_params($con, $queryFile, [$hw['id'], $student['id']]);
                $fileRow = pg_fetch_assoc($resFile);
                $filePath = $fileRow ? $fileRow['file_path'] : '';
                $submittedAt = $fileRow ? $fileRow['submitted_at'] : null;
                
                $isLate = false;
                if ($submittedAt && strtotime($submittedAt) > strtotime($hw['due_date'])) {
                    $isLate = true;
                }
                ?>
                <td>
                    <form class="inline-form" method="post" action="">
                        <input type="hidden" name="homework_id" value="<?= (int)$hw['id'] ?>">
                        <input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>">
                        <input type="text" name="grade" class="grade-input" maxlength="3" value="<?= htmlspecialchars($gradeVal) ?>">
                        <button type="submit">Save</button>
                    </form>
                </td>
                <td <?php if ($isLate) echo 'style="background-color:#ffe6e6; color:#cc0000; font-weight:bold;"'; ?>>
                    <?php if ($filePath): ?>
                        <a class="download" href="uploads/homeworks/<?= htmlspecialchars($filePath) ?>" download>Download</a>
                        <?php if ($isLate): ?>
                            <div style="color:#cc0000; font-weight:bold; font-size:0.85em;">⚠️ Submitted late</div>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</tbody>

    </table>
<?php endif; ?>

</body>
</html>
