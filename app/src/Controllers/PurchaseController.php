<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) {
  $_SESSION['error'] = 'Giriş yapmalısınız.';
  header('Location: /login');
  exit;
}

$userId = (int)$u['id'];

$routeId = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
// seat_no veya seat_number'ı destekle
$seatNo  = isset($_POST['seat_no']) ? (int)$_POST['seat_no'] : (isset($_POST['seat_number']) ? (int)$_POST['seat_number'] : 0);
$couponCode  = strtoupper(trim($_POST['coupon_code'] ?? ''));

if ($routeId <= 0 || $seatNo <= 0) {
  $_SESSION['error'] = 'Eksik bilgi. Lütfen tekrar deneyin.';
  header('Location: /routes?id=' . $routeId);
  exit;
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT r.*, f.name AS firm_name FROM routes r JOIN firms f ON f.id=r.firm_id WHERE r.id=:id");
    $stmt->execute([':id'=>$routeId]);
    $route = $stmt->fetch();
    if (!$route) {
      $_SESSION['error'] = 'Sefer bulunamadı.';
      header('Location: /');
      exit;
    }
    
    $seatCount = (int)$route['seat_count'];
    if ($seatNo < 1 || $seatNo > $seatCount) {
      $_SESSION['error'] = 'Geçersiz koltuk numarası.';
      header('Location: /routes?id=' . $routeId);
      exit;
    }
    
    $priceCents = (int)$route['price_cents'];
    $discountCents = 0;
    $coupon = null;
    
    $availableCoupons = [
        'INDIRIM10' => ['type' => 'percent', 'value' => 10],
        'YENI20' => ['type' => 'percent', 'value' => 20],
        'WELCOME50' => ['type' => 'fixed', 'value' => 5000]
    ];
    
    if ($couponCode !== '' && isset($availableCoupons[$couponCode])) {
        $coupon = $availableCoupons[$couponCode];
        if ($coupon['type'] === 'percent') {
            $discountCents = (int)round($priceCents * $coupon['value'] / 100);
        } else {
            $discountCents = $coupon['value'];
        }
    }
    
    $payCents = max(0, $priceCents - $discountCents);
    
    $me = $db->prepare("SELECT credit_cents FROM users WHERE id=:id");
    $me->execute([':id'=>$userId]);
    $meRow = $me->fetch();
    if (!$meRow) { 
        $_SESSION['error'] = 'Kullanıcı bulunamadı.';
        header('Location: /');
        exit;
    }
    
    $credit = (int)$meRow['credit_cents'];
    if ($credit < $payCents) { 
        $_SESSION['error'] = 'Yetersiz bakiye! Gerekli: ' . number_format($payCents/100,2) . '₺, Mevcut: ' . number_format($credit/100,2) . '₺';
        header('Location: /routes?id=' . $routeId);
        exit;
    }
    
    $db->beginTransaction();
    
    $chk = $db->prepare("SELECT 1 FROM tickets WHERE route_id=:r AND seat_no=:s AND status='ACTIVE' LIMIT 1");
    $chk->execute([':r'=>$routeId, ':s'=>$seatNo]);
    if ($chk->fetchColumn()) {
        $db->rollBack();
        $_SESSION['error'] = 'Bu koltuk az önce satıldı. Lütfen başka bir koltuk seçin.';
        header('Location: /routes?id=' . $routeId);
        exit;
    }
    
    // Bakiye düş
    $uupd = $db->prepare("UPDATE users SET credit_cents = credit_cents - :amt WHERE id = :id AND credit_cents >= :amt");
    $uupd->execute([':amt' => $payCents, ':id' => $userId]);
    if ($uupd->rowCount() === 0) {
        $db->rollBack();
        $_SESSION['error'] = 'Bakiye yetersiz.';
        header('Location: /routes?id=' . $routeId);
        exit;
    }
    
    // Bilet oluştur
    $ins = $db->prepare("INSERT INTO tickets (user_id,route_id,seat_no,price_paid_cents,status,coupon_code,created_at)
                          VALUES (:u,:r,:s,:p,'ACTIVE',:c,datetime('now'))");
    $ins->execute([
      ':u'=>$userId, ':r'=>$routeId, ':s'=>$seatNo, ':p'=>$payCents, ':c'=>($couponCode !== '' ? $couponCode : null)
    ]);
    $ticketId = (int)$db->lastInsertId();
    
    // Wallet transaction
    $w2 = $db->prepare("INSERT INTO wallet_tx (user_id,amount_cents,reason,created_at)
                         VALUES (:u,:a,:r,datetime('now'))");
    $reason = "Bilet satın alma - Koltuk {$seatNo} (Bilet #{$ticketId})";
    if ($couponCode !== '') {
        $reason .= " [Kupon: {$couponCode}]";
    }
    $w2->execute([':u'=>$userId, ':a'=>-$payCents, ':r'=>$reason]);
    
    $db->commit();
    
    // Session güncelle
    $_SESSION['user_credit'] = $credit - $payCents;
    
    $message = '✅ Bilet satın alma başarılı!';
    if ($discountCents > 0) {
        $message .= ' İndirim uygulandı: ' . number_format($discountCents/100, 2) . '₺';
    }
    $message .= ' | Ödenen: ' . number_format($payCents/100, 2) . '₺';
    
    $_SESSION['success'] = $message;
    header('Location: /tickets');
    exit;
    
} catch (Throwable $e) {
  if (isset($db) && $db->inTransaction()) $db->rollBack();
  error_log("Purchase error: " . $e->getMessage());
  $_SESSION['error'] = 'Satın alma işlemi başarısız. Lütfen tekrar deneyin.';
  header('Location: /routes?id=' . $routeId);
  exit;
}
