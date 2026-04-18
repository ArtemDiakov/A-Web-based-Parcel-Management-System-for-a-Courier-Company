<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /send.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['send_order_error'] = 'Invalid request.';
    header('Location: /summary_payment.php');
    exit;
}

$sendOrder = $_SESSION['send_order'] ?? null;

if (!$sendOrder || !is_array($sendOrder)) {
    $_SESSION['send_order_error'] = 'Your order session has expired. Please enter the parcel details again.';
    header('Location: /send.php');
    exit;
}

function redirectSummaryError(string $message): void
{
    $_SESSION['send_order_error'] = $message;
    header('Location: /summary_payment.php');
    exit;
}

function generateReferenceNumber($conn): string
{
    do {
        $reference = 'PP' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));

        $check = pg_query_params(
            $conn,
            "SELECT 1 FROM public.orders WHERE reference_number = $1 LIMIT 1",
            [$reference]
        );

        $exists = $check && pg_num_rows($check) > 0;
    } while ($exists);

    return $reference;
}

function estimateDeliveryDate(string $deliveryType): string
{
    $daysToAdd = ($deliveryType === 'collection') ? 2 : 3;
    $date = new DateTime();

    while ($daysToAdd > 0) {
        $date->modify('+1 day');
        $dayOfWeek = (int) $date->format('N'); // 1 = Mon, 7 = Sun

        if ($dayOfWeek < 6) {
            $daysToAdd--;
        }
    }

    return $date->format('Y-m-d');
}

$quote = $sendOrder['quote'] ?? null;

