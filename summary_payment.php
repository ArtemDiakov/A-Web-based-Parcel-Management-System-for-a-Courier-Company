<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/csrf.php';

$sendOrder = $_SESSION['send_order'] ?? null;

$flashError = $_SESSION['send_order_error'] ?? '';
unset($_SESSION['send_order_error']);

if (!$sendOrder) {
  header('Location: /send.php');
  exit;
}

function e($value): string
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$deliveryLabel = ($sendOrder['delivery_type'] === 'collection')
  ? 'Collection from my address'
  : 'Drop-off at collection point';
?>

<body>
  <?php require_once __DIR__ . '/includes/navbar.php'; ?>

  <section class="hero send-hero">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">

          <?php if ($flashError !== ''): ?>
            <div class="send-message-error rounded-3 p-3 mb-4">
              <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

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
            <div class="step-item">
              <div class="step-circle">3</div>
              <div class="step-label">Confirmation</div>
            </div>
          </div>

          <div class="text-center mb-4">
            <h2 class="mb-2">Summary & Payment</h2>
            <p class="lead mb-0">Review your parcel details before continuing.</p>
          </div>

          <div class="row g-4">
            <div class="col-lg-8">

              <div class="form-panel mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                  <div>
                    <h4 class="mb-1">Contact Details</h4>
                    <p class="text-muted mb-0">Booking contact information.</p>
                  </div>
                  <a href="/send.php#contactSection" class="btn btn-outline-primary btn-sm">Edit</a>
                </div>

                <p class="mb-1"><strong>Email:</strong> <?= e($sendOrder['contact_email'] ?: 'Not provided') ?></p>
                <p class="mb-0"><strong>Phone:</strong> <?= e($sendOrder['contact_phone']) ?></p>
              </div>

              <div class="form-panel mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                  <div>
                    <h4 class="mb-1">Sender Details</h4>
                    <p class="text-muted mb-0">Collection or sender address.</p>
                  </div>
                  <a href="/send.php#senderSection" class="btn btn-outline-primary btn-sm">Edit</a>
                </div>

                <p class="mb-1"><strong><?= e($sendOrder['sender_name']) ?></strong></p>
                <p class="mb-1"><?= e($sendOrder['sender_address1']) ?></p>
                <?php if (!empty($sendOrder['sender_address2'])): ?>
                  <p class="mb-1"><?= e($sendOrder['sender_address2']) ?></p>
                <?php endif; ?>
                <p class="mb-0"><?= e($sendOrder['sender_city']) ?>, <?= e($sendOrder['sender_postcode']) ?></p>
              </div>

              <div class="form-panel mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                  <div>
                    <h4 class="mb-1">Recipient Details</h4>
                    <p class="text-muted mb-0">Delivery destination.</p>
                  </div>
                  <a href="/send.php#recipientSection" class="btn btn-outline-primary btn-sm">Edit</a>
                </div>

                <p class="mb-1"><strong><?= e($sendOrder['recipient_name']) ?></strong></p>
                <p class="mb-1"><?= e($sendOrder['recipient_address1']) ?></p>
                <?php if (!empty($sendOrder['recipient_address2'])): ?>
                  <p class="mb-1"><?= e($sendOrder['recipient_address2']) ?></p>
                <?php endif; ?>
                <p class="mb-1"><?= e($sendOrder['recipient_city']) ?>, <?= e($sendOrder['recipient_postcode']) ?></p>

                <?php if (!empty($sendOrder['delivery_instructions'])): ?>
                  <hr>
                  <p class="mb-0"><strong>Instructions:</strong> <?= nl2br(e($sendOrder['delivery_instructions'])) ?></p>
                <?php endif; ?>
              </div>

              <div class="form-panel mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                  <div>
                    <h4 class="mb-1">Parcel & Delivery</h4>
                    <p class="text-muted mb-0">Package details and selected option.</p>
                  </div>
                  <a href="/send.php#parcelSection" class="btn btn-outline-primary btn-sm">Edit</a>
                </div>

                <div class="row g-3">
                  <div class="col-md-6"><strong>Weight:</strong> <?= e($sendOrder['weight']) ?> kg</div>
                  <div class="col-md-6"><strong>Quantity:</strong> <?= e($sendOrder['quantity']) ?></div>
                  <div class="col-md-6"><strong>Length:</strong> <?= e($sendOrder['length']) ?> cm</div>
                  <div class="col-md-6"><strong>Width:</strong> <?= e($sendOrder['width']) ?> cm</div>
                  <div class="col-md-6"><strong>Height:</strong> <?= e($sendOrder['height']) ?> cm</div>
                  <div class="col-md-6"><strong>Parcel Value:</strong> £<?= e($sendOrder['parcel_value']) ?></div>
                  <div class="col-12"><strong>Delivery Type:</strong> <?= e($deliveryLabel) ?></div>
                </div>
              </div>

            </div>

            <div class="col-lg-4">
              <div class="quote-box">
                <h4 class="mb-3">Price Summary</h4>

                <div class="d-flex justify-content-between mb-2">
                  <span>Base price</span>
                  <span>£<?= number_format($sendOrder['quote']['base_price'], 2) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span>Weight charge</span>
                  <span>£<?= number_format($sendOrder['quote']['weight_charge'], 2) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span>Size charge</span>
                  <span>£<?= number_format($sendOrder['quote']['size_charge'], 2) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span>Quantity charge</span>
                  <span>£<?= number_format($sendOrder['quote']['quantity_charge'], 2) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span><?= e($deliveryLabel) ?></span>
                  <span>£<?= number_format($sendOrder['quote']['delivery_charge'], 2) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-3">
                  <span>VAT</span>
                  <span>£<?= number_format($sendOrder['quote']['vat'], 2) ?></span>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-4">
                  <strong>Total</strong>
                  <strong class="fs-5">£<?= number_format($sendOrder['quote']['total'], 2) ?></strong>
                </div>

                <form method="POST" action="/orders/complete_order.php">

                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <strong>Total</strong>
                    <strong class="fs-5">£<?= number_format($sendOrder['quote']['total'], 2) ?></strong>
                  </div>

                  <hr class="my-4">

                  <h5 class="mb-3">Payment Option</h5>

                  <div class="mb-3">
                    <label class="form-label">Cardholder Name</label>
                    <input type="text" class="form-control" id="card_name" placeholder="John Smith" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Card Number</label>
                    <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Expiry Date</label>
                      <input type="text" class="form-control" id="card_expiry" placeholder="MM/YY" maxlength="5" required>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">CVV</label>
                      <input type="password" class="form-control" id="card_cvv" placeholder="123" maxlength="4" required>
                    </div>
                  </div>

                  <hr>

                  <h6 class="mb-3">Conditions</h6>

                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="termsCheck">
                    <label class="form-check-label" for="termsCheck">
                      I accept the terms and conditions
                    </label>
                  </div>

                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="prohibitedCheck">
                    <label class="form-check-label" for="prohibitedCheck">
                      I confirm my shipment does not contain prohibited items
                    </label>
                  </div>

                  <button type="submit" id="confirmOrderBtn" class="btn btn-primary w-100 btn-lg" disabled>
                    Pay & Place Order
                  </button>

                </form>

                <p class="text-muted small mt-3 mb-0">
              
                </p>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <script src="/js/summary_payment.js"></script>
  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>

</html>