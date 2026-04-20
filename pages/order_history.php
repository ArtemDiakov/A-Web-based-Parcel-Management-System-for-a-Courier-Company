<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin();

$userId = $_SESSION['user_id'];

$result = pg_query_params(
    $conn,
    "SELECT reference_number, created_at, total_price, current_status
     FROM public.orders
     WHERE user_id = $1
     ORDER BY created_at DESC",
    [$userId]
);
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">

            <h2 class="mb-4">Order History</h2>

            <div class="form-panel">

                <div class="tracking-card">

                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">

                            <div>
                                <strong>Order № <?= htmlspecialchars($row['reference_number']) ?></strong><br>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                </small>
                            </div>

                            <div class="text-center">
                                <div class="text-muted small">Total</div>
                                <strong>£<?= number_format($row['total_price'], 2) ?></strong>
                            </div>

                            <div class="text-center">
                                <div class="text-muted small">Status</div>
                                <strong><?= ucwords(str_replace('_', ' ', $row['current_status'])) ?></strong>
                            </div>

                            <div>
                                <a href="/pages/order_view.php?reference=<?= urlencode($row['reference_number']) ?>"
                                    class="btn btn-outline-primary btn-sm">
                                    View
                                </a>
                            </div>

                        </div>
                    <?php endwhile; ?>

                </div>

            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>