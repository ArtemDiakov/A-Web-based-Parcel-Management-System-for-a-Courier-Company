<?php
header('Content-Type: application/json');
require '../includes/db.php';

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["exists" => false]);
    exit;
}

$query = "SELECT id FROM users WHERE email = $1 LIMIT 1";
$result = pg_query_params($conn, $query, [$email]);
$user = pg_fetch_assoc($result);

echo json_encode([
    "exists" => $user ? true : false
]);