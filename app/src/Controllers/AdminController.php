<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

if (($u['role'] ?? '') !== 'ADMIN') {
  http_response_code(403);
  echo "Bu sayfaya erişim yetkin yok.";
  exit;
}

/**
 * TR formatındaki "GG.AA.YYYY SS:dd" tarih-saatini
 * "YYYY-MM-DD HH:MM:00" biçimine çevirir. Hatalıysa '' döner.
 */
function toIsoDatetime(string $input): string {
  $input = trim($input);
  if ($input === '') return '';

  $parts = explode(' ', $input, 2);
  if (count($parts) !== 2) return '';
  [$dmy, $hm] = $parts;

  $d = explode('.', $dmy);
  if (count($d) !== 3) return '';
  [$day, $mon, $year] = $d;

  $t = explode(':', $hm);
  if (count($t) < 2) return '';
  [$hour, $min] = $t;

  if (!checkdate((int)$mon, (int)$day, (int)$year)) return '';
  $hour = (int)$hour; $min = (int)$min;
  if ($hour < 0 || $hour > 23 || $min < 0 || $min > 59) return '';

  return sprintf('%04d-%02d-%02d %02d:%02d:00', (int)$year, (int)$mon, (int)$day, $hour, $min);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $firm_id     = (int)($_POST['firm_id'] ?? 0);
    $origin      = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $depart_at_i = trim($_POST['depart_at'] ?? '');
    $price_tl    = (float)($_POST['price_tl'] ?? 0);
    $seat_count  = (int)($_POST['seat_count'] ?? 0);

    $depart_at = toIsoDatetime($depart_at_i);
    $price_cents = (int) round($price_tl * 100);

    $errors = [];
    if ($firm_id <= 0)           $errors[] = "Firma seçiniz.";
    if ($origin === '')          $errors[] = "Kalkış (origin) boş olamaz.";
    if ($destination === '')     $errors[] = "Varış (destination) boş olamaz.";
    if ($depart_at === '')       $errors[] = "Geçerli bir tarih/saat giriniz (ör. 20.10.2025 09:30).";
    if ($price_cents <= 0)       $errors[] = "Fiyat TL > 0 olmalı.";
    if ($seat_count <= 0)        $errors[] = "Koltuk sayısı > 0 olmalı.";

    if (!$errors) {
      $ins = $pdo->prepare("
        INSERT INTO routes (firm_id, origin, destination, depart_at, price_cents, seat_count)
        VALUES (:fid, :o, :d, :at, :pc, :sc)
      ");
      $ins->execute([
        ':fid' => $firm_id,
        ':o'   => $origin,
        ':d'   => $destination,
        ':at'  => $depart_at,
        ':pc'  => $price_cents,
        ':sc'  => $seat_count,
      ]);
      header('Location: /admin?ok=1');
      exit;
    }
  }

  if ($action === 'delete') {
    $route_id = (int)($_POST['route_id'] ?? 0);
    if ($route_id > 0) {
      $del = $pdo->prepare("DELETE FROM routes WHERE id=:id");
      $del->execute([':id' => $route_id]);
    }
    header('Location: /admin?deleted=1');
    exit;
  }
}

$firms = $pdo->query("SELECT id, name FROM firms ORDER BY name ASC")->fetchAll();

$routes = $pdo->query("
  SELECT r.id, r.firm_id, f.name AS firm_name, r.origin, r.destination, r.depart_at, r.price_cents, r.seat_count
  FROM routes r
  JOIN firms f ON f.id = r.firm_id
  ORDER BY r.depart_at DESC
")->fetchAll();

// Basit sayfa
echo "<h1>Admin Paneli</h1>";

if (!empty($errors)) {
  echo "<div style='color:#b00;'><ul>";
  foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>";
  echo "</ul></div>";
} elseif (isset($_GET['ok'])) {
  echo "<div style='color:#060;'>Sefer eklendi.</div>";
} elseif (isset($_GET['deleted'])) {
  echo "<div style='color:#060;'>Sefer silindi (varsa).</div>";
}

echo "<h2>Yeni Sefer Ekle</h2>";
echo "<form method='POST' action='/admin' style='display:grid;grid-template-columns:repeat(2, minmax(240px, 1fr));gap:10px;max-width:720px;'>
        <input type='hidden' name='action' value='create'>

        <label>Firma
          <select name='firm_id' required>";
foreach ($firms as $f) {
  echo "<option value='".(int)$f['id']."'>".htmlspecialchars($f['name'])."</option>";
}
echo "  </select>
        </label>

        <label>Kalkış (Origin)
          <input name='origin' required placeholder='Bursa'>
        </label>

        <label>Varış (Destination)
          <input name='destination' required placeholder='Ankara'>
        </label>

        <label>Kalkış Tarihi/Saati
          <input name='depart_at' required placeholder='GG.AA.YYYY SS:dd (ör. 20.10.2025 09:30)'>
        </label>

        <label>Fiyat (TL)
          <input type='number' step='0.01' min='0' name='price_tl' required placeholder='350.00'>
        </label>

        <label>Koltuk Sayısı
          <input type='number' min='1' name='seat_count' required placeholder='44'>
        </label>

        <div></div>
        <div><button type='submit'>Seferi Ekle</button></div>
      </form>";

echo "<h2>Mevcut Rotalar</h2>";
if (!$routes) {
  echo "<p>Hiç rota yok.</p>";
} else {
  echo "<table border='1' cellpadding='6' cellspacing='0'>
          <tr>
            <th>#</th>
            <th>Firma</th>
            <th>Rota</th>
            <th>Kalkış</th>
            <th>Fiyat</th>
            <th>Koltuk</th>
            <th>Sil</th>
          </tr>";
  foreach ($routes as $r) {
    $price = number_format(((int)$r['price_cents'])/100, 2, ',', '.');
    $rid   = (int)$r['id'];
    echo "<tr>
            <td>{$rid}</td>
            <td>".htmlspecialchars($r['firm_name'])."</td>
            <td>".htmlspecialchars($r['origin'])." → ".htmlspecialchars($r['destination'])."</td>
            <td>".htmlspecialchars($r['depart_at'])."</td>
            <td>{$price} TL</td>
            <td>".(int)$r['seat_count']."</td>
            <td>
              <form method='POST' action='/admin' onsubmit='return confirm(\"Bu sefer silinsin mi?\");'>
                <input type='hidden' name='action' value='delete'>
                <input type='hidden' name='route_id' value='{$rid}'>
                <button type='submit'>Sil</button>
              </form>
            </td>
          </tr>";
  }
  echo "</table>";
}
