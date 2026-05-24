<?php
require_once '../autoload.php';

function redirectBack($role) {
    $target = $role === 'guardian'
        ? '../dashboards/guardian_dashb.php'
        : '../dashboards/teacher_dashb.php';

    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectBack($_SESSION['role'] ?? '');
}

$role = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$email = $_SESSION['email'] ?? '';

if (empty($userId) || empty($email) || !in_array($role, ['teacher', 'guardian'], true)) {
    $_SESSION['error'] = 'Only teacher and guardian accounts can change their password here.';
    redirectBack($role);
}

if (!csrf_check()) {
    $_SESSION['error'] = 'Your session expired. Please try changing your password again.';
    redirectBack($role);
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $_SESSION['error'] = 'Please complete all password fields.';
    redirectBack($role);
}

if (strlen($newPassword) < 8) {
    $_SESSION['error'] = 'New password must be at least 8 characters.';
    redirectBack($role);
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = 'New password and confirmation do not match.';
    redirectBack($role);
}

try {
    $db = new Database();
    $conn = $db->conn;

    $stmt = $conn->prepare("
        SELECT user_id, password, role
        FROM users
        WHERE user_id = ?
        AND email = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !in_array($user['role'], ['teacher', 'guardian'], true)) {
        $_SESSION['error'] = 'Account not found or not allowed to change password.';
        redirectBack($role);
    }

    if (!password_verify($currentPassword, $user['password'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
        redirectBack($role);
    }

    if (password_verify($newPassword, $user['password'])) {
        $_SESSION['error'] = 'New password must be different from your current password.';
        redirectBack($role);
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $update->execute([$hashedPassword, $userId]);

    if ($user['role'] === 'teacher') {
        $profileUpdate = $conn->prepare("UPDATE teacher SET password = ? WHERE email = ?");
        $profileUpdate->execute([$hashedPassword, $email]);
    } elseif ($user['role'] === 'guardian') {
        $profileUpdate = $conn->prepare("UPDATE guardian SET password = ? WHERE email = ?");
        $profileUpdate->execute([$hashedPassword, $email]);
    }

    $_SESSION['success'] = 'Password changed successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Password could not be changed. Please try again.';
}

redirectBack($role);
?>
