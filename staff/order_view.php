<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['staff', 'admin']);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatStatusLabel(string $status): string
{
    return match ($status) {
        'order_placed' => 'Order Placed',
        'awaiting_collection' => 'Awaiting Collection',
        'collection_failed' => 'Collection Failed',
        'collected' => 'Collected',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivery_failed' => 'Delivery Failed',
        'delivered' => 'Delivered',
        'returned_to_sender' => 'Returned to Sender',
        'cancelled' => 'Cancelled',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function formatDeliveryTypeLabel(string $type): string
{
    return $type === 'collection'
        ? 'Collection from my address'
        : 'Drop-off at collection point';
}

function buildAddress(array $row, string $prefix): string
{
    $parts = [
        trim((string)($row[$prefix . '_address1'] ?? '')),
        trim((string)($row[$prefix . '_address2'] ?? '')),
        trim((string)($row[$prefix . '_city'] ?? '')),
        trim((string)($row[$prefix . '_postcode'] ?? '')),
    ];

    return implode(', ', array_values(array_filter($parts, static fn($part) => $part !== '')));
}

$reference = strtoupper(trim((string)($_GET['reference'] ?? '')));

if ($reference === '') {
    http_response_code(400);
    exit('Invalid reference.');
}

$orderResult = pg_query_params(
    $conn,
    "SELECT o.*, u.full_name AS customer_name
     FROM public.orders o
     LEFT JOIN public.users u ON u.id = o.user_id
     WHERE UPPER(o.reference_number) = $1
     LIMIT 1",
    [$reference]
);

$order = $orderResult ? pg_fetch_assoc($orderResult) : false;

if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$trackingResult = pg_query_params(
    $conn,
    "SELECT th.*, u.full_name AS updated_by_name
     FROM public.tracking_history th
     LEFT JOIN public.users u ON u.id = th.updated_by
     WHERE th.order_id = $1
     ORDER BY th.created_at DESC, th.id DESC",
    [(int)$order['id']]
);

$trackingRows = $trackingResult ? (pg_fetch_all($trackingResult) ?: []) : [];

$flashSuccess = $_SESSION['staff_success'] ?? '';
$flashError = $_SESSION['staff_error'] ?? '';
unset($_SESSION['staff_success'], $_SESSION['staff_error']);

$allowedStatuses = [
    'awaiting_collection',
    'collection_failed',
    'collected',
    'in_transit',
    'out_for_delivery',
    'delivery_failed',
    'delivered',
    'returned_to_sender',
];

if (($_SESSION['role'] ?? '') === 'admin') {
    $allowedStatuses[] = 'cancelled';
}
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Staff Order View</h2>
                    <p class="lead mb-0">Review order details and update delivery progress.</p>
                </div>
                <a href="/staff/dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
            </div>

            <?php if ($flashSuccess !== ''): ?>
                <div class="send-message-success rounded-3 p-3 mb-4"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError !== ''): ?>
                <div class="send-message-error rounded-3 p-3 mb-4"><?= e($flashError) ?></div>
            <?php endif; ?>

            <?php require_once __DIR__ . '/../includes/order_tracking_view.php'; ?>

            <div class="row g-4 mt-1">
                <div class="col-lg-6">
                    <div class="tracking-card h-100">
                        <h5 class="mb-3">Operational Details</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Customer</span>
                                    <strong><?= e($order['customer_name'] ?: 'Guest order') ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Payment Status</span>
                                    <strong><?= e(ucfirst((string)$order['payment_status'])) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Contact Phone</span>
                                    <strong><?= e($order['contact_phone']) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Contact Email</span>
                                    <strong><?= e($order['contact_email'] ?: 'Not provided') ?></strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Sender</span>
                                    <strong><?= e($order['sender_name']) ?></strong><br>
                                    <?= e(buildAddress($order, 'sender')) ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Recipient</span>
                                    <strong><?= e($order['recipient_name']) ?></strong><br>
                                    <?= e(buildAddress($order, 'recipient')) ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Weight</span>
                                    <strong><?= e($order['weight']) ?> kg</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Quantity</span>
                                    <strong><?= e($order['quantity']) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Value</span>
                                    <strong>£<?= number_format((float)$order['parcel_value'], 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="tracking-detail-box">
                                    <span class="tracking-detail-label">Parcel Size</span>
                                    <strong>
                                        L <?= e($order['length'] ?: 'N/A') ?> ×
                                        W <?= e($order['width'] ?: 'N/A') ?> ×
                                        H <?= e($order['height'] ?: 'N/A') ?> cm
                                    </strong>
                                </div>
                            </div>
                            <?php if (!empty($order['delivery_instructions'])): ?>
                                <div class="col-12">
                                    <div class="tracking-detail-box">
                                        <span class="tracking-detail-label">Delivery Instructions</span>
                                        <strong><?= nl2br(e($order['delivery_instructions'])) ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <?php $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; ?>

                    <div class="tracking-card mb-4">
                        <h5 class="mb-3">Update Parcel Progress</h5>

                        <?php if ($order['current_status'] === 'cancelled' && !$isAdmin): ?>
                            <div class="send-message-error rounded-3 p-3 mb-0">
                                This order has been cancelled and can only be changed by an admin.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="/staff/update_tracking.php" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                <input type="hidden" name="reference" value="<?= e($order['reference_number']) ?>">

                                <div class="mb-3">
                                    <label for="status" class="form-label">New Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <?php foreach ($allowedStatuses as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $status === $order['current_status'] ? 'selected' : '' ?>>
                                                <?= e(formatStatusLabel($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="location"
                                        name="location"
                                        maxlength="150"
                                        placeholder="e.g. Aberystwyth Depot">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Tracking Update</label>
                                    <textarea
                                        class="form-control"
                                        id="description"
                                        name="description"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Describe what changed or what action was taken."></textarea>
                                    <div class="form-text">Required when keeping the same status.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Save Update</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="tracking-card">
                        <h5 class="mb-3">Staff Rules</h5>
                        <ul class="mb-0 ps-3">
                            <li>Staff can update operational statuses only.</li>
                            <li>Only admins can set an order to Cancelled.</li>
                            <li>Tracking updates are recorded with the signed-in staff member.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>