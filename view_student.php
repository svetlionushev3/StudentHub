<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    header("Location: login_student.php");
    exit;
}

$egn = $_SESSION['student_egn'];

$queryStudent = "SELECT * FROM students WHERE egn = $1";
$resultStudent = pg_query_params($con, $queryStudent, [$egn]);
$student = pg_fetch_assoc($resultStudent);
if (!$student) {
    echo "Student not found.";
    exit;
}

$queryCourses = "
    SELECT c.id, c.course_name, c.description
    FROM courses c
    JOIN student_courses sc ON sc.course_id = c.id
    WHERE sc.student_id = $1
";
$resultCourses = pg_query_params($con, $queryCourses, [$student['id']]);

$courses = [];
while ($row = pg_fetch_assoc($resultCourses)) {
    $courses[] = $row;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Courses and homeworks</title>
    <link rel="stylesheet" href="style.css">
    <style>
    html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    background-color: #f4f7fa;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

body {
    display: block; 
}

.container {
    max-width: 900px;
    margin: 80px auto; 
    background: white;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
        h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #2c3e50;
            font-weight: 700;
            letter-spacing: 1.2px;
        }
    .page-container {
    max-width: 900px;
    margin: 40px auto; 
    padding: 20px 30px;
}

.course-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    padding: 20px 25px;
    margin-bottom: 30px; 
}

        .course-title {
            font-size: 24px;
            color: #34495e;
            margin-bottom: 10px;
            font-weight: 700;
            border-bottom: 2px solid #6a11cb;
            padding-bottom: 8px;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .course-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
            font-style: italic;
        }
        .assignments-header {
            font-size: 20px;
            color: #6a11cb;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .assignment-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }
        .assignment-item {
            background: white;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        .assignment-title {
            font-weight: 700;
            font-size: 17px;
            color: #3d3d3d;
            margin-bottom: 6px;
        }
        .assignment-due-date {
            color: #a94442;
            font-style: italic;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .assignment-description {
            font-size: 15px;
            color: #555;
            white-space: pre-wrap;
        }
        .no-assignments {
            font-style: italic;
            color: #888;
        }
        .late-submission {
            background-color: #ffe6e6;
            border: 1px solid #cc0000;
            color: #cc0000;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .late-text {
            font-weight: 700;
            color: #cc0000;
            margin-top: 8px;
        }
        .upload-form {
            margin-top: 10px;
        }
        .upload-form input[type="file"] {
            margin-bottom: 8px;
        }
        .upload-form button {
            background: #6a11cb;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .upload-form button:hover {
            background: #4e0fb1;
        }
        .submission-info {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f4ff;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .submission-info a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
        }
        .submission-info small {
            color: #555;
        }
        .public-checkbox {
            margin-top: 8px;
        }
        .public-checkbox label {
            cursor: pointer;
            user-select: none;
        }
        .public-checkbox button {
            margin-left: 10px;
            background: #6a11cb;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .public-checkbox button:hover {
            background: #4e0fb1;
        }
        
        .lecture-list {
            list-style-type: disc;
            padding-left: 20px;
            margin-top: 15px;
        }
        .lecture-item {
            background: #e9f0fc;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            font-size: 15px;
            color: #2c3e50;
        }
        .lecture-item a {
            color: #34495e;
            font-weight: 600;
            text-decoration: none;
        }
        .lecture-item a:hover {
            text-decoration: underline;
        }
        .lecture-uploaded {
            font-size: 13px;
            color: #555;
            margin-top: 6px;
        }
        .grade-badge {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    font-weight: bold;
    font-size: 16px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: transform 0.2s, box-shadow 0.2s;
}

.grade-badge:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
    </style>
</head>
<body>

<?php include("navbar_student.php"); ?>

<div class="container">
    <h2 style="text-align:center;">Your courses</h2>

    <?php if (empty($courses)) : ?>
        <p class="no-assignments">You are not enrolled in any courses yet.</p>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <div class="course-title">
                    <span><?= htmlspecialchars($course['course_name']) ?></span>
                    <a href="chat.php?course_id=<?= (int)$course['id'] ?>" 
                       style="background: #6a11cb; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
                       CHAT
                    </a>
                </div>

                <div class="course-description"><?= htmlspecialchars($course['description']) ?></div>

                <?php
              

                $queryHomeworks = "SELECT * FROM homeworks WHERE course_id = $1 ORDER BY due_date";
                $resultHomeworks = pg_query_params($con, $queryHomeworks, [$course['id']]);
                $homeworks = [];
                while ($hw = pg_fetch_assoc($resultHomeworks)) {
                    $homeworks[] = $hw;
                }

               
                $queryLectures = "SELECT * FROM course_lectures WHERE course_id = $1 ORDER BY uploaded_at";
                $resultLectures = pg_query_params($con, $queryLectures, [$course['id']]);
                $lectures = [];
                while ($lecture = pg_fetch_assoc($resultLectures)) {
                    $lectures[] = $lecture;
                }
                ?>

                <?php if (!empty($lectures)): ?>
                    <div class="assignments-header">Lectures:</div>
                    <ul class="lecture-list">
                        <?php foreach ($lectures as $lecture): ?>
                            <li class="lecture-item">
                                <div><strong>Description:</strong> <?= htmlspecialchars($lecture['description']) ?></div>
                                <?php if (!empty($lecture['filename'])): ?>
                                    <div>
                                        <a href="uploads/course_lectures/<?= htmlspecialchars($lecture['filename']) ?>" target="_blank">
                                            View Lecture File
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="lecture-uploaded">
                                    Uploaded on: <?= date("d.m.Y H:i", strtotime($lecture['uploaded_at'])) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="assignments-header">Homeworks:</div>
                <?php if (empty($homeworks)): ?>
                    <p class="no-assignments">There are no assigned homeworks for this course.</p>
                <?php else: ?>
                    <ul class="assignment-list">
                        <?php foreach ($homeworks as $hw): ?>
                            <li class="assignment-item">
                                <div class="assignment-title"><?= htmlspecialchars($hw['title']) ?></div>
                                <?php if (!empty($hw['due_date'])): ?>
                                    <div class="assignment-due-date">Deadline: <?= date("d.m.Y H:i", strtotime($hw['due_date'])) ?></div>
                                <?php endif; ?>
                                <div class="assignment-description"><?= nl2br(htmlspecialchars($hw['description'])) ?></div>

                                <?php
                                    $querySubmission = "SELECT * FROM homework_submissions WHERE homework_id = $1 AND student_id = $2";
                                    $resultSubmission = pg_query_params($con, $querySubmission, [$hw['id'], $student['id']]);
                                    $submission = pg_fetch_assoc($resultSubmission);
?>

                                <?php if (!$submission): ?>
                                    <form class="upload-form" action="upload_homework.php" method="post" enctype="multipart/form-data">
                                      <input type="hidden" name="homework_id" value="<?= (int)$hw['id'] ?>">
                                      <input type="file" name="homework_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" required>
                                       <br>
                                        <button type="submit">Upload Homework</button>
                                    </form>
                                <?php endif; ?>


                                <?php
                                $querySubmission = "SELECT * FROM homework_submissions WHERE homework_id = $1 AND student_id = $2";
                                $resultSubmission = pg_query_params($con, $querySubmission, [$hw['id'], $student['id']]);
                                $submission = pg_fetch_assoc($resultSubmission);
                                ?>

                                <?php if ($submission): ?>
                                    <?php 
                                    $is_late = !empty($submission['submitted_at']) && !empty($hw['due_date']) && strtotime($submission['submitted_at']) > strtotime($hw['due_date']);

                                    $queryGrade = "SELECT grade FROM homework_grades WHERE homework_id = $1 AND student_id = $2";
                                    $resGrade = pg_query_params($con, $queryGrade, [$hw['id'], $student['id']]);
                                    $gradeRow = pg_fetch_assoc($resGrade);
                                    $gradeVal = $gradeRow ? $gradeRow['grade'] : null;
                                    $badgeColor = '#ccc';
                                    if ($gradeVal !== null) {
                                        if ($gradeVal <= 2) $badgeColor = '#e74c3c';
                                        elseif ($gradeVal == 3 || $gradeVal == 4) $badgeColor = '#3498db';
                                        elseif ($gradeVal >= 5) $badgeColor = '#2ecc71';
                                    }
                                    ?>
                                    <div class="submission-info <?= $is_late ? 'late-submission' : '' ?>">
                                        <strong>Uploaded Homework:</strong>
                                        <a href="uploads/homeworks/<?= htmlspecialchars($submission['file_path']) ?>" target="_blank">
                                            <?= htmlspecialchars($submission['file_path']) ?>
                                        </a><br>
                                        <small>Uploaded on: <?= date("d.m.Y H:i", strtotime($submission['submitted_at'])) ?></small><br>
                                        <?php if ($is_late): ?>
                                            <div class="late-text">⚠️ Uploaded after the deadline!</div>
                                        <?php endif; ?>
                                        <div class="grade-badge" style="background-color:<?= $badgeColor ?>;">
                                            <?= $gradeVal !== null ? htmlspecialchars($gradeVal) : '-' ?>
                                        </div>
                                        <form method="post" action="update_visibility.php" class="public-checkbox">
    <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
    <label>
        <input type="checkbox" name="is_public" value="1" 
        <?= isset($submission['is_public']) && $submission['is_public'] === 't' ? 'checked' : '' ?>>
        Public for other students
    </label>
    <button type="submit">Save</button>
</form>
                               
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top:10px; font-style:italic; color:#555;">
                                        Not graded yet
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <a href="public_homeworks.php" 
       style="display: inline-block; background: #6a11cb; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.3s ease;">
       View all students public homework submissions
    </a>
</div>
</div>

</body>
</html>
