<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include __DIR__ . '/../src/Views/layouts/header.php';
    include __DIR__ . '/../src/Views/register.php';
    include __DIR__ . '/../src/Views/layouts/footer.php';
    exit;
}

$ad = htmlspecialchars(trim($_POST['ad']));
$soyad = htmlspecialchars(trim($_POST['soyad']));
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$telefon = preg_replace('/[^0-9]/', '', $_POST['telefon'] ?? '');
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

$errors = [];

if (empty($ad) || empty($soyad)) {
    $errors[] = 'Ad ve soyad zorunludur.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Geçerli bir e-posta adresi girin.';
}

if (!empty($telefon) && (strlen($telefon) !== 11 || !str_starts_with($telefon, '05'))) {
    $errors[] = 'Geçerli bir telefon numarası girin (05XXXXXXXXX).';
}

if (strlen($password) < 6) {
    $errors[] = 'Şifre en az 6 karakter olmalıdır.';
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
        header('Location: /register.php');
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $fullName = $ad . ' ' . $soyad;

    $stmt = $db->prepare("
        INSERT INTO users (name, email, password_hash, role, credit_cents) 
        VALUES (:name, :email, :password_hash, 'USER', 100000)
    ");
    
    $result = $stmt->execute([
        ':name' => $fullName,
        ':email' => $email,
        ':password_hash' => $hashed_password
    ]);
    
    if ($result) {
        $_SESSION['success'] = 'Kayıt başarılı! Giriş yapabilirsiniz. Başlangıç kredisi: 1.000 ₺';
        header('Location: /login.php');
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
    header('Location: /register.php');
    exit;
}