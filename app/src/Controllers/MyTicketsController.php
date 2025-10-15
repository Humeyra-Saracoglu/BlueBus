<?php
declare(strict_types=1);
require_once __DIR__ . '/../Utils/Auth.php';
require_once __DIR__ . '/../../config/db.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

$stmt = $pdo->prepare("SELECT t.*, r.origin, r.destination, r.depart_at
                       FROM tickets t JOIN routes r ON r.id=t.route_id
                       WHERE t.user_id=:uid ORDER BY t.created_at DESC");
$stmt->execute([':uid'=>$u['id']]);
$rows = $stmt->fetchAll();

echo "<h1>Biletlerim</h1>";
if (!$rows) { echo "<p>Henüz biletin yok.</p>"; exit; }

echo "<ul>";
foreach ($rows as $t) {
  $paid = number_format(((int)$t['price_paid_cents'])/100,2,',','.');
  $dt   = date('d.m.Y H:i', strtotime($t['depart_at']));
  echo "<li>#{$t['id']} ".htmlspecialchars($t['origin'])." → ".htmlspecialchars($t['destination'])
     ." ({$dt}) | Koltuk {$t['seat_no']} | {$paid} TL | {$t['status']}</li>";
}
echo "</ul>";
