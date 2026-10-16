<?php
session_start();

// Protect script
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.html");
    exit();
}

require_once "db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM loans WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Handle deletion error if needed
    }
}

header("Location: dashboard.php");
exit();
?>