if (!$quote || !is_array($quote)) {
    redirectSummaryError('Pricing information is missing. Please return to the send page and try again.');
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

$contactEmail = trim((string)($sendOrder['contact_email'] ?? ''));
$contactPhone = trim((string)($sendOrder['contact_phone'] ?? ''));

$senderName = trim((string)($sendOrder['sender_name'] ?? ''));
$senderAddress1 = trim((string)($sendOrder['sender_address1'] ?? ''));
$senderAddress2 = trim((string)($sendOrder['sender_address2'] ?? ''));
$senderCity = trim((string)($sendOrder['sender_city'] ?? ''));
$senderPostcode = trim((string)($sendOrder['sender_postcode'] ?? ''));

$recipientName = trim((string)($sendOrder['recipient_name'] ?? ''));
$recipientAddress1 = trim((string)($sendOrder['recipient_address1'] ?? ''));
$recipientAddress2 = trim((string)($sendOrder['recipient_address2'] ?? ''));
$recipientCity = trim((string)($sendOrder['recipient_city'] ?? ''));
$recipientPostcode = trim((string)($sendOrder['recipient_postcode'] ?? ''));
$deliveryInstructions = trim((string)($sendOrder['delivery_instructions'] ?? ''));

$weight = (float)($sendOrder['weight'] ?? 0);
$length = (float)($sendOrder['length'] ?? 0);
$width = (float)($sendOrder['width'] ?? 0);
$height = (float)($sendOrder['height'] ?? 0);
$quantity = (int)($sendOrder['quantity'] ?? 0);
$parcelValue = (float)($sendOrder['parcel_value'] ?? 0);
$deliveryType = (string)($sendOrder['delivery_type'] ?? '');

if (
    $contactPhone === '' ||
    $senderName === '' ||
    $senderAddress1 === '' ||
    $senderCity === '' ||
    $senderPostcode === '' ||
    $recipientName === '' ||
    $recipientAddress1 === '' ||
    $recipientCity === '' ||
    $recipientPostcode === '' ||
    $weight <= 0 ||
    $quantity <= 0 ||
    !in_array($deliveryType, ['collection', 'dropoff'], true)
) {
    redirectSummaryError('Some required order details are missing. Please review your parcel details.');
}

$basePrice = (float)($quote['base_price'] ?? 0);
$weightCharge = (float)($quote['weight_charge'] ?? 0);
$sizeCharge = (float)($quote['size_charge'] ?? 0);
$quantityCharge = (float)($quote['quantity_charge'] ?? 0);
$collectionCharge = (float)($quote['delivery_charge'] ?? 0);
$totalPrice = (float)($quote['total'] ?? 0);

$deliveryPrice = round($basePrice + $weightCharge + $sizeCharge + $quantityCharge, 2);
$collectionPrice = round($collectionCharge, 2);

$referenceNumber = generateReferenceNumber($conn);
$estimatedDeliveryDate = estimateDeliveryDate($deliveryType);

pg_query($conn, 'BEGIN');

$insertOrderSql = "
    INSERT INTO public.orders (
        reference_number,
        user_id,
        sender_name,
        sender_address1,
        sender_address2,
        sender_city,
        sender_postcode,
        recipient_name,
        recipient_address1,
        recipient_address2,
        recipient_city,
        recipient_postcode,
        delivery_instructions,
        weight,
        length,
        width,
        height,
        quantity,
        parcel_value,
        delivery_type,
        delivery_price,
        collection_price,
        total_price,
        current_status,
        estimated_delivery_date,
        contact_email,
        contact_phone,
        payment_status,
        updated_at
    )
    VALUES (
        $1, $2, $3, $4, $5, $6, $7,
        $8, $9, $10, $11, $12, $13,
        $14, $15, $16, $17, $18, $19,
        $20::delivery_type_enum,
        $21, $22, $23,
        'order_placed'::order_status_enum,
        $24,
        $25, $26,
        'paid'::payment_status_enum,
        NOW()
    )
    RETURNING id, reference_number
";

$orderParams = [
    $referenceNumber,
    $userId,
    $senderName,
    $senderAddress1,
    ($senderAddress2 !== '' ? $senderAddress2 : null),
    $senderCity,
    $senderPostcode,
    $recipientName,
    $recipientAddress1,
    ($recipientAddress2 !== '' ? $recipientAddress2 : null),
    $recipientCity,
    $recipientPostcode,
    ($deliveryInstructions !== '' ? $deliveryInstructions : null),
    $weight,
    $length,
    $width,
    $height,
    $quantity,
    $parcelValue,
    $deliveryType,
    $deliveryPrice,
    $collectionPrice,
    $totalPrice,
    $estimatedDeliveryDate,
    ($contactEmail !== '' ? $contactEmail : null),
    $contactPhone
];

$orderResult = pg_query_params($conn, $insertOrderSql, $orderParams);

if (!$orderResult) {
    pg_query($conn, 'ROLLBACK');
    redirectSummaryError('Could not place the order. Please try again.');
}

$orderRow = pg_fetch_assoc($orderResult);
$orderId = (int)$orderRow['id'];

$transactionReference = 'DEMO-' . strtoupper(bin2hex(random_bytes(4)));

$insertPaymentSql = "
    INSERT INTO public.payments (
        order_id,
        payment_method,
        amount,
        payment_status,
        transaction_reference
    )
    VALUES ($1, $2, $3, 'paid'::payment_status_enum, $4)
";

$paymentParams = [
    $orderId,
    'card_demo',
    $totalPrice,
    $transactionReference
];

$paymentResult = pg_query_params($conn, $insertPaymentSql, $paymentParams);

if (!$paymentResult) {
    pg_query($conn, 'ROLLBACK');
    redirectSummaryError('Order was created but payment record failed. Please try again.');
}

$insertTrackingSql = "
    INSERT INTO public.tracking_history (
        order_id,
        status,
        location,
        description,
        updated_by
    )
    VALUES ($1, 'order_placed'::order_status_enum, $2, $3, $4)
";

$trackingParams = [
    $orderId,
    $senderCity,
    'Order placed and payment confirmed.',
    13
];

$trackingResult = pg_query_params($conn, $insertTrackingSql, $trackingParams);

if (!$trackingResult) {
    pg_query($conn, 'ROLLBACK');
    redirectSummaryError('Order was created but tracking history failed. Please try again.');
}

pg_query($conn, 'COMMIT');

$_SESSION['last_order'] = [
    'order_id' => $orderId,
    'reference_number' => $referenceNumber,
    'payment_status' => 'paid',
    'contact_phone' => $contactPhone,
    'contact_email' => $contactEmail,
    'delivery_type' => $deliveryType,
    'total_price' => number_format($totalPrice, 2, '.', ''),
    'estimated_delivery_date' => $estimatedDeliveryDate
];

unset($_SESSION['send_order']);

header('Location: /confirmation.php');
exit;
