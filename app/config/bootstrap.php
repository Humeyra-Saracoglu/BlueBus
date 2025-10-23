<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

require_once __DIR__ . '/../src/Utils/Auth.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Europe/Istanbul');

header('Content-Type: text/html; charset=utf-8');