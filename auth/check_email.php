<?php
header('Content-Type: application/json');
require '../includes/db.php';
require '../includes/csrf.php';

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Invalid email address.'
    ]);
    exit;
}

$query = "SELECT id FROM users WHERE email = $1 LIMIT 1";
$result = pg_query_params($conn, $query, [$email]);
$user = pg_fetch_assoc($result);

echo json_encode([
    'success' => true,
    'exists' => $user ? true : false
]);
exit;