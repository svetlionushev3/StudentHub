<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    header("Location: login_student.php");
    exit;
}

$egn = $_SESSION['student_egn'];

$queryStudent = "SELECT * FROM students WHERE egn = $1";
$resultStudent = pg_query_params($con, $queryStudent, [$egn]);
$student = pg_fetch_assoc($resultStudent);
if (!$student) {
    echo "Student not found.";
    exit;
}

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    echo "Invalid course ID.";
    exit;
}

$course_id = (int)$_GET['course_id'];
$queryCourse = "SELECT course_name FROM courses WHERE id = $1";
$resultCourse = pg_query_params($con, $queryCourse, [$course_id]);
$course = pg_fetch_assoc($resultCourse);

if (!$course) {
    echo "Course not found.";
    exit;
}

$queryCheck = "SELECT 1 FROM student_courses WHERE student_id = $1 AND course_id = $2";
$resultCheck = pg_query_params($con, $queryCheck, [$student['id'], $course_id]);
if (pg_num_rows($resultCheck) == 0) {
    echo "You are not enrolled in this course.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);
    $insertQuery = "INSERT INTO course_chat (course_id, student_id, message) VALUES ($1, $2, $3)";
    pg_query_params($con, $insertQuery, [$course_id, $student['id'], $message]);
    header("Location: chat.php?course_id=$course_id");
    exit;
}

$queryMessages = "
    SELECT cc.id, cc.student_id, cc.message, cc.sent_at, s.first_name, s.last_name 
    FROM course_chat cc 
    JOIN students s ON cc.student_id = s.id
    WHERE cc.course_id = $1
    ORDER BY cc.sent_at ASC
";

$resultMessages = pg_query_params($con, $queryMessages, [$course_id]);

$messages = [];
while ($row = pg_fetch_assoc($resultMessages)) {
    $messages[] = $row;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Chat for Course</title>
       <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 30px auto;
            background: #f4f7fa;
            padding: 20px;
            border-radius: 10px;
        }
        h2 {
            text-align: center;
            color: #6a11cb;
        }
        .chat-box {
            border: 1px solid #ccc;
            height: 400px;
            overflow-y: scroll;
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .message {
            margin-bottom: 10px;
        }
        .message strong {
            color: #6a11cb;
        }
        .message time {
            font-size: 0.8em;
            color: #999;
            margin-left: 5px;
        }
        .my-message {
    background-color: #d1e7dd;
    border-radius: 8px;
    padding: 8px;
    color: #0f5132;
}

        form textarea {
            width: 100%;
            height: 60px;
            resize: none;
            border-radius: 6px;
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 14px;
        }
        form button {
            margin-top: 8px;
            background: #6a11cb;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        form button:hover {
            background: #4e0fb1;
        }
    </style>
    <script>
        function scrollToBottom() {
            const chatBox = document.querySelector('.chat-box');
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function fetchMessages() {
            fetch('chat_messages.php?course_id=<?= $course_id ?>')
                .then(response => response.text())
                .then(data => {
                    document.querySelector('.chat-box').innerHTML = data;
                    scrollToBottom();
                });
        }
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
        if (confirm('Are you sure you want to delete this message?')) {
            const messageId = e.target.getAttribute('data-id');
            
            fetch('delete_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'message_id=' + encodeURIComponent(messageId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    e.target.closest('.message').remove();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(() => alert('Request failed.'));
        }
    }
});
        window.onload = () => {
            scrollToBottom();
            setInterval(fetchMessages, 5000);
        }
    </script>
</head>
<body>
<?php include("navbar_student.php"); ?>
<h2>Chat for Course: <?= htmlspecialchars($course['course_name']) ?></h2>


<div class="chat-box">
    <?php foreach ($messages as $msg): ?>
<div class="message <?= ($msg['student_id'] == $student['id']) ? 'my-message' : '' ?>" data-message-id="<?= $msg['id'] ?>">
    <strong><?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?>:</strong>
    <?= nl2br(htmlspecialchars($msg['message'])) ?>
    <time>(<?= date("d.m.Y H:i", strtotime($msg['sent_at'])) ?>)</time>

    <?php if ($msg['student_id'] == $student['id']): ?>
        <button class="delete-btn" data-id="<?= $msg['id'] ?>">Delete</button>
    <?php endif; ?>
</div>

<?php endforeach; ?>

</div>

<form method="post">
    <textarea name="message" placeholder="Write your message here..." required
        style="width: 100%; height: 60px; resize: none; border-radius: 6px; border: 1px solid #ccc; padding: 10px; font-size: 14px;"></textarea>

    <div style="display: flex; gap: 10px; margin-top: 8px;">
        <button type="submit" class="button">
            Send
        </button>

        <button type="button" onclick="window.location.href='view_student.php';" class="btn-back">
            Back
        </button>
    </div>
</form>


</body>
</html>
