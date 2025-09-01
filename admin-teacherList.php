<?php
session_start();
include("connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

$error = "";
$new_password_display = "";
$success = "";

if (isset($_GET['reset_id'])) {
    $teacher_id = intval($_GET['reset_id']);
    $new_password = generatePassword();
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $query = "UPDATE teachers SET password = $1, plain_password = $2 WHERE id = $3";
    $result = pg_query_params($con, $query, [$hashed_password, $new_password, $teacher_id]);

    if ($result) {
        $new_password_display = "Password has been reset successfully.";
    } else {
        $error = "An error occurred while resetting password: " . pg_last_error($con);
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_teacher'])) {
    $title = $_POST['title'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = generatePassword();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $email = strtolower($first_name . $last_name . '@student-hub.com');

    if (empty($title) || empty($first_name) || empty($last_name)) {
        $error = "Please fill in all the fields.";
    } elseif (!preg_match('/^[а-яА-Яa-zA-Z]+$/u', $first_name)) {
        $error = "The first name must contain only letters.";
    } elseif (!preg_match('/^[а-яА-Яa-zA-Z]+$/u', $last_name)) {
        $error = "The last name must contain only letters.";
    } else {
        $query = "INSERT INTO teachers (title, first_name, last_name, email, password, plain_password)
                  VALUES ($1, $2, $3, $4, $5, $6)";
        $result = pg_query_params($con, $query, [$title, $first_name, $last_name, $email, $hashed_password, $password]);

        if ($result) {
            $new_password_display = "Teacher created successfully.";
        } else {
            $error = "Database write error.";
        }
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_query = "DELETE FROM teachers WHERE id = $1";
    $del_result = pg_query_params($con, $del_query, [$delete_id]);
    if ($del_result) {
        $_SESSION['form_message'] = "Teacher deleted successfully.";
        header("Location: admin-teacherList.php");
        exit;
    } else {
        $error = "An error occurred while deleting the teacher.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_course'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $course_id = intval($_POST['course_id']);
    
    $check_query = "SELECT * FROM teacher_courses WHERE teacher_id = $1 AND course_id = $2";
    $check_result = pg_query_params($con, $check_query, [$teacher_id, $course_id]);

    if (pg_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO teacher_courses (teacher_id, course_id) VALUES ($1, $2)";
        pg_query_params($con, $insert_query, [$teacher_id, $course_id]);
    }

    header("Location: admin-teacherList.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_course'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $course_id = intval($_POST['course_id']);

    $delete_query = "DELETE FROM teacher_courses WHERE teacher_id = $1 AND course_id = $2";
    pg_query_params($con, $delete_query, [$teacher_id, $course_id]);

    header("Location: admin-teacherList.php");
    exit;
}

$result = pg_query($con, "SELECT * FROM teachers ORDER BY id ASC");
$teachers = [];
while ($row = pg_fetch_assoc($result)) {
    $teachers[] = $row;
}

$course_result = pg_query($con, "SELECT * FROM courses ORDER BY course_name ASC");
$courses = [];
while ($row = pg_fetch_assoc($course_result)) {
    $courses[] = $row;
}

function getTeacherCourses($con, $teacher_id) {
    $query = "SELECT c.course_name, c.id FROM courses c 
              JOIN teacher_courses tc ON c.id = tc.course_id
              WHERE tc.teacher_id = $1";
    $result = pg_query_params($con, $query, [$teacher_id]);
    $courses = [];
    while ($row = pg_fetch_assoc($result)) {
        $courses[] = $row;
    }
    return $courses;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teachers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-teachers">

<h2 class="page-title">Teachers</h2>
<a href="view_admin.php"><button class="btn-back">Back</button></a>

<?php if ($error): ?>
    <div class="error-popup">
        <?= htmlspecialchars($error) ?>
        <button onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<?php if ($new_password_display): ?>
    <div class="error-popup success">
        <?= htmlspecialchars($new_password_display) ?>
        <button onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>


<?php if (isset($_SESSION['form_message'])): ?>
    <div class="error-popup" style="background:#7fff7f; color:black;" id="deletePopup">
        <?= htmlspecialchars($_SESSION['form_message']) ?>
        <button onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    <?php unset($_SESSION['form_message']); ?>
<?php endif; ?>

<div class="top-bar">
    <button class="btn" onclick="toggleForm()">➕ Create Teacher Account</button>
</div>

<div id="teacherForm" class="form-container <?= $error || $success ? '' : 'hidden' ?>">
    <form action="admin-teacherList.php" method="post">
        <input type="hidden" name="create_teacher" value="1">

        <select name="title" required>
            <option value="">-- Select rank --</option>
            <option value="Asst.">Asst.</option>
            <option value="Sr. Asst. Prof. Dr.">Sr. Asst. Prof. Dr.</option>
            <option value="Assoc. Prof. Dr.">Assoc. Prof. Dr.</option>
            <option value="Prof. Dr.">Prof. Dr.</option>
        </select>

        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>

        <?php if ($error): ?>
            <div class="error-popup" id="errorPopup" style="margin-top: 10px;">
                <?= htmlspecialchars($error) ?>
                <button onclick="document.getElementById('errorPopup').style.display='none'" style="float:right; background:none; border:none; color:white; font-weight:bold;">&times;</button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="error-popup" style="background:#7fff7f; color:black;" id="successPopup">
                <?= htmlspecialchars($success) ?>
                <button onclick="document.getElementById('successPopup').style.display='none'" style="float:right; background:none; border:none; color:black; font-weight:bold;">&times;</button>
            </div>
        <?php endif; ?>

        <input type="submit" value="Create Account" class="btn">
    </form>
</div>


<table class="styled-table">
    <thead>
    <tr>
        <th>#</th>
        <th>Title</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Password</th>
        <th>Assigned Courses</th>
        <th>Assign Course</th>
        <th>Reset Password</th>
        <th>Action</th>
    </tr>
    </thead>

    <tbody>
    <?php $i = 1; foreach ($teachers as $teacher): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($teacher['title']) ?></td>
            <td><?= htmlspecialchars($teacher['first_name']) ?></td>
            <td><?= htmlspecialchars($teacher['last_name']) ?></td>
            <td><?= htmlspecialchars($teacher['email']) ?></td>
            <td>
                <?php 
                if ($teacher['plain_password']) {
                    echo htmlspecialchars($teacher['plain_password']);
                } else {
                    echo "<em>Hidden</em>";
                }
                ?>
            </td>
            <td>
                   <?php
$assigned = getTeacherCourses($con, $teacher['id']);
if ($assigned) {
    $query = "SELECT c.id, c.course_name FROM courses c
              JOIN teacher_courses tc ON c.id = tc.course_id
              WHERE tc.teacher_id = $1";
    $res = pg_query_params($con, $query, [$teacher['id']]);
    while ($row = pg_fetch_assoc($res)) {
        echo '<div style="margin-bottom: 4px;">' .
                htmlspecialchars($row['course_name']) .
                '<form method="post" style="display:inline;" onsubmit="return confirm(\'re you sure you want to remove this course from the teacher?\');">
                    <input type="hidden" name="remove_course" value="1">
                    <input type="hidden" name="teacher_id" value="' . $teacher['id'] . '">
                    <input type="hidden" name="course_id" value="' . $row['id'] . '">
                    <button type="submit" style="background:red; color:white; border:none; padding:2px 6px; margin-left:5px;">–</button>
                </form>
             </div>';
     }
} else {
    echo "<em>None</em>";
}
?>
            </td>
            <td>
                <form method="post">
                    <input type="hidden" name="assign_course" value="1">
                    <input type="hidden" name="teacher_id" value="<?= $teacher['id'] ?>">
                    <select name="course_id" required>
                        <option value="">-- Choose course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">➕</button>
                </form>
            </td>
            <td>
                <a href="admin-teacherList.php?reset_id=<?= $teacher['id'] ?>" onclick="return confirm('Reset password?')">
                    <button class="btn-reset">Reset</button>
                </a>
            </td>
            <td>
                <a href="admin-teacherList.php?delete_id=<?= $teacher['id'] ?>" onclick="return confirm('Delete teacher?')">
                    <button class="btn-delete">Delete</button>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
    function toggleForm() {
        const form = document.getElementById('teacherForm');
        form.classList.toggle('hidden');
    }
</script>

</body>
</html>
