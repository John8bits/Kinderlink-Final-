<?php
//session_start();
require_once '../autoload.php';

$db = new Database();
$conn = $db->conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date = $_POST['date'] ?? '';
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $quarter = $_POST['quarter'] ?? '';
    $statuses = $_POST['status'] ?? [];
    $today = date('Y-m-d');
    $currentMonth = (int) date('m');
    $currentYear = (int) date('Y');
    $currentQuarter = $currentMonth <= 3 ? 'Q1' : ($currentMonth <= 6 ? 'Q2' : ($currentMonth <= 9 ? 'Q3' : 'Q4'));
    $quarterRanges = [
        'Q1' => ['start' => "$currentYear-01-01", 'end' => "$currentYear-03-31"],
        'Q2' => ['start' => "$currentYear-04-01", 'end' => "$currentYear-06-30"],
        'Q3' => ['start' => "$currentYear-07-01", 'end' => "$currentYear-09-30"],
        'Q4' => ['start' => "$currentYear-10-01", 'end' => "$currentYear-12-31"],
    ];
    if (!isset($quarterRanges[$quarter])) {
        $quarter = $currentQuarter;
    }

    $redirectUrl = "../dashboards/teacher_dashb.php?page=attendance&date=" . urlencode($date ?: $today) . "&quarter=" . urlencode($quarter);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date !== $today || $quarter !== $currentQuarter) {
        $_SESSION['error'] = "Attendance can only be saved for today's date in the current quarter.";
        header("Location: $redirectUrl");
        exit;
    }

    if (!$class_id || !is_array($statuses)) {
        $_SESSION['error'] = "Invalid attendance submission.";
        header("Location: $redirectUrl");
        exit;
    }

    try {

        $classCheck = $conn->prepare("
            SELECT c.class_id
            FROM class c
            JOIN teacher t ON c.teacher_teacher_id = t.teacher_id
            WHERE c.class_id = ? AND t.email = ?
            LIMIT 1
        ");
        $classCheck->execute([$class_id, $_SESSION['email'] ?? '']);
        if (!$classCheck->fetchColumn()) {
            $_SESSION['error'] = "You can only save attendance for your assigned class.";
            header("Location: $redirectUrl");
            exit;
        }

        foreach ($statuses as $pupil_id => $status) {
            $pupil_id = filter_var($pupil_id, FILTER_VALIDATE_INT);

            // skip if no status selected
            if (!$pupil_id || $status == '' || !in_array($status, ['Present', 'Absent', 'Late'], true)) continue;

            $pupilCheck = $conn->prepare("
                SELECT pupil_id
                FROM pupil
                WHERE pupil_id = ? AND class_class_id = ?
                LIMIT 1
            ");
            $pupilCheck->execute([$pupil_id, $class_id]);
            if (!$pupilCheck->fetchColumn()) continue;

            // check existing record
            $check = $conn->prepare("
                SELECT attendance_id 
                FROM attendance 
                WHERE pupil_pupil_id = ? 
                AND DATE(date) = ?
            ");
            $check->execute([$pupil_id, $date]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {

                // UPDATE
                $stmt = $conn->prepare("
                    UPDATE attendance
                    SET status = ?, date = ?
                    WHERE attendance_id = ?
                ");
                $stmt->execute([$status, $date, $existing['attendance_id']]);

            } else {

                // INSERT
                $stmt = $conn->prepare("
                    INSERT INTO attendance (pupil_pupil_id, date, status)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$pupil_id, $date, $status]);
            }
        }

        $presentCount = 0;
        $absentCount = 0;
        $lateCount = 0;
        foreach ($statuses as $status) {
            if ($status === 'Present') {
                $presentCount++;
            } elseif ($status === 'Absent') {
                $absentCount++;
            } elseif ($status === 'Late') {
                $lateCount++;
            }
        }

        $activity = new TeacherActivity();
        $activity->logByTeacherEmail(
            $_SESSION['email'] ?? '',
            'attendance_saved',
            'Attendance taken',
            $presentCount . ' present, ' . $absentCount . ' absent, ' . $lateCount . ' late for ' . date('M j, Y', strtotime($date))
        );

        $_SESSION['success'] = "Attendance saved successfully!";
        header("Location: $redirectUrl");
        exit;

    } catch (Exception $e) {

        $_SESSION['error'] = "Failed to save attendance.";
        header("Location: $redirectUrl");
        exit;
    }
}
?>
