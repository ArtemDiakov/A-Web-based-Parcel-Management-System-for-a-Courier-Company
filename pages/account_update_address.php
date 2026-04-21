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
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$line1 = trim($_POST['address_line1'] ?? '');
$line2 = trim($_POST['address_line2'] ?? '');
$city = trim($_POST['city'] ?? '');

$ukPostcodeRegex = '/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/';

function cleanPostcode(string $value): string
{
    $value = strtoupper(trim($value));
    $value = preg_replace('/\s+/', ' ', $value);
    return $value;
}

$postcode = cleanPostcode($_POST['postcode'] ?? '');

if (!preg_match($ukPostcodeRegex, $postcode)) {
    echo json_encode(['success' => false, 'message' => 'Invalid postcode.']);
    exit;
}

if ($line1 === '' || strlen($line1) > 150) {
    echo json_encode(['success' => false, 'message' => 'Address line 1 is required.']);
    exit;
}

if (strlen($line2) > 150) {
    echo json_encode(['success' => false, 'message' => 'Address line 2 too long.']);
    exit;
}

if ($city === '' || strlen($city) > 100) {
    echo json_encode(['success' => false, 'message' => 'City is required.']);
    exit;
}

/* check if user already has address */
$check = pg_query_params(
    $conn,
    "SELECT id FROM public.user_addresses WHERE user_id = $1 AND is_default = true LIMIT 1",
    [$userId]
);

$existing = $check ? pg_fetch_assoc($check) : null;

if ($existing) {
    $result = pg_query_params(
        $conn,
        "UPDATE public.user_addresses
         SET address_line1=$1, address_line2=$2, city=$3, postcode=$4
         WHERE id=$5",
        [$line1, $line2 ?: null, $city, $postcode, $existing['id']]
    );
} else {
    $result = pg_query_params(
        $conn,
        "INSERT INTO public.user_addresses
         (user_id, address_line1, address_line2, city, postcode, is_default)
         VALUES ($1,$2,$3,$4,$5,true)",
        [$userId, $line1, $line2 ?: null, $city, $postcode]
    );
}

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Could not save address.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Address saved successfully.',
    'data' => [
        'postcode' => $postcode,
        'address_line1' => $line1,
        'address_line2' => $line2,
        'city' => $city
    ]
]);
exit;
