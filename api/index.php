<?php
declare(strict_types=1);

/**
 * MindBridge: Vercel Serverless Gateway Router
 * Routes incoming web requests to the appropriate PHP pages.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Normalize URI
$cleanUri = trim($uri, '/');

// 1. Root page
if ($cleanUri === '' || $cleanUri === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require __DIR__ . '/../index.php';
    exit;
}

// 2. Direct match or with .php extension
$baseDir = dirname(__DIR__);
$targetPath = $baseDir . '/' . $cleanUri;

// If target is directory, look for index.php inside
if (is_dir($targetPath)) {
    $targetPath = rtrim($targetPath, '/') . '/index.php';
    $cleanUri = rtrim($cleanUri, '/') . '/index.php';
}

// Append .php extension if omitted (clean URLs)
if (!file_exists($targetPath) && file_exists($targetPath . '.php')) {
    $targetPath .= '.php';
    $cleanUri .= '.php';
}

// 3. Execute matched PHP script
if (file_exists($targetPath) && str_ends_with($targetPath, '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $cleanUri;
    $_SERVER['PHP_SELF'] = '/' . $cleanUri;
    require $targetPath;
    exit;
}

// 4. Fallback 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 Not Found · MindBridge</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; text-align: center; }
        .card { background: #1e293b; padding: 2.5rem; border-radius: 1rem; border: 1px solid #334155; max-width: 450px; }
        h1 { font-size: 3rem; margin: 0 0 1rem; color: #818cf8; }
        a { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: #4f46e5; color: white; text-decoration: none; border-radius: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The requested page <code><?= htmlspecialchars($uri) ?></code> does not exist.</p>
        <a href="/">Return to MindBridge</a>
    </div>
</body>
</html>
