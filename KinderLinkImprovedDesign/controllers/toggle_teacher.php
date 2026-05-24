<?php
require_once '../autoload.php';

$teacher = new teacher();

$id = $_GET['id'];
$status = $_GET['status'];

if ($status == 'active') {
    $teacher->softDelete($id);
} else {
    $teacher->activate($id);
}

header("Location: ../dashboards/admin_dashb.php?page=teacher");
exit;
?>