<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Generate or retrieve the current session CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden HTML input field containing the CSRF token.
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate the CSRF token sent via POST or HTTP header.
 */
function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || empty($token)) {
        return false;
    }
    return hash_equals(csrf_token(), $token);
}

/**
 * Enforce CSRF token verification; terminates with HTTP 403 on failure.
 */
function require_csrf(): void
{
    if (!csrf_verify()) {
        http_response_code(403);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Invalid or expired CSRF security token. Please refresh the page.']);
            exit;
        }
        die('
            <div style="font-family:sans-serif; max-width:600px; margin:50px auto; padding:24px; border:1px solid #fed7d7; border-radius:12px; background:#fff5f5;">
                <h2 style="color:#9b2c2c; margin-top:0;">Security Verification Failed</h2>
                <p style="color:#4a5568;">Your request could not be validated (CSRF mismatch). Please go back, refresh the page, and try again.</p>
                <a href="' . BASE_URL . '/" style="display:inline-block; padding:10px 18px; background:#1f3a93; color:#fff; text-decoration:none; border-radius:6px;">Return Home</a>
            </div>
        ');
    }
}

/**
 * Compatibility alias for require_csrf.
 */
function verify_csrf(): void
{
    require_csrf();
}
