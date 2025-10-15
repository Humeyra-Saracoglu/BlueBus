<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

$stmt = $pdo->prepare("SELECT credit_cents FROM users WHERE id=:id");
$stmt->execute([':id'=>$u['id']]);
$credit = (int)($stmt->fetchColumn() ?: 0);

$tx = $pdo->prepare("SELECT amount_cents, reason, created_at 
                     FROM wallet_tx WHERE user_id=:uid 
                     ORDER BY id DESC LIMIT 20");
$tx->execute([':uid'=>$u['id']]);
$rows = $tx->fetchAll();

echo "<h1>Cüzdanım</h1>";
echo "<p><strong>Bakiye:</strong> ".number_format($credit/100, 2, ',', '.')." TL</p>";

echo "<h3>Son İşlemler</h3>";
if (!$rows) {
  echo "<p>Henüz işlem yok.</p>";
  exit;
}

echo "<table border='1' cellpadding='6' cellspacing='0'>
        <tr><th>Tutar</th><th>Açıklama</th><th>Tarih</th></tr>";
foreach ($rows as $r) {
  $amt = number_format(((int)$r['amount_cents'])/100, 2, ',', '.');
  $sign = ((int)$r['amount_cents'] >= 0) ? '+' : '';
  echo "<tr>
          <td>{$sign}{$amt} TL</td>
          <td>".htmlspecialchars($r['reason'])."</td>
          <td>".htmlspecialchars($r['created_at'])."</td>
        </tr>";
}
echo "</table>";
