<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    header("Location: login_student.php");
    exit;
}

$egn = $_SESSION['student_egn'];
$message = "";

$query = "SELECT * FROM students WHERE egn = $1";
$result = pg_query_params($con, $query, [$egn]);
$student = pg_fetch_assoc($result);

if (!$student) {
    $message = "An error occurred while finding the profile.";
    $full_name = "Student";
} else {
    $full_name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $student['password'])) {
        $message = "Invalid current password.";
    } elseif ($new !== $confirm) {
        $message = "The new password does not match the confirmation.";
    } else {
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = "UPDATE students SET password = $1 WHERE id = $2";
        pg_query_params($con, $update, [$new_hashed, $student['id']]);
        $message = "✅ The password was changed successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f4f6f8;
        }

        .container {
            max-width: 500px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 18px;
            background-color: #6a11cb;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            background: #eaf4ff;
            border: 1px solid #b3d4fc;
            border-radius: 5px;
            color: #333;
        }

        a {
            display: inline-block;
            margin-top: 10px;
            color: #6a11cb;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="navbar">
        <div class="navbar-left">
            <img src="logo2.png" alt="logo" class="navbar-logo">
        </div>
        <div class="navbar-right">
        <img src="profile.png" alt="Profile picture" class="profile-img">
            <div class="dropdown">
                <button class="dropbtn"><?php echo $full_name; ?> &#x25BC;</button>
                <div class="dropdown-content">
                    <a href="profile_student.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

<div class="container">
    <h2>Change password</h2>

    <?php if (!empty($message)) : ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="current_password">Current password:</label>
        <input type="password" name="current_password" required>

        <label for="new_password">New password:</label>
        <input type="password" name="new_password" required>

        <label for="confirm_password">Confirm new password:</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Change password</button>
    </form>

    <a href="view_student_profile.php">⬅ Back</a>
</div>
</body>
</html>
