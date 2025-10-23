<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'PDF indirmek için giriş yapmalısınız.';
    header('Location: /login.php');
    exit;
}

$ticketId = $_GET['id'] ?? null;

if (!$ticketId) {
    $_SESSION['error'] = 'Geçersiz bilet!';
    header('Location: /biletlerim.php');
    exit;
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            t.*,
            r.origin,
            r.destination,
            r.depart_at,
            r.duration,
            f.name as firm_name,
            u.ad,
            u.soyad,
            u.email
        FROM tickets t
        JOIN routes r ON t.route_id = r.id
        JOIN firms f ON r.firm_id = f.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = :ticket_id AND t.user_id = :user_id
    ");
    $stmt->execute([
        ':ticket_id' => $ticketId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        $_SESSION['error'] = 'Bilet bulunamadı!';
        header('Location: /biletlerim.php');
        exit;
    }
    
    $departTime = new DateTime($ticket['depart_at']);
    $arrivalTime = clone $departTime;
    $arrivalTime->modify('+' . $ticket['duration'] . ' minutes');
    
    if ($ticket['status'] == 'cancelled') {
        $statusText = 'İPTAL EDİLDİ';
    } elseif ($ticket['status'] == 'completed') {
        $statusText = 'TAMAMLANDI';
    } else {
        $statusText = 'AKTİF';
    }
    
} catch (Exception $e) {
    error_log("PDF error: " . $e->getMessage());
    $_SESSION['error'] = 'Bilet bilgileri alınamadı!';
    header('Location: /biletlerim.php');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilet #<?= str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="/css/ticket-pdf.css">
</head>

<body>
    <button class="print-btn" onclick="window.print()">🖨️ Yazdır</button>
    
    <div class="ticket-container">
        <div class="ticket-header">
            <div class="logo">🚍 BiletGo</div>
            <div class="ticket-number">Bilet No: #<?= str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) ?></div>
        </div>
        
        <div class="ticket-body">
            <div class="route-section">
                <div class="route-point">
                    <div class="city-name"><?= strtoupper(htmlspecialchars($ticket['origin'])) ?></div>
                    <div class="time"><?= $departTime->format('H:i') ?></div>
                    <div class="date"><?= $departTime->format('d F Y') ?></div>
                </div>
                
                <div class="arrow">→</div>
                
                <div class="route-point">
                    <div class="city-name"><?= strtoupper(htmlspecialchars($ticket['destination'])) ?></div>
                    <div class="time"><?= $arrivalTime->format('H:i') ?></div>
                    <div class="date"><?= $arrivalTime->format('d F Y') ?></div>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Yolcu Adı Soyadı</div>
                    <div class="info-value"><?= htmlspecialchars($ticket['ad'] . ' ' . $ticket['soyad']) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Koltuk Numarası</div>
                    <div class="info-value"><?= $ticket['seat_number'] ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Firma</div>
                    <div class="info-value"><?= htmlspecialchars($ticket['firm_name']) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Bilet Ücreti</div>
                    <div class="info-value"><?= number_format($ticket['price_cents'] / 100, 2) ?> ₺</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">E-posta</div>
                    <div class="info-value"><?= htmlspecialchars($ticket['email']) ?></div>
                </div>
                    <div class="info-item">
                <div class="info-label">Satın Alma Tarihi</div>
                <div class="info-value"><?= date('d F Y H:i', strtotime($ticket['purchase_date'])) ?></div>
            </div>
        </div>
        
        <div style="text-align: center;">
            <span class="status-badge status-<?= $ticket['status'] ?>">
                <?= $statusText ?>
            </span>
        </div>
        
        <div class="footer">
            <p><strong>BiletGo - Otobüs Bileti Platformu</strong></p>
            <p>Bu bilet kişiye özeldir ve devredilmez.</p>
            <p>Kalkış saatinden 1 saat öncesine kadar iptal edilebilir.</p>
            <p>Yolculuk sırasında yanınızda bulundurmanız gerekmektedir.</p>
        </div>
    </div>
</div>
</body>
</html>