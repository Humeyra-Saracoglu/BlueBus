<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Auth.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
    header('Location: /login.php');
    exit;
}

include __DIR__ . '/../src/Views/layouts/header.php';
include __DIR__ . '/../src/Views/biletlerim.php';
include __DIR__ . '/../src/Views/layouts/footer.php';