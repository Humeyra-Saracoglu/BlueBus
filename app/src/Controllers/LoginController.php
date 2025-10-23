<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include __DIR__ . '/../Views/layouts/header.php';
    include __DIR__ . '/../Views/login.php';
    include __DIR__ . '/../Views/layouts/footer.php';
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
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_credit'] = $user['credit_cents'];
        $_SESSION['user_email'] = $user['email'];
        
        if ($user['role'] === 'FIRM_ADMIN' && $user['firm_id']) {
            $_SESSION['firm_id'] = $user['firm_id'];
        }
        
        $_SESSION['success'] = 'Giriş başarılı! Hoş geldiniz, ' . $user['name'] . '!';
        header('Location: /');
        exit;
    } else {
        $_SESSION['error'] = 'E-posta veya şifre hatalı!';
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