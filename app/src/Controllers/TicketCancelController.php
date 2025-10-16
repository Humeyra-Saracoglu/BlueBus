<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
if ($ticketId <= 0) { http_response_code(400); echo "Geçersiz istek."; exit; }

$info = $pdo->prepare("
  SELECT t.id, t.user_id, t.status, t.route_id, r.price_cents
  FROM tickets t
  JOIN routes r ON r.id = t.route_id
  WHERE t.id = :tid
");
$info->execute([':tid' => $ticketId]);
$ticket = $info->fetch();

if (!$ticket) { http_response_code(404); echo "Bilet bulunamadı."; exit; }
if ((int)$ticket['user_id'] !== (int)$u['id']) { http_response_code(403); echo "Bu bileti iptal edemezsin."; exit; }
if ($ticket['status'] !== 'ACTIVE') { http_response_code(409); echo "Bilet zaten iptal edilmiş ya da aktif değil."; exit; }

$priceCents = (int)$ticket['price_cents'];

try {
  $pdo->beginTransaction();

  $up = $pdo->prepare("UPDATE tickets SET status='CANCELED' WHERE id=:tid AND status='ACTIVE'");
  $up->execute([':tid' => $ticketId]);
  if ($up->rowCount() !== 1) {
    throw new RuntimeException('Bilet durum güncellenemedi.');
  }

  $inc = $pdo->prepare("UPDATE users SET credit_cents = credit_cents + :amt WHERE id=:uid");
  $inc->execute([':amt' => $priceCents, ':uid' => $u['id']]);

  $tx = $pdo->prepare("INSERT INTO wallet_tx(user_id, amount_cents, reason) VALUES(:uid, :amt, :rsn)");
  $tx->execute([
    ':uid' => $u['id'],
    ':amt' => $priceCents,
    ':rsn' => "Bilet iptali #{$ticketId}"
  ]);

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  http_response_code(500);
  echo "İptal sırasında hata oluştu.";
  exit;
}

header('Location: /tickets');
exit;
