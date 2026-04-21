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

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmNewPassword = $_POST['confirm_new_password'] ?? '';

if ($currentPassword === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your current password.'
    ]);
    exit;
}

if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be between 8 and 72 characters.'
    ]);
    exit;
}

if (
    !preg_match('/[A-Z]/', $newPassword) ||
    !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/[0-9]/', $newPassword)
) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must contain uppercase, lowercase and number.'
    ]);
    exit;
}

if ($newPassword !== $confirmNewPassword) {
    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);
    exit;
}

$userResult = pg_query_params(
    $conn,
    "SELECT password_hash
     FROM public.users
     WHERE id = $1
     LIMIT 1",
    [$userId]
);

$user = $userResult ? pg_fetch_assoc($userResult) : null;

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found.'
    ]);
    exit;
}

if (!password_verify($currentPassword, $user['password_hash'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Current password is incorrect.'
    ]);
    exit;
}

if (password_verify($newPassword, $user['password_hash'])) {
    echo json_encode([
        'success' => false,
        'message' => 'New password must be different from your current password.'
    ]);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$updateResult = pg_query_params(
    $conn,
    "UPDATE public.users
     SET password_hash = $1,
         updated_at = NOW()
     WHERE id = $2",
    [$newHash, $userId]
);

if (!$updateResult) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not update your password.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Password updated successfully.'
]);
exit;
