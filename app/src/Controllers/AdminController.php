<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    header('Location: /');
    exit;
}

$db = getDbConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_firm') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if ($name === '') {
            $errors[] = 'Firma adı boş olamaz';
        } else {
            $stmt = $db->prepare("INSERT INTO firms (name, email, phone) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $phone]);
            $_SESSION['success'] = 'Firma başarıyla eklendi';
            header('Location: /admin');
            exit;
        }
    }

    if ($action === 'add_route') {
        $firm_id = (int)($_POST['firm_id'] ?? 0);
        $origin = trim($_POST['origin'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $depart_date = trim($_POST['depart_date'] ?? '');
        $depart_time = trim($_POST['depart_time'] ?? '');
        $price_tl = (float)($_POST['price_tl'] ?? 0);
        $seat_count = (int)($_POST['seat_count'] ?? 40);
        $bus_type = trim($_POST['bus_type'] ?? '2+2');
        $duration = (int)($_POST['duration_minutes'] ?? 360);
        
        $depart_at = $depart_date . ' ' . $depart_time . ':00';
        $price_cents = (int)($price_tl * 100);
        
        if ($firm_id > 0 && $origin && $destination && $depart_at && $price_cents > 0) {
            $stmt = $db->prepare("INSERT INTO routes (firm_id, origin, destination, depart_at, price_cents, seat_count, bus_type, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firm_id, $origin, $destination, $depart_at, $price_cents, $seat_count, $bus_type, $duration]);
            $_SESSION['success'] = 'Sefer başarıyla eklendi';
            header('Location: /admin');
            exit;
        } else {
            $errors[] = 'Tüm alanları doldurun';
        }
    }

    if ($action === 'delete_route') {
        $route_id = (int)($_POST['route_id'] ?? 0);
        if ($route_id > 0) {
            $stmt = $db->prepare("DELETE FROM routes WHERE id = ?");
            $stmt->execute([$route_id]);
            $_SESSION['success'] = 'Sefer başarıyla silindi';
            header('Location: /admin');
            exit;
        }
    }
}

$statsStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM firms) as total_firms,
        (SELECT COUNT(*) FROM routes) as total_routes,
        (SELECT COUNT(*) FROM users WHERE role = 'USER') as total_users,
        (SELECT COUNT(*) FROM tickets WHERE status = 'ACTIVE') as total_tickets
");
$stats = $statsStmt->fetch();

$firmsStmt = $db->query("SELECT * FROM firms ORDER BY name");
$firms = $firmsStmt->fetchAll();

$routesStmt = $db->query("
    SELECT r.*, f.name as firm_name,
           (r.seat_count - COALESCE((SELECT COUNT(*) FROM tickets t WHERE t.route_id = r.id AND t.status = 'ACTIVE'), 0)) as available_seats
    FROM routes r
    JOIN firms f ON r.firm_id = f.id
    ORDER BY r.depart_at DESC
    LIMIT 50
");
$routes = $routesStmt->fetchAll();

$ticketsStmt = $db->query("
    SELECT 
        t.id,
        t.seat_no,
        t.price_paid_cents,
        t.status,
        t.created_at,
        u.ad,
        u.soyad,
        u.email,
        r.origin,
        r.destination,
        r.depart_at,
        f.name as firm_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    JOIN routes r ON t.route_id = r.id
    JOIN firms f ON r.firm_id = f.id
    ORDER BY t.created_at DESC
    LIMIT 100
");
$tickets = $ticketsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - BiletGo</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        .admin-header h1 { font-size: 28px; margin-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #6b7280; font-size: 14px; font-weight: 500; margin-bottom: 10px; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #667eea; }
        .section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section h2 { font-size: 22px; margin-bottom: 20px; color: #1a1a1a; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
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
                <li><a href="/admin" class="active">⚙️ Admin Paneli</a></li>
                <li><span style="font-weight: 600;">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span></li>
                <li><a href="/logout" class="btn btn-outline">Çıkış</a></li>
            </ul>
        </nav>
    </header>

    <div class="admin-container">
        <div class="admin-header">
            <h1>⚙️ Admin Yönetim Paneli</h1>
            <p>Tüm sistemi buradan yönetebilirsiniz</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p>❌ <?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- İstatistikler -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Toplam Firma</h3>
                <div class="value"><?= $stats['total_firms'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Sefer</h3>
                <div class="value"><?= $stats['total_routes'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Kullanıcı</h3>
                <div class="value"><?= $stats['total_users'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Aktif Bilet</h3>
                <div class="value"><?= $stats['total_tickets'] ?></div>
            </div>
        </div>

        <!-- Yeni Firma Ekle -->
        <div class="section">
            <h2>➕ Yeni Firma Ekle</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_firm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Firma Adı *</label>
                        <input type="text" name="name" required placeholder="Örn: GreenLine">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="info@greenline.com">
                    </div>
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="tel" name="phone" placeholder="0850 123 45 67">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Ekle</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Yeni Sefer Ekle -->
        <div class="section">
            <h2>🚌 Yeni Sefer Ekle</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_route">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Firma *</label>
                        <select name="firm_id" required>
                            <option value="">Firma Seçiniz</option>
                            <?php foreach ($firms as $firm): ?>
                                <option value="<?= $firm['id'] ?>"><?= htmlspecialchars($firm['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kalkış *</label>
                        <input type="text" name="origin" required placeholder="İstanbul">
                    </div>
                    <div class="form-group">
                        <label>Varış *</label>
                        <input type="text" name="destination" required placeholder="Ankara">
                    </div>
                    <div class="form-group">
                        <label>Kalkış Tarihi *</label>
                        <input type="date" name="depart_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Kalkış Saati *</label>
                        <input type="time" name="depart_time" required value="09:00">
                    </div>
                    <div class="form-group">
                        <label>Fiyat (TL) *</label>
                        <input type="number" step="0.01" name="price_tl" required placeholder="350.00">
                    </div>
                    <div class="form-group">
                        <label>Koltuk Sayısı *</label>
                        <input type="number" name="seat_count" required value="40" min="10" max="64">
                    </div>
                    <div class="form-group">
                        <label>Otobüs Tipi *</label>
                        <select name="bus_type" required>
                            <option value="2+2">2+2 (Standart)</option>
                            <option value="2+1">2+1 (Premium)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Süre (Dakika)</label>
                        <input type="number" name="duration_minutes" value="360" min="30">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Sefer Ekle</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Mevcut Rotalar -->
        <div class="section">
            <h2>📋 Mevcut Rotalar</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Firma</th>
                        <th>Rota</th>
                        <th>Tarih / Saat</th>
                        <th>Fiyat</th>
                        <th>Koltuk</th>
                        <th>Tip</th>
                        <th>Sil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><?= $route['id'] ?></td>
                        <td><?= htmlspecialchars($route['firm_name']) ?></td>
                        <td><?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($route['depart_at'])) ?></td>
                        <td><?= number_format($route['price_cents'] / 100, 2) ?> ₺</td>
                        <td><?= ($route['seat_count'] - $route['available_seats']) ?> / <?= $route['seat_count'] ?></td>
                        <td><?= $route['bus_type'] ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="action" value="delete_route">
                                <input type="hidden" name="route_id" value="<?= $route['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">🗑️ Sil</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tüm Biletler -->
        <div class="section">
            <h2>🎫 Tüm Biletler (Son 100)</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Yolcu</th>
                        <th>Firma</th>
                        <th>Rota</th>
                        <th>Tarih</th>
                        <th>Koltuk</th>
                        <th>Ücret</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= $ticket['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($ticket['ad'] . ' ' . $ticket['soyad']) ?><br>
                            <small style="color: #6b7280;"><?= htmlspecialchars($ticket['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($ticket['firm_name']) ?></td>
                        <td><?= htmlspecialchars($ticket['origin']) ?> → <?= htmlspecialchars($ticket['destination']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($ticket['depart_at'])) ?></td>
                        <td><?= $ticket['seat_no'] ?></td>
                        <td><?= number_format($ticket['price_paid_cents'] / 100, 2) ?> ₺</td>
                        <td>
                            <?php if ($ticket['status'] === 'ACTIVE'): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php elseif ($ticket['status'] === 'CANCELLED'): ?>
                                <span class="badge badge-danger">İptal</span>
                            <?php else: ?>
                                <span class="badge">Tamamlandı</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>