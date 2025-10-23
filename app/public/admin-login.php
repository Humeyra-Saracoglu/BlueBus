<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include __DIR__ . '/../src/Views/layouts/header.php';
    include __DIR__ . '/../src/Views/admin-login.php';
    include __DIR__ . '/../src/Views/layouts/footer.php';
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

try {
    $db = getDbConnection();

    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = 'ADMIN'");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_role'] = 'ADMIN';
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_credit'] = $admin['credit_cents'];
        $_SESSION['is_admin'] = true;
        
        $_SESSION['success'] = 'Admin girişi başarılı! Hoş geldiniz.';
 
        if (file_exists(__DIR__ . '/../admin/index.php')) {
            header('Location: /admin/index.php');
        } else {
            header('Location: /index.php');
        }
        exit;
    } else {
        $_SESSION['error'] = 'Admin girişi başarısız! E-posta veya şifre hatalı.';
        $_SESSION['form_data'] = ['email' => $email];
        header('Location: /admin-login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    $_SESSION['error'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    header('Location: /admin-login.php');
    exit;
}