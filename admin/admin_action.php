<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.',
            'redirect' => '/admin/dashboard.php',
        ]);
        exit;
    }

    $_SESSION['admin_error'] = 'Invalid request.';
    header('Location: /admin/dashboard.php');
    exit;
}

function wantsJsonResponse(): bool
{
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

    return strtolower($requestedWith) === 'xmlhttprequest'
        || str_contains(strtolower($accept), 'application/json');
}

function redirectAdmin(string $tab, string $message, bool $success = true): void
{
    $redirectUrl = '/admin/dashboard.php?tab=' . urlencode($tab);

    if (wantsJsonResponse()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'redirect' => $redirectUrl,
        ]);
        exit;
    }

    $_SESSION[$success ? 'admin_success' : 'admin_error'] = $message;
    header('Location: ' . $redirectUrl);
    exit;
}

function cleanText(string $value): string
{
    return trim($value);
}

function cleanPhone(string $value): string
{
    return preg_replace('/\s+/', '', trim($value));
}

function cleanEmail(string $value): string
{
    return strtolower(trim($value));
}

function cleanPostcode(string $value): string
{
    $value = strtoupper(trim($value));
    return preg_replace('/\s+/', ' ', $value);
}

function normalisePostcode(string $postcode): string
{
    return strtoupper(preg_replace('/\s+/', '', trim($postcode)));
}

