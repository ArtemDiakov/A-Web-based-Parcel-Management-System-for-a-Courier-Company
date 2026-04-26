<?php
include 'includes/header.php';
require_once __DIR__ . '/includes/db.php';

$announcementResult = pg_query(
  $conn,
  "SELECT title, message
   FROM public.announcements
   WHERE is_active = true
     AND (expires_at IS NULL OR expires_at >= NOW())
   ORDER BY created_at DESC
   LIMIT 3"
);

$homepageAnnouncements = $announcementResult ? (pg_fetch_all($announcementResult) ?: []) : [];
?>

<body>
  <!-- NAVBAR -->

  <?php include 'includes/navbar.php'; ?>

  <!-- HERO -->

  <section class="hero">
    <div class="container">
      <h2 class="text-center mb-4">Send or Track Your Parcel</h2>

      <div class="card shadow-sm">
        <div class="card-body">
          <ul class="nav nav-tabs mb-3" id="serviceTabs">
            <li class="nav-item">
              <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#send">
                Send Parcel
              </button>
            </li>

            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#track">
                Track Parcel
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- SEND TAB -->

            <div class="tab-pane fade show active" id="send">
              <form action="/send.php" method="GET" class="row g-2" id="heroSendForm" novalidate>
                <div class="col-md">
                  <input
                    type="text"
                    class="form-control postcode-input"
                    name="from_postcode"
                    placeholder="From postcode"
                    maxlength="20"
                    pattern="^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$"
                    aria-label="From postcode" />
                  <div class="invalid-feedback">Please enter a valid UK postcode.</div>
                </div>

                <div class="col-md">
                  <input
                    type="text"
                    class="form-control postcode-input"
                    name="to_postcode"
                    placeholder="To postcode"
                    maxlength="20"
                    pattern="^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$"
                    aria-label="To postcode" />
                  <div class="invalid-feedback">Please enter a valid UK postcode.</div>
                </div>

                <div class="col-md">
                  <input
                    type="number"
                    class="form-control"
                    name="weight"
                    placeholder="Parcel weight (kg)"
                    min="0.1"
                    max="999.99"
                    step="0.01"
                    aria-label="Parcel weight" />
                  <div class="invalid-feedback">Enter a valid weight between 0.1 and 999.99 kg.</div>
                </div>

                <div class="col-md-auto">
                  <button type="submit" class="btn btn-primary">Get Quote</button>
                </div>
              </form>
            </div>

            <!-- TRACK TAB -->

            <div class="tab-pane fade" id="track">
              <form action="/track.php" method="GET" class="row g-2" id="heroTrackForm" novalidate>
                <div class="col-md">
                  <input
                    type="text"
                    class="form-control"
                    name="reference"
                    placeholder="Reference number"
                    maxlength="16"
                    pattern="^PP\d{8}[A-F0-9]{6}$"
                    aria-label="Reference number" />
                  <div class="invalid-feedback">Please enter a valid reference number.</div>
                </div>

                <div class="col-md-auto">
                  <button type="submit" class="btn btn-primary">Track Parcel</button>
                </div>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PROMO BANNER -->

  <?php if (!empty($homepageAnnouncements)): ?>
    <div class="container mt-4">
      <?php foreach ($homepageAnnouncements as $announcement): ?>
        <div class="alert alert-info alert-dismissible fade show">
          <strong><?= htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8') ?>:</strong>
          <?= htmlspecialchars($announcement['message'], ENT_QUOTES, 'UTF-8') ?>

          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- FEATURE 1 -->

  <section class="feature-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h3>Reliable Parcel Delivery Within Your Area</h3>

          <p>
            Our parcel delivery service ensures safe and reliable
            transportation of your packages within the supported delivery
            zones.
          </p>
        </div>

        <div class="col-md-6">
          <img src="images/img1.jpg" class="img-fluid rounded" />
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURE 2 -->

  <section class="feature-section bg-light">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6 order-md-2">
          <h3>Track Your Parcel Anytime</h3>

          <p>
            Customers can easily track the current location and delivery
            status of their parcels through our tracking system.
          </p>
        </div>

        <div class="col-md-6 order-md-1">
          <img src="images/img2.jpg" class="img-fluid rounded" />
        </div>
      </div>
    </div>
  </section>

  <!-- INFO BLOCK -->

  <section class="feature-section text-center">
    <div class="container">
      <h3>Simple, Transparent and Reliable</h3>

      <p class="mt-3">
        Our system is designed to provide a clear and user-friendly parcel
        delivery experience with transparent pricing and real-time delivery
        updates.
      </p>
    </div>
  </section>

  <!-- FOOTER -->
  <?php include 'includes/footer.php'; ?>

  <!-- COOKIE BOX -->
  <div class="cookie-box">
    <p class="mb-2">
      <strong>Cookies</strong><br />
      We use cookies to improve your experience.
    </p>

    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-sm btn-outline-secondary">
        Cookie Settings
      </button>

      <button class="btn btn-sm btn-outline-secondary">Reject All</button>

      <button class="btn btn-sm btn-primary">Accept All</button>
    </div>
  </div>
</body>

</html>