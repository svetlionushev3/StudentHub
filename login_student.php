	<?php 
		session_start();
		include("connection.php");

		if ($_SERVER['REQUEST_METHOD'] == "POST") {
			$faculty_number = $_POST['faculty_number']??'';
			$egn = $_POST['egn']?? '';

			if (!empty($faculty_number) && !empty($egn)) {
				$query = "SELECT * FROM students WHERE faculty_number = $1 AND egn = $2 LIMIT 1";
				$result = pg_query_params($con, $query, array($faculty_number, $egn));

				if ($result && pg_num_rows($result) > 0) {
					$user_data = pg_fetch_assoc($result);
					$_SESSION['student_egn'] = $user_data['egn']; 
					header("Location: view_student.php");
					die;
				} else {
					$error = "Wrong Faculty number or EGN!";
				}
			} else {
				$error = "Please fill in all the fields.";
			}
		}
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<title>Student Log in</title>
		
		<link rel="stylesheet" href="style.css">
	</head>
	<body class="login">
		
		<div class="center">
		<img src="images/logo.png" alt="Лого" class="logo">
			<h1>Student</h1>
			<form method="post">
				<div class="txt_field">
					<label>Faculty Number</label>
					<input type="text" name="faculty_number" required>
				</div>
				<div class="txt_field">
					<label>EGN</label>
					<input type="text" name="egn" required>
				</div>
				<input type="submit" value="Log in">
				<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
			</form>
		</div>
	</body>
	</html>