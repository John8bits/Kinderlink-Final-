<?php
//session_start();
require_once '../autoload.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $milestoneManager = new MilestoneManager();

        if (empty($_POST['title'])) {
            throw new Exception("Milestone title is required.");
        }

        // Check for duplicate titles (only for new milestones)
        if (empty($_POST['milestone_id'])) {
            $existingMilestone = $milestoneManager->getMilestoneByTitle($_POST['title']);
            if ($existingMilestone) {
                throw new Exception("A milestone with this title already exists.");
            }
        }

        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? ''
        ];

        // Check if updating existing milestone
        if (!empty($_POST['milestone_id'])) {
            $milestoneManager->updateMilestone($_POST['milestone_id'], $data);
            $activity = new TeacherActivity();
            $activity->logByTeacherEmail(
                $_SESSION['email'] ?? '',
                'milestone_template_updated',
                'Milestone template edited',
                $data['title']
            );
            $_SESSION['success'] = "Milestone updated successfully!";
        } else {
            // Add new milestone
            $milestoneManager->addMilestone($data);
            $activity = new TeacherActivity();
            $activity->logByTeacherEmail(
                $_SESSION['email'] ?? '',
                'milestone_template_created',
                'Milestone template created',
                $data['title']
            );
            $_SESSION['success'] = "Milestone added successfully!";
        }

        $response['success'] = true;
        $response['message'] = $_SESSION['success'];
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        $response['message'] = $e->getMessage();
    }
}

// If this is an AJAX request, return JSON
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Redirect back to milestones page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
