<?php
declare(strict_types=1);

// Application Configuration
define('APP_NAME', 'MindBridge');
define('APP_TAGLINE', 'A Student Mental Wellness, Awareness, and Support Referral System');
define('APP_THEME', 'Suicide Prevention and Mental Health Awareness');
// Helper to read config from $_ENV, $_SERVER, or getenv() in serverless runtimes
function get_config_var(string $key, string $default = ''): string {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return trim((string)$_ENV[$key]);
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return trim((string)$_SERVER[$key]);
    $val = getenv($key);
    if ($val !== false && $val !== '') return trim((string)$val);
    return trim($default);
}

// Dynamic Base URL detection (supports local XAMPP subfolder and cloud root domain)
$customBase = get_config_var('BASE_URL');
if ($customBase !== '') {
    define('BASE_URL', rtrim($customBase, '/'));
} elseif (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/mindbridge')) {
    define('BASE_URL', '/mindbridge');
} else {
    define('BASE_URL', '');
}

// Database Credentials (resolves from $_ENV, $_SERVER, getenv, or local XAMPP default)
define('DB_HOST', get_config_var('DB_HOST', '127.0.0.1'));
define('DB_PORT', get_config_var('DB_PORT', '3306'));
define('DB_NAME', get_config_var('DB_NAME', 'mindbridge_db'));
define('DB_USER', get_config_var('DB_USER', 'root'));
define('DB_PASS', get_config_var('DB_PASS', ''));
define('DB_CHARSET', get_config_var('DB_CHARSET', 'utf8mb4'));
define('DB_SSL', in_array(strtolower(get_config_var('DB_SSL', 'false')), ['true', '1', 'yes'], true));

// Session Lifetime (30 minutes of inactivity)
define('SESSION_LIFETIME', 1800);

// Default Timezone
date_default_timezone_set('Asia/Manila');

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Session timeout validation
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_error'] = 'Your session has expired due to inactivity. Please sign in again.';
}
$_SESSION['LAST_ACTIVITY'] = time();
