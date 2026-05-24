<?php
//session_start();
require_once '../autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/teacher_dashb.php?page=announcements');
    exit;
}

$announcementId = $_POST['announcement_id'] ?? '';
$teacherEmail = $_SESSION['email'] ?? '';

if (empty($announcementId) || empty($teacherEmail)) {
    $_SESSION['error'] = 'Announcement could not be deleted.';
    header('Location: ../dashboards/teacher_dashb.php?page=announcements');
    exit;
}

$db = new Database();
$conn = $db->conn;

$stmt = $conn->prepare("
    SELECT a.title
    FROM announcement a
    JOIN class c ON a.class_class_id = c.class_id
    JOIN teacher t ON c.teacher_teacher_id = t.teacher_id
    WHERE a.announcement_id = ?
    AND t.email = ?
    LIMIT 1
");
$stmt->execute([$announcementId, $teacherEmail]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    DELETE a
    FROM announcement a
    JOIN class c ON a.class_class_id = c.class_id
    JOIN teacher t ON c.teacher_teacher_id = t.teacher_id
    WHERE a.announcement_id = ?
    AND t.email = ?
");
$stmt->execute([$announcementId, $teacherEmail]);

if ($stmt->rowCount() > 0) {
    $activity = new TeacherActivity();
    $activity->logByTeacherEmail(
        $teacherEmail,
        'announcement_deleted',
        'Announcement deleted',
        $announcement['title'] ?? 'Removed announcement'
    );
}

$_SESSION['success'] = $stmt->rowCount() > 0
    ? 'Announcement deleted successfully.'
    : 'Announcement was not found or you do not have permission to delete it.';

header('Location: ../dashboards/teacher_dashb.php?page=announcements');
exit;
