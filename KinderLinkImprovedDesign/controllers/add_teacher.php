<?php
require_once '../autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher = new teacher();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['pass'] ?? '';
    $confirmPassword = $_POST['confirm_pass'] ?? '';
    $class = trim($_POST['cls'] ?? '');

    $redirect = "../dashboards/admin_dashb.php?page=teacher";
    $oldInput = [
        'teacher_name' => $name,
        'teacher_email' => $email,
        'teacher_class' => $class,
    ];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['admin_form_errors'] = ['teacher_email' => 'Enter a valid email address.'];
        $_SESSION['admin_old_input'] = $oldInput;
        $_SESSION['admin_open_modal'] = 'teacher';
        header("Location: $redirect");
        exit;
    }

    $db = new Database();
    $stmt = $db->conn->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    if ((int) $stmt->fetchColumn() > 0) {
        $_SESSION['admin_form_errors'] = ['teacher_email' => 'Email is already taken.'];
        $_SESSION['admin_old_input'] = $oldInput;
        $_SESSION['admin_open_modal'] = 'teacher';
        header("Location: $redirect");
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters.";
        header("Location: $redirect");
        exit;
    }

    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Password and confirmation do not match.";
        header("Location: $redirect");
        exit;
    }
    
    $created = $teacher->create(
        $name,
        $email,
        $password,
        $class
    );

    if ($created) {
        $_SESSION['success'] = "Teacher account added successfully.";
    } else {
        $_SESSION['admin_form_errors'] = ['teacher_email' => 'Email is already taken.'];
        $_SESSION['admin_old_input'] = $oldInput;
        $_SESSION['admin_open_modal'] = 'teacher';
    }

    header("Location: $redirect");
    exit;
}
?>
