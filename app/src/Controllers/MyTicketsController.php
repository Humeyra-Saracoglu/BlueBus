<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

echo "<h1>Biletlerim</h1>";

$stmt = $pdo->prepare("
  SELECT t.id AS ticket_id,
         t.status,
         t.seat_no,
         t.created_at AS bought_at,
         r.origin, r.destination, r.depart_at, r.price_cents,
         f.name AS firm_name
  FROM tickets t
  JOIN routes r ON r.id = t.route_id
  JOIN firms  f ON f.id = r.firm_id
  WHERE t.user_id = :uid
  ORDER BY t.id DESC
");
$stmt->execute([':uid' => $u['id']]);
$rows = $stmt->fetchAll();

if (!$rows) {
  echo "<p>Henüz biletin yok.</p>";
  exit;
}

echo "<table border='1' cellpadding='6' cellspacing='0'>
        <tr>
          <th>#</th>
          <th>Firma</th>
          <th>Rota</th>
          <th>Kalkış</th>
          <th>Koltuk</th>
          <th>Fiyat</th>
          <th>Durum</th>
          <th>Aksiyon</th>
        </tr>";
foreach ($rows as $r) {
  $price = number_format(((int)$r['price_cents'])/100, 2, ',', '.');
  $tid   = (int)$r['ticket_id'];
  $canCancel = ($r['status'] === 'ACTIVE');

  echo "<tr>
          <td>{$tid}</td>
          <td>".htmlspecialchars($r['firm_name'])."</td>
          <td>".htmlspecialchars($r['origin'])." → ".htmlspecialchars($r['destination'])."</td>
          <td>".htmlspecialchars($r['depart_at'])."</td>
          <td>".(int)$r['seat_no']."</td>
          <td>{$price} TL</td>
          <td>".htmlspecialchars($r['status'])."</td>
          <td>";
  if ($canCancel) {
    echo "<form method='POST' action='/tickets/cancel' onsubmit='return confirm(\"Bu bileti iptal edip ücret iadesi almak istiyor musun?\");' style='display:inline'>
            <input type='hidden' name='ticket_id' value='{$tid}'>
            <button type='submit'>İptal et</button>
          </form>";
  } else {
    echo "-";
  }
  echo    "</td>
        </tr>";
}
echo "</table>";
