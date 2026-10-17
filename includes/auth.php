<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Returns the currently authenticated user session data, or null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Checks whether any user is currently signed in.
 */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Ensures the client is logged in; redirects to login page otherwise.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please sign in to access this page.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    // Enforce initial mandatory password change
    $user = current_user();
    if (!empty($user['must_change_password'])) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        if (!str_contains($currentUri, 'change-password.php') && !str_contains($currentUri, 'logout.php')) {
            $_SESSION['flash_warning'] = 'For security, you must update your temporary password before accessing the system.';
            header('Location: ' . BASE_URL . '/change-password.php');
            exit;
        }
    }
}

/**
 * Ensures the logged in user possesses the requested role.
 */
function require_role(string $requiredRole): void
{
    require_login();
    $user = current_user();

    if (($user['role'] ?? '') !== $requiredRole) {
        http_response_code(403);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Insufficient role permissions.']);
            exit;
        }
        die('
            <div style="font-family:sans-serif; max-width:600px; margin:50px auto; padding:24px; border:1px solid #fed7d7; border-radius:12px; background:#fff5f5;">
                <h2 style="color:#9b2c2c; margin-top:0;">Access Denied (403)</h2>
                <p style="color:#4a5568;">You do not have administrative credentials to view this page.</p>
                <a href="' . BASE_URL . '/user/dashboard.php" style="display:inline-block; padding:10px 18px; background:#1f3a93; color:#fff; text-decoration:none; border-radius:6px;">Go to Student Dashboard</a>
            </div>
        ');
    }
}

/**
 * Enforces Administrator role check.
 */
function require_admin(): void
{
    require_role('administrator');
}

/**
 * Enforces Student (User) role check.
 */
function require_student(): void
{
    require_role('user');
}

/**
 * Authenticates a user into session with session regeneration.
 */
function login_user(array $userData): void
{
    session_regenerate_id(true);
    // Never store raw password or hash in session
    unset($userData['password_hash']);
    $_SESSION['user'] = $userData;
    $_SESSION['LAST_ACTIVITY'] = time();
}

/**
 * Signs the user out and flushes the session.
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
