<?php
session_start();
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login_teacher.php");
    exit;
}

$email = $_SESSION['teacher_email'];
$query = "SELECT * FROM teachers WHERE email = $1";
$result = pg_query_params($con, $query, [$email]);

if (!$result) {
    echo "Error in the database query.";
    exit;
}

$teacher = pg_fetch_assoc($result);
if (!$teacher) {
    echo "Teacher not found.";
    exit;
}
?>


<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Teacher profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="profile-container" style="max-width: 600px; margin-top: 100; padding: 20px;">
<?php include("navbar_teacher.php"); ?>
    <h2 style="text-align:center;">Your profile</h2>
<?php
$profilePicturePath = 'images/default_profile.png'; 
if (!empty($teacher['profile_picture'])) {
    $profilePicturePath = 'uploads/' . htmlspecialchars($teacher['profile_picture']);
}
?>

<img id="preview" src="<?= $profilePicturePath . '?t=' . time() ?>" alt="Profile picture" class="profile-picture">



<form id="uploadForm" action="upload_teacher_image.php" method="post" enctype="multipart/form-data">
    <input type="file" name="profile_picture" id="fileInput" accept="image/*" style="display: none;">
    <button type="button" class="btn-change-pic" onclick="document.getElementById('fileInput').click();">Change profile picture</button>
</form>


    <div class="profile-info" style="text-align:center; margin-top: 20px;">
        <p><strong>First name:</strong> <?= htmlspecialchars($teacher['first_name']) ?></p>
        <p><strong>Last name:</strong> <?= htmlspecialchars($teacher['last_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email']) ?></p>
    </div>

    <div class="button-container" style="text-align:center; margin-top: 20px;">
        <a href="change_teacher_password.php" class="btn-primary" style="margin-right: 10px;">Change password</a>
        <a href="view_teacher.php" class="btn-secondary">Back</a>
    </div>
</div>

<script>

   document.getElementById('fileInput').addEventListener('change', function() {
    if(this.files.length > 0) {
        document.getElementById('uploadForm').submit();
    }
});

</script>

</body>
</html>
