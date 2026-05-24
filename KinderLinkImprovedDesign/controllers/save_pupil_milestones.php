<?php
// session_start();
// require_once '../config/database.php';
// require_once '../models/milestone_manager.php';
require_once '../autoload.php';

$pupil_id = $_POST['pupil_id'] ?? null;
$quarter = $_POST['quarter'] ?? null;
$currentMonth = (int) date('m');
$currentYear = (int) date('Y');
$currentQuarter = $currentMonth <= 3 ? 'Q1' : ($currentMonth <= 6 ? 'Q2' : ($currentMonth <= 9 ? 'Q3' : 'Q4'));
$quarterRanges = [
    'Q1' => ['start' => "$currentYear-01-01", 'end' => "$currentYear-03-31"],
    'Q2' => ['start' => "$currentYear-04-01", 'end' => "$currentYear-06-30"],
    'Q3' => ['start' => "$currentYear-07-01", 'end' => "$currentYear-09-30"],
    'Q4' => ['start' => "$currentYear-10-01", 'end' => "$currentYear-12-31"],
];
$quarterLabels = [
    'Q1' => 'Quarter 1',
    'Q2' => 'Quarter 2',
    'Q3' => 'Quarter 3',
    'Q4' => 'Quarter 4',
];
if (!isset($quarterRanges[$quarter])) {
    $quarter = $currentQuarter;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/teacher_dashb.php?page=milestones' . ($quarter ? '&quarter=' . urlencode($quarter) : ''));
    exit;
}

try {
    if (empty($pupil_id)) {
        throw new Exception('Please select a pupil to save milestones.');
    }

    if ($quarter !== $currentQuarter) {
        $quarterOrder = array_keys($quarterRanges);
        $selectedQuarterIndex = array_search($quarter, $quarterOrder, true);
        $currentQuarterIndex = array_search($currentQuarter, $quarterOrder, true);
        $quarterLabel = $quarterLabels[$quarter] ?? $quarter;
        if ($selectedQuarterIndex !== false && $selectedQuarterIndex > $currentQuarterIndex) {
            throw new Exception($quarterLabel . ' has not started yet. Milestones can only be saved for the current day.');
        }
        throw new Exception($quarterLabel . ' has ended. Milestones can only be saved for the current day.');
    }

    $db = new Database();
    $conn = $db->conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM attendance
        WHERE pupil_pupil_id = ?
        AND DATE(date) = ?
        AND status = 'Absent'
    ");
    $stmt->execute([$pupil_id, date('Y-m-d')]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('This pupil is absent today. Milestone progress cannot be updated.');
    }

    $completed = $_POST['completed'] ?? [];
    if (!is_array($completed)) {
        $completed = [];
    }

    $milestoneManager = new MilestoneManager();

    // Get current pupil milestone statuses to compare
    $currentMilestones = $milestoneManager->getPupilMilestones($pupil_id);

    // Create a map of current statuses
    $currentStatuses = [];
    foreach ($currentMilestones as $milestone) {
        $currentStatuses[$milestone['milestone_id']] = $milestone['status'] ?? 'Not Started';
    }

    // Only update milestones that have changed
    $statusesToUpdate = [];
    $allMilestones = $milestoneManager->getAllMilestones();

    foreach ($allMilestones as $milestone) {
        $milestoneId = $milestone['milestone_id'];
        $newStatus = in_array((string)$milestoneId, $completed, true) ? 'Completed' : 'Not Started';
        $currentStatus = $currentStatuses[$milestoneId] ?? 'Not Started';

        // Only update if status has changed
        if ($newStatus !== $currentStatus) {
            $statusesToUpdate[$milestoneId] = $newStatus;
        }
    }

    // Only save if there are changes
    if (!empty($statusesToUpdate)) {
        $milestoneManager->savePupilMilestones($pupil_id, $statusesToUpdate);
        $completedCount = 0;
        $resetCount = 0;
        foreach ($statusesToUpdate as $status) {
            if ($status === 'Completed') {
                $completedCount++;
            } else {
                $resetCount++;
            }
        }

        $stmt = $conn->prepare("SELECT first_name, last_name FROM pupil WHERE pupil_id = ? LIMIT 1");
        $stmt->execute([$pupil_id]);
        $pupil = $stmt->fetch(PDO::FETCH_ASSOC);
        $pupilName = trim(($pupil['first_name'] ?? '') . ' ' . ($pupil['last_name'] ?? ''));

        $summaryParts = [];
        if ($completedCount > 0) {
            $summaryParts[] = $completedCount . ' completed';
        }
        if ($resetCount > 0) {
            $summaryParts[] = $resetCount . ' reset';
        }

        $activity = new TeacherActivity();
        $activity->logByTeacherEmail(
            $_SESSION['email'] ?? '',
            'milestone_checklist_updated',
            'Milestone checklist updated',
            ($pupilName ?: 'Selected pupil') . ': ' . implode(', ', $summaryParts)
        );
        $_SESSION['success'] = 'Milestone progress saved successfully.';
    } else {
        $_SESSION['success'] = 'No changes to save.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../dashboards/teacher_dashb.php?page=milestones&pupil_id=' . urlencode($pupil_id) . ($quarter ? '&quarter=' . urlencode($quarter) : ''));
exit;
