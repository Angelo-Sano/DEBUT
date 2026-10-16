<?php

session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === 'admin' && $password === 'admin123') {

    session_regenerate_id(true);

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';

    header('Location: dashboard.php');
    exit();

}

header('Location: login.html?error=1');
exit();

?>