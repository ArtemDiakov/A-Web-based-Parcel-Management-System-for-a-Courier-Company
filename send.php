<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/order_security.php';

$fullName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$formStartedAt = sendFormStartedAt();
?>

<body>
  <?php require_once __DIR__ . '/includes/navbar.php'; ?>

  <section class="hero send-hero">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">

          <!-- STEP TRACKER -->
          <div class="step-tracker mb-4">
            <div class="step-item active">
              <div class="step-circle">1</div>
              <div class="step-label">Order Details</div>
            </div>
            <div class="step-line"></div>
            <div class="step-item">
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
            <h2 class="mb-2">Send Parcel</h2>
            <p class="lead mb-0">Enter parcel details to receive a delivery quote.</p>
          </div>

          <div class="card shadow-sm border-0 send-card">
            <div class="card-body p-4 p-lg-5">
              <div id="sendFormMessage" class="d-none rounded-3 p-3 mb-4"></div>

              <form id="sendParcelForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_started_at" value="<?= (int) $formStartedAt ?>">

                <div class="bot-trap" aria-hidden="true">
                  <label for="website">Website</label>
                  <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- CONTACT -->
                <div class="section-heading mb-3">
                  <h4 class="mb-1">Contact Details</h4>
                  <p class="text-muted mb-0">We will use these details for updates about your order.</p>
                </div>

                <div class="row g-3 mb-4 section-block active-section" id="contactSection">
                  <div class="col-md-6">
                    <label for="contact_email" class="form-label">Contact Email</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" maxlength="150">
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="contact_phone" class="form-label">Contact Phone*</label>
                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" required pattern="^(?:\+44|0)7\d{9}$" maxlength="20" placeholder="e.g. 07911123456">
                    <div class="invalid-feedback">Enter a valid UK mobile number.</div>
                  </div>
                </div>

                <!-- SENDER / RECIPIENT -->
                <div class="row g-4">
                  <div class="col-lg-6">
                    <div class="form-panel h-100 section-block inactive-section" id="senderSection" data-lock-message="Complete previous section first">
                      <div class="section-heading mb-3">
                        <h4 class="mb-1">Sender Details</h4>
                        <p class="text-muted mb-0">Where the parcel is coming from.</p>
                      </div>

                      <div class="row g-3">
                        <div class="col-12">
                          <label for="sender_name" class="form-label">Sender Name*</label>
                          <input type="text" class="form-control sender-input" id="sender_name" name="sender_name" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required maxlength="100" disabled>
                          <div class="invalid-feedback">Please enter the sender name.</div>
                        </div>
                        <div class="col-12">
                          <label for="sender_address1" class="form-label">Address Line 1*</label>
                          <input type="text" class="form-control sender-input" id="sender_address1" name="sender_address1" required maxlength="150" disabled>
                          <div class="invalid-feedback">Please enter the first address line.</div>
                        </div>
                        <div class="col-12">
                          <label for="sender_address2" class="form-label">Address Line 2</label>
                          <input type="text" class="form-control sender-input" id="sender_address2" name="sender_address2" maxlength="150" disabled>
                        </div>
                        <div class="col-md-7">
                          <label for="sender_city" class="form-label">City*</label>
                          <input type="text" class="form-control sender-input" id="sender_city" name="sender_city" required maxlength="100" disabled>
                          <div class="invalid-feedback">Please enter the sender city.</div>
                        </div>
                        <div class="col-md-5">
                          <label for="sender_postcode" class="form-label">Postcode*</label>
                          <input type="text" class="form-control postcode-input sender-input" id="sender_postcode" name="sender_postcode" required maxlength="20" disabled>
                          <div class="invalid-feedback">Please enter a valid UK postcode.</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-6">
                    <div class="form-panel h-100 section-block inactive-section" id="recipientSection" data-lock-message="Complete previous section first">
                      <div class="section-heading mb-3">
                        <h4 class="mb-1">Recipient Details</h4>
                        <p class="text-muted mb-0">Where the parcel should be delivered.</p>
                      </div>

                      <div class="row g-3">
                        <div class="col-12">
                          <label for="recipient_name" class="form-label">Recipient Name*</label>
                          <input type="text" class="form-control recipient-input" id="recipient_name" name="recipient_name" required maxlength="100" disabled>
                          <div class="invalid-feedback">Please enter the recipient name.</div>
                        </div>
                        <div class="col-12">
                          <label for="recipient_address1" class="form-label">Address Line 1*</label>
                          <input type="text" class="form-control recipient-input" id="recipient_address1" name="recipient_address1" required maxlength="150" disabled>
                          <div class="invalid-feedback">Please enter the first address line.</div>
                        </div>
                        <div class="col-12">
                          <label for="recipient_address2" class="form-label">Address Line 2</label>
                          <input type="text" class="form-control recipient-input" id="recipient_address2" name="recipient_address2" maxlength="150" disabled>
                        </div>
                        <div class="col-md-7">
                          <label for="recipient_city" class="form-label">City*</label>
                          <input type="text" class="form-control recipient-input" id="recipient_city" name="recipient_city" required maxlength="100" disabled>
                          <div class="invalid-feedback">Please enter the recipient city.</div>
                        </div>
                        <div class="col-md-5">
                          <label for="recipient_postcode" class="form-label">Postcode*</label>
                          <input type="text" class="form-control postcode-input recipient-input" id="recipient_postcode" name="recipient_postcode" required maxlength="20" disabled>
                          <div class="invalid-feedback">Please enter a valid UK postcode.</div>
                        </div>
                        <div class="col-12">
                          <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                          <textarea class="form-control recipient-input" id="delivery_instructions" name="delivery_instructions" rows="4" maxlength="1000" placeholder="Optional notes for collection or delivery." disabled></textarea>
                          <div class="form-text">Optional. Maximum 1000 characters.</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- PARCEL + DELIVERY OPTIONS -->
                <div class="row g-4 mt-1">
                  <div class="col-lg-8">
                    <div class="form-panel h-100 section-block inactive-section" id="parcelSection" data-lock-message="Complete previous section first">
                      <div class="section-heading mb-3">
                        <h4 class="mb-1">Parcel Details</h4>
                        <p class="text-muted mb-0">Add dimensions and parcel value for your booking.</p>
                      </div>

                      <div class="row g-3">
                        <div class="col-md-3">
                          <label for="weight" class="form-label">Weight (kg)*</label>
                          <input type="number" class="form-control parcel-input" id="weight" name="weight" required min="0.1" max="999.99" step="0.01" disabled>
                          <div class="invalid-feedback">Enter a valid weight.</div>
                        </div>
                        <div class="col-md-3">
                          <label for="length" class="form-label">Length (cm)*</label>
                          <input type="number" class="form-control parcel-input" id="length" name="length" required min="1" max="999.99" step="0.01" disabled>
                          <div class="invalid-feedback">Enter a valid length.</div>
                        </div>
                        <div class="col-md-3">
                          <label for="width" class="form-label">Width (cm)*</label>
                          <input type="number" class="form-control parcel-input" id="width" name="width" required min="1" max="999.99" step="0.01" disabled>
                          <div class="invalid-feedback">Enter a valid width.</div>
                        </div>
                        <div class="col-md-3">
                          <label for="height" class="form-label">Height (cm)*</label>
                          <input type="number" class="form-control parcel-input" id="height" name="height" required min="1" max="999.99" step="0.01" disabled>
                          <div class="invalid-feedback">Enter a valid height.</div>
                        </div>
                        <div class="col-md-4">
                          <label for="quantity" class="form-label">Quantity*</label>
                          <input type="number" class="form-control parcel-input" id="quantity" name="quantity" required min="1" max="20" step="1" value="1" disabled>
                          <div class="invalid-feedback">Quantity must be between 1 and 20.</div>
                        </div>
                        <div class="col-md-4">
                          <label for="parcel_value" class="form-label">Parcel Value (£)*</label>
                          <input type="number" class="form-control parcel-input" id="parcel_value" name="parcel_value" required min="0" max="50000" step="0.01" value="0.00" disabled>
                          <div class="invalid-feedback">Enter a valid parcel value.</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-4">
                    <div class="form-panel h-100 section-block inactive-section" id="deliverySection" data-lock-message="Complete previous section first">
                      <div class="section-heading mb-3">
                        <h4 class="mb-1">Delivery Options</h4>
                        <p class="text-muted mb-0">Choose how you would like to send your parcel.</p>
                      </div>

                      <div class="delivery-option-list">
                        <label class="delivery-option-card">
                          <input class="form-check-input delivery-input" type="radio" name="delivery_type" value="collection" required disabled>
                          <div>
                            <div class="fw-semibold">Collection from my address</div>
                            <div class="text-muted small">£2.50</div>
                          </div>
                        </label>

                        <label class="delivery-option-card">
                          <input class="form-check-input delivery-input" type="radio" name="delivery_type" value="dropoff" required disabled>
                          <div>
                            <div class="fw-semibold">Drop-off at collection point</div>
                            <div class="text-muted small">Free</div>
                          </div>
                        </label>
                      </div>

                      <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitOrderBtn" disabled>
                          Get Delivery Quote
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
  <script src="/js/send.js"></script>
</body>

</html>