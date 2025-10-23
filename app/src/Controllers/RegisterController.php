<?php
declare(strict_types=1);
require_once __DIR__ . '/../Utils/Csrf.php';
require_once __DIR__ . '/../Utils/RateLimit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include __DIR__ . '/../Views/layouts/header.php';
    include __DIR__ . '/../Views/register.php';
    include __DIR__ . '/../Views/layouts/footer.php';
    exit;
}

require_csrf();

$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit('register', $client_ip, 3, 3600)) {
    $remaining_time = get_rate_limit_remaining_time('register', $client_ip);
    $minutes = ceil($remaining_time / 60);
    $_SESSION['error'] = "Çok fazla kayıt denemesi yaptınız. Lütfen {$minutes} dakika sonra tekrar deneyin.";
    header('Location: /register');
    exit;
}

$ad = htmlspecialchars(trim($_POST['ad']));
$soyad = htmlspecialchars(trim($_POST['soyad']));
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$telefon = preg_replace('/[^0-9]/', '', $_POST['telefon']);
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

$errors = [];

if (empty($ad) || empty($soyad)) {
    $errors[] = 'Ad ve soyad zorunludur.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Geçerli bir e-posta adresi girin.';
}

if (strlen($telefon) !== 11 || !str_starts_with($telefon, '05')) {
    $errors[] = 'Geçerli bir telefon numarası girin (05XXXXXXXXX).';
}

if (strlen($password) < 8) {
    $errors[] = 'Şifre en az 8 karakter olmalıdır.';
}

// Şifre güvenlik kontrolü
if (!preg_match('/[a-z]/', $password)) {
    $errors[] = 'Şifre en az bir küçük harf içermelidir.';
}

if (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Şifre en az bir büyük harf içermelidir.';
}

if (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Şifre en az bir rakam içermelidir.';
}

if ($password !== $password_confirm) {
    $errors[] = 'Şifreler eşleşmiyor.';
}

if (!isset($_POST['terms'])) {
    $errors[] = 'Kullanım şartlarını kabul etmelisiniz.';
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
    }
 
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: /register');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO users (ad, soyad, email, telefon, password, role, credit_cents) 
        VALUES (:ad, :soyad, :email, :telefon, :password, 'USER', 100000)
    ");
    
    $result = $stmt->execute([
        ':ad' => $ad,
        ':soyad' => $soyad,
        ':email' => $email,
        ':telefon' => $telefon,
        ':password' => $hashed_password
    ]);
    
    if ($result) {
        reset_rate_limit('register', $client_ip);
        
        $_SESSION['success'] = 'Kayıt başarılı! Giriş yapabilirsiniz. Başlangıç kredisi: 1.000 ₺';
        header('Location: /login');
        exit;
    } else {
        $errors[] = 'Kayıt sırasında bir hata oluştu.';
    }
    
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    $errors[] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: /register');
    exit;
}