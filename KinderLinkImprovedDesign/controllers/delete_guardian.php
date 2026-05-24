<?php
    require_once '../autoload.php';

    $guardian = new guardian();
    $guardian->toggle($_GET['id']);

    header("Location: ../dashboards/admin_dashb.php?page=guardians");
    exit;
?>