<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo '<h1>Kayıt Ol</h1>
  <form method="POST" style="display:flex;gap:8px;flex-direction:column;max-width:320px;">
    <input name="name" placeholder="Ad Soyad" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Şifre (min 6)" required>
    <button type="submit">Kayıt Ol</button>
    <p>Zaten hesabın var mı? <a href="/login">Giriş</a></p>
  </form>';
  exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$pass = (string)($_POST['password'] ?? '');

if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
  http_response_code(400);
  echo "Geçersiz veri.";
  exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (:n,:e,:h,"USER")');
try {
  $stmt->execute([':n'=>$name, ':e'=>$email, ':h'=>$hash]);
  echo "Kayıt tamam. <a href=\"/login\">Giriş yap</a>";
} catch (PDOException $e) {
  if (str_contains($e->getMessage(), 'UNIQUE')) {
    echo "Bu email zaten kayıtlı. <a href=\"/login\">Giriş yap</a>";
  } else {
    throw $e;
  }
}
