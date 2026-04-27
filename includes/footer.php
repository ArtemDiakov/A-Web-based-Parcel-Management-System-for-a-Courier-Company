<footer class="footer">

  <div class="container">
    <div class="row">
      <div class="col-md-4">
        <h5>ParcelPro</h5>
        <p>Reliable parcel delivery service.</p>
      </div>

      <div class="col-md-4">
        <h6>Navigation</h6>
        <ul class="list-unstyled">
          <li><a href="/send.php">Send Parcel</a></li>
          <li><a href="/track.php">Track Parcel</a></li>
          <li><a href="/help.php">Help / FAQ</a></li>
        </ul>
      </div>

      <div class="col-md-4">
        <h6>Support</h6>
        <p>Email: support@example.com</p>
        <p>Phone: +44 123456789</p>
      </div>
    </div>

  </div>
  <?php if (!isset($_SESSION['user_id']) && !defined('LOGIN_MODAL_INCLUDED')): ?>
    <?php define('LOGIN_MODAL_INCLUDED', true); ?>
    <?php require_once __DIR__ . '/login_modal.php'; ?>
  <?php endif; ?>

  <!-- COOKIE BANNER -->
  <div id="cookieBanner" class="cookie-banner" style="display:none;">
    <p class="mb-2">
      <strong>Cookies</strong><br>
      We use essential cookies for login/session security and optional cookies to improve your experience.
    </p>

    <div class="d-flex gap-2 justify-content-center flex-wrap">
      <button id="rejectCookies" type="button" class="btn btn-sm btn-outline-light">
        Reject Optional
      </button>

      <button id="acceptCookies" type="button" class="btn btn-sm btn-primary">
        Accept All
      </button>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/main.js"></script>
  <script src="/js/auth.js"></script>
  <script src="/js/hero-forms.js"></script>

</footer>