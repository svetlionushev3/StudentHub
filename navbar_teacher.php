<?php
if (!isset($teacher)) {

    $email = $_SESSION['teacher_email'] ?? '';
    $query = "SELECT * FROM teachers WHERE email = $1";
    $result = pg_query_params($con, $query, [$email]);
    $teacher = pg_fetch_assoc($result);
}

$profilePicture = !empty($teacher['profile_picture']) 
    ? 'uploads/' . htmlspecialchars($teacher['profile_picture']) 
    : 'images/default_profile.png';

$full_name = htmlspecialchars($teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']);
?>

<div class="navbar">
    <div class="navbar-left">
        <a href="view_teacher.php"><img src="images/logo2.png" alt="logo" class="navbar-logo"></a>
    </div>
    <div class="navbar-right">
        <img src="<?= $profilePicture ?>" alt="Profile picture" class="profile-img" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
        <div class="dropdown">
            <button class="dropbtn"><?= $full_name ?> &#x25BC;</button>
            <div class="dropdown-content">
                <a href="profile_teacher.php">Profile</a>
                <a href="logout_teacher.php">Logout</a>
            </div>
        </div>
    </div>
</div>
