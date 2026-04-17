<?php
session_start();

require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /send.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['send_order_error'] = 'Invalid request.';
    header('Location: /send.php');
    exit;
}

if (!empty($_POST['website'] ?? '')) {
    $_SESSION['send_order_error'] = 'Invalid submission.';
    header('Location: /send.php');
    exit;
}

function cleanText(string $value): string
{
    return trim($value);
}

function cleanPhone(string $value): string
{
    return preg_replace('/\s+/', '', trim($value));
}

function cleanPostcode(string $value): string
{
    $value = strtoupper(trim($value));
    $value = preg_replace('/\s+/', ' ', $value);
    return $value;
}

function redirectWithError(string $message): void
{
    $_SESSION['send_order_error'] = $message;
    header('Location: /send.php');
    exit;
}

$contactEmail = strtolower(trim($_POST['contact_email'] ?? ''));
$contactPhone = cleanPhone($_POST['contact_phone'] ?? '');

$senderName = cleanText($_POST['sender_name'] ?? '');
$senderAddress1 = cleanText($_POST['sender_address1'] ?? '');
$senderAddress2 = cleanText($_POST['sender_address2'] ?? '');
$senderCity = cleanText($_POST['sender_city'] ?? '');
$senderPostcode = cleanPostcode($_POST['sender_postcode'] ?? '');

$recipientName = cleanText($_POST['recipient_name'] ?? '');
$recipientAddress1 = cleanText($_POST['recipient_address1'] ?? '');
$recipientAddress2 = cleanText($_POST['recipient_address2'] ?? '');
$recipientCity = cleanText($_POST['recipient_city'] ?? '');
$recipientPostcode = cleanPostcode($_POST['recipient_postcode'] ?? '');
$deliveryInstructions = cleanText($_POST['delivery_instructions'] ?? '');

$weight = (float)($_POST['weight'] ?? 0);
$length = (float)($_POST['length'] ?? 0);
$width = (float)($_POST['width'] ?? 0);
$height = (float)($_POST['height'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$parcelValue = (float)($_POST['parcel_value'] ?? 0);
$deliveryType = $_POST['delivery_type'] ?? '';

$ukPostcodeRegex = '/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/';
$ukMobileRegex = '/^(?:\+44|0)7\d{9}$/';

if ($contactEmail !== '' && (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL) || strlen($contactEmail) > 150)) {
    redirectWithError('Please enter a valid contact email address.');
}

if (!preg_match($ukMobileRegex, $contactPhone)) {
    redirectWithError('Please enter a valid UK mobile number.');
}

if ($senderName === '' || strlen($senderName) > 100) {
    redirectWithError('Please enter the sender name.');
}

if ($senderAddress1 === '' || strlen($senderAddress1) > 150) {
    redirectWithError('Please enter sender address line 1.');
}

if (strlen($senderAddress2) > 150) {
    redirectWithError('Sender address line 2 is too long.');
}

if ($senderCity === '' || strlen($senderCity) > 100) {
    redirectWithError('Please enter the sender city.');
}

if (!preg_match($ukPostcodeRegex, $senderPostcode)) {
    redirectWithError('Please enter a valid sender postcode.');
}

if ($recipientName === '' || strlen($recipientName) > 100) {
    redirectWithError('Please enter the recipient name.');
}

if ($recipientAddress1 === '' || strlen($recipientAddress1) > 150) {
    redirectWithError('Please enter recipient address line 1.');
}

if (strlen($recipientAddress2) > 150) {
    redirectWithError('Recipient address line 2 is too long.');
}

if ($recipientCity === '' || strlen($recipientCity) > 100) {
    redirectWithError('Please enter the recipient city.');
}

if (!preg_match($ukPostcodeRegex, $recipientPostcode)) {
    redirectWithError('Please enter a valid recipient postcode.');
}

if (strlen($deliveryInstructions) > 1000) {
    redirectWithError('Delivery instructions are too long.');
}

if ($weight < 0.1 || $weight > 999.99) {
    redirectWithError('Please enter a valid parcel weight.');
}

if ($length < 1 || $length > 999.99 || $width < 1 || $width > 999.99 || $height < 1 || $height > 999.99) {
    redirectWithError('Please enter valid parcel dimensions.');
}

if ($quantity < 1 || $quantity > 20) {
    redirectWithError('Quantity must be between 1 and 20.');
}

if ($parcelValue < 0 || $parcelValue > 50000) {
    redirectWithError('Please enter a valid parcel value.');
}

if (!in_array($deliveryType, ['collection', 'dropoff'], true)) {
    redirectWithError('Please select a delivery option.');
}

// Simple quote logic for now
$basePrice = 4.99;
$weightCharge = $weight * 0.85;
$sizeCharge = (($length + $width + $height) / 100) * 0.60;
$quantityCharge = max(0, $quantity - 1) * 1.25;
$deliveryCharge = ($deliveryType === 'collection') ? 2.50 : 0.00;

$subtotal = $basePrice + $weightCharge + $sizeCharge + $quantityCharge + $deliveryCharge;
$vat = round($subtotal * 0.20, 2);
$total = round($subtotal + $vat, 2);

$_SESSION['send_order'] = [
    'contact_email' => $contactEmail,
    'contact_phone' => $contactPhone,

    'sender_name' => $senderName,
    'sender_address1' => $senderAddress1,
    'sender_address2' => $senderAddress2,
    'sender_city' => $senderCity,
    'sender_postcode' => $senderPostcode,

    'recipient_name' => $recipientName,
    'recipient_address1' => $recipientAddress1,
    'recipient_address2' => $recipientAddress2,
    'recipient_city' => $recipientCity,
    'recipient_postcode' => $recipientPostcode,
    'delivery_instructions' => $deliveryInstructions,

    'weight' => number_format($weight, 2, '.', ''),
    'length' => number_format($length, 2, '.', ''),
    'width' => number_format($width, 2, '.', ''),
    'height' => number_format($height, 2, '.', ''),
    'quantity' => $quantity,
    'parcel_value' => number_format($parcelValue, 2, '.', ''),
    'delivery_type' => $deliveryType,

    'quote' => [
        'base_price' => round($basePrice, 2),
        'weight_charge' => round($weightCharge, 2),
        'size_charge' => round($sizeCharge, 2),
        'quantity_charge' => round($quantityCharge, 2),
        'delivery_charge' => round($deliveryCharge, 2),
        'vat' => $vat,
        'total' => $total,
    ],
    'updated_at' => time(),
];

header('Location: /summary_payment.php');
exit;