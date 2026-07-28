<?php
/**
 * Shared helpers: secure session bootstrap, validation rules,
 * CSRF protection, and the login guard used by protected pages.
 */

// ---- 1. Secure session bootstrap ----
// Must run before session_start(). This is what "issues and stores the
// token" on the backend side: PHP's session id, sent as an HttpOnly cookie,
// is our session token — JS on the frontend can never read it.
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7, // 7 days
        'path'     => '/',
        'secure'   => $isHttps,         // only over HTTPS in production
        'httponly' => true,             // not readable from JavaScript
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- 2. Validation rules (mirrored by client-side JS for instant feedback) ----
function validate_full_name(string $name): string {
    $name = trim($name);
    if ($name === '') return 'Full name is required.';
    if (mb_strlen($name) < 2) return 'Full name is too short.';
    return '';
}

function validate_email_field(string $email): string {
    $email = trim($email);
    if ($email === '') return 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Enter a valid email address.';
    return '';
}

function validate_password_field(string $password): string {
    if ($password === '') return 'Password is required.';
    // 8+ chars, at least one uppercase, one lowercase, one digit, one special char.
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
    if (!preg_match($pattern, $password)) {
        return 'Min 8 characters with uppercase, lowercase, number & special character.';
    }
    return '';
}

// ---- 3. CSRF protection ----
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ---- 4. Auth guard for protected pages ----
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php?redirected=1');
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

// ---- 5. Roles: every account is either 'user' or 'admin' ----
function current_user_role(): string {
    return $_SESSION['role'] ?? 'user';
}

function is_admin(): bool {
    return is_logged_in() && current_user_role() === 'admin';
}

// Send a logged-in non-admin somewhere safe instead of letting them see
// admin-only pages/data.
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: ' . home_path_for_role());
        exit;
    }
}

// A regular user should never land on an admin page, and an admin has no
// reason to be on the shopping pages meant for customers — send each role
// to its own home so the two areas stay separate.
function home_path_for_role(): string {
    return current_user_role() === 'admin' ? 'admin/dashboard.php' : 'home.php';
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string {
    return '$' . number_format($amount, 2);
}
