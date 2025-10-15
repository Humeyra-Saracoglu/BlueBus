<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
  $stmt = $pdo->prepare("SELECT r.*, f.name AS firm_name FROM routes r JOIN firms f ON f.id=r.firm_id WHERE r.id=:id");
  $stmt->execute([':id' => $id]);
  $route = $stmt->fetch();
  if (!$route) { echo "Sefer bulunamadı."; exit; }

  $sold = $pdo->prepare("SELECT seat_no FROM tickets WHERE route_id=:rid AND status='ACTIVE'");
  $sold->execute([':rid' => $id]);
  $soldSeats = array_map(fn($x) => (int)$x['seat_no'], $sold->fetchAll());

  $dtDetail = date('d.m.Y H:i', strtotime($route['depart_at']));

  echo "<h1>Sefer Detayı</h1>";
  echo "<p><strong>".htmlspecialchars($route['firm_name'])."</strong> | "
     . htmlspecialchars($route['origin'])." → ".htmlspecialchars($route['destination'])
     . " | ".htmlspecialchars($dtDetail)." | "
     . number_format(((int)$route['price_cents'])/100, 2, ',', '.')." TL</p>";

  $seatCount = (int)$route['seat_count'];
  echo "<h3>Koltuk Seç</h3>";
  echo "<div style='display:grid;grid-template-columns:repeat(4,60px);gap:8px;max-width:260px;'>";
  for ($s = 1; $s <= $seatCount; $s++) {
    $isSold = in_array($s, $soldSeats, true);
    $style = "padding:10px;border:1px solid #ccc;text-align:center;border-radius:6px;";
    if ($isSold) $style .= "background:#eee;color:#888;";
    echo "<div style='$style'>".($isSold ? "Dolu<br>$s" : "Boş<br>$s")."</div>";
  }
  echo "</div>";

  $u = auth_user();
  if (!$u) {
    echo "<p><a href='/login'>Giriş</a> yapmadan satın alamazsın.</p>";
    exit;
  }

  echo "<h3>Satın Al</h3>";
  echo "<form method='POST' action='/buy' style='display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;'>
          <input type='hidden' name='route_id' value='{$id}'>
          <label>Koltuk No: <input type='number' min='1' max='{$seatCount}' name='seat_no' required></label>
          <label>Kupon (opsiyonel): <input name='coupon_code' placeholder='INDIRIM10'></label>
          <button type='submit'>Öde ve Al</button>
        </form>
        <p style='color:#666'>Dolu koltuk numarası girersen işlem iptal olur.</p>";
  exit;
}

$origin      = trim($_GET['origin'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$dateInput   = trim($_GET['date'] ?? '');
$date = '';

if ($dateInput !== '') {
  $parts = explode('.', $dateInput);
  if (count($parts) === 3) {
    [$day, $month, $year] = $parts;
    if (checkdate((int)$month, (int)$day, (int)$year)) {
      $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
  }
}

$sql = "SELECT r.*, f.name AS firm_name
        FROM routes r JOIN firms f ON f.id = r.firm_id
        WHERE 1=1";
$params = [];

if ($origin !== '')      { $sql .= " AND r.origin LIKE :o";      $params[':o']  = "%$origin%"; }
if ($destination !== '') { $sql .= " AND r.destination LIKE :d"; $params[':d']  = "%$destination%"; }
if ($date !== '')        { $sql .= " AND date(r.depart_at)=:dt"; $params[':dt'] = $date; }

$sql .= " ORDER BY r.depart_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

echo "<h1>Sonuçlar</h1>";
if (!$rows) { echo "<p>Kayıt yok.</p>"; exit; }

echo "<ul>";
foreach ($rows as $r) {
  $rid   = (int)$r['id'];
  $price = number_format(((int)$r['price_cents'])/100, 2, ',', '.');
  $dt    = date('d.m.Y H:i', strtotime($r['depart_at']));
  echo "<li>#{$rid} ".htmlspecialchars($r['firm_name'])." — "
     . htmlspecialchars($r['origin'])." → ".htmlspecialchars($r['destination'])
     . " | ".htmlspecialchars($dt)." | {$price} TL "
     . "| <a href='/routes?id={$rid}'>Detay</a></li>";
}
echo "</ul>";
