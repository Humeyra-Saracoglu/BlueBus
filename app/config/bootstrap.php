<?php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Utils/Csrf.php';
// require_once __DIR__ . '/../src/Utils/Logger.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Istanbul');

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');