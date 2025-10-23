<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bilet satın almak için giriş yapmalısınız.';
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$routeId = $_POST['route_id'] ?? null;
$seatNumber = $_POST['seat_number'] ?? null;
$priceCents = $_POST['price'] ?? null;

if (!$routeId || !$seatNumber || !$priceCents) {
    $_SESSION['error'] = 'Eksik bilgi!';
    header('Location: /index.php');
    exit;
}

try {
    $db = getDbConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT credit_cents FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user['credit_cents'] < $priceCents) {
        throw new Exception('Yetersiz bakiye! Lütfen kredi yükleyin. Mevcut bakiye: ' . number_format($user['credit_cents'] / 100, 2) . ' ₺');
    }
    
    $stmt = $db->prepare("
        SELECT id FROM tickets 
        WHERE route_id = :route_id AND seat_no = :seat_no AND status = 'ACTIVE'
    ");
    $stmt->execute([
        ':route_id' => $routeId,
        ':seat_no' => $seatNumber
    ]);
    
    if ($stmt->fetch()) {
        throw new Exception('Bu koltuk zaten dolu! Lütfen başka bir koltuk seçin.');
    }
    
    $stmt = $db->prepare("
        SELECT depart_at, seat_count,
        (seat_count - COALESCE((
            SELECT COUNT(*) FROM tickets t 
            WHERE t.route_id = routes.id AND t.status = 'ACTIVE'
        ), 0)) as available_seats
        FROM routes 
        WHERE id = :route_id
    ");
    $stmt->execute([':route_id' => $routeId]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$route || $route['available_seats'] <= 0) {
        throw new Exception('Bu seferde boş koltuk kalmamıştır!');
    }
    
    $departTime = new DateTime($route['depart_at']);
    $now = new DateTime();
    if ($departTime < $now) {
        throw new Exception('Bu sefer için bilet alımı süresi dolmuştur!');
    }
    
    $stmt = $db->prepare("
        INSERT INTO tickets (user_id, route_id, seat_no, price_paid_cents, status) 
        VALUES (:user_id, :route_id, :seat_no, :price_paid_cents, 'active')
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':route_id' => $routeId,
        ':seat_no' => $seatNumber,
        ':price_paid_cents' => $priceCents
    ]);
    
    $ticketId = $db->lastInsertId();
    
    $stmt = $db->prepare("
        UPDATE users 
        SET credit_cents = credit_cents - :price 
        WHERE id = :user_id
    ");
    $stmt->execute([
        ':price' => $priceCents,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $stmt = $db->prepare("
        INSERT INTO wallet_tx (user_id, amount_cents, reason) 
        VALUES (:user_id, :amount, :reason)
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':amount' => -$priceCents,
        ':reason' => 'Bilet satın alma - Koltuk ' . $seatNumber . ' (Bilet #' . $ticketId . ')'
    ]);
    
    $db->commit();
    
    $_SESSION['user_credit'] = $user['credit_cents'] - $priceCents;
    
    $_SESSION['success'] = 'Bilet satın alma işlemi başarılı! Koltuk: ' . $seatNumber . ' | Tutar: ' . number_format($priceCents / 100, 2) . ' ₺';
    header('Location: /biletlerim.php');
    exit;
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    header('Location: /sefer-detay.php?id=' . $routeId);
    exit;
}