<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_egn'])) {
    exit;
}

$egn = $_SESSION['student_egn'];

$queryStudent = "SELECT * FROM students WHERE egn = $1";
$resultStudent = pg_query_params($con, $queryStudent, [$egn]);
$student = pg_fetch_assoc($resultStudent);

$course_id = (int)$_GET['course_id'];

$queryMessages = "
    SELECT cc.id, cc.student_id, cc.message, cc.sent_at, s.first_name, s.last_name 
    FROM course_chat cc 
    JOIN students s ON cc.student_id = s.id
    WHERE cc.course_id = $1
    ORDER BY cc.sent_at ASC
";

$resultMessages = pg_query_params($con, $queryMessages, [$course_id]);

while ($msg = pg_fetch_assoc($resultMessages)) {
    $isMyMessage = ($msg['student_id'] == $student['id']);
    ?>
    <div class="message <?= $isMyMessage ? 'my-message' : '' ?>" data-message-id="<?= $msg['id'] ?>">
        <strong><?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?>:</strong>
        <?= nl2br(htmlspecialchars($msg['message'])) ?>
        <time>(<?= date("d.m.Y H:i", strtotime($msg['sent_at'])) ?>)</time>

        <?php if ($isMyMessage): ?>
            <button class="delete-btn" data-id="<?= $msg['id'] ?>">Delete</button>
        <?php endif; ?>
    </div>
    <?php
}
?>
