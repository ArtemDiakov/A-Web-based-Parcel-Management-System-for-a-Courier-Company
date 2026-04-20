<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/csrf.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatStatusLabel(string $status): string
{
    return match ($status) {
        'order_placed' => 'Order Placed',
        'awaiting_collection' => 'Awaiting Collection',
        'collected' => 'Collected',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function formatDeliveryTypeLabel(string $deliveryType): string
{
    return match ($deliveryType) {
        'collection' => 'Collection from my address',
        'dropoff' => 'Drop-off at collection point',
        default => ucwords(str_replace('_', ' ', $deliveryType)),
    };
}

function buildAddress(array $row, string $prefix): string
{
    $parts = [
        trim((string)($row[$prefix . '_address1'] ?? '')),
        trim((string)($row[$prefix . '_address2'] ?? '')),
        trim((string)($row[$prefix . '_city'] ?? '')),
        trim((string)($row[$prefix . '_postcode'] ?? '')),
    ];

    $parts = array_values(array_filter($parts, static fn($value) => $value !== ''));
    return implode(', ', $parts);
}

$reference = strtoupper(trim((string)($_GET['reference'] ?? '')));
$referenceError = '';
$order = null;
$trackingHistory = [];

$referencePattern = '/^PP\d{8}[A-F0-9]{6}$/';
$statusSteps = [
    'order_placed',
    'awaiting_collection',
    'collected',
    'in_transit',
    'out_for_delivery',
    'delivered',
];

if ($reference !== '') {
    if (!preg_match($referencePattern, $reference)) {
        $referenceError = 'Please enter a valid reference number in the correct format.';
    } else {
        $orderSql = "
            SELECT
                o.id,
                o.reference_number,
                o.current_status,
                o.delivery_type,
                o.estimated_delivery_date,
                o.recipient_name,
                o.recipient_address1,
                o.recipient_address2,
                o.recipient_city,
                o.recipient_postcode,
                o.weight,
                o.total_price,
                o.created_at,
                p.amount AS paid_amount,
                p.payment_status
            FROM public.orders o
            LEFT JOIN public.payments p ON p.order_id = o.id
            WHERE UPPER(o.reference_number) = $1
            LIMIT 1
        ";

        $orderResult = pg_query_params($conn, $orderSql, [$reference]);

        if ($orderResult && pg_num_rows($orderResult) > 0) {
            $order = pg_fetch_assoc($orderResult);

            $historySql = "
                SELECT
                    th.status,
                    th.location,
                    th.description,
                    th.created_at
                FROM public.tracking_history th
                WHERE th.order_id = $1
                ORDER BY th.created_at DESC, th.id DESC
            ";

            $historyResult = pg_query_params($conn, $historySql, [(int)$order['id']]);

            if ($historyResult) {
                while ($row = pg_fetch_assoc($historyResult)) {
                    $trackingHistory[] = $row;
                }
            }
        } else {
            $referenceError = 'No parcel was found for that reference number.';
        }
    }
}

$currentStatus = $order['current_status'] ?? '';
$currentStepIndex = array_search($currentStatus, $statusSteps, true);
if ($currentStepIndex === false) {
    $currentStepIndex = -1;
}
?>

<body>
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="text-center mb-4">
                        <h2 class="mb-2">Track Your Parcel</h2>
                        <p class="lead mb-0">Enter your parcel reference number to view the latest delivery updates.</p>
                    </div>

                    <div class="form-panel mb-4">
                        <form action="/track.php" method="GET" id="trackParcelForm" novalidate>
                            <div class="row g-3 align-items-start">
                                <div class="col-lg-9">
                                    <label for="reference" class="form-label">Reference Number</label>
                                    <input
                                        type="text"
                                        class="form-control <?= $referenceError !== '' ? 'is-invalid' : '' ?>"
                                        id="reference"
                                        name="reference"
                                        placeholder="PP20260419ABC123"
                                        maxlength="16"
                                        value="<?= e($reference) ?>"
                                        required>
                                    <div class="invalid-feedback">
                                        <?= $referenceError !== '' ? e($referenceError) : 'Please enter a valid reference number.' ?>
                                    </div>
                                </div>
                                <div class="col-lg-3 d-grid">
                                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-lg">Track</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($order): ?>
                        <?php
                        // Map variable names to what the include expects
                        $trackingRows = $trackingHistory ?? [];
                        ?>

                        <?php require_once __DIR__ . '/includes/order_tracking_view.php'; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="/js/track.js"></script>
</body>

</html>