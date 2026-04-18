<?php
require_once __DIR__ . '/includes/header.php';

$lastOrder = $_SESSION['last_order'] ?? null;

if (!$lastOrder) {
    header('Location: /send.php');
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<body>
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="step-tracker mb-4">
                        <div class="step-item active">
                            <div class="step-circle">1</div>
                            <div class="step-label">Order Details</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item active">
                            <div class="step-circle">2</div>
                            <div class="step-label">Summary & Payment</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item active">
                            <div class="step-circle">3</div>
                            <div class="step-label">Confirmation</div>
                        </div>
                    </div>

                    <div class="confirmation-card text-center">
                        <div class="confirmation-icon mb-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>

                        <h2 class="mb-2">Order was placed successfully</h2>
                        <p class="text-muted mb-4">Your parcel booking has been completed.</p>

                        <div class="confirmation-status mb-4">
                            <div class="confirmation-pill success-pill mb-3">
                                Payment Successful
                            </div>

                            <div class="confirmation-reference">
                                <span class="d-block text-muted small mb-1">Order Reference Number</span>
                                <strong><?= e($lastOrder['reference_number']) ?></strong>
                            </div>
                        </div>

                        <div class="confirmation-info text-start mx-auto mb-4">
                            <div class="confirmation-info-row">
                                <span>SMS confirmation sent</span>
                                <strong><?= e($lastOrder['contact_phone']) ?></strong>
                            </div>

                            <?php if (!empty($lastOrder['contact_email'])): ?>
                                <div class="confirmation-info-row">
                                    <span>Email confirmation</span>
                                    <strong><?= e($lastOrder['contact_email']) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="confirmation-info-row">
                                <span>Payment status</span>
                                <strong>Paid</strong>
                            </div>

                            <div class="confirmation-info-row">
                                <span>Total paid</span>
                                <strong>£<?= e($lastOrder['total_price']) ?></strong>
                            </div>

                            <div class="confirmation-info-row">
                                <span>Estimated delivery date</span>
                                <strong><?= e(date('d M Y', strtotime($lastOrder['estimated_delivery_date']))) ?></strong>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                            <a href="/track.php" class="btn btn-primary btn-lg px-4">
                                Track Parcel
                            </a>
                            <a href="/index.php" class="btn btn-outline-primary btn-lg px-4">
                                Return Home
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>

</html>