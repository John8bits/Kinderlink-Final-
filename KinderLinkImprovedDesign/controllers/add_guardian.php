<?php
//session_start();
require_once '../autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = trim($_POST['pass'] ?? '');
        $confirmPass = trim($_POST['confirm_pass'] ?? '');
        $redirect = "../dashboards/admin_dashb.php?page=guardians";
        $oldInput = [
            'guardian_name' => $name,
            'guardian_email' => $email,
        ];

        if (empty($name) || empty($email) || empty($pass) || empty($confirmPass)) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: $redirect");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['admin_form_errors'] = ['guardian_email' => 'Enter a valid email address.'];
            $_SESSION['admin_old_input'] = $oldInput;
            $_SESSION['admin_open_modal'] = 'guardian';
            header("Location: $redirect");
            exit;
        }

        $db = new Database();
        $stmt = $db->conn->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        if ((int) $stmt->fetchColumn() > 0) {
            $_SESSION['admin_form_errors'] = ['guardian_email' => 'Email is already taken.'];
            $_SESSION['admin_old_input'] = $oldInput;
            $_SESSION['admin_open_modal'] = 'guardian';
            header("Location: $redirect");
            exit;
        }

        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters.";
            header("Location: $redirect");
            exit;
        }

        if ($pass !== $confirmPass) {
            $_SESSION['error'] = "Password and confirmation do not match.";
            header("Location: $redirect");
            exit;
        }

        $guardian = new guardian();
        $guardian->create($name, $email, $pass);

        $_SESSION['success'] = "Guardian account created successfully!";
        header("Location: $redirect");
        exit;
    } catch (Exception $e) {
        $_SESSION['admin_form_errors'] = ['guardian_email' => 'Email is already taken.'];
        $_SESSION['admin_old_input'] = [
            'guardian_name' => trim($_POST['name'] ?? ''),
            'guardian_email' => trim($_POST['email'] ?? ''),
        ];
        $_SESSION['admin_open_modal'] = 'guardian';
        header("Location: ../dashboards/admin_dashb.php?page=guardians");
        exit;
    }
}
?>