function parseAnnouncementExpiry(string $expiresAt)
{
    if ($expiresAt === '') {
        return null;
    }

    $timestamp = strtotime($expiresAt);

    if ($timestamp === false) {
        return false;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function validateUserFields(string $name, string $email, string $phone): ?string
{
    if ($name === '' || strlen($name) > 100 || !preg_match('/[A-Za-zÀ-ÿ]/', $name) || preg_match('/\d/', $name)) {
        return 'Please enter a valid name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        return 'Please enter a valid email address.';
    }

    if ($phone !== '' && !preg_match('/^(?:\+44|0)7\d{9}$/', $phone)) {
        return 'Please enter a valid UK mobile number.';
    }

    return null;
}

function ensureUniqueEmail($conn, string $email, int $userId): ?string
{
    $result = pg_query_params(
        $conn,
        "SELECT id FROM public.users WHERE email = $1 AND id <> $2 LIMIT 1",
        [$email, $userId]
    );

    if ($result && pg_num_rows($result) > 0) {
        return 'That email address is already used by another account.';
    }

    return null;
}

$action = (string)($_POST['action'] ?? '');

switch ($action) {
    case 'update_user': {
            $id = (int)($_POST['id'] ?? 0);

            if ($id === 13) {
                redirectAdmin('users', 'This is a protected system account and cannot be modified.', false);
            }

            $name = cleanText($_POST['full_name'] ?? '');
            $email = cleanEmail($_POST['email'] ?? '');
            $phone = cleanPhone($_POST['phone'] ?? '');
            $isActive = ($_POST['is_active'] ?? '0') === '1';
            $role = (string)($_POST['role'] ?? 'customer');

            if (!in_array($role, ['customer', 'staff', 'admin'], true)) {
                redirectAdmin('users', 'Invalid role selected.', false);
            }

            if ($id <= 0) {
                redirectAdmin('users', 'Invalid user selected.', false);
            }

            $error = validateUserFields($name, $email, $phone) ?: ensureUniqueEmail($conn, $email, $id);
            if ($error) {
                redirectAdmin('users', $error, false);
            }

            $result = pg_query_params(
                $conn,
                "UPDATE public.users
                SET full_name = $1,
                    email = $2,
                    phone = $3,
                    role = $4,
                    is_active = $5,
                    updated_at = NOW()
                WHERE id = $6 AND role = 'customer'",
                [$name, $email, ($phone !== '' ? $phone : null), $role, $isActive ? 'true' : 'false', $id]
            );

            if (!$result || pg_affected_rows($result) !== 1) {
                redirectAdmin('users', 'Could not update the user account.', false);
            }

            redirectAdmin('users', 'User account updated successfully.');
        }

    case 'update_staff': {
            $id = (int)($_POST['id'] ?? 0);

            if ($id === 13) {
                redirectAdmin('staff', 'This is a protected system account and cannot be modified.', false);
            }

            $name = cleanText($_POST['full_name'] ?? '');
            $email = cleanEmail($_POST['email'] ?? '');
            $phone = cleanPhone($_POST['phone'] ?? '');
            $role = (string)($_POST['role'] ?? '');
            $isActive = ($_POST['is_active'] ?? '0') === '1';
            $currentAdminId = (int)($_SESSION['user_id'] ?? 0);

            if ($id <= 0) {
                redirectAdmin('staff', 'Invalid staff account selected.', false);
            }

            if (!in_array($role, ['customer', 'staff', 'admin'], true)) {
                redirectAdmin('staff', 'Invalid role selected.', false);
            }

            if ($id === $currentAdminId && (!$isActive || $role !== 'admin')) {
                redirectAdmin('staff', 'You cannot disable your own admin access.', false);
            }

            $error = validateUserFields($name, $email, $phone) ?: ensureUniqueEmail($conn, $email, $id);
            if ($error) {
                redirectAdmin('staff', $error, false);
            }

            $result = pg_query_params(
                $conn,
                "UPDATE public.users
             SET full_name = $1,
                 email = $2,
                 phone = $3,
                 role = $4,
                 is_active = $5,
                 updated_at = NOW()
             WHERE id = $6 AND role IN ('staff', 'admin')",
                [$name, $email, ($phone !== '' ? $phone : null), $role, $isActive ? 'true' : 'false', $id]
            );

            if (!$result || pg_affected_rows($result) !== 1) {
                redirectAdmin('staff', 'Could not update the staff account.', false);
            }

            redirectAdmin('staff', 'Staff account updated successfully.');
        }

    case 'add_area': {
            $postcode = cleanPostcode($_POST['postcode'] ?? '');
            $city = cleanText($_POST['city'] ?? '');

            if (!preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/', $postcode)) {
                redirectAdmin('areas', 'Please enter a valid UK postcode.', false);
            }

            if ($city === '' || strlen($city) > 100) {
                redirectAdmin('areas', 'Please enter a valid city/town.', false);
            }

            $duplicate = pg_query_params(
                $conn,
                "SELECT id FROM public.operating_areas
             WHERE REPLACE(UPPER(postcode), ' ', '') = $1
             LIMIT 1",
                [normalisePostcode($postcode)]
            );

            if ($duplicate && pg_num_rows($duplicate) > 0) {
                redirectAdmin('areas', 'That postcode is already in operating areas.', false);
            }

            $result = pg_query_params(
                $conn,
                "INSERT INTO public.operating_areas (postcode, city, is_active)
             VALUES ($1, $2, true)",
                [$postcode, $city]
            );

            if (!$result) {
                redirectAdmin('areas', 'Could not add postcode.', false);
            }

            redirectAdmin('areas', 'Operating area added successfully.');
        }

    case 'update_area': {
            $id = (int)($_POST['id'] ?? 0);

            if (isset($_POST['delete_area'])) {
                $result = pg_query_params($conn, "DELETE FROM public.operating_areas WHERE id = $1", [$id]);
                if (!$result || pg_affected_rows($result) !== 1) {
                    redirectAdmin('areas', 'Could not delete the operating area.', false);
                }
                redirectAdmin('areas', 'Operating area deleted successfully.');
            }

            $postcode = cleanPostcode($_POST['postcode'] ?? '');
            $city = cleanText($_POST['city'] ?? '');
            $isActive = ($_POST['is_active'] ?? '0') === '1';

            if ($id <= 0) {
                redirectAdmin('areas', 'Invalid operating area selected.', false);
            }

            if (!preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/', $postcode)) {
                redirectAdmin('areas', 'Please enter a valid UK postcode.', false);
            }

            if ($city === '' || strlen($city) > 100) {
                redirectAdmin('areas', 'Please enter a valid city/town.', false);
            }

            $duplicate = pg_query_params(
                $conn,
                "SELECT id FROM public.operating_areas
             WHERE REPLACE(UPPER(postcode), ' ', '') = $1
               AND id <> $2
             LIMIT 1",
                [normalisePostcode($postcode), $id]
            );

            if ($duplicate && pg_num_rows($duplicate) > 0) {
                redirectAdmin('areas', 'That postcode is already in operating areas.', false);
            }

            $result = pg_query_params(
                $conn,
                "UPDATE public.operating_areas
             SET postcode = $1,
                 city = $2,
                 is_active = $3
             WHERE id = $4",
                [$postcode, $city, $isActive ? 'true' : 'false', $id]
            );

            if (!$result || pg_affected_rows($result) !== 1) {
                redirectAdmin('areas', 'Could not update the operating area.', false);
            }

            redirectAdmin('areas', 'Operating area updated successfully.');
        }

    case 'add_announcement': {
            $title = cleanText($_POST['title'] ?? '');
            $message = cleanText($_POST['message'] ?? '');
            $expiresAt = cleanText($_POST['expires_at'] ?? '');

            if ($expiresAt === '') {
                redirectAdmin('announcements', 'Please enter an expiry date for the announcement.', false);
            }

            $isActive = isset($_POST['is_active']);
            $createdBy = (int)($_SESSION['user_id'] ?? 0);

            if ($title === '' || strlen($title) > 150) {
                redirectAdmin('announcements', 'Please enter a valid announcement title.', false);
            }

            if ($message === '' || strlen($message) > 1000) {
                redirectAdmin('announcements', 'Announcement message must be between 1 and 1000 characters.', false);
            }

            $expiresValue = parseAnnouncementExpiry($expiresAt);

            if ($expiresValue === false) {
                redirectAdmin('announcements', 'Please enter a valid expiry date.', false);
            }

            $result = pg_query_params(
                $conn,
                "INSERT INTO public.announcements (title, message, is_active, created_by, expires_at)
             VALUES ($1, $2, $3, $4, $5)",
                [$title, $message, $isActive ? 'true' : 'false', $createdBy, $expiresValue]
            );

            if (!$result) {
                redirectAdmin('announcements', 'Could not create announcement.', false);
            }

            redirectAdmin('announcements', 'Announcement created successfully.');
        }

    case 'update_announcement': {
            $id = (int)($_POST['id'] ?? 0);

            if (isset($_POST['delete_announcement'])) {
                $result = pg_query_params($conn, "DELETE FROM public.announcements WHERE id = $1", [$id]);
                if (!$result || pg_affected_rows($result) !== 1) {
                    redirectAdmin('announcements', 'Could not delete the announcement.', false);
                }
                redirectAdmin('announcements', 'Announcement deleted successfully.');
            }

            $title = cleanText($_POST['title'] ?? '');
            $message = cleanText($_POST['message'] ?? '');
            $expiresAt = cleanText($_POST['expires_at'] ?? '');
            $isActive = isset($_POST['is_active']);

            if ($id <= 0) {
                redirectAdmin('announcements', 'Invalid announcement selected.', false);
            }

            if ($title === '' || strlen($title) > 150) {
                redirectAdmin('announcements', 'Please enter a valid announcement title.', false);
            }

            if ($message === '' || strlen($message) > 1000) {
                redirectAdmin('announcements', 'Announcement message must be between 1 and 1000 characters.', false);
            }

            $expiresValue = parseAnnouncementExpiry($expiresAt);

            if ($expiresValue === false) {
                redirectAdmin('announcements', 'Please enter a valid expiry date.', false);
            }

            $result = pg_query_params(
                $conn,
                "UPDATE public.announcements
             SET title = $1,
                 message = $2,
                 is_active = $3,
                 expires_at = $4
             WHERE id = $5",
                [$title, $message, $isActive ? 'true' : 'false', $expiresValue, $id]
            );

            if (!$result || pg_affected_rows($result) !== 1) {
                redirectAdmin('announcements', 'Could not update the announcement.', false);
            }

            redirectAdmin('announcements', 'Announcement updated successfully.');
        }

    default:
        redirectAdmin('overview', 'Invalid admin action.', false);
}
