<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

$u = auth_user();
if (!$u) { header('Location: /login'); exit; }

$role = $u['role'] ?? '';
$firmId = (int)($u['firm_id'] ?? 0);

if ($role !== 'FIRM_ADMIN' || $firmId <= 0) { 
    http_response_code(403); 
    echo "Bu sayfaya erişim yetkin yok."; 
    exit; 
}

$db = getDbConnection();
$errors = [];

function toIsoDatetimeFromParts(string $datePart, string $timePart): string {
  $datePart = trim($datePart);
  $timePart = trim($timePart);
  if ($datePart === '' || $timePart === '') return '';
  $d = DateTime::createFromFormat('d.m.Y', $datePart);
  $t = DateTime::createFromFormat('H:i', $timePart);
  if (!$d || !$t) return '';
  $d->setTime((int)$t->format('H'), (int)$t->format('i'), 0);
  return $d->format('Y-m-d H:i:00');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $origin       = trim($_POST['origin'] ?? '');
    $destination  = trim($_POST['destination'] ?? '');
    $depart_date  = trim($_POST['depart_date'] ?? '');
    $depart_time  = trim($_POST['depart_time'] ?? '');
    $price_tl     = (float)($_POST['price_tl'] ?? 0);
    $seat_count   = (int)($_POST['seat_count'] ?? 0);
    $bus_type     = trim($_POST['bus_type'] ?? '2+2');
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 360);

    $depart_at    = toIsoDatetimeFromParts($depart_date, $depart_time);
    $price_cents  = (int) round($price_tl * 100);

    if ($origin === '')          $errors[] = "Kalkış (origin) boş olamaz.";
    if ($destination === '')     $errors[] = "Varış (destination) boş olamaz.";
    if ($depart_at === '')       $errors[] = "Geçerli bir tarih ve saat giriniz (ör. 20.10.2025 ve 09:30).";
    if ($price_cents <= 0)       $errors[] = "Fiyat TL > 0 olmalı.";
    if ($seat_count <= 0)        $errors[] = "Koltuk sayısı > 0 olmalı.";

    if (!$errors) {
      $ins = $db->prepare("INSERT INTO routes (firm_id, origin, destination, depart_at, price_cents, seat_count, bus_type, duration_minutes) VALUES (:fid, :o, :d, :at, :pc, :sc, :bt, :dm)");
      $ins->execute([
        ':fid' => $firmId,
        ':o'   => $origin,
        ':d'   => $destination,
        ':at'  => $depart_at,
        ':pc'  => $price_cents,
        ':sc'  => $seat_count,
        ':bt'  => $bus_type,
        ':dm'  => $duration_minutes,
      ]);
      header('Location: /firm-admin?ok=1'); exit;
    }
  }

  if ($action === 'delete') {
    $route_id = (int)($_POST['route_id'] ?? 0);
    if ($route_id > 0) {
      $del = $db->prepare("DELETE FROM routes WHERE id=:id AND firm_id=:fid");
      $del->execute([':id' => $route_id, ':fid' => $firmId]);
    }
    header('Location: /firm-admin?deleted=1'); exit;
  }
}

$firmStmt = $db->prepare("SELECT name FROM firms WHERE id=:id");
$firmStmt->execute([':id' => $firmId]);
$firm = $firmStmt->fetch();

