<?php
session_start();
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login_teacher.php");
    exit;
}

$course_id = $_POST['course_id'] ?? $_GET['course_id'] ?? null;
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

$course_name = "Course";
$course_name_query = "SELECT course_name FROM courses WHERE id = $1";
$course_name_result = pg_query_params($con, $course_name_query, [$course_id]);
if ($row = pg_fetch_assoc($course_name_result)) {
    $course_name = $row['course_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_homework_id'])) {
    $hw_id = $_POST['delete_homework_id'];

$delete_submissions_query = "DELETE FROM homework_submissions WHERE homework_id = $1";
pg_query_params($con, $delete_submissions_query, [$hw_id]);

$delete_query = "DELETE FROM homeworks WHERE id = $1 AND course_id = $2";
$del_res = pg_query_params($con, $delete_query, [$hw_id, $course_id]);

    if ($del_res) {
        $message = "The homework was deleted successfully.";
    } else {
        $err = pg_last_error($con);
        $message = "Error while deleting.: $err";
    }
}

date_default_timezone_set('Europe/Sofia');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? null;

    if ($due_date) {
        $now = date('Y-m-d H:i:s');
        $due_date_check = date('Y-m-d H:i:s', strtotime($due_date));
        if ($due_date_check < $now) {
            $message = "The deadline cannot be in the past.";
        } else {
           
        }
    }

    if ($title) {
        $insert_query = "INSERT INTO homeworks (course_id, title, description, due_date) VALUES ($1, $2, $3, $4)";
        $res = pg_query_params($con, $insert_query, [$course_id, $title, $description, $due_date]);
        if ($res) {
            $message = "The homework has been added successfully.";
        } else {
            $message = "Error while adding.";
        }
    } else {
        $message = "The title is required.";
    }
}

$homeworks_query = "SELECT * FROM homeworks WHERE course_id = $1 ORDER BY due_date NULLS LAST, id DESC";
$homeworks_result = pg_query_params($con, $homeworks_query, [$course_id]);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Add homework</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #f7f7f7;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #4a4a4a;
        }

        label {
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="datetime-local"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            resize: vertical;
        }

        button.btn {
            background-color: #6a11cb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
        }

        button.btn:hover {
            background-color: #530ea4;
        }

        a.back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #6a11cb;
            text-decoration: none;
            font-weight: 600;
        }

        a.back-link:hover {
            text-decoration: underline;
        }
        p.message {
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .homework-list {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.05);
        }

        .homework-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
            font-size: 14px;
        }

        .homework-item:last-child {
            border-bottom: none;
        }

        .hw-title {
            font-weight: 600;
            color: #333;
        }

        .hw-date {
            color: #666;
            font-style: italic;
            margin-left: 15px;
        }

        .hw-actions form {
            display: inline;
            margin-left: 10px;
        }

        .hw-actions button {
            background: transparent;
            border: none;
            color: #d33;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }

        .hw-actions button:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
     <?php include("navbar_teacher.php"); ?>
    <div class="container">
        <h2>Adding homework for course: <?= htmlspecialchars($course_name) ?></h2>

        <?php if (!empty($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if (pg_num_rows($homeworks_result) > 0): ?>
            <div class="homework-list">
                <strong>Current homework assignments for the course:</strong>
                <?php while ($hw = pg_fetch_assoc($homeworks_result)): ?>
                    <div class="homework-item">
                        <span class="hw-title"><?= htmlspecialchars($hw['title']) ?></span>
                        <?php if (!empty($hw['due_date'])): ?>
                            <span class="hw-date">(due date: <?= date('d.m.Y H:i', strtotime($hw['due_date'])) ?>)</span>
                        <?php endif; ?>
                        <span class="hw-actions">
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this homework?');">
                                <input type="hidden" name="delete_homework_id" value="<?= $hw['id'] ?>">
                                <input type="hidden" name="course_id" value="<?= htmlspecialchars($course_id) ?>">
                                <button type="submit" title="Delete homework">Delete</button>
                            </form>
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="course_id" value="<?= htmlspecialchars($course_id) ?>">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="5"></textarea>

            <label for="due_date">Deadline:</label>
            <input type="datetime-local" id="due_date" name="due_date" min="<?= date('Y-m-d\TH:i') ?>">

            <button type="submit" class="btn">Add homework</button>
        </form>

        <a href="view_teacher.php" class="back-link">Back</a>
    </div>
</body>
</html>
