<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) {
  $_SESSION['error'] = 'Önce giriş yapın.';
  header('Location: /login');
  exit;
}

$amountCents = (int)($_POST['amount_cents'] ?? 0);
if ($amountCents <= 0) {
  $_SESSION['error'] = 'Geçersiz tutar.';
  header('Location: /wallet');
  exit;
}

try {
  $db = getDbConnection();
  $db->beginTransaction();

  $up = $db->prepare("UPDATE users SET credit_cents = credit_cents + :a WHERE id = :uid");
  $up->execute([':a' => $amountCents, ':uid' => $u['id']]);

  $ins = $db->prepare("INSERT INTO wallet_tx (user_id, amount_cents, reason, created_at)
                        VALUES (:uid, :a, 'Para Yükleme', datetime('now'))");
  $ins->execute([':uid' => $u['id'], ':a' => $amountCents]);

  $db->commit();
  
  $_SESSION['user_credit'] = ($_SESSION['user_credit'] ?? 0) + $amountCents;
  
  $_SESSION['success'] = 'Bakiye yükleme başarılı! Yüklenen: ' . number_format($amountCents/100, 2) . ' ₺';
  header("Location: /wallet?ok=1");
  exit;
} catch (Throwable $e) {
  if ($db->inTransaction()) $db->rollBack();
  $_SESSION['error'] = 'Yükleme başarısız: ' . $e->getMessage();
  header('Location: /wallet');
  exit;
}
