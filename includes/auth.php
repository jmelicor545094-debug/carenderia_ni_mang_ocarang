<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ../admin/login.php');
        exit;
    }
}

function generateId($prefix = 'ID') {
    return $prefix . strtoupper(substr(uniqid(), -8));
}
?>
