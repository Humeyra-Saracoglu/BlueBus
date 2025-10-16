<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

$ok = isset($_GET['ok']);

$stmt = $pdo->prepare("SELECT credit_cents FROM users WHERE id=:id");
$stmt->execute([':id' => $u['id']]);
$credit = (int)($stmt->fetchColumn() ?: 0);

$tx = $pdo->prepare("SELECT amount_cents, reason, created_at
                     FROM wallet_tx
                     WHERE user_id=:uid
                     ORDER BY id DESC
                     LIMIT 20");
$tx->execute([':uid' => $u['id']]);
$rows = $tx->fetchAll();

echo "<h1>Cüzdanım</h1>";

if ($ok) {
  echo "<p style='color:green;margin:8px 0;'>Yükleme başarılı ✅</p>";
}

echo "<p><strong>Bakiye:</strong> " . number_format($credit/100, 2, ',', '.') . " TL</p>";

echo "<h2>Cüzdana Para Yükle</h2>";
echo "<form method='POST' action='/wallet/deposit' style='margin:12px 0; display:flex; gap:8px; align-items:end;'>
        <label>Tutar (TL):
          <input type='number' name='amount_tl' min='1' step='1'
                 oninput=\"document.getElementById('amount_cents').value = Math.round((+this.value || 0)*100)\">
        </label>
        <input type='hidden' id='amount_cents' name='amount_cents' value='0'>
        <button type='submit'>Cüzdana Yükle</button>
      </form>";

echo "<h3>Son İşlemler</h3>";
if (!$rows) {
  echo "<p>Henüz işlem yok.</p>";
} else {
  echo "<table border='1' cellpadding='6' cellspacing='0'>
          <tr><th>Tutar</th><th>Açıklama</th><th>Tarih</th></tr>";
  foreach ($rows as $r) {
    $amt = number_format(((int)$r['amount_cents'])/100, 2, ',', '.');
    $sign = ((int)$r['amount_cents'] >= 0) ? '+' : '';
    echo "<tr>
            <td>{$sign}{$amt} TL</td>
            <td>" . htmlspecialchars($r['reason']) . "</td>
            <td>" . htmlspecialchars($r['created_at']) . "</td>
          </tr>";
  }
  echo "</table>";
}
