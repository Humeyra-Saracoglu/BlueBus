<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';
require_once __DIR__ . '/../Utils/Csrf.php';

// Logger - opsiyonel (dosya yoksa atla)
$logger_path = __DIR__ . '/../Utils/Logger.php';
if (file_exists($logger_path)) {
    require_once $logger_path;
}

$u = auth_user();
if (!$u) {
  $_SESSION['error'] = 'Giriş yapmalısınız.';
  header('Location: /login');
  exit;
}

// CSRF Token kontrolü
require_csrf();

// CSRF Token kontrolü
if (!verify_csrf_token()) {
  $_SESSION['error'] = 'CSRF token hatalı.';
  header('Location: /');
  exit;
}

$userId = (int)$u['id'];

// Input validation - güvenli integer dönüşümü
$routeId = filter_var($_POST['route_id'] ?? 0, FILTER_VALIDATE_INT);
$seatNo = filter_var($_POST['seat_no'] ?? $_POST['seat_number'] ?? 0, FILTER_VALIDATE_INT);
$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

// Validation kontrolleri
if ($routeId === false || $routeId <= 0) {
    $_SESSION['error'] = 'Geçersiz sefer ID.';
    header('Location: /');
    exit;
}

if ($seatNo === false || $seatNo <= 0) {
    $_SESSION['error'] = 'Geçersiz koltuk numarası.';
    header('Location: /routes?id=' . $routeId);
    exit;
}

// Kupon kodu güvenlik kontrolü (sadece alfanumerik ve tire)
if ($couponCode !== '' && !preg_match('/^[A-Z0-9\-]{3,20}$/', $couponCode)) {
    $_SESSION['error'] = 'Geçersiz kupon kodu formatı.';
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
    $couponId = null;
    
    // Kupon kodu kontrolü - Database'den çek
    if ($couponCode !== '') {
        try {
            // Debug: Sorguyu logla
            error_log("Searching coupon: {$couponCode} for firm_id: " . $route['firm_id']);
            
            $stmt = $db->prepare("
                SELECT * FROM coupons 
                WHERE code = :code 
                AND active = 1 
                AND (expires_at IS NULL OR expires_at > datetime('now'))
                AND (firm_id IS NULL OR firm_id = :firm_id)
                AND (usage_limit IS NULL OR used_count < usage_limit)
            ");
            $stmt->execute([
                ':code' => $couponCode,
                ':firm_id' => $route['firm_id']
            ]);
            $coupon = $stmt->fetch();
            
            if ($coupon) {
                // Kupon bulundu!
                $couponId = (int)$coupon['id'];
                $discountPercent = (int)$coupon['percent'];
                $discountCents = (int)round($priceCents * $discountPercent / 100);
                
                error_log("Coupon applied: {$couponCode}, Discount: {$discountCents} cents");
            } else {
                // Kupon bulunamadı - debug için tüm kuponları kontrol et
                error_log("Coupon NOT found. Checking all coupons with this code...");
                
                $debugStmt = $db->prepare("SELECT * FROM coupons WHERE code = :code");
                $debugStmt->execute([':code' => $couponCode]);
                $allCoupons = $debugStmt->fetchAll();
                error_log("All coupons with code {$couponCode}: " . json_encode($allCoupons));
                
                // UYARI VER AMA DEVAM ET (exit yapma!)
                $_SESSION['warning'] = 'Kupon kodu geçerli değil veya bu firma için kullanılamıyor. Kuponsuz devam ediliyor.';
                
                // Kupon bilgilerini sıfırla
                $couponCode = '';
                $couponId = null;
                $discountCents = 0;
            }
            
        } catch (Exception $e) {
            error_log("Coupon query error: " . $e->getMessage());
            
            // Hata durumunda kuponsuz devam et
            $couponCode = '';
            $couponId = null;
            $discountCents = 0;
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
    
    // Kupon kullanıldıysa, kullanım sayısını artır ve kaydet
    if ($couponId !== null && $coupon) {
        // Kupon kullanım sayısını artır
        $cupd = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
        $cupd->execute([':id' => $couponId]);
        
        // coupon_usages tablosuna kaydet
        $cins = $db->prepare("
            INSERT INTO coupon_usages (coupon_id, user_id, ticket_id, discount_amount_cents, used_at)
            VALUES (:cid, :uid, :tid, :amt, datetime('now'))
        ");
        $cins->execute([
            ':cid' => $couponId,
            ':uid' => $userId,
            ':tid' => $ticketId,
            ':amt' => $discountCents
        ]);
    }
    
    $db->commit();
    
    // Bilet satın alma işlemini log'la (eğer logger varsa)
    if (function_exists('log_ticket_purchase')) {
        log_ticket_purchase($ticketId, $routeId, $payCents);
    }
    
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