<?php
require_once '../autoload.php';

$redirect = "../dashboards/admin_dashb.php?page=pupil";
$guardianId = filter_input(INPUT_POST, 'guardian_id', FILTER_VALIDATE_INT);
$pupilId = filter_input(INPUT_POST, 'pupil_id', FILTER_VALIDATE_INT);

if (!$guardianId || !$pupilId) {
    $_SESSION['error'] = "Please choose an available guardian and pupil.";
    header("Location: $redirect");
    exit;
}

$link = new PupilLink();

if ($link->pairExists($guardianId, $pupilId)) {
    $_SESSION['error'] = "This guardian and pupil are already linked.";
    header("Location: $redirect");
    exit;
}

if ($link->guardianHasLink($guardianId)) {
    $_SESSION['error'] = "This guardian is already linked. Remove the existing link first.";
    header("Location: $redirect");
    exit;
}

if ($link->pupilHasLink($pupilId)) {
    $_SESSION['error'] = "This pupil is already linked. Remove the existing link first.";
    header("Location: $redirect");
    exit;
}

$link->link($guardianId, $pupilId);

$_SESSION['success'] = "Guardian and pupil linked successfully.";
header("Location: $redirect");
exit;
?>
