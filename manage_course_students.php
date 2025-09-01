    <?php
    session_start();
    include("connection.php");

    if (!isset($_SESSION['teacher_email'])) {
        header("Location: login_teacher.php");
        exit;
    }

    $course_id = $_GET['course_id'] ?? null;
    if (!$course_id) {
        die("No course selected.");
    }

    $email = $_SESSION['teacher_email'];
    $query = "SELECT * FROM teachers WHERE email = $1";
    $result = pg_query_params($con, $query, [$email]);
    $teacher = pg_fetch_assoc($result);
    if (!$teacher) {
        die("Teacher not found.");
    }

    $check_query = "SELECT * FROM teacher_courses WHERE teacher_id = $1 AND course_id = $2";
    $check_res = pg_query_params($con, $check_query, [$teacher['id'], $course_id]);
    if (pg_num_rows($check_res) == 0) {
        die("You do not have access to this course.");
    }

    $course_name = "Course";
    $course_name_query = "SELECT course_name FROM courses WHERE id = $1";
    $course_name_result = pg_query_params($con, $course_name_query, [$course_id]);
    if ($row = pg_fetch_assoc($course_name_result)) {
        $course_name = $row['course_name'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        $exists_query = "SELECT * FROM student_courses WHERE student_id = $1 AND course_id = $2";
        $exists_res = pg_query_params($con, $exists_query, [$student_id, $course_id]);
        if (pg_num_rows($exists_res) == 0) {
            $insert_query = "INSERT INTO student_courses (student_id, course_id) VALUES ($1, $2)";
            pg_query_params($con, $insert_query, [$student_id, $course_id]);
            $message = "✅ The student has been added successfully.";
        } else {
            $message = "⚠️ The student is already enrolled in the course.";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_student_id'])) {
        $remove_id = $_POST['remove_student_id'];
        $delete_query = "DELETE FROM student_courses WHERE student_id = $1 AND course_id = $2";
        pg_query_params($con, $delete_query, [$remove_id, $course_id]);
        $message = "🗑️ The student has been removed from the course.";
    }

    $students_query = "SELECT * FROM students ORDER BY last_name, first_name";
    $students_result = pg_query($con, $students_query);

    $current_students_query = "
        SELECT s.*
        FROM students s
        JOIN student_courses sc ON s.id = sc.student_id
        WHERE sc.course_id = $1
        ORDER BY s.last_name, s.first_name
    ";
    $current_students_result = pg_query_params($con, $current_students_query, [$course_id]);
    ?>

    <!DOCTYPE html>
    <html lang="bg">
    <head>
        <meta charset="UTF-8">
        <title>Student Management</title>
        <link rel="stylesheet" href="style.css">
        <style>
            body {
                font-family: "Segoe UI", sans-serif;
                background-color: #f4f6f8;
                margin: 0;
            }

            .container {
                max-width: 900px;
                margin: 60px auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }

            h2, h3 {
                color: #333;
            }

            .message {
                padding: 10px;
                margin-bottom: 20px;
                 color: #155724;
                background-color: #d4edda;
               border: 1px solid #c3e6cb;
                color: #333;
                border-radius: 5px;
            }

            
            select, input[type="text"] {
                padding: 10px;
                border-radius: 5px;
                border: 1px solid #ccc;
                width: 250px;
                margin-bottom: 10px;
            }

            button {
                padding: 10px 18px;
                background-color: #6a11cb;
                color: white;
                border: none;
                border-radius: 5px;
                font-weight: bold;
                cursor: pointer;
                transition: 0.3s;
            }

            button:hover {
                background-color: #530ea4;
            }

            .student-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #eee;
                padding: 8px 0;
            }

            .student-name {
                font-weight: 500;
            }

            .remove-form {
                display: inline;
            }

            .search-box {
                margin-bottom: 10px;
            }

            .back-link {
                display: inline-block;
                margin-top: 20px;
                text-decoration: none;
                color: #6a11cb;
                font-weight: bold;
            }

            .back-link:hover {
                text-decoration: underline;
            }
        </style>
        <script>
            function filterStudents() {
                const input = document.getElementById("studentSearch").value.toLowerCase();
                const options = document.querySelectorAll("#studentSelect option");

                options.forEach(option => {
                    const text = option.textContent.toLowerCase();
                    option.style.display = text.includes(input) ? "block" : "none";
                });
            }
        </script>
    </head>
    <body>
       <?php include("navbar_teacher.php"); ?>
        <div class="container">
            <h2>Student Management for Course: <em><?= htmlspecialchars($course_name) ?></em></h2>

            <?php if (!empty($message)) : ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>

            <h3>➕ Add students</h3>
            <div class="search-box">
                <input type="text" id="studentSearch" onkeyup="filterStudents()" placeholder="Search by name...">
            </div>
            <form method="post">
                <select name="student_id" id="studentSelect" required>
                    <option value="">-- Choose student --</option>
                    
                    <?php while ($student = pg_fetch_assoc($students_result)) : ?>
                        <option value="<?= $student['id'] ?>">
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Add</button>
            </form>

            <h3>👨‍🎓 Current students in the course</h3>
            <ul>
                <?php while ($student = pg_fetch_assoc($current_students_result)) : ?>
                    <li class="student-item">
                        <span class="student-name"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></span>
                        <form method="post" class="remove-form">
                            <input type="hidden" name="remove_student_id" value="<?= $student['id'] ?>">
                            <button class="btn-delete" type="submit" onclick="return confirm('Are you sure you want to remove this student?')">Remove</button>
                        </form>
                    </li>
                <?php endwhile; ?>
            </ul>

            <a href="view_teacher.php" class="back-link">⬅ Back</a>
        </div>
    </body>
    </html>
