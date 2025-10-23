<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
if ($ticketId <= 0) { http_response_code(400); echo "Geçersiz istek."; exit; }

$db = getDbConnection();

$info = $db->prepare("
  SELECT t.id, t.user_id, t.status, t.route_id, t.price_paid_cents, r.depart_at
  FROM tickets t
  JOIN routes r ON r.id = t.route_id
  WHERE t.id = :tid
");
$info->execute([':tid' => $ticketId]);
$ticket = $info->fetch();

if (!$ticket) { http_response_code(404); echo "Bilet bulunamadı."; exit; }
if ((int)$ticket['user_id'] !== (int)$u['id']) { http_response_code(403); echo "Bu bileti iptal edemezsin."; exit; }
if ($ticket['status'] !== 'ACTIVE') { http_response_code(409); echo "Bilet zaten iptal edilmiş ya da aktif değil."; exit; }

// 1 SAAT KURALI KONTROLÜ
$departAt = strtotime($ticket['depart_at']);
$now = time();
$hourInSeconds = 3600;

if (($departAt - $now) < $hourInSeconds) {
    $_SESSION['error'] = 'Kalkış saatine 1 saatten az süre kaldı. Bilet iptal edilemez.';
    header('Location: /tickets'); 
    exit;
}

$priceCents = (int)$ticket['price_paid_cents'];

try {
  $db->beginTransaction();

  $up = $db->prepare("UPDATE tickets SET status='CANCELLED' WHERE id=:tid AND status='ACTIVE'");
  $up->execute([':tid' => $ticketId]);
  if ($up->rowCount() !== 1) {
    throw new RuntimeException('Bilet durum güncellenemedi.');
  }

  $inc = $db->prepare("UPDATE users SET credit_cents = credit_cents + :amt WHERE id=:uid");
  $inc->execute([':amt' => $priceCents, ':uid' => $u['id']]);

  $tx = $db->prepare("INSERT INTO wallet_tx(user_id, amount_cents, reason, created_at) VALUES(:uid, :amt, :rsn, datetime('now'))");
  $tx->execute([
    ':uid' => $u['id'],
    ':amt' => $priceCents,
    ':rsn' => "Bilet iptali #{$ticketId}"
  ]);

  $db->commit();
  
  $_SESSION['success'] = 'Bilet başarıyla iptal edildi. ' . number_format($priceCents/100, 2) . ' ₺ hesabınıza iade edildi.';
} catch (Throwable $e) {
  if ($db->inTransaction()) { $db->rollBack(); }
  $_SESSION['error'] = 'İptal sırasında hata oluştu.';
}

header('Location: /tickets');
exit;
