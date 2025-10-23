<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { 
    $_SESSION['error'] = 'Lütfen giriş yapın.';
    header('Location: /login'); 
    exit; 
}

$ok = isset($_GET['ok']);

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT credit_cents FROM users WHERE id=:id");
    $stmt->execute([':id' => $u['id']]);
    $credit = (int)($stmt->fetchColumn() ?: 0);
    
    $tx = $db->prepare("SELECT amount_cents, reason, created_at
                         FROM wallet_tx
                         WHERE user_id=:uid
                         ORDER BY id DESC
                         LIMIT 20");
    $tx->execute([':uid' => $u['id']]);
    $rows = $tx->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Hata: ' . $e->getMessage();
    $credit = 0;
    $rows = [];
}

include __DIR__ . '/../Views/layouts/Header.php';
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 2rem;">
    <h1>💰 Hesabım</h1>
    
    <?php if ($ok): ?>
        <div class="alert alert-success">✅ Yükleme başarılı!</div>
    <?php endif; ?>
    
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
        <h2 style="margin: 0 0 0.5rem 0;">Mevcut Bakiye</h2>
        <p style="font-size: 2rem; font-weight: bold; color: #28a745; margin: 0;">
            <?= number_format($credit/100, 2, ',', '.') ?> ₺
        </p>
    </div>
    
    <div style="background: white; padding: 1.5rem; border: 1px solid #dee2e6; border-radius: 8px; margin: 1.5rem 0;">
        <h2>Cüzdana Para Yükle</h2>
        <form method="POST" action="/wallet/deposit" style="margin: 1rem 0; display: flex; gap: 1rem; align-items: end;">
            <div style="flex: 1;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Tutar (TL):
                </label>
                <input type="number" name="amount_tl" min="1" step="1" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px;"
                       oninput="document.getElementById('amount_cents').value = Math.round((+this.value || 0)*100)"
                       placeholder="Örn: 100">
                <input type="hidden" id="amount_cents" name="amount_cents" value="0">
            </div>
            <button type="submit" class="btn btn-primary">Yükle</button>
        </form>
    </div>
    
    <div style="margin: 2rem 0;">
        <h2>Son İşlemler</h2>
        <?php if (!$rows): ?>
            <p style="color: #6c757d; padding: 2rem; text-align: center; background: #f8f9fa; border-radius: 8px;">
                Henüz işlem yok.
            </p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                        <th style="padding: 0.75rem; text-align: left;">Tutar</th>
                        <th style="padding: 0.75rem; text-align: left;">Açıklama</th>
                        <th style="padding: 0.75rem; text-align: left;">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): 
                        $amt = number_format(((int)$r['amount_cents'])/100, 2, ',', '.');
                        $sign = ((int)$r['amount_cents'] >= 0) ? '+' : '';
                        $color = ((int)$r['amount_cents'] >= 0) ? '#28a745' : '#dc3545';
                    ?>
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 0.75rem; color: <?= $color ?>; font-weight: 600;">
                                <?= $sign ?><?= $amt ?> ₺
                            </td>
                            <td style="padding: 0.75rem;">
                                <?= htmlspecialchars($r['reason']) ?>
                            </td>
                            <td style="padding: 0.75rem; color: #6c757d; font-size: 0.9rem;">
                                <?= date('d.m.Y H:i', strtotime($r['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php

include __DIR__ . '/../Views/layouts/footer.php';
