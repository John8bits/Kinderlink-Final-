<?php

function csrf_token() {
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" id="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function csrf_check() {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && $_POST['csrf_token'] === $_SESSION['csrf_token'];
}

?>