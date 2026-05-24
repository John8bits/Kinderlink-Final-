<?php
//session_start();
require_once '../autoload.php';

try {
    if (empty($_POST['milestone_id'])) {
        throw new Exception("Milestone ID is required");
    }

    $milestoneManager = new MilestoneManager();
    $milestone = $milestoneManager->getMilestoneById($_POST['milestone_id']);
    $milestoneManager->deleteMilestone($_POST['milestone_id']);
    $activity = new TeacherActivity();
    $activity->logByTeacherEmail(
        $_SESSION['email'] ?? '',
        'milestone_template_deleted',
        'Milestone template deleted',
        $milestone['title'] ?? 'Removed milestone template'
    );

    $_SESSION['success'] = "Milestone deleted successfully!";
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to milestones page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
