<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include __DIR__ . '/../src/Views/layouts/header.php';
    include __DIR__ . '/../src/Views/login.php';
    include __DIR__ . '/../src/Views/layouts/footer.php';
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['ad'] . ' ' . $user['soyad'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_credit'] = $user['credit_cents'];
        $_SESSION['user_email'] = $user['email'];
        
        if ($user['role'] === 'FIRM_ADMIN' && isset($user['firm_id']) && $user['firm_id']) {
            $_SESSION['firm_id'] = $user['firm_id'];
        }
        
        $_SESSION['success'] = 'Giris basarili! Hos geldiniz, ' . $user['ad'] . ' ' . $user['soyad'] . '!';
        header('Location: /index.php');
        exit;

    } else {
        $_SESSION['error'] = "E-posta veya şifre hatalı!";
        $_SESSION['form_data'] = ['email' => $email];
        header('Location: /login.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    header('Location: /login.php');
    exit;
}