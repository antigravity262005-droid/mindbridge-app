<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

logout_user();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

flash_set('info', 'You have been safely signed out. Take care of yourself!');
header('Location: ' . BASE_URL . '/login.php');
exit;
