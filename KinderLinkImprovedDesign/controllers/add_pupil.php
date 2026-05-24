<?php   
    //session_start();
    require_once '../autoload.php';

    $pupil = new PupilManager();
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $firstName = normalize_pupil_text($_POST['first_name'] ?? '');
    $lastName = normalize_pupil_text($_POST['last_name'] ?? '');
    $guardianName = normalize_pupil_text($_POST['guardian_name'] ?? '');
    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $data = [
        'first_name'     => $firstName,
        'last_name'      => $lastName,
        'age'            => $age,
        'gender'         => $_POST['gender'] ?? '',
        'birthdate'      => $_POST['birthdate'] ?? '',
        'home_address'   => normalize_pupil_text($_POST['home_address'] ?? ''),
        'health_notes'   => normalize_pupil_text($_POST['health_notes'] ?? ''),
        'guardian_name'  => $guardianName,
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'class_id'       => $classId
    ];

    function redirectToPupils() {
        header("Location: ../dashboards/teacher_dashb.php?page=pupils");
        exit;
    }

    $errors = validate_pupil_payload($data);
    if (!$classId) {
        $errors[] = "Invalid class selected.";
    }
    if (!empty($errors)) {
        $_SESSION['error'] = $errors[0];
        redirectToPupils();
    }

    if ($pupil->pupilNameExists($firstName, $lastName, $classId)) {
        $_SESSION['error'] = "Pupil name already exist.";
        redirectToPupils();
    }

    $guardian = $pupil->findGuardianByName($guardianName);
    if (!$guardian) {
        $_SESSION['error'] = "Guardian not exist.";
        redirectToPupils();
    }

    if ($pupil->guardianHasPupilLink($guardian['guardian_id'])) {
        $_SESSION['error'] = "Guardian already linked.";
        redirectToPupils();
    }

    $data['guardian_id'] = $guardian['guardian_id'];

   if ($pupil->addPupil($data)) {
        $activity = new TeacherActivity();
        $activity->logByTeacherEmail(
            $_SESSION['email'] ?? '',
            'pupil_created',
            'Pupil added',
            trim($firstName . ' ' . $lastName)
        );
        $_SESSION['success'] = "Pupil added successfully!";
        redirectToPupils();
    }   

    $_SESSION['error'] = "Failed to add pupil.";
    redirectToPupils();
?>
