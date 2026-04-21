<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

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

function normalisePostcodeSearch(string $value): string
{
    return strtoupper(preg_replace('/\s+/', '', trim($value)));
}

$search = trim((string)($_GET['q'] ?? ''));
$normalisedSearch = normalisePostcodeSearch($search);
$statusFilter = trim((string)($_GET['status'] ?? 'active'));

$summarySql = "
    SELECT
        COUNT(*) FILTER (WHERE current_status = 'awaiting_collection') AS awaiting_collection_count,
        COUNT(*) FILTER (WHERE current_status = 'in_transit') AS in_transit_count,
        COUNT(*) FILTER (WHERE current_status = 'out_for_delivery') AS out_for_delivery_count,
        COUNT(*) FILTER (
            WHERE current_status IN ('collection_failed', 'delivery_failed', 'returned_to_sender')
        ) AS exception_count,
        COUNT(*) FILTER (
            WHERE current_status = 'delivered'
              AND DATE(updated_at) = CURRENT_DATE
        ) AS delivered_today_count
    FROM public.orders
";

$summaryResult = pg_query($conn, $summarySql);
$summary = $summaryResult ? pg_fetch_assoc($summaryResult) : [];

$whereParts = [];
$params = [];
$paramIndex = 1;

$whereParts[] = "o.current_status <> 'cancelled'::order_status_enum";

if ($search !== '') {
    $params[] = '%' . $search . '%';
    $likeParam = '$' . $paramIndex++;

    $params[] = '%' . $normalisedSearch . '%';
    $postcodeParam = '$' . $paramIndex++;

    $whereParts[] = "(
        o.reference_number ILIKE {$likeParam}
        OR o.recipient_name ILIKE {$likeParam}
        OR REPLACE(UPPER(o.recipient_postcode), ' ', '') LIKE {$postcodeParam}
        OR REPLACE(UPPER(o.sender_postcode), ' ', '') LIKE {$postcodeParam}
    )";
}

switch ($statusFilter) {
    case 'awaiting_collection':
        $whereParts[] = "o.current_status = 'awaiting_collection'::order_status_enum";
        break;

    case 'collected':
        $whereParts[] = "o.current_status = 'collected'::order_status_enum";
        break;

    case 'in_transit':
        $whereParts[] = "o.current_status = 'in_transit'::order_status_enum";
        break;

    case 'out_for_delivery':
        $whereParts[] = "o.current_status = 'out_for_delivery'::order_status_enum";
        break;

    case 'exceptions':
        $whereParts[] = "o.current_status IN (
            'collection_failed'::order_status_enum,
            'delivery_failed'::order_status_enum,
            'returned_to_sender'::order_status_enum
        )";
        break;

    case 'delivered':
        $whereParts[] = "o.current_status = 'delivered'::order_status_enum";
        break;

    case 'active':
    default:
        $whereParts[] = "o.current_status IN (
            'order_placed'::order_status_enum,
            'awaiting_collection'::order_status_enum,
            'collection_failed'::order_status_enum,
            'collected'::order_status_enum,
            'in_transit'::order_status_enum,
            'out_for_delivery'::order_status_enum,
            'delivery_failed'::order_status_enum,
            'returned_to_sender'::order_status_enum
        )";
        break;
}

$whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$orderSql = "
    SELECT
        o.reference_number,
        o.recipient_name,
        o.recipient_postcode,
        o.delivery_type,
        o.current_status,
        o.estimated_delivery_date,
        o.total_price,
        o.created_at,
        u.full_name AS customer_name
    FROM public.orders o
    LEFT JOIN public.users u ON u.id = o.user_id
    {$whereSql}
    ORDER BY o.created_at DESC
    LIMIT 100
";

