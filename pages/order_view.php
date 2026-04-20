<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin();

$reference = $_GET['reference'] ?? '';

if ($reference === '') {
    die("Invalid reference.");
}

function formatStatusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function formatDeliveryTypeLabel(string $type): string {
    return $type === 'collection'
        ? 'Collection from my address'
        : 'Drop-off at collection point';
}

function buildAddress(array $order, string $prefix): string
{
    $parts = [
        $order[$prefix . '_address1'] ?? '',
        $order[$prefix . '_address2'] ?? '',
        $order[$prefix . '_city'] ?? '',
        $order[$prefix . '_postcode'] ?? ''
    ];

    return implode(', ', array_filter($parts));
}

// Get order (ONLY for this user)
$result = pg_query_params(
    $conn,
    "SELECT * FROM public.orders
     WHERE reference_number = $1 AND user_id = $2
     LIMIT 1",
    [$reference, $_SESSION['user_id']]
);

$order = pg_fetch_assoc($result);

if (!$order) {
    die("Order not found.");
}

// Get tracking history
$trackingResult = pg_query_params(
    $conn,
    "SELECT * FROM public.tracking_history
     WHERE order_id = $1
     ORDER BY created_at DESC",
    [$order['id']]
);

$trackingRows = pg_fetch_all($trackingResult) ?: [];
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">

            <h2 class="mb-4">Order Details</h2>

            <?php require_once __DIR__ . '/../includes/order_tracking_view.php'; ?>

        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
