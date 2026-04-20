<?php

if (!function_exists('formatStatus')) {
    function formatStatus($status)
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}

if (!function_exists('buildAddress')) {
    function buildAddress($prefix, $order)
    {
        return implode(', ', array_filter([
            $order[$prefix . '_address1'] ?? '',
            $order[$prefix . '_address2'] ?? '',
            $order[$prefix . '_city'] ?? '',
            $order[$prefix . '_postcode'] ?? ''
        ]));
    }
}

$progressSteps = [
    'order_placed',
    'awaiting_collection',
    'collected',
    'in_transit',
    'out_for_delivery',
    'delivered'
];

$currentIndex = array_search($order['current_status'], $progressSteps);

$exceptionStatuses = [
    'collection_failed',
    'delivery_failed',
    'returned_to_sender',
    'cancelled'
];
?>

<div class="tracking-card mb-4">

    <!-- Reference -->
    <div class="tracking-reference-badge mb-4">
        <span class="d-block text-muted small">Order Reference</span>
        <strong><?= htmlspecialchars($order['reference_number']) ?></strong>
    </div>

    <!-- Exception -->
    <?php if (in_array($order['current_status'], $exceptionStatuses)): ?>
        <div class="alert alert-danger mb-4">
            <?= formatStatusLabel($order['current_status']) ?>
        </div>
    <?php endif; ?>

    <!-- Progress -->
    <div class="tracking-progress mb-4">
        <?php foreach ($progressSteps as $index => $step): ?>
            <div class="tracking-progress-item <?= ($index <= $currentIndex) ? 'active' : '' ?>">
                <div class="tracking-progress-circle"></div>
                <div class="tracking-progress-label"><?= formatStatusLabel($step) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Details -->
    <div class="row g-3">

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Current Status</span>
                <strong><?= formatStatusLabel($order['current_status']) ?></strong>
            </div>
        </div>

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Delivery Type</span>
                <strong><?= formatStatusLabel($order['delivery_type']) ?></strong>
            </div>
        </div>

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Estimated Delivery</span>
                <strong><?= htmlspecialchars($order['estimated_delivery_date']) ?></strong>
            </div>
        </div>

        <div class="col-md-6">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Total Paid</span>
                <strong>£<?= htmlspecialchars($order['total_price']) ?></strong>
            </div>
        </div>

        <div class="col-12">
            <div class="tracking-detail-box">
                <span class="tracking-detail-label">Recipient</span>
                <strong><?= htmlspecialchars($order['recipient_name']) ?></strong><br>
                <?= htmlspecialchars(buildAddress($order, 'recipient')) ?>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trackingRows as $row): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>