<?php
declare(strict_types=1);
require_once __DIR__ . '/../Utils/Csrf.php';
require_once __DIR__ . '/../Utils/RateLimit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include __DIR__ . '/../Views/layouts/header.php';
    include __DIR__ . '/../Views/login.php';
    include __DIR__ . '/../Views/layouts/footer.php';
    exit;
}

require_csrf();

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit('login', $client_ip, 5, 900)) {
    $remaining_time = get_rate_limit_remaining_time('login', $client_ip);
    $minutes = ceil($remaining_time / 60);
    $_SESSION['error'] = "Çok fazla başarısız giriş denemesi yaptınız. Lütfen {$minutes} dakika sonra tekrar deneyin.";
    header('Location: /login');
    exit;
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        
        reset_rate_limit('login', $client_ip);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['ad'] . ' ' . $user['soyad'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_credit'] = $user['credit_cents'];
        $_SESSION['user_email'] = $user['email'];
        
        if ($user['role'] === 'FIRM_ADMIN' && $user['firm_id']) {
            $_SESSION['firm_id'] = $user['firm_id'];
        }
        
        $_SESSION['success'] = 'Giriş başarılı! Hoş geldiniz, ' . htmlspecialchars($user['ad'] . ' ' . $user['soyad']) . '!';
        header('Location: /');
        exit;
    } else {
        $remaining = get_rate_limit_remaining_attempts('login', $client_ip, 5);
        
        if ($remaining > 0 && $remaining <= 2) {
            $_SESSION['error'] = "E-posta veya şifre hatalı! Dikkat: {$remaining} deneme hakkınız kaldı.";
        } else {
            $_SESSION['error'] = 'E-posta veya şifre hatalı!';
        }
        
        $_SESSION['form_data'] = ['email' => $email];
        header('Location: /login');
        exit;
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    header('Location: /login');
    exit;
}