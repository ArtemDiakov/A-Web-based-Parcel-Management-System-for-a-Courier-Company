<?php
session_start();
require '../includes/db.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email.");
}

if ($password === '') {
    die("Password is required.");
}

$query = "SELECT id, full_name, password_hash, role, is_active
          FROM users
          WHERE email = $1
          LIMIT 1";

$result = pg_query_params($conn, $query, [$email]);
$user = pg_fetch_assoc($result);

if (!$user || !$user['is_active']) {
    die("Invalid email or password.");
}

if (password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];

    header("Location: /index.php");
    exit;
} else {
    die("Invalid email or password.");
}