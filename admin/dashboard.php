<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole(['admin']);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalisePostcode(string $postcode): string
{
    return strtoupper(preg_replace('/\s+/', '', trim($postcode)));
}

function statusLabel($active): string
{
    return ($active === true || $active === 't' || $active === '1') ? 'Active' : 'Inactive';
}

function boolChecked($value): string
{
    return ($value === true || $value === 't' || $value === '1') ? 'checked' : '';
}

$userSearch = trim((string)($_GET['user_q'] ?? ''));
$staffSearch = trim((string)($_GET['staff_q'] ?? ''));
$areaSearch = trim((string)($_GET['area_q'] ?? ''));
$announcementSearch = trim((string)($_GET['ann_q'] ?? ''));

$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$selectedStaffId = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$selectedAreaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;
$selectedAnnouncementId = isset($_GET['announcement_id']) ? (int)$_GET['announcement_id'] : 0;

$flashSuccess = $_SESSION['admin_success'] ?? '';
$flashError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$overviewResult = pg_query($conn, "
    SELECT
        (SELECT COUNT(*) FROM public.orders) AS total_orders,
        (SELECT COUNT(*) FROM public.orders WHERE current_status NOT IN ('delivered', 'cancelled', 'returned_to_sender')) AS active_deliveries,
        (SELECT COUNT(*) FROM public.orders WHERE current_status = 'delivered') AS delivered_parcels,
        (SELECT COUNT(*) FROM public.users WHERE role = 'customer') AS registered_users,
        (SELECT COUNT(*) FROM public.users WHERE role IN ('staff', 'admin')) AS staff_accounts,
        (SELECT COUNT(*) FROM public.operating_areas WHERE is_active = true) AS active_areas,
        (SELECT COUNT(*) FROM public.announcements WHERE is_active = true AND (expires_at IS NULL OR expires_at >= NOW())) AS active_announcements
");
$overview = $overviewResult ? pg_fetch_assoc($overviewResult) : [];

$userParams = [];
$userWhere = "role = 'customer'";
if ($userSearch !== '') {
    $userWhere .= " AND (CAST(id AS TEXT) ILIKE $1 OR full_name ILIKE $1 OR email ILIKE $1 OR phone ILIKE $1)";
    $userParams[] = '%' . $userSearch . '%';
}
$userResult = pg_query_params($conn, "
    SELECT id, full_name, email, phone, is_active, created_at
    FROM public.users
    WHERE {$userWhere}
    ORDER BY id DESC
    LIMIT 50
", $userParams);
$users = $userResult ? (pg_fetch_all($userResult) ?: []) : [];

$staffParams = [];
$staffWhere = "role IN ('staff', 'admin')";
if ($staffSearch !== '') {
    $staffWhere .= " AND (CAST(id AS TEXT) ILIKE $1 OR full_name ILIKE $1 OR email ILIKE $1 OR phone ILIKE $1)";
    $staffParams[] = '%' . $staffSearch . '%';
}
$staffResult = pg_query_params($conn, "
    SELECT id, full_name, email, phone, role, is_active, created_at
    FROM public.users
    WHERE {$staffWhere}
    ORDER BY role, id DESC
    LIMIT 50
", $staffParams);
$staffRows = $staffResult ? (pg_fetch_all($staffResult) ?: []) : [];

$areaParams = [];
$areaWhere = "1=1";
if ($areaSearch !== '') {
    $areaWhere .= " AND (REPLACE(UPPER(postcode), ' ', '') LIKE $1 OR city ILIKE $2)";
    $areaParams[] = '%' . normalisePostcode($areaSearch) . '%';
    $areaParams[] = '%' . $areaSearch . '%';
}
$areaResult = pg_query_params($conn, "
    SELECT id, postcode, city, is_active, created_at
    FROM public.operating_areas
    WHERE {$areaWhere}
    ORDER BY city, postcode
    LIMIT 100
", $areaParams);
$areas = $areaResult ? (pg_fetch_all($areaResult) ?: []) : [];

$annParams = [];
$annWhere = "1=1";
if ($announcementSearch !== '') {
    $annWhere .= " AND (title ILIKE $1 OR message ILIKE $1)";
    $annParams[] = '%' . $announcementSearch . '%';
}
$announcementResult = pg_query_params($conn, "
    SELECT a.id, a.title, a.message, a.is_active, a.created_at, a.expires_at, u.full_name AS created_by_name
    FROM public.announcements a
    LEFT JOIN public.users u ON u.id = a.created_by
    WHERE {$annWhere}
    ORDER BY a.created_at DESC
    LIMIT 50
", $annParams);
$announcements = $announcementResult ? (pg_fetch_all($announcementResult) ?: []) : [];
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <section class="hero send-hero">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="mb-2">Admin Dashboard</h2>
                <p class="lead mb-0">Manage users, staff, service areas and site announcements.</p>
            </div>

            <?php if ($flashSuccess !== ''): ?>
                <div class="send-message-success rounded-3 p-3 mb-4"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError !== ''): ?>
                <div class="send-message-error rounded-3 p-3 mb-4"><?= e($flashError) ?></div>
            <?php endif; ?>

            <div id="adminPageMessage" class="d-none rounded-3 p-3 mb-4"></div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="form-panel account-sidebar">
                        <div class="account-nav nav flex-column nav-pills" id="adminTab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#overview-panel" type="button">System Overview</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#users-panel" type="button">Manage Users</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#staff-panel" type="button">Manage Staff</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#areas-panel" type="button">Operating Areas</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#announcements-panel" type="button">Announcements</button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="tab-content" id="adminTabContent">

                        <div class="tab-pane fade show active" id="overview-panel" tabindex="0">
                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">System Overview</h4>
                                    <p class="text-muted mb-0">Live totals from the parcel system.</p>
                                </div>

                                <div class="row g-3">
                                    <?php
                                    $cards = [
                                        'Total Orders' => $overview['total_orders'] ?? 0,
                                        'Active Deliveries' => $overview['active_deliveries'] ?? 0,
                                        'Delivered Parcels' => $overview['delivered_parcels'] ?? 0,
                                        'Registered Users' => $overview['registered_users'] ?? 0,
                                        'Staff Accounts' => $overview['staff_accounts'] ?? 0,
                                        'Active Areas' => $overview['active_areas'] ?? 0,
                                        'Active Announcements' => $overview['active_announcements'] ?? 0,
                                    ];
                                    foreach ($cards as $label => $value): ?>
                                        <div class="col-md-6">
                                            <div class="tracking-detail-box h-100">
                                                <span class="tracking-detail-label"><?= e($label) ?></span>
                                                <strong class="fs-4"><?= (int)$value ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="users-panel" tabindex="0">
                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">Manage Users</h4>
                                    <p class="text-muted mb-0">Search customers and update account status/details.</p>
                                </div>

                                <form method="GET" action="/admin/dashboard.php" class="row g-3 align-items-end mb-4">
                                    <input type="hidden" name="tab" value="users">
                                    <div class="col-md-9">
                                        <label class="form-label" for="user_q">Search</label>
                                        <input class="form-control" id="user_q" name="user_q" value="<?= e($userSearch) ?>" placeholder="User ID, name, email or phone">
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button class="btn btn-primary" type="submit">Search</button>
                                    </div>
                                </form>

                                <?php if (empty($users)): ?>
                                    <div class="tracking-empty-state">No users found.</div>
                                <?php else: ?>
                                    <?php foreach ($users as $row): ?>
                                        <div class="admin-list-row">
                                            <div>
                                                <strong>#<?= (int)$row['id'] ?></strong><br>
                                                <span><?= e($row['full_name']) ?></span>
                                            </div>
                                            <div class="text-md-center">
                                                <span class="badge text-bg-light staff-status-badge"><?= e(statusLabel($row['is_active'])) ?></span>
                                            </div>
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/dashboard.php?tab=users&user_q=<?= urlencode($userSearch) ?>&user_id=<?= (int)$row['id'] ?>">View</a>
                                        </div>

                                        <?php if ($selectedUserId === (int)$row['id']): ?>
                                            <div class="admin-edit-box">
                                                <form method="POST" action="/admin/admin_action.php" novalidate>
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="update_user">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">User ID</label>
                                                            <input class="form-control account-readonly" value="<?= (int)$row['id'] ?>" disabled>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Account Status</label>
                                                            <select class="form-select" name="is_active">
                                                                <option value="1" <?= $row['is_active'] === 't' ? 'selected' : '' ?>>Active</option>
                                                                <option value="0" <?= $row['is_active'] !== 't' ? 'selected' : '' ?>>Disabled</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Role</label>
                                                            <select class="form-select" name="role">
                                                                <option value="customer" selected>Customer</option>
                                                                <option value="staff">Staff</option>
                                                                <option value="admin">Admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Name</label>
                                                            <input class="form-control" name="full_name" maxlength="100" required value="<?= e($row['full_name']) ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control" name="phone" maxlength="20" pattern="^(?:\+44|0)7\d{9}$" value="<?= e($row['phone']) ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Email</label>
                                                            <input class="form-control" name="email" type="email" maxlength="150" required value="<?= e($row['email']) ?>">
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2 mt-4">
                                                        <button class="btn btn-primary" type="submit">Save</button>
                                                        <a class="btn btn-outline-secondary" href="/admin/dashboard.php?tab=users&user_q=<?= urlencode($userSearch) ?>">Cancel</a>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="staff-panel" tabindex="0">
                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">Manage Staff</h4>
                                    <p class="text-muted mb-0">Manage staff/admin roles and status.</p>
                                </div>

                                <form method="GET" action="/admin/dashboard.php" class="row g-3 align-items-end mb-4">
                                    <input type="hidden" name="tab" value="staff">
                                    <div class="col-md-9">
                                        <label class="form-label" for="staff_q">Search</label>
                                        <input class="form-control" id="staff_q" name="staff_q" value="<?= e($staffSearch) ?>" placeholder="Staff ID, name, email or phone">
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button class="btn btn-primary" type="submit">Search</button>
                                    </div>
                                </form>

                                <?php if (empty($staffRows)): ?>
                                    <div class="tracking-empty-state">No staff accounts found.</div>
                                <?php else: ?>
                                    <?php foreach ($staffRows as $row): ?>
                                        <div class="admin-list-row">
                                            <div>
                                                <strong>#<?= (int)$row['id'] ?></strong><br>
                                                <span><?= e($row['full_name']) ?></span>
                                            </div>
                                            <div class="text-md-center">
                                                <span class="badge text-bg-light staff-status-badge"><?= e(ucfirst($row['role'])) ?> · <?= e(statusLabel($row['is_active'])) ?></span>
                                            </div>
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/dashboard.php?tab=staff&staff_q=<?= urlencode($staffSearch) ?>&staff_id=<?= (int)$row['id'] ?>">View</a>
                                        </div>

                                        <?php if ($selectedStaffId === (int)$row['id']): ?>
                                            <div class="admin-edit-box">
                                                <form method="POST" action="/admin/admin_action.php" novalidate>
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="update_staff">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Staff ID</label>
                                                            <input class="form-control account-readonly" value="<?= (int)$row['id'] ?>" disabled>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Role</label>
                                                            <select class="form-select" name="role">
                                                                <option value="customer" <?= $row['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                                                <option value="staff" <?= $row['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                                                <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Account Status</label>
                                                            <select class="form-select" name="is_active">
                                                                <option value="1" <?= $row['is_active'] === 't' ? 'selected' : '' ?>>Active</option>
                                                                <option value="0" <?= $row['is_active'] !== 't' ? 'selected' : '' ?>>Disabled</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control" name="phone" maxlength="20" pattern="^(?:\+44|0)7\d{9}$" value="<?= e($row['phone']) ?>">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Name</label>
                                                            <input class="form-control" name="full_name" maxlength="100" required value="<?= e($row['full_name']) ?>">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Email</label>
                                                            <input class="form-control" name="email" type="email" maxlength="150" required value="<?= e($row['email']) ?>">
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2 mt-4">
                                                        <button class="btn btn-primary" type="submit">Save</button>
                                                        <a class="btn btn-outline-secondary" href="/admin/dashboard.php?tab=staff&staff_q=<?= urlencode($staffSearch) ?>">Cancel</a>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="areas-panel" tabindex="0">
                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">Operating Areas</h4>
                                    <p class="text-muted mb-0">Add or update the postcode areas accepted by the send form.</p>
                                </div>

                                <form method="POST" action="/admin/admin_action.php" class="row g-3 align-items-end mb-4" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="add_area">
                                    <div class="col-md-4">
                                        <label class="form-label">Add Postcode</label>
                                        <input class="form-control postcode-input" name="postcode" maxlength="20" pattern="^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$" required placeholder="YO12 3QT">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">City/Town</label>
                                        <input class="form-control" name="city" maxlength="100" required placeholder="Beverley">
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button class="btn btn-primary" type="submit">Add</button>
                                    </div>
                                </form>

                                <form method="GET" action="/admin/dashboard.php" class="row g-3 align-items-end mb-4">
                                    <input type="hidden" name="tab" value="areas">
                                    <div class="col-md-9">
                                        <label class="form-label" for="area_q">Search</label>
                                        <input class="form-control" id="area_q" name="area_q" value="<?= e($areaSearch) ?>" placeholder="Postcode or city/town">
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button class="btn btn-primary" type="submit">Search</button>
                                    </div>
                                </form>

                                <?php if (empty($areas)): ?>
                                    <div class="tracking-empty-state">No operating areas found.</div>
                                <?php else: ?>
                                    <?php foreach ($areas as $row): ?>
                                        <div class="admin-list-row">
                                            <div>
                                                <strong><?= e($row['postcode']) ?></strong><br>
                                                <span><?= e($row['city']) ?></span>
                                            </div>
                                            <div class="text-md-center">
                                                <span class="badge text-bg-light staff-status-badge"><?= e(statusLabel($row['is_active'])) ?></span>
                                            </div>
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/dashboard.php?tab=areas&area_q=<?= urlencode($areaSearch) ?>&area_id=<?= (int)$row['id'] ?>">View</a>
                                        </div>

                                        <?php if ($selectedAreaId === (int)$row['id']): ?>
                                            <div class="admin-edit-box">
                                                <form method="POST" action="/admin/admin_action.php" novalidate>
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="update_area">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Postcode</label>
                                                            <input class="form-control postcode-input" name="postcode" maxlength="20" pattern="^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$" required value="<?= e($row['postcode']) ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">City/Town</label>
                                                            <input class="form-control" name="city" maxlength="100" required value="<?= e($row['city']) ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="is_active">
                                                                <option value="1" <?= $row['is_active'] === 't' ? 'selected' : '' ?>>Active</option>
                                                                <option value="0" <?= $row['is_active'] !== 't' ? 'selected' : '' ?>>Inactive</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 d-flex align-items-end">
                                                            <button class="btn btn-outline-danger w-100" name="delete_area" value="1" type="submit">Delete Location</button>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2 mt-4">
                                                        <button class="btn btn-primary" type="submit">Save</button>
                                                        <a class="btn btn-outline-secondary" href="/admin/dashboard.php?tab=areas&area_q=<?= urlencode($areaSearch) ?>">Cancel</a>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="announcements-panel" tabindex="0">
                            <div class="form-panel">
                                <div class="section-heading mb-4">
                                    <h4 class="mb-1">Announcements</h4>
                                    <p class="text-muted mb-0">Create service messages for customers.</p>
                                </div>

                                <form method="POST" action="/admin/admin_action.php" class="mb-4" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="add_announcement">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Title</label>
                                            <input class="form-control" name="title" maxlength="150" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Message</label>
                                            <textarea class="form-control" name="message" rows="4" required></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Expires At</label>
                                            <input class="form-control" type="datetime-local" name="expires_at">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="new_ann_active" name="is_active" value="1" checked>
                                                <label class="form-check-label" for="new_ann_active">Active</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit">Create Announcement</button>
                                        </div>
                                    </div>
                                </form>

                                <form method="GET" action="/admin/dashboard.php" class="row g-3 align-items-end mb-4">
                                    <input type="hidden" name="tab" value="announcements">
                                    <div class="col-md-9">
                                        <label class="form-label" for="ann_q">Search</label>
                                        <input class="form-control" id="ann_q" name="ann_q" value="<?= e($announcementSearch) ?>" placeholder="Title or message">
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button class="btn btn-primary" type="submit">Search</button>
                                    </div>
                                </form>

                                <?php if (empty($announcements)): ?>
                                    <div class="tracking-empty-state">No announcements found.</div>
                                <?php else: ?>
                                    <?php foreach ($announcements as $row): ?>
                                        <div class="admin-list-row">
                                            <div>
                                                <strong><?= e($row['title']) ?></strong><br>
                                                <small class="text-muted">By <?= e($row['created_by_name'] ?: 'Admin') ?> · <?= e($row['expires_at'] ?: 'No expiry') ?></small>
                                            </div>
                                            <div class="text-md-center">
                                                <span class="badge text-bg-light staff-status-badge"><?= e(statusLabel($row['is_active'])) ?></span>
                                            </div>
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/dashboard.php?tab=announcements&ann_q=<?= urlencode($announcementSearch) ?>&announcement_id=<?= (int)$row['id'] ?>">View</a>
                                        </div>

                                        <?php if ($selectedAnnouncementId === (int)$row['id']): ?>
                                            <div class="admin-edit-box">
                                                <form method="POST" action="/admin/admin_action.php" novalidate>
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="update_announcement">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label class="form-label">Title</label>
                                                            <input class="form-control" name="title" maxlength="150" required value="<?= e($row['title']) ?>">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Message</label>
                                                            <textarea class="form-control" name="message" rows="4" required><?= e($row['message']) ?></textarea>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Expires At</label>
                                                            <input class="form-control" type="datetime-local" name="expires_at" value="<?= !empty($row['expires_at']) ? e(date('Y-m-d\TH:i', strtotime($row['expires_at']))) : '' ?>">
                                                        </div>
                                                        <div class="col-md-6 d-flex align-items-end">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ann_active_<?= (int)$row['id'] ?>" name="is_active" value="1" <?= boolChecked($row['is_active']) ?>>
                                                                <label class="form-check-label" for="ann_active_<?= (int)$row['id'] ?>">Active</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2 mt-4">
                                                        <button class="btn btn-primary" type="submit">Save</button>
                                                        <button class="btn btn-outline-danger" name="delete_announcement" value="1" type="submit">Delete</button>
                                                        <a class="btn btn-outline-secondary" href="/admin/dashboard.php?tab=announcements&ann_q=<?= urlencode($announcementSearch) ?>">Cancel</a>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="/js/admin.js"></script>
</body>

</html>