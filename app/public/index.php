<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

if ($path === '/')        { require __DIR__ . '/../src/Controllers/HomeController.php'; exit; }
if ($path === '/routes')  { require __DIR__ . '/../src/Controllers/RouteController.php'; exit; }

if ($path === '/login')    { require __DIR__ . '/../src/Controllers/LoginController.php'; exit; }
if ($path === '/register') { require __DIR__ . '/../src/Controllers/RegisterController.php'; exit; }
if ($path === '/logout')   { require __DIR__ . '/../src/Controllers/LogoutController.php'; exit; }

http_response_code(404);
echo "404";