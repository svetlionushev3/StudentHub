<?php
session_start();
include("connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $del_courses_query = "DELETE FROM student_courses WHERE student_id = $1";
    $del_courses_result = pg_query_params($con, $del_courses_query, [$delete_id]);

    $del_homework_query = "DELETE FROM homework_submissions WHERE student_id = $1";
    $del_homework_result = pg_query_params($con, $del_homework_query, [$delete_id]);

    if ($del_courses_result && $del_homework_result) {

        $del_student_query = "DELETE FROM students WHERE id = $1";
        $del_student_result = pg_query_params($con, $del_student_query, [$delete_id]);

        if ($del_student_result) {
            header("Location: admin-studentList.php");
            exit;
        } else {
            $error = "An error occurred while deleting the student.";
        }
    } else {
        $error = "An error occurred while deleting the student's related records.";
    }
}



$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $faculty_number = trim($_POST['faculty_number']);
    $egn = trim($_POST['egn']);

    if (empty($first_name) || empty($last_name) || empty($faculty_number) || empty($egn)) {
        $error = "Please fill in all the fields.";
    } elseif (!preg_match('/^[а-яА-Яa-zA-Z]+$/u', $first_name)) {
        $error = "The name must contain only letters.";
    } elseif (!preg_match('/^[а-яА-Яa-zA-Z]+$/u', $last_name)) {
        $error = "The surname must contain only letters.";
    } elseif (!preg_match('/^\d{9}$/', $faculty_number)) {
        $error = "The faculty number must contain exactly 9 digits.";
    } elseif (!preg_match('/^\d{10}$/', $egn)) {
        $error = "The Personal ID number must contain exactly 10 digits.";
    } else {
        $query = "INSERT INTO students (first_name, last_name, faculty_number, egn) VALUES ($1, $2, $3, $4)";
        $result = pg_query_params($con, $query, [$first_name, $last_name, $faculty_number, $egn]);

        if ($result) {
            header("Location: admin-studentList.php");
            exit;
        } else {
            $error = "An error occurred while saving to the database.";
        }
    }
}

$result = pg_query($con, "SELECT * FROM students ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Students</title>
    <button class="btn-back" onclick="window.location.href='view_admin.php'">Back</button>
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-students">

    <h1 class="page-title">Students</h1>

    <div class="top-bar">
        <button class="btn" onclick="toggleForm()">➕ Create Student Account</button>
    </div>

    <?php if ($error): ?>
        <div class="error-popup" id="errorPopup" style="background: #f44336; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
            <button onclick="document.getElementById('errorPopup').style.display='none'" style="float:right; background:none; border:none; color:white; font-weight:bold; font-size:16px; cursor:pointer;">&times;</button>
        </div>
    <?php endif; ?>

    <div id="studentForm" class="form-container <?php if (!$error) echo 'hidden'; ?>">
    <form action="admin-studentList.php" method="post">
        <input type="text" name="first_name" placeholder="First Name" required value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
        <input type="text" name="last_name" placeholder="Last Name" required value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
        <input type="text" name="faculty_number" placeholder="Faculty Number" required value="<?= isset($_POST['faculty_number']) ? htmlspecialchars($_POST['faculty_number']) : '' ?>">
        <input type="text" name="egn" placeholder="EGN" required value="<?= isset($_POST['egn']) ? htmlspecialchars($_POST['egn']) : '' ?>">
        <input type="submit" value="Create Account" class="btn">
    </form>
</div>
    <table class="styled-table">
        <thead>
            <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Faculty Number</th>
                <th>EGN</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            while ($row = pg_fetch_assoc($result)) {
                echo "<tr>
                        <td>$i</td>
                        <td>" . htmlspecialchars($row['first_name']) . "</td>
                        <td>" . htmlspecialchars($row['last_name']) . "</td>
                        <td>" . htmlspecialchars($row['faculty_number']) . "</td>
                        <td>" . htmlspecialchars($row['egn']) . "</td>
                        <td>
                            <a href='admin-studentList.php?delete_id={$row['id']}' onclick='return confirm(\"Сигурни ли сте, че искате да изтриете този студент?\");'>
                               <button class='btn-delete'>Delete</button>
                            </a>
                        </td>
                      </tr>";
                $i++;
            }
            ?>
        </tbody>
    </table>

<script>
function toggleForm() {
    const form = document.getElementById('studentForm');
    form.classList.toggle('hidden');
}
</script>

</body>
</html>