$orderResult = pg_query_params($conn, $orderSql, $params);
$orders = $orderResult ? (pg_fetch_all($orderResult) ?: []) : [];
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Staff Dashboard</h2>
                    <p class="lead mb-0">Search, filter and manage live parcel orders.</p>
                </div>
                <div class="text-muted small">
                    Signed in as <strong><?= e($_SESSION['full_name'] ?? '') ?></strong>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-2-4">
                    <div class="tracking-detail-box h-100">
                        <span class="tracking-detail-label">Awaiting Collection</span>
                        <strong class="fs-4"><?= (int)($summary['awaiting_collection_count'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4">
                    <div class="tracking-detail-box h-100">
                        <span class="tracking-detail-label">In Transit</span>
                        <strong class="fs-4"><?= (int)($summary['in_transit_count'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4">
                    <div class="tracking-detail-box h-100">
                        <span class="tracking-detail-label">Out for Delivery</span>
                        <strong class="fs-4"><?= (int)($summary['out_for_delivery_count'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4">
                    <div class="tracking-detail-box h-100 border-danger-subtle">
                        <span class="tracking-detail-label">Exceptions</span>
                        <strong class="fs-4"><?= (int)($summary['exception_count'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4">
                    <div class="tracking-detail-box h-100">
                        <span class="tracking-detail-label">Delivered Today</span>
                        <strong class="fs-4"><?= (int)($summary['delivered_today_count'] ?? 0) ?></strong>
                    </div>
                </div>
            </div>

            <div class="tracking-card mb-4">
                <h5 class="mb-3">Search & Filters</h5>

                <form method="GET" action="/staff/dashboard.php" class="row g-3 align-items-end">

                    <div class="col-lg-6">
                        <label for="q" class="form-label">Search</label>
                        <input
                            type="text"
                            class="form-control"
                            id="q"
                            name="q"
                            value="<?= e($search) ?>"
                            placeholder="Reference, recipient name or postcode">
                    </div>

                    <div class="col-lg-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>All active</option>
                            <option value="awaiting_collection" <?= $statusFilter === 'awaiting_collection' ? 'selected' : '' ?>>Awaiting collection</option>
                            <option value="collected" <?= $statusFilter === 'collected' ? 'selected' : '' ?>>Collected</option>
                            <option value="in_transit" <?= $statusFilter === 'in_transit' ? 'selected' : '' ?>>In transit</option>
                            <option value="out_for_delivery" <?= $statusFilter === 'out_for_delivery' ? 'selected' : '' ?>>Out for delivery</option>
                            <option value="exceptions" <?= $statusFilter === 'exceptions' ? 'selected' : '' ?>>Exceptions</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>

                    <div class="col-lg-3 d-grid">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>

                </form>
            </div>

            <div class="tracking-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Order Work Queue</h5>
                    <span class="text-muted small"><?= count($orders) ?> result(s)</span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="tracking-empty-state">
                        No matching orders were found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table tracking-history-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Recipient</th>
                                    <th>Postcode</th>
                                    <th>Status</th>
                                    <th>Delivery</th>
                                    <th>Created</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $row): ?>
                                    <?php
                                    $status = (string)$row['current_status'];
                                    $isException = in_array($status, ['collection_failed', 'delivery_failed', 'returned_to_sender'], true);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($row['reference_number']) ?></strong><br>
                                            <small class="text-muted">£<?= number_format((float)$row['total_price'], 2) ?></small>
                                        </td>
                                        <td>
                                            <?= e($row['recipient_name']) ?><br>
                                            <small class="text-muted"><?= e($row['customer_name'] ?: 'Guest order') ?></small>
                                        </td>
                                        <td><?= e($row['recipient_postcode']) ?></td>
                                        <td>
                                            <span class="badge <?= $isException ? 'text-bg-danger' : 'text-bg-light' ?> staff-status-badge">
                                                <?= e(formatStatusLabel($status)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?= e(ucfirst((string)$row['delivery_type'])) ?></div>
                                            <small class="text-muted">
                                                ETA: <?= e($row['estimated_delivery_date'] ?: 'N/A') ?>
                                            </small>
                                        </td>
                                        <td><?= date('d M Y H:i', strtotime((string)$row['created_at'])) ?></td>
                                        <td class="text-end">
                                            <a href="/staff/order_view.php?reference=<?= urlencode((string)$row['reference_number']) ?>" class="btn btn-outline-primary btn-sm">
                                                Open
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>