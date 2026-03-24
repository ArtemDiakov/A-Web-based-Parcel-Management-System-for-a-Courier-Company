<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email.']);
    exit;
}

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

$query = "SELECT id, full_name, password_hash, role, is_active
          FROM users
          WHERE email = $1
          LIMIT 1";

$result = pg_query_params($conn, $query, [$email]);
$user = pg_fetch_assoc($result);

if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid password.']);
    exit;
}

// SUCCESS
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];

echo json_encode(['success' => true]);
