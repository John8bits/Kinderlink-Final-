<?php
require_once '../autoload.php';

$pupil = new PupilManager();
$db = new Database();
$conn = $db->conn;
$age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
$pupil_id = filter_input(INPUT_POST, 'pupil_id', FILTER_VALIDATE_INT);
$firstName = normalize_pupil_text($_POST['first_name'] ?? '');
$lastName = normalize_pupil_text($_POST['last_name'] ?? '');
$guardianName = normalize_pupil_text($_POST['guardian_name'] ?? '');

function redirectToPupils() {
    header("Location: ../dashboards/teacher_dashb.php?page=pupils");
    exit;
}

$data = [
    'first_name'     => $firstName,
    'last_name'      => $lastName,
    'age'            => $age,
    'gender'         => $_POST['gender'] ?? '',
    'birthdate'      => $_POST['birthdate'] ?? '',
    'home_address'   => normalize_pupil_text($_POST['home_address'] ?? ''),
    'health_notes'   => normalize_pupil_text($_POST['health_notes'] ?? ''),
    'guardian_name'  => $guardianName,
    'contact_number' => trim($_POST['contact_number'] ?? '')
];

$errors = validate_pupil_payload($data);
if (!$pupil_id) {
    $errors[] = "Invalid pupil selected.";
}
if (!empty($errors)) {
    $_SESSION['error'] = $errors[0];
    redirectToPupils();
}

$teacherEmail = $_SESSION['email'] ?? '';
$classStmt = $conn->prepare("
    SELECT c.class_id
    FROM class c
    JOIN teacher t ON c.teacher_teacher_id = t.teacher_id
    WHERE t.email = ?
    LIMIT 1
");
$classStmt->execute([$teacherEmail]);
$classId = (int) $classStmt->fetchColumn();

if (!$classId) {
    $_SESSION['error'] = "No assigned class found for your teacher account.";
    redirectToPupils();
}

$pupilClassStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM pupil
    WHERE pupil_id = ?
    AND class_class_id = ?
");
$pupilClassStmt->execute([$pupil_id, $classId]);
if ((int) $pupilClassStmt->fetchColumn() === 0) {
    $_SESSION['error'] = "You can only update pupils in your assigned class.";
    redirectToPupils();
}

if ($pupil->pupilNameExists($firstName, $lastName, $classId, $pupil_id)) {
    $_SESSION['error'] = "Pupil name already exist.";
    redirectToPupils();
}

$guardian = $pupil->findGuardianByName($guardianName);
if (!$guardian) {
    $_SESSION['error'] = "Guardian not exist.";
    redirectToPupils();
}

$guardianList = $pupil->getGuardianValidationList();
foreach ($guardianList as $item) {
    if ((int) $item['guardian_id'] === (int) $guardian['guardian_id']) {
        $linkedPupilId = (int) ($item['linked_pupil_id'] ?? 0);
        if ((int) ($item['linked_count'] ?? 0) > 0 && $linkedPupilId !== (int) $pupil_id) {
            $_SESSION['error'] = "Guardian already linked.";
            redirectToPupils();
        }
        break;
    }
}

$data['guardian_id'] = $guardian['guardian_id'];

if ($pupil->updatePupil($pupil_id, $data)) {
    $activity = new TeacherActivity();
    $activity->logByTeacherEmail(
        $_SESSION['email'] ?? '',
        'pupil_updated',
        'Pupil profile edited',
        trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''))
    );
    $_SESSION['success'] = "Pupil updated successfully!";
    header("Location: ../dashboards/teacher_dashb.php?page=pupils");
    exit;
}

$_SESSION['error'] = "Failed to update pupil.";
redirectToPupils();
?>
