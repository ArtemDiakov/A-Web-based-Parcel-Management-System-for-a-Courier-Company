<?php
session_start();
require '../includes/db.php';

$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$errors = [];

// Full name
if ($name === '' || strlen($name) > 100) {
    $errors[] = "Full name is required and must be under 100 characters.";
}

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    $errors[] = "Invalid email address.";
}

// Phone (simple UK-friendly pattern, adjustable)
if (!preg_match('/^(?:\+44|0)7\d{9}$/', preg_replace('/\s+/', '', $phone))) {
    $errors[] = "Enter a valid UK mobile number.";
}

// Password
if (strlen($password) < 8 || strlen($password) > 72) {
    $errors[] = "Password must be between 8 and 72 characters.";
} elseif (
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password)
) {
    $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
}

if ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
}

if (!empty($errors)) {
    die(implode("<br>", array_map('htmlspecialchars', $errors)));
}

// Check duplicate email
$check = pg_query_params($conn, "SELECT id FROM users WHERE email = $1 LIMIT 1", [$email]);
if (pg_fetch_assoc($check)) {
    die("An account with this email already exists.");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users
(full_name, email, phone, password_hash, role, created_at, is_active)
VALUES ($1, $2, $3, $4, 'customer', NOW(), true)
RETURNING id, full_name, role";

$result = pg_query_params($conn, $query, [$name, $email, $phone, $hash]);

if (!$result) {
    die("Registration failed.");
}

$user = pg_fetch_assoc($result);

session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];

header("Location: /index.php");
exit;