<?php
session_start();
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $query = "SELECT * FROM admins WHERE username = $1 LIMIT 1";
        $result = pg_query_params($con, $query, [$username]);

        if ($result && pg_num_rows($result) > 0) {
            $admin_data = pg_fetch_assoc($result);

            if (password_verify($password, $admin_data['password'])) {
                $_SESSION['admin_id'] = $admin_data['id'];  
                header("Location: view_admin.php");
                exit;
            } else {
                $error = "Wrong username or password!";
            }
        } else {
            $error = "Wrong username or password!";
        }
    } else {
        $error = "Please fill in all the fields.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Log in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login"> 
    <div class="center">
        <img src="images/logo.png" alt="Logo" class="logo">
        <h1>Admin</h1>
        <form method="post">
            <div class="txt_field">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="txt_field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <input type="submit" value="Log in">
            
            <?php if (isset($error)): ?>
                <div class="error-popup" style="margin-top:10px;">
                    <?= htmlspecialchars($error) ?>
                    <button onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>
            
        </form>
    </div>
</body>
</html>
