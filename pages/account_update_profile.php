<?php
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$fullName = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = preg_replace('/\s+/', '', trim($_POST['phone'] ?? ''));

if ($fullName === '' || strlen($fullName) > 100) {
    echo json_encode([
        'success' => false,
        'message' => 'Full name is required and must be under 100 characters.'
    ]);
    exit;
}

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $email) ||
    strlen($email) > 150
) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);
    exit;
}

if (!preg_match('/^(?:\+44|0)7\d{9}$/', $phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Enter a valid UK mobile number.'
    ]);
    exit;
}

$emailCheck = pg_query_params(
    $conn,
    "SELECT id
     FROM public.users
     WHERE email = $1 AND id <> $2
     LIMIT 1",
    [$email, $userId]
);

if ($emailCheck && pg_fetch_assoc($emailCheck)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email already exists.'
    ]);
    exit;
}

$updateResult = pg_query_params(
    $conn,
    "UPDATE public.users
     SET full_name = $1,
         email = $2,
         phone = $3,
         updated_at = NOW()
     WHERE id = $4",
    [$fullName, $email, $phone, $userId]
);

if (!$updateResult) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not save your profile changes.'
    ]);
    exit;
}

$_SESSION['full_name'] = $fullName;

echo json_encode([
    'success' => true,
    'message' => 'Profile changes saved successfully.',
    'data' => [
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone
    ]
]);
exit;
