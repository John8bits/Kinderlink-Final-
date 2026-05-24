<?php
//session_start();
require_once '../autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/teacher_dashb.php?page=announcements');
    exit;
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$announcementId = $_POST['announcement_id'] ?? '';
$email = $_SESSION['email'] ?? '';

if (empty($title) || empty($content) || empty($email)) {
    $_SESSION['success'] = 'Announcement could not be saved. Please try again.';
    header('Location: ../dashboards/teacher_dashb.php?page=announcements');
    exit;
}

$db = new Database();
$conn = $db->conn;

$stmt = $conn->prepare("SELECT c.class_id FROM class c JOIN teacher t ON c.teacher_teacher_id = t.teacher_id WHERE t.email = ? LIMIT 1");
$stmt->execute([$email]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    $_SESSION['success'] = 'Class not found for your account. Announcement was not saved.';
    header('Location: ../dashboards/teacher_dashb.php?page=announcements');
    exit;
}

$classId = $class['class_id'];

if (!empty($announcementId)) {
    $stmt = $conn->prepare("
        UPDATE announcement
        SET title = ?, content = ?
        WHERE announcement_id = ?
        AND class_class_id = ?
    ");
    $stmt->execute([$title, $content, $announcementId, $classId]);
    $activity = new TeacherActivity();
    $activity->logByTeacherEmail($email, 'announcement_updated', 'Announcement edited', $title);
    $_SESSION['success'] = 'Announcement updated successfully.';
} else {
    $stmt = $conn->prepare("INSERT INTO announcement (title, content, date_posted, class_class_id) VALUES (?, ?, NOW(), ?)");
    $stmt->execute([$title, $content, $classId]);
    $activity = new TeacherActivity();
    $activity->logByTeacherEmail($email, 'announcement_created', 'Announcement created', $title);
    $_SESSION['success'] = 'Announcement posted successfully.';
}

header('Location: ../dashboards/teacher_dashb.php?page=announcements');
exit;
