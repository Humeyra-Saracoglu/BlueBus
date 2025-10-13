<?php
declare(strict_types=1);
require_once __DIR__ . '/../Utils/Auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo '<h1>Giriş Yap</h1>
  <form method="POST" style="display:flex;gap:8px;flex-direction:column;max-width:320px;">
    <input name="email" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Şifre" required>
    <button type="submit">Giriş</button>
    <p>Hesabın yok mu? <a href="/register">Kayıt ol</a></p>
  </form>';
  exit;
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$pass  = (string)($_POST['password'] ?? '');

$stmt = $pdo->prepare('SELECT id,name,role,firm_id,password_hash FROM users WHERE email=:e');
$stmt->execute([':e'=>$email]);
$user = $stmt->fetch();

if ($user && password_verify($pass, (string)$user['password_hash'])) {
  auth_login($user);
  header('Location: /');
  exit;
}
http_response_code(401);
echo "Geçersiz email veya şifre. <a href=\"/login\">Tekrar dene</a>";
