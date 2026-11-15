<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Role check: 1 = Admin, 2 = Staff
$isAdmin = (isset($_SESSION['role_id']) && (int)$_SESSION['role_id'] === 1);
$currentUserName = $_SESSION['full_name'] ?? 'User';
?>