<?php
if (!isset($student)) {

    $egn = $_SESSION['student_egn'] ?? '';
    $query = "SELECT * FROM students WHERE egn = $1";
    $result = pg_query_params($con, $query, [$egn]);
    $student = pg_fetch_assoc($result);
}

$profilePicture = !empty($student['profile_picture']) 
    ? 'uploads/' . htmlspecialchars($student['profile_picture']) 
    : 'images/default_profile.png';

$full_name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
?>

<div class="navbar">
    <div class="navbar-left">
        <a href="view_student.php"><img src="images/logo2.png" alt="logo" class="navbar-logo"></a>
    </div>
    <div class="navbar-right">
        <img src="<?= $profilePicture ?>" alt="Profile picture" class="profile-img" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
        <div class="dropdown">
            <button class="dropbtn"><?= $full_name ?> &#x25BC;</button>
            <div class="dropdown-content">
                <a href="profile_student.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>
