<?php
require_once '../autoload.php';

$redirect = "../dashboards/admin_dashb.php?page=pupil";
$linkId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$linkId) {
    $_SESSION['error'] = "The selected guardian-pupil link could not be found.";
    header("Location: $redirect");
    exit;
}

$pupil = new PupilLink();
$pupil->removeLink($linkId);

$_SESSION['success'] = "Guardian-pupil link removed successfully.";
header("Location: $redirect");
exit;
?>
