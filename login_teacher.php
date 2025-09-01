<?php 
session_start();
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $query = "SELECT * FROM teachers WHERE email = $1 LIMIT 1";
        $result = pg_query_params($con, $query, [$email]);

        if ($result && pg_num_rows($result) > 0) {
            $teacher_data = pg_fetch_assoc($result);
            $is_valid = false;
           
            if (!empty($teacher_data['plain_password']) && $password === $teacher_data['plain_password']) {
                $is_valid = true;
            }
            if (!$is_valid && password_verify($password, $teacher_data['password'])) {
                $is_valid = true;
            }
            if ($is_valid) {
                $_SESSION['teacher_id'] = $teacher_data['id'];
                $_SESSION['teacher_email'] = $teacher_data['email']; 
                header("Location: view_teacher.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Log in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login">

<div class="center">
    <img src="images/logo.png" alt="Logo" class="logo">
    <h1>Teacher</h1>
    <form method="post">
        <div class="txt_field">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="txt_field">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <input type="submit" value="Log in">
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    </form>
</div>

</body>
</html>
