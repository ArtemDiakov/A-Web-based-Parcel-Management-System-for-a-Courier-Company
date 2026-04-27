<?php

if (!function_exists('formatStatusLabel')) {
    function formatStatusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}

if (!function_exists('formatDeliveryTypeLabel')) {
    function formatDeliveryTypeLabel(string $type): string
    {
        return $type === 'collection'
            ? 'Collection from my address'
            : 'Drop-off at collection point';
    }
}

if (!function_exists('buildAddress')) {
    function buildAddress(array $order, string $prefix): string
    {
        return implode(', ', array_filter([
            $order[$prefix . '_address1'] ?? '',
            $order[$prefix . '_address2'] ?? '',
            $order[$prefix . '_city'] ?? '',
            $order[$prefix . '_postcode'] ?? ''
        ]));
    }
}

if (!isset($order) || !is_array($order)) {
    return;
}

$progressSteps = [
    'order_placed',
    'awaiting_collection',
    'collected',
    'in_transit',
    'out_for_delivery',
    'delivered'
];

$currentIndex = array_search($order['current_status'], $progressSteps, true);

$exceptionStatuses = [
    'collection_failed',
    'delivery_failed',
    'returned_to_sender',
    'cancelled'
];
?>

<div class="tracking-card mb-4">

    <div class="tracking-reference-badge mb-4">
        <span class="d-block text-muted small">Order Reference</span>
        <strong><?= htmlspecialchars((string)$order['reference_number'], ENT_QUOTES, 'UTF-8') ?></strong>
    </div>

    <?php if (in_array($order['current_status'], $exceptionStatuses, true)): ?>
        <div class="alert alert-danger mb-4">
            <?= htmlspecialchars(formatStatusLabel((string)$order['current_status']), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="tracking-progress mb-4">
        <?php foreach ($progressSteps as $index => $step): ?>
            <div class="tracking-progress-item <?= ($currentIndex !== false && $index <= $currentIndex) ? 'active' : '' ?>">
                <div class="tracking-progress-circle"></div>
                <div class="tracking-progress-label"><?= htmlspecialchars(formatStatusLabel($step), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Current Status</span>
                <strong><?= htmlspecialchars(formatStatusLabel((string)$order['current_status']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Delivery Type</span>
                <strong><?= htmlspecialchars(formatDeliveryTypeLabel((string)$order['delivery_type']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Estimated Delivery</span>
                <strong><?= htmlspecialchars((string)($order['estimated_delivery_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Total Paid</span>
                <strong>£<?= number_format((float)$order['total_price'], 2) ?></strong>
            </div>
        </div>

        <div class="col-12">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Recipient</span>
                <strong><?= htmlspecialchars((string)$order['recipient_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                <?= htmlspecialchars(buildAddress($order, 'recipient'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

    </div>
</div>

<div class="tracking-card">
    <h5 class="mb-3">Tracking History</h5>

    <?php if (empty($trackingRows)): ?>
        <div class="tracking-empty-state">
            No tracking updates available yet.
        </div>
    <?php else: ?>
        <table class="table tracking-history-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Location</th>
                    <th>Description</th>

                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['staff', 'admin'], true)): ?>
                        <th>Updated By</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trackingRows as $row): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime((string)$row['created_at'])) ?></td>
                        <td><?= htmlspecialchars((string)($row['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

                        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['staff', 'admin'], true)): ?>
                            <td><?= htmlspecialchars((string)($row['updated_by_name'] ?? 'System'), ENT_QUOTES, 'UTF-8') ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>