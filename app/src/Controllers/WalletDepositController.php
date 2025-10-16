<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) {
  http_response_code(401);
  echo "Önce giriş yapın.";
  exit;
}

$amountCents = (int)($_POST['amount_cents'] ?? 0);
if ($amountCents <= 0) {
  http_response_code(422);
  echo "Geçersiz tutar.";
  exit;
}

try {
  $pdo->beginTransaction();

  // Bakiye artırmma
  $up = $pdo->prepare("UPDATE users SET credit_cents = credit_cents + :a WHERE id = :uid");
  $up->execute([':a' => $amountCents, ':uid' => $u['id']]);

  $ins = $pdo->prepare("INSERT INTO wallet_tx (user_id, amount_cents, reason, created_at)
                        VALUES (:uid, :a, 'DEPOSIT', datetime('now'))");
  $ins->execute([':uid' => $u['id'], ':a' => $amountCents]);

  $pdo->commit();
  header("Location: /wallet?ok=1");
  exit;
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "Yükleme başarısız.";
  exit;
}
