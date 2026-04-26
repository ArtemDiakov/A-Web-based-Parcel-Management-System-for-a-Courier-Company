<?php
session_start();

require_once __DIR__ . '/../includes/csrf.php';

function redirectPaymentError(string $message): void
{
    $_SESSION['send_order_error'] = $message;
    header('Location: /summary_payment.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /send.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirectPaymentError('Invalid payment request.');
}

$sendOrder = $_SESSION['send_order'] ?? null;

if (!$sendOrder || empty($sendOrder['quote']['total'])) {
    $_SESSION['send_order_error'] = 'Order summary expired. Please try again.';
    header('Location: /send.php');
    exit;
}

$stripeSecretKey = 'sk_test_REDACTED';

if (!str_starts_with($stripeSecretKey, 'sk_test_')) {
    redirectPaymentError('Stripe test secret key is missing.');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $scheme . '://' . $host;

$totalPence = (int) round(((float)$sendOrder['quote']['total']) * 100);

if ($totalPence < 50) {
    redirectPaymentError('Payment amount is too low for Stripe.');
}

$successUrl = $baseUrl . '/orders/complete_order.php?stripe_session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/summary_payment.php?payment_cancelled=1';

$stripeFields = [
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'client_reference_id' => 'parcelpro_' . session_id(),
    'metadata[source]' => 'parcelpro_coursework',
    'metadata[contact_phone]' => $sendOrder['contact_phone'] ?? '',
    'line_items[0][quantity]' => 1,
    'line_items[0][price_data][currency]' => 'gbp',
    'line_items[0][price_data][unit_amount]' => $totalPence,
    'line_items[0][price_data][product_data][name]' => 'ParcelPro delivery order',
];

if (!empty($sendOrder['contact_email'])) {
    $stripeFields['customer_email'] = $sendOrder['contact_email'];
}

$postFields = http_build_query($stripeFields);

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');

if ($ch === false) {
    redirectPaymentError('Stripe connection could not be initialised.');
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $stripeSecretKey,
        'Content-Type: application/x-www-form-urlencoded',
    ],
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode((string)$response, true);

if ($response === false || $curlError !== '') {
    redirectPaymentError('Could not connect to Stripe: ' . $curlError);
}

if ($httpCode < 200 || $httpCode >= 300 || empty($data['url']) || empty($data['id'])) {
    $stripeMessage = $data['error']['message'] ?? 'Unknown Stripe error.';
    redirectPaymentError('Could not start Stripe payment: ' . $stripeMessage);
}

$_SESSION['stripe_checkout_session_id'] = $data['id'];

header('Location: ' . $data['url']);
exit;
