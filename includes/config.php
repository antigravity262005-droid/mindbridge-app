<?php
declare(strict_types=1);

// Application Configuration
define('APP_NAME', 'MindBridge');
define('APP_TAGLINE', 'A Student Mental Wellness, Awareness, and Support Referral System');
define('APP_THEME', 'Suicide Prevention and Mental Health Awareness');
// Dynamic Base URL detection (supports local XAMPP subfolder and cloud root domain)
if (getenv('BASE_URL') !== false) {
    define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
} elseif (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/mindbridge')) {
    define('BASE_URL', '/mindbridge');
} else {
    define('BASE_URL', '');
}

// Database Credentials (from Cloud Environment or local XAMPP default)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'mindbridge_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('DB_SSL', getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1');

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
