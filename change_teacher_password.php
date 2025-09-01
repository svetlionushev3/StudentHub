<?php
session_start();
include("connection.php");

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login_teacher.php");
    exit;
}

$email = $_SESSION['teacher_email'];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $query = "SELECT password FROM teachers WHERE email = $1";
    $result = pg_query_params($con, $query, [$_SESSION['teacher_email']]);
    $teacher = pg_fetch_assoc($result);

    if (!$teacher) {
        $error = "User not found.";
    } elseif (!password_verify($current_password, $teacher['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "The new password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE teachers SET password = $1, plain_password = NULL WHERE email = $2";
        $update_result = pg_query_params($con, $update_query, [$hashed, $_SESSION['teacher_email']]);

        if ($update_result) {
            $success = "Password changed successfully.";
        } else {
            $error = "An error occurred while changing the password.";
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Change password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login">
  <div class="center">
    <h1>CHANGE PASSWORD</h1>

    <?php if (!empty($error)): ?>
      <p style="color:rgb(216, 0, 0); font-weight: 600;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success)): ?>
      <p style="color: #7fff7f; font-weight: 600;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="txt_field">
        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" required>
      </div>
      <div class="txt_field">
        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" required>
      </div>
      <div class="txt_field">
        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>
      <div class="button-container">
      <button type="submit">Change password</button>
      <button type="button" onclick="location.href='profile_teacher.php'">Back</button>
    </div>
    </form>
  </div>
</body>
</html>
