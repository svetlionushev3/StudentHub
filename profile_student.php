<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    header("Location: login_student.php");
    exit;
}

$egn = $_SESSION['student_egn'];
$query = "SELECT * FROM students WHERE egn = $1";
$result = pg_query_params($con, $query, [$egn]);


if (!$result) {
    echo "Error in the database query.";
    exit;
}

$student = pg_fetch_assoc($result);
if (!$student) {
    echo "Student not found.";
    exit;
}

$full_name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Student profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include("navbar_student.php"); ?>

<div class="profile-container"  style="max-width: 600px; margin-top: 100; padding: 20px;">
    <h2 style="text-align:center;">Your profile</h2>

    <?php
    $profilePicturePath = 'images/default_profile.png';
    if (!empty($student['profile_picture'])) {
        $profilePicturePath = 'uploads/' . htmlspecialchars($student['profile_picture']);
    }
    ?>

    <img id="preview" src="<?= $profilePicturePath . '?t=' . time() ?>" alt="profile picture" class="profile-picture">

    <form id="uploadForm" action="upload_student_image.php" method="post" enctype="multipart/form-data">
        <input type="file" name="profile_picture" id="fileInput" accept="image/*" style="display: none;">
        <button type="button" class="btn-change-pic" onclick="document.getElementById('fileInput').click();">Change profile picture</button>
    </form>

    <div class="profile-info" style="text-align:center; margin-top: 20px;">
        <p><strong>First name:</strong> <?= htmlspecialchars($student['first_name']) ?></p>
        <p><strong>Last name:</strong> <?= htmlspecialchars($student['last_name']) ?></p>
        <p><strong>Faculty number:</strong> <?= htmlspecialchars($student['faculty_number']) ?></p>
    </div>

    <div class="button-container" style="text-align:center; margin-top: 20px;">
        <a href="view_student.php" class="btn-secondary">Back</a>
    </div>
</div>

<script>
document.getElementById('fileInput').addEventListener('change', function () {
    if (this.files.length > 0) {
        document.getElementById('uploadForm').submit();
    }
});
</script>

</body>
</html>
