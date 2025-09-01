<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-dashboard">
  <div class="dashboard-panel">
    <h1>Admin Dashboard</h1>

    <div class="button-container">
      <button onclick="location.href='admin-teacherList.php'">Teachers</button>
      <button onclick="location.href='admin-studentList.php'">Students</button>
      <button onclick="location.href='admin-courseCreate.php'">Courses</button>
    </div>
  </div>
</body>

</html>
