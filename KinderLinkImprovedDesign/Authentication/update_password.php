<?php
require_once '../autoload.php';
// $db = new Database();
// $conn = $db->conn;

$admins = [
    ['email' => 'jbits@gmail.com',  'password' => 'jbits123'],
    ['email' => 'kaye@gmail.com',   'password' => 'kaye123'],
    ['email' => 'paz@gmail.com',    'password' => 'paz123'],
];

foreach ($admins as $admin) {
    $hashed = password_hash($admin['password'], PASSWORD_BCRYPT);

    $stmt = $db->conn->prepare("UPDATE users SET password = :password WHERE email = :email AND role = 'admin'");
    $stmt->execute([
        ':password' => $hashed,
        ':email'    => $admin['email'],
    ]);
}
?>