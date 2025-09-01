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
    echo "The student was not found.";
    exit;
}

$student_id = $student['id'];

$query = "
    SELECT 
    hs.id, 
    hs.file_path, 
    hs.submitted_at, 
    s.first_name, 
    s.last_name, 
    h.title AS homework_title, 
    c.course_name,
    h.due_date,
    hg.grade
FROM homework_submissions hs
JOIN students s ON hs.student_id = s.id
JOIN homeworks h ON hs.homework_id = h.id
JOIN courses c ON h.course_id = c.id
LEFT JOIN homework_grades hg ON hg.homework_id = hs.homework_id AND hg.student_id = hs.student_id
WHERE hs.is_public = TRUE
ORDER BY hs.submitted_at DESC
";

$result = pg_query($con, $query);


$result = pg_query($con, $query);  

if (!$result) {
    die("Error with the request: " . pg_last_error($con));
}

?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Public homeworks</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
        a.download {
            color: #007bff;
            text-decoration: none;
        }
        a.download:hover {
            text-decoration: underline;
        }
        .late {
    background-color: #ffe6e6;
    color: #cc0000;
    font-weight: bold;
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
a.download {
    display: inline-block;
    padding: 8px 16px;
    background-color: #3498db; 
    color: white;
    font-weight: bold;
    text-decoration: none;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.course-name {
    font-weight: bold;       
}

a.download:hover {
    background-color: #2980b9; 
    transform: translateY(-2px);
    box-shadow: 0 6px 10px rgba(0,0,0,0.2);
}

a.download:active {
    transform: translateY(0);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

    </style>
<body>
<?php include("navbar_student.php"); ?>
<div class="profile-container">
<h1>Public homeworks</h1>
 <a href="view_student.php"><button class="btn-back">Back</button></a>
</div>
<?php if (pg_num_rows($result) > 0): ?>
   <table class="styled-table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Homework title</th>
                <th>Student</th>
                <th>Upload date</th>
                <th>Grade</th>
                <th>File</th>
            </tr>   
        </thead>
        <tbody>
            
          <?php while ($row = pg_fetch_assoc($result)): 
    $late = strtotime($row['submitted_at']) > strtotime($row['due_date']);
?>
    <tr>
       <td class="course-name"><?php echo htmlspecialchars($row['course_name']); ?></td>
        <td><?php echo htmlspecialchars($row['homework_title']); ?></td>
        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
       <td 
    <?php if ($late) echo 'style="color: #cc0000; background-color: #ffecec; border: 1px solid #f5c6cb; padding: 4px; border-radius: 4px;"'; ?>
>
    <?= date("d.m.Y H:i:s", strtotime($row['submitted_at'])) ?>
    <?php if ($late): ?>
        <strong> ⚠️ Submitted late</strong>
    <?php endif; ?>
</td>

      <?php
$gradeVal = $row['grade'];
$badgeColor = '#ccc'; 
if ($gradeVal !== null) {
    if ($gradeVal <= 2) {
        $badgeColor = '#e74c3c'; 
    } elseif ($gradeVal == 3 || $gradeVal == 4) {
        $badgeColor = '#3498db'; 
    } elseif ($gradeVal >= 5) {
        $badgeColor = '#2ecc71'; 
    }
}
?>

    <?php
$gradeVal = $row['grade'];
$badgeColor = '#ccc'; 
if ($gradeVal !== null) {
    if ($gradeVal <= 2) {
        $badgeColor = '#e74c3c'; 
    } elseif ($gradeVal == 3 || $gradeVal == 4) {
        $badgeColor = '#3498db'; 
    } elseif ($gradeVal >= 5) {
        $badgeColor = '#2ecc71'; 
    }
}
?>
<td>
    <div class="grade-badge" style="background-color:<?= $badgeColor ?>;">
        <?= $gradeVal !== null ? htmlspecialchars($gradeVal) : '-' ?>
    </div>
</td>

</td>

        <td><a class="download" href="uploads/homeworks/<?php echo htmlspecialchars($row['file_path']); ?>" download>Download</a></td>
    </tr>
<?php endwhile; ?>


        </tbody>
    </table>
<?php else: ?>
    <p>There are no public homework uploaded by other students.</p>
<?php endif; ?>

</body>
</html>
