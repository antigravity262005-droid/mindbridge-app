<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Returns the singleton PDO database connection.
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $isCloudDb = (defined('DB_SSL') && DB_SSL) || str_contains(DB_HOST, 'tidbcloud.com') || str_contains(DB_HOST, 'aivencloud.com');
        if ($isCloudDb) {
            $caPath = __DIR__ . '/cacert.pem';
            if (file_exists($caPath)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
            }
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            // Log actual error on server, show friendly message
            error_log('Database Connection Error: ' . $e->getMessage());
            die('
                <div style="font-family:sans-serif; max-width:600px; margin:50px auto; padding:24px; border:1px solid #e2e8f0; border-radius:12px; background:#fff5f5;">
                    <h2 style="color:#c53030; margin-top:0;">Database Connection Notice</h2>
                    <p style="color:#4a5568;">MindBridge could not connect to MySQL at <code>' . htmlspecialchars(DB_HOST . ':' . DB_PORT) . '</code>.</p>
                    <p style="color:#718096; font-size:12px; font-family:monospace; background:#edf2f7; padding:8px; border-radius:6px; word-break:break-all;">' . htmlspecialchars($e->getMessage()) . '</p>
                </div>
            ');
        }
    }

    return $pdo;
}
