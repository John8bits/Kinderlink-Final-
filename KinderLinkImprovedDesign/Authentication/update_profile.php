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
$currentEmail = $_SESSION['email'] ?? '';

if (empty($userId) || empty($role) || !in_array($role, ['teacher', 'guardian'], true)) {
    $_SESSION['error'] = 'Invalid account. Please log in again.';
    redirectBack($role);
}

if (!csrf_check()) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirectBack($role);
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($name === '' || $email === '') {
    $_SESSION['error'] = 'Name and email are required.';
    redirectBack($role);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    redirectBack($role);
}

try {
    $db = new Database();
    $conn = $db->conn;

    $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?');
    $stmt->execute([$email, $userId]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = 'That email address is already in use.';
        redirectBack($role);
    }

    $conn->beginTransaction();

    if ($role === 'teacher') {
        $profileStmt = $conn->prepare('UPDATE teacher SET teacher_name = ?, email = ? WHERE email = ? LIMIT 1');
    } else {
        $profileStmt = $conn->prepare('UPDATE guardian SET guardian_name = ?, email = ? WHERE email = ? LIMIT 1');
    }

    $profileStmt->execute([$name, $email, $currentEmail]);

    $userStmt = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE user_id = ?');
    $userStmt->execute([$name, $email, $userId]);

    $conn->commit();

    $_SESSION['username'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['success'] = 'Profile updated successfully.';
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = 'Profile could not be updated. Please try again.';
}

redirectBack($role);
