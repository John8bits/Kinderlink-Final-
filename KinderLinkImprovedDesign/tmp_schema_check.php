<?php
require_once 'config/Database.php';
$db = new Database();
$stmt = $db->conn->query('SHOW CREATE TABLE pupil_milestone');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'];
