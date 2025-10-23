<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
    header('Location: /login');
    exit;
}

include __DIR__ . '/../Views/layouts/header.php';
include __DIR__ . '/../Views/biletlerim.php';
include __DIR__ . '/../Views/layouts/footer.php';