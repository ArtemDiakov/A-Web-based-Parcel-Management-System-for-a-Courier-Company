<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];

$userResult = pg_query_params(
    $conn,
    "SELECT id, full_name, email, phone, role, created_at
     FROM public.users
     WHERE id = $1
     LIMIT 1",
    [$userId]
);

$user = $userResult ? pg_fetch_assoc($userResult) : null;

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

$createdAt = !empty($user['created_at'])
    ? date('d M Y', strtotime($user['created_at']))
    : '—';

$roleLabel = ucfirst((string) ($user['role'] ?? 'customer'));
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="mb-2">Manage Account</h2>
                <p class="lead mb-0">View and update your account details.</p>
            </div>

            <div id="accountPageMessage" class="d-none rounded-3 p-3 mb-4"></div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="form-panel account-sidebar">
                        <div class="account-nav nav flex-column nav-pills" id="accountTab" role="tablist" aria-orientation="vertical">
                            <button
                                class="nav-link active"
                                id="profile-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#profile-panel"
                                type="button"
                                role="tab"
                                aria-controls="profile-panel"
                                aria-selected="true">
                                Profile Information
                            </button>

                            <button
                                class="nav-link"
                                id="security-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#security-panel"
                                type="button"
                                role="tab"
                                aria-controls="security-panel"
                                aria-selected="false">
                                Security
                            </button>

                            <button
                                class="nav-link"
                                id="address-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#address-panel"
                                type="button"
                                role="tab"
                                aria-controls="address-panel"
                                aria-selected="false">
                                My Address
                            </button>

                            <form method="POST" action="/logout.php" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="tab-content" id="accountTabContent">

                        <!-- PROFILE -->
                        <div
                            class="tab-pane fade show active"
                            id="profile-panel"
                            role="tabpanel"
                            aria-labelledby="profile-tab"
                            tabindex="0">

                            <div class="form-panel">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                                    <div>
                                        <div class="section-heading mb-0">
                                            <h4 class="mb-1">Profile Information</h4>
                                            <p class="text-muted mb-0">Update your name, email and phone number.</p>
                                        </div>
                                    </div>
                                </div>

                                <form id="profileInfoForm" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="full_name" class="form-label">Full Name*</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="full_name"
                                                name="full_name"
                                                maxlength="100"
                                                required
                                                value="<?= e($user['full_name']) ?>"
                                                disabled>
                                            <div class="invalid-feedback">
                                                Please enter your full name.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="email" class="form-label">Email Address*</label>
                                            <input
                                                type="email"
                                                class="form-control"
                                                id="email"
                                                name="email"
                                                maxlength="150"
                                                pattern="^[^\s@]+@[^\s@]+\.[^\s@]{2,}$"
                                                required
                                                value="<?= e($user['email']) ?>"
                                                disabled>
                                            <div class="invalid-feedback">
                                                Please enter a valid email address.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="phone" class="form-label">Phone Number*</label>
                                            <input
                                                type="tel"
                                                class="form-control"
                                                id="phone"
                                                name="phone"
                                                maxlength="20"
                                                pattern="^(?:\+44|0)7\d{9}$"
                                                placeholder="e.g. 07911123456"
                                                required
                                                value="<?= e($user['phone']) ?>"
                                                disabled>
                                            <div class="invalid-feedback">
                                                Enter a valid UK mobile number (e.g. 07911123456 or +447911123456).
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Date Created</label>
                                            <input
                                                type="text"
                                                class="form-control account-readonly"
                                                value="<?= e($createdAt) ?>"
                                                disabled>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Account Role</label>
                                            <input
                                                type="text"
                                                class="form-control account-readonly"
                                                value="<?= e($roleLabel) ?>"
                                                disabled>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-4">
                                        <button type="button" class="btn btn-primary" id="profileEditBtn">
                                            Edit
                                        </button>

                                        <button type="submit" class="btn btn-primary d-none" id="profileSaveBtn">
                                            Save Changes
                                        </button>

                                        <button type="button" class="btn btn-outline-secondary d-none" id="profileCancelBtn">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- SECURITY PLACEHOLDER -->
                        <div
                            class="tab-pane fade"
                            id="security-panel"
                            role="tabpanel"
                            aria-labelledby="security-tab"
                            tabindex="0">

                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">Security</h4>
                                    <p class="text-muted mb-0">Update your account password.</p>
                                </div>

                                <form id="securityForm" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <div class="input-group has-validation">
                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    id="current_password"
                                                    name="current_password"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary"
                                                    onclick="togglePassword('current_password', this)">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <div class="invalid-feedback">
                                                    Please enter your current password.
                                                </div>
                                            </div>
                                            <p class="text-muted mb-0">For security, please enter your current password before choosing a new one.</p>
                                        </div>

                                        <div class="col-12">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <div class="input-group has-validation">
                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    id="new_password"
                                                    name="new_password"
                                                    required
                                                    minlength="8"
                                                    maxlength="72"
                                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,72}$">

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary"
                                                    onclick="togglePassword('new_password', this)">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <div class="invalid-feedback">
                                                    Password must be 8-72 characters and include uppercase, lowercase and number.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                            <div class="input-group has-validation">
                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    id="confirm_new_password"
                                                    name="confirm_new_password"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary"
                                                    onclick="togglePassword('confirm_new_password', this)">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <div class="invalid-feedback">
                                                    Please confirm your new password.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- ADDRESS PLACEHOLDER -->
                        <?php
                        $addressResult = pg_query_params(
                            $conn,
                            "SELECT address_line1, address_line2, city, postcode
     FROM public.user_addresses
     WHERE user_id = $1 AND is_default = true
     LIMIT 1",
                            [$userId]
                        );

                        $address = $addressResult ? pg_fetch_assoc($addressResult) : null;
                        ?>

                        <div
                            class="tab-pane fade"
                            id="address-panel"
                            role="tabpanel"
                            aria-labelledby="address-tab"
                            tabindex="0">

                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">My Address</h4>
                                    <p class="text-muted mb-0">Save your default sender address.</p>
                                </div>

                                <form id="addressForm" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">

                                    <div class="row g-3">

                                        <div class="col-12">
                                            <label for="address_postcode" class="form-label">Postcode*</label>
                                            <input
                                                type="text"
                                                class="form-control postcode-input"
                                                id="address_postcode"
                                                name="postcode"
                                                maxlength="20"
                                                pattern="^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$"
                                                required
                                                value="<?= e($address['postcode'] ?? '') ?>"
                                                disabled>
                                            <div class="invalid-feedback">Please enter a valid UK postcode.</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Address Line 1*</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="address_line1"
                                                maxlength="150"
                                                required
                                                value="<?= e($address['address_line1'] ?? '') ?>"
                                                disabled>
                                            <div class="invalid-feedback">Please enter address line 1.</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Address Line 2</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="address_line2"
                                                maxlength="150"
                                                value="<?= e($address['address_line2'] ?? '') ?>"
                                                disabled>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">City*</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="city"
                                                maxlength="100"
                                                required
                                                value="<?= e($address['city'] ?? '') ?>"
                                                disabled>
                                            <div class="invalid-feedback">Please enter city.</div>
                                        </div>

                                    </div>

                                    <div class="d-flex gap-2 mt-4">
                                        <button type="button" class="btn btn-primary" id="addressEditBtn">
                                            Edit
                                        </button>

                                        <button type="submit" class="btn btn-primary d-none" id="addressSaveBtn">
                                            Save Address
                                        </button>

                                        <button type="button" class="btn btn-outline-secondary d-none" id="addressCancelBtn">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="/js/account.js"></script>
</body>

</html>