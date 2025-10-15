<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) {
  http_response_code(401);
  echo "Giriş yapmalısın.";
  exit;
}

$userId = (int)$u['id'];

$routeId = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
$seatNo  = isset($_POST['seat_no'])  ? (int)$_POST['seat_no']  : 0;
$coupon  = trim($_POST['coupon_code'] ?? '');

if ($routeId <= 0 || $seatNo <= 0) {
  http_response_code(400);
  echo "Eksik veri (route_id/seat_no).";
  exit;
}

// Seferi çekME
$stmt = $pdo->prepare("SELECT r.*, f.name AS firm_name FROM routes r JOIN firms f ON f.id=r.firm_id WHERE r.id=:id");
$stmt->execute([':id'=>$routeId]);
$route = $stmt->fetch();
if (!$route) {
  http_response_code(404);
  echo "Sefer bulunamadı.";
  exit;
}

$seatCount = (int)$route['seat_count'];
if ($seatNo < 1 || $seatNo > $seatCount) {
  http_response_code(400);
  echo "Koltuk aralık dışında.";
  exit;
}

// Fiyat + kupon
$priceCents = (int)$route['price_cents']; 
$discountPct = 0;

if ($coupon !== '') {
  $c = $pdo->prepare("SELECT * FROM coupons WHERE code=:c AND (firm_id IS NULL OR firm_id=:fid)");
  $c->execute([':c'=>$coupon, ':fid'=>(int)$route['firm_id']]);
  $cp = $c->fetch();
  if (!$cp) {
    echo "Kupon geçersiz."; exit;
  }
  // Son kullanma/limit kontrolü 
  if (!empty($cp['expires_at']) && strtotime((string)$cp['expires_at']) < time()) {
    echo "Kupon süresi dolmuş."; exit;
  }
  $discountPct = (int)$cp['percent'];
}
$payCents = (int)round($priceCents * (100 - $discountPct) / 100);

// Bakiye kontrolü
$me = $pdo->prepare("SELECT credit_cents FROM users WHERE id=:id");
$me->execute([':id'=>$userId]);
$meRow = $me->fetch();
if (!$meRow) { echo "Kullanıcı bulunamadı."; exit; }

$credit = (int)$meRow['credit_cents'];
if ($credit < $payCents) { echo "Bakiye yetersiz. Gerekli: $payCents, mevcut: $credit"; exit; }

try {
  $pdo->beginTransaction();

  // Koltuk dolu mu Kontrol
  $chk = $pdo->prepare("SELECT 1 FROM tickets WHERE route_id=:r AND seat_no=:s AND status='ACTIVE' LIMIT 1");
  $chk->execute([':r'=>$routeId, ':s'=>$seatNo]);
  if ($chk->fetchColumn()) {
    $pdo->rollBack();
    echo "Bu koltuk az önce satıldı.";
    exit;
  }

  // Ticket ekleme
  $ins = $pdo->prepare("INSERT INTO tickets (user_id,route_id,seat_no,price_paid_cents,status,coupon_code,created_at)
                        VALUES (:u,:r,:s,:p,'ACTIVE',:c,datetime('now'))");
  $ins->execute([
    ':u'=>$userId, ':r'=>$routeId, ':s'=>$seatNo, ':p'=>$payCents, ':c'=>($coupon !== '' ? $coupon : null)
  ]);
  $ticketId = (int)$pdo->lastInsertId();

  // Cüzdan düşme + işlem kaydı
  $w1 = $pdo->prepare("UPDATE users SET credit_cents = credit_cents - :amt WHERE id=:id");
  $w1->execute([':amt'=>$payCents, ':id'=>$userId]);

  $w2 = $pdo->prepare("INSERT INTO wallet_tx (user_id,amount_cents,reason,created_at)
                       VALUES (:u,:a,:r,datetime('now'))");
  $w2->execute([':u'=>$userId, ':a'=>-$payCents, ':r'=>"Ticket#$ticketId"]);

  $pdo->commit();

  echo "✅ Satın alma başarılı. Ticket ID: {$ticketId}. Ödenen: " . number_format($payCents/100,2,',','.') . " TL";
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "İşlem hatası: " . htmlspecialchars($e->getMessage());
}
