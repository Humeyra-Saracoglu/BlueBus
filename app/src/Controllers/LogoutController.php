<?php
declare(strict_types=1);
require_once __DIR__ . '/../Utils/Auth.php';
auth_logout();
header('Location: /');
