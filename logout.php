<?php

session_start();
require 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}

$_SESSION = [];
session_destroy();

header("Location: /index.php");
exit;