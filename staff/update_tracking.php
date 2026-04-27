<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sms.php';

requireRole(['staff', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /staff/dashboard.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['staff_error'] = 'Invalid request.';
    header('Location: /staff/dashboard.php');
    exit;
}

$reference = strtoupper(trim((string)($_POST['reference'] ?? '')));
$status = trim((string)($_POST['status'] ?? ''));
$location = trim((string)($_POST['location'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$userRole = (string)($_SESSION['role'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($reference === '') {
    $_SESSION['staff_error'] = 'Missing order reference.';
    header('Location: /staff/dashboard.php');
    exit;
}

$staffAllowedStatuses = [
    'awaiting_collection',
    'collection_failed',
    'collected',
    'in_transit',
    'out_for_delivery',
    'delivery_failed',
    'delivered',
    'returned_to_sender',
];

$adminOnlyStatuses = ['cancelled'];

if (in_array($status, $adminOnlyStatuses, true) && $userRole !== 'admin') {
    $_SESSION['staff_error'] = 'Only admins can cancel an order.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

if (!in_array($status, $staffAllowedStatuses, true) && !($userRole === 'admin' && in_array($status, $adminOnlyStatuses, true))) {
    $_SESSION['staff_error'] = 'Invalid status selected.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

if ($location !== '' && strlen($location) > 150) {
    $_SESSION['staff_error'] = 'Location is too long.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

if ($description !== '' && strlen($description) > 1000) {
    $_SESSION['staff_error'] = 'Tracking update is too long.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

$orderResult = pg_query_params(
    $conn,
    "SELECT id, current_status, contact_phone FROM public.orders WHERE UPPER(reference_number) = $1 LIMIT 1",
    [$reference]
);

$order = $orderResult ? pg_fetch_assoc($orderResult) : false;

if (!$order) {
    $_SESSION['staff_error'] = 'Order not found.';
    header('Location: /staff/dashboard.php');
    exit;
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($order['current_status'] === 'cancelled' && !$isAdmin) {
    $_SESSION['staff_error'] = 'Cancelled orders can only be changed by an admin.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

if ($status === $order['current_status'] && $description === '') {
    $_SESSION['staff_error'] = 'Add a tracking update note when keeping the same status.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

if ($location === '') {
    $location = null;
}

if ($description === '') {
    $description = 'Status updated to ' . str_replace('_', ' ', $status) . '.';
}

pg_query($conn, 'BEGIN');

$updateResult = pg_query_params(
    $conn,
    "UPDATE public.orders
     SET current_status = $1::order_status_enum,
         updated_at = NOW()
     WHERE id = $2",
    [$status, (int)$order['id']]
);

$trackingResult = pg_query_params(
    $conn,
    "INSERT INTO public.tracking_history (order_id, status, location, description, updated_by)
     VALUES ($1, $2::order_status_enum, $3, $4, $5)",
    [(int)$order['id'], $status, $location, $description, $userId]
);

if (!$updateResult || !$trackingResult) {
    pg_query($conn, 'ROLLBACK');
    $_SESSION['staff_error'] = 'Could not save the tracking update.';
    header('Location: /staff/order_view.php?reference=' . urlencode($reference));
    exit;
}

$statusText = str_replace('_', ' ', $status);

$smsMessage = "ParcelPro: Your parcel {$reference} status is now {$statusText}.";

if ($location !== null) {
    $smsMessage .= " Location: {$location}.";
}

sendParcelSms($conn, (int)$order['id'], (string)$order['contact_phone'], $smsMessage);

pg_query($conn, 'COMMIT');

$_SESSION['staff_success'] = 'Tracking updated successfully.';
header('Location: /staff/order_view.php?reference=' . urlencode($reference));
exit;
