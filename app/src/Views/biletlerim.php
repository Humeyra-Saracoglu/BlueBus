<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
    header('Location: /login.php');
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
            r.duration_minutes,
            f.name as firm_name
        FROM tickets t
        JOIN routes r ON t.route_id = r.id
        JOIN firms f ON r.firm_id = f.id
        WHERE t.user_id = :user_id
        ORDER BY r.depart_at DESC
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $tickets = [];
    error_log("Biletlerim query error: " . $e->getMessage());
}
?>

<div class="profile-container">
    <h1 class="page-title">Biletlerim</h1>

    <div class="journey-list">
        <?php if (empty($tickets)): ?>
            <div class="profile-card">
                <p style="text-align: center; color: #6b7280; padding: 2rem;">
                    Henüz biletiniz bulunmamaktadır.
                </p>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="/index.php" class="btn btn-primary">Sefer Ara</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $ticket): 
                $departTime = new DateTime($ticket['depart_at']);
                $arrivalTime = clone $departTime;
                $durationMinutes = $ticket['duration_minutes'] ?? 360;
                $arrivalTime->modify('+' . $durationMinutes . ' minutes');
                
                $now = new DateTime();
                
                if ($ticket['status'] == 'CANCELLED') {
                    $statusClass = 'cancelled';
                    $statusText = 'İptal Edildi';
                } elseif ($departTime < $now) {
                    $statusClass = 'completed';
                    $statusText = 'Tamamlandı';
                } else {
                    $statusClass = 'active';
                    $statusText = 'Aktif';
                }
                
                $timeDiff = $now->diff($departTime);
                $hoursUntilDeparture = ($timeDiff->days * 24) + $timeDiff->h;
                $canCancel = ($ticket['status'] == 'ACTIVE' && $hoursUntilDeparture >= 1 && $departTime > $now);
            ?>
                <div class="profile-card">
                    <div class="ticket-header">
                        <div class="ticket-status <?= $statusClass ?>"><?= $statusText ?></div>
                        <div class="ticket-number">#BLT-<?= str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) ?></div>
                    </div>

                    <div class="ticket-route">
                        <div>
                            <h3><?= ucfirst(htmlspecialchars($ticket['origin'])) ?></h3>
                            <p class="ticket-time"><?= $departTime->format('H:i') ?></p>
                            <p class="ticket-date"><?= $departTime->format('d F Y') ?></p>
                        </div>
                        <div class="ticket-arrow">🚌</div>
                        <div style="text-align: right;">
                            <h3><?= ucfirst(htmlspecialchars($ticket['destination'])) ?></h3>
                            <p class="ticket-time"><?= $arrivalTime->format('H:i') ?></p>
                            <p class="ticket-date"><?= $arrivalTime->format('d F Y') ?></p>
                        </div>
                    </div>

                    <div class="ticket-info-grid">
                        <div class="info-box">
                            <span class="info-label">Firma</span>
                            <span class="info-value"><?= htmlspecialchars($ticket['firm_name']) ?></span>
                        </div>
                        <div class="info-box">
                            <span class="info-label">Koltuk No</span>
                            <span class="info-value"><?= $ticket['seat_no'] ?></span>
                        </div>
                        <div class="info-box">
                            <span class="info-label">Yolcu Adı</span>
                            <span class="info-value"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                        </div>
                        <div class="info-box">
                            <span class="info-label">Fiyat</span>
                            <span class="info-value"><?= number_format($ticket['price_paid_cents'] / 100, 2) ?> ₺</span>
                        </div>
                    </div>

                    <div class="ticket-actions">
                        <a href="/ticket-pdf?id=<?= $ticket['id'] ?>" class="btn btn-outline" target="_blank">
                            📄 PDF İndir
                        </a>
                        
                        <?php if ($canCancel): ?>
                            <form method="POST" action="/cancel-ticket" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?\n\nÜcret: <?= number_format($ticket['price_paid_cents'] / 100, 2) ?> ₺ hesabınıza iade edilecektir.')">
                                    ❌ Bileti İptal Et
                                </button>
                            </form>
                        <?php elseif ($ticket['status'] == 'ACTIVE' && $hoursUntilDeparture < 1): ?>
                            <button class="btn btn-danger" disabled style="opacity: 0.5; cursor: not-allowed;" 
                                    title="Kalkışa 1 saatten az kaldı">
                                ❌ İptal Edilemez (<?= $hoursUntilDeparture ?> saat kaldı)
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>