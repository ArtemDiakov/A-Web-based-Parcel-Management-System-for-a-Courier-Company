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
                        <div class="tracking-card mb-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                <div>
                                    <h4 class="mb-1">Parcel details</h4>
                                    <p class="text-muted mb-0">Latest tracking summary for this parcel.</p>
                                </div>
                                <div class="tracking-reference-badge">
                                    <span class="d-block small text-muted mb-1">Order Reference Number</span>
                                    <strong><?= e($order['reference_number']) ?></strong>
                                </div>
                            </div>

                            <div class="tracking-progress mb-4">
                                <?php foreach ($statusSteps as $index => $step): ?>
                                    <div class="tracking-progress-item <?= $index <= $currentStepIndex ? 'active' : '' ?>">
                                        <div class="tracking-progress-circle"></div>
                                        <div class="tracking-progress-label"><?= e(formatStatusLabel($step)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="row g-3 tracking-detail-grid">
                                <div class="col-md-6">
                                    <div class="tracking-detail-box">
                                        <span class="tracking-detail-label">Current Status</span>
                                        <strong><?= e(formatStatusLabel((string)$order['current_status'])) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tracking-detail-box">
                                        <span class="tracking-detail-label">Delivery Type</span>
                                        <strong><?= e(formatDeliveryTypeLabel((string)$order['delivery_type'])) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tracking-detail-box">
                                        <span class="tracking-detail-label">Estimated Delivery Date</span>
                                        <strong>
                                            <?= !empty($order['estimated_delivery_date'])
                                                ? e(date('d/m/Y', strtotime((string)$order['estimated_delivery_date'])))
                                                : 'Not available' ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tracking-detail-box">
                                        <span class="tracking-detail-label">Recipient Name</span>
                                        <strong><?= e($order['recipient_name']) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tracking-detail-box h-100">
                                        <span class="tracking-detail-label">Delivery Address</span>
                                        <strong><?= e(buildAddress($order, 'recipient')) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="tracking-detail-box h-100">
                                        <span class="tracking-detail-label">Parcel Weight</span>
                                        <strong><?= e(number_format((float)$order['weight'], 2)) ?> kg</strong>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="tracking-detail-box h-100">
                                        <span class="tracking-detail-label">Total Paid</span>
                                        <strong>£<?= e(number_format((float)($order['paid_amount'] ?? $order['total_price']), 2)) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tracking-card">
                            <div class="mb-4">
                                <h4 class="mb-1">Tracking history</h4>
                                <p class="text-muted mb-0">Most recent updates for this parcel.</p>
                            </div>

                            <?php if (!empty($trackingHistory)): ?>
                                <div class="table-responsive">
                                    <table class="table tracking-history-table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date &amp; Time</th>
                                                <th>Location</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($trackingHistory as $entry): ?>
                                                <tr>
                                                    <td>
                                                        <?= e(date('d/m/Y - H:i', strtotime((string)$entry['created_at']))) ?>
                                                    </td>
                                                    <td>
                                                        <?= e($entry['location'] ?: '—') ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold mb-1"><?= e(formatStatusLabel((string)$entry['status'])) ?></div>
                                                        <div class="text-muted small"><?= e($entry['description'] ?: 'Tracking update recorded.') ?></div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="tracking-empty-state">
                                    No tracking history is available for this parcel yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="/js/track.js"></script>
</body>

</html>