$routes = $db->prepare("
  SELECT r.id, r.origin, r.destination, r.depart_at, r.price_cents, r.seat_count, r.bus_type,
         (r.seat_count - COALESCE((SELECT COUNT(*) FROM tickets t WHERE t.route_id = r.id AND t.status = 'ACTIVE'), 0)) as available_seats
  FROM routes r
  WHERE r.firm_id = :fid
  ORDER BY r.depart_at DESC
");
$routes->execute([':fid' => $firmId]);
$routesList = $routes->fetchAll();

$statsStmt = $db->prepare("
  SELECT 
    COUNT(*) as total_routes,
    COUNT(CASE WHEN depart_at > datetime('now') THEN 1 END) as active_routes,
    COALESCE(SUM(CASE WHEN depart_at > datetime('now') THEN (SELECT COUNT(*) FROM tickets WHERE route_id = r.id AND status = 'ACTIVE') END), 0) as total_tickets_sold
  FROM routes r
  WHERE r.firm_id = :fid
");
$statsStmt->execute([':fid' => $firmId]);
$stats = $statsStmt->fetch();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetim Paneli - <?= htmlspecialchars($firm['name'] ?? '') ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/firm-admin.css">
</head>
<body>
    <!-- Navbar -->
    <header>
        <nav>
            <div class="logo" onclick="window.location.href='/'">
                <div class="logo-icon">🚍</div>
                <span class="logo-text">BiletGo</span>
            </div>
            <ul class="nav-links">
                <li><a href="/">Ana Sayfa</a></li>
                <li><a href="/firm-admin" class="active">🏢 Sefer Yönetimi</a></li>
                <li><span style="font-weight: 600;">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Firma Admin') ?></span></li>
                <li><a href="/logout" class="btn btn-outline">Çıkış</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="header">
            <h1>🏢 Firma Yönetim Paneli</h1>
            <p><?= htmlspecialchars($firm['name'] ?? '') ?></p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Toplam Sefer</h3>
                <div class="value"><?= $stats['total_routes'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Aktif Sefer</h3>
                <div class="value"><?= $stats['active_routes'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Satılan Bilet</h3>
                <div class="value"><?= $stats['total_tickets_sold'] ?></div>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul style="list-style: none;">
                    <?php foreach ($errors as $e): ?>
                        <li>❌ <?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (isset($_GET['ok'])): ?>
            <div class="alert alert-success">✅ Sefer başarıyla eklendi!</div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Sefer başarıyla silindi!</div>
        <?php endif; ?>

        <div class="section">
            <h2>➕ Yeni Sefer Ekle</h2>
            <form method="POST" action="/firm-admin">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label>Kalkış Şehri</label>
                    <input name="origin" required placeholder="İstanbul">
                </div>
                
                <div>
                    <label>Varış Şehri</label>
                    <input name="destination" required placeholder="Ankara">
                </div>
                
                <div>
                    <label>Kalkış Tarihi</label>
                    <input name="depart_date" required placeholder="GG.AA.YYYY" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}">
                </div>
                
                <div>
                    <label>Kalkış Saati</label>
                    <input name="depart_time" required placeholder="09:30" pattern="[0-9]{2}:[0-9]{2}">
                </div>
                
                <div>
                    <label>Fiyat (₺)</label>
                    <input type="number" step="0.01" min="0" name="price_tl" required placeholder="250.00">
                </div>
                
                <div>
                    <label>Koltuk Sayısı</label>
                    <input type="number" min="10" max="64" name="seat_count" required placeholder="40" value="40">
                </div>
                
                <div>
                    <label>Otobüs Tipi</label>
                    <select name="bus_type" required>
                        <option value="2+2">2+2 (Standart)</option>
                        <option value="2+1">2+1 (Premium - Tekli Koltuklar)</option>
                    </select>
                </div>
                
                <div>
                    <label>Yolculuk Süresi (Dakika)</label>
                    <input type="number" min="30" name="duration_minutes" required placeholder="360" value="360">
                </div>
                
                <div class="form-row">
                    <button type="submit">🚌 Sefer Ekle</button>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>📋 Seferlerim</h2>
            <?php if (empty($routesList)): ?>
                <p style="text-align: center; color: #6b7280; padding: 40px;">Henüz sefer eklenmemiş.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Rota</th>
                            <th>Tarih / Saat</th>
                            <th>Fiyat</th>
                            <th>Koltuk</th>
                            <th>Tip</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routesList as $r): 
                            $price = number_format(((int)$r['price_cents'])/100, 2);
                            $rid   = (int)$r['id'];
                            $depart_display = '';
                            if (!empty($r['depart_at'])) {
                                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $r['depart_at']);
                                if ($dt) $depart_display = $dt->format('d.m.Y H:i');
                                else $depart_display = htmlspecialchars($r['depart_at']);
                            }
                            $busType = $r['bus_type'] ?? '2+2';
                            $isPremium = ($busType === '2+1');
                            $soldSeats = (int)$r['seat_count'] - (int)$r['available_seats'];
                        ?>
                            <tr>
                                <td><?= $rid ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['origin']) ?></strong> → 
                                    <strong><?= htmlspecialchars($r['destination']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($depart_display) ?></td>
                                <td><?= $price ?> ₺</td>
                                <td><?= $soldSeats ?> / <?= (int)$r['seat_count'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $isPremium ? 'premium' : 'standard' ?>">
                                        <?= htmlspecialchars($busType) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="/firm-admin" style="display: inline;" onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="route_id" value="<?= $rid ?>">
                                        <button type="submit" class="danger" style="padding: 6px 12px; font-size: 12px;">🗑️ Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/" style="color: #667eea; text-decoration: none; font-weight: 600;">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>