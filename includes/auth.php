<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /index.php");
        exit;
    }
}

function requireRole(array $roles) {
    requireLogin();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        exit('Access denied.');
    }
}