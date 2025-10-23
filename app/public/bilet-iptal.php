<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bilet iptal etmek için giriş yapmalısınız.';
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /biletlerim.php');
    exit;
}

$ticketId = $_POST['ticket_id'] ?? null;

if (!$ticketId) {
    $_SESSION['error'] = 'Geçersiz bilet!';
    header('Location: /biletlerim.php');
    exit;
}

try {
    $db = getDbConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT t.*, r.depart_at 
        FROM tickets t
        JOIN routes r ON t.route_id = r.id
        WHERE t.id = :ticket_id AND t.user_id = :user_id AND t.status = 'ACTIVE'
    ");
    $stmt->execute([
        ':ticket_id' => $ticketId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Bilet bulunamadı veya zaten iptal edilmiş!');
    }
    
    $departTime = new DateTime($ticket['depart_at']);
    $now = new DateTime();
    $diff = $now->diff($departTime);
    
    $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    
    if ($departTime < $now) {
        throw new Exception('Bu sefer için iptal süresi geçmiştir!');
    }
    
    if ($totalMinutes < 60) {
        throw new Exception('Kalkışa 1 saatten az kaldı. Bilet iptal edilemez! (Kalan: ' . $totalMinutes . ' dakika)');
    }
    
    $stmt = $db->prepare("
        UPDATE tickets 
        SET status = 'cancelled' 
        WHERE id = :ticket_id
    ");
    $stmt->execute([':ticket_id' => $ticketId]);
    
    $stmt = $db->prepare("
        UPDATE users 
        SET credit = credit + :price 
        WHERE id = :user_id
    ");
    $stmt->execute([
        ':price' => $ticket['price_cents'],
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $stmt = $db->prepare("
        INSERT INTO wallet_tx (user_id, amount_cents, reason) 
        VALUES (:user_id, :amount, :reason)
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':amount' => $ticket['price_cents'],
        ':reason' => 'Bilet iptali - Koltuk ' . $ticket['seat_number'] . ' (Bilet #' . $ticketId . ')'
    ]);

    $stmt = $db->prepare("
        UPDATE routes 
        SET available_seats = available_seats + 1 
        WHERE id = :route_id
    ");
    $stmt->execute([':route_id' => $ticket['route_id']]);
    
    $db->commit();
    
    $_SESSION['user_credit'] += $ticket['price_cents'];
    
    $_SESSION['success'] = 'Bilet başarıyla iptal edildi. Ücret (' . number_format($ticket['price_cents'] / 100, 2) . ' ₺) hesabınıza iade edildi.';
    header('Location: /biletlerim.php');
    exit;
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    header('Location: /biletlerim.php');
    exit;
}