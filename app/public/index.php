<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

if     ($path === '/' || $path === '/index.php')        { require __DIR__ . '/../src/Controllers/HomeController.php'; exit; }
elseif ($path === '/routes')  { require __DIR__ . '/../src/Controllers/RouteController.php'; exit; }
elseif ($path === '/buy' && $_SERVER['REQUEST_METHOD'] === 'POST')  { require __DIR__ . '/../src/Controllers/PurchaseController.php'; exit; }
elseif ($path === '/tickets') { require __DIR__ . '/../src/Controllers/MyTicketsController.php'; exit; }
elseif ($path === '/wallet')  { require __DIR__ . '/../src/Controllers/WalletController.php'; exit; }
elseif ($path === '/wallet/deposit' && $_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/../src/Controllers/WalletDepositController.php'; exit; }
elseif ($path === '/tickets/download') { require __DIR__ . '/../src/Controllers/TicketPdfController.php'; exit; }
elseif ($path === '/ticket-pdf') { require __DIR__ . '/../src/Controllers/TicketPdfController.php'; exit; }
elseif ($path === '/tickets/cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/../src/Controllers/TicketCancelController.php'; exit; }
elseif ($path === '/cancel-ticket' && $_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/../src/Controllers/TicketCancelController.php'; exit; }

elseif ($path === '/login')   { require __DIR__ . '/../src/Controllers/LoginController.php'; exit; }
elseif ($path === '/register'){ require __DIR__ . '/../src/Controllers/RegisterController.php'; exit; }
elseif ($path === '/logout')  { require __DIR__ . '/../src/Controllers/LogoutController.php'; exit; }
elseif ($path === '/admin')   { require __DIR__ . '/../src/Controllers/AdminController.php'; exit; }

elseif ($path === '/firm-admin') { require __DIR__ . '/../src/Controllers/FirmAdminController.php'; exit; }
elseif ($path === '/firm-admin/route/add') { require __DIR__ . '/../src/Controllers/FirmAdminRouteAddController.php'; exit; }
elseif ($path === '/firm-admin/route/edit') { require __DIR__ . '/../src/Controllers/FirmAdminRouteEditController.php'; exit; }
elseif ($path === '/firm-admin/route/delete') { require __DIR__ . '/../src/Controllers/FirmAdminRouteDeleteController.php'; exit; }
elseif ($path === '/firm-admin/coupon/add') { require __DIR__ . '/../src/Controllers/FirmAdminCouponAddController.php'; exit; }
elseif ($path === '/firm-admin/coupon/edit') { require __DIR__ . '/../src/Controllers/FirmAdminCouponEditController.php'; exit; }
elseif ($path === '/firm-admin/coupon/delete') { require __DIR__ . '/../src/Controllers/FirmAdminCouponDeleteController.php'; exit; }

// 404
http_response_code(404);
echo "404 - Sayfa bulunamadı";