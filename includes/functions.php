<?php
// Shared helper functions used across the whole project.
// This file starts the session, so include it early (before any HTML output).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Trim + escape a string for safe output/storage.
 */
function sanitize_input(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a clearly-labeled demo transaction reference, e.g. DEMO-FPX-20260810-A1B2C3.
 * Used only by the mock payment gateway (customer/payment_fpx.php, payment_card.php)
 * — never a real transaction identifier from any real processor.
 */
function generate_demo_transaction_ref(string $prefix): string
{
    return 'DEMO-' . strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Basic phone number sanity check: digits, spaces, +, -, () only, 7-20 chars.
 */
function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

/**
 * Redirect to another page (relative to BASE_URL) and stop execution.
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/**
 * Store a one-time flash message in the session.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message, if any.
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Total quantity of items in the logged-in customer's cart (for the navbar badge).
 */
function get_cart_count(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Guard for AJAX endpoints: replies with JSON + stops execution instead of
 * redirecting, since the caller is fetch(), not a browser navigation.
 */
function require_login_json(): void
{
    if (!is_logged_in() || is_admin()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please log in as a customer to continue.']);
        exit;
    }
}

/**
 * Generate (or reuse) a CSRF token for the current session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Check a submitted CSRF token against the session token.
 */
function verify_csrf_token(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate and move an uploaded image into $destDir with a random safe filename.
 * Returns ['success' => bool, 'filename' => string|null, 'error' => string|null].
 * 'filename' is null with success=true when no file was submitted (optional upload).
 */
function handle_image_upload(array $file, string $destDir): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'File upload failed.'];
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'filename' => null, 'error' => 'Image must be smaller than 2MB.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Only JPG, PNG, GIF, or WEBP images are allowed.'];
    }

    if (@getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'filename' => null, 'error' => 'The uploaded file is not a valid image.'];
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename)) {
        return ['success' => false, 'filename' => null, 'error' => 'Could not save the uploaded image.'];
    }

    return ['success' => true, 'filename' => $filename, 'error' => null];
}

/**
 * Delete a previously uploaded file (e.g. when replacing or removing a record's image).
 */
function delete_uploaded_file(string $dir, ?string $filename): void
{
    if ($filename) {
        $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
