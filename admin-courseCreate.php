<?php
session_start();
include("connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$error = "";
$success = "";

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $delete_submissions_query = "DELETE FROM homework_submissions WHERE homework_id IN (SELECT id FROM homeworks WHERE course_id = $1)";
    pg_query_params($con, $delete_submissions_query, [$delete_id]);

    $delete_homeworks_query = "DELETE FROM homeworks WHERE course_id = $1";
    pg_query_params($con, $delete_homeworks_query, [$delete_id]);

    pg_query_params($con, "DELETE FROM student_courses WHERE course_id = $1", [$delete_id]);

    pg_query_params($con, "DELETE FROM teacher_courses WHERE course_id = $1", [$delete_id]);

    $del_query = "DELETE FROM courses WHERE id = $1";
    $del_result = pg_query_params($con, $del_query, [$delete_id]);

    if ($del_result) {
        $success = "The course has been deleted successfully.";
    } else {
        $error = "An error occurred while deleting the course.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = trim($_POST['course_name']);

    if (empty($course_name)) {
        $error = "Please enter a course name.";
        
    } else {

$check_query = "SELECT * FROM courses WHERE LOWER(course_name) = LOWER($1)";
$check_result = pg_query_params($con, $check_query, [$course_name]);

if (pg_num_rows($check_result) > 0) {
    $error = "A course with this name already exists.";
} else {
    $query = "INSERT INTO courses (course_name) VALUES ($1)";
    $result = pg_query_params($con, $query, [$course_name]);

    if ($result) {
        $success = "The course has been added successfully!";
    } else {
        $error = "An error occurred while saving the course.";
        }
    }     
    }
}

$course_result = pg_query($con, "SELECT * FROM courses ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Courses</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { 
            max-width: 600px; 
            margin: auto; padding: 20px; 
        }
        .success { 
            background-color: #d4edda; 
            color: #155724; 
            padding: 10px;
            margin: 10px 0; 
            border-radius: 5px; 
        }
        .error { 
            background-color: #f8d7da; 
            color: #721c24; 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 5px;
         }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            padding: 10px; 
            border: 1px solid #ccc; 
            text-align: left;
         }
        th { 
            background-color: #f0f0f0;
         }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body class="courses-page">
    <div class="container">
        <h2>Create Course</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        

        <form method="post">
            <input type="text" name="course_name" placeholder="Course name" required>
            <input type="submit" value="Add course">
        </form>
        

        <h3>Existing Courses:</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Course name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while ($row = pg_fetch_assoc($course_result)) {
                    echo "<tr>
                            <td>{$i}</td>
                            <td>" . htmlspecialchars($row['course_name']) . "</td>
                            <td>
                                <a href='?delete_id={$row['id']}' onclick='return confirm(\"Are you sure you want to delete this course?\")'>
                                    <button class='btn-delete'>Delete</button>
                                </a>
                            </td>
                          </tr>";
                    $i++;
                }
                ?>
            </tbody>
        </table>

        <br>
        <a href="view_admin.php"><button class="btn-back">⬅ Back</button></a>
    </div>
</body>
</html>
