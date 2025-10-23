<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Utils/Auth.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bilet satın almak için giriş yapmalısınız.';
    echo '<div class="alert alert-error">Lütfen giriş yapın.</div>';
    exit;
}

$routeId = $_GET['id'] ?? null;

if (!$routeId) {
    $_SESSION['error'] = 'Geçersiz sefer!';
    echo '<div class="alert alert-error">Geçersiz sefer ID!</div>';
    exit;
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT r.*, f.name as firm_name,
        (r.seat_count - COALESCE((
            SELECT COUNT(*) FROM tickets t 
            WHERE t.route_id = r.id AND t.status = 'ACTIVE'
        ), 0)) as available_seats
        FROM routes r
        JOIN firms f ON r.firm_id = f.id
        WHERE r.id = :id AND r.depart_at > datetime('now')
    ");
    $stmt->execute([':id' => $routeId]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$route) {
        $_SESSION['error'] = 'Sefer bulunamadı!';
        echo '<div class="alert alert-error">Sefer bulunamadı veya süresi geçmiş!</div>';
        exit;
    }
    
    $busType = $route['bus_type'] ?? '2+2';
    $is2Plus1 = ($busType === '2+1');
    
    $stmt = $db->prepare("
        SELECT seat_no 
        FROM tickets 
        WHERE route_id = :route_id AND status = 'ACTIVE'
    ");
    $stmt->execute([':route_id' => $routeId]);
    $occupiedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $departTime = new DateTime($route['depart_at']);
    
    $arrivalTime = clone $departTime;
    if (!empty($route['duration_minutes'])) {
        $arrivalTime->modify('+' . (int)$route['duration_minutes'] . ' minutes');
    } else {
        $arrivalTime->modify('+5 hours');
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Bir hata oluştu!';
    echo '<div class="alert alert-error">Hata: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>
<link rel="stylesheet" href="/css/seat-selection.css">

<div class="seat-selection-container">
    <div class="bus-container">
        <div class="bus-header">
            <h2>🚌 Koltuk Seçimi</h2>
            <p style="color: #6c757d;">Lütfen bir koltuk seçiniz</p>
        </div>
        
        <div class="bus-body">
            <div class="bus-front">ŞOFÖR</div>
            
            <div class="bus-seats layout-2-1">
                <?php 
                $totalSeats = (int)$route['seat_count'];
                for ($i = 1; $i <= $totalSeats; $i++): 
                    $isOccupied = in_array($i, $occupiedSeats);
                    $seatClass = $isOccupied ? 'seat seat-occupied' : 'seat seat-available';
                    
                    $position = ($i - 1) % 3;
                    
                    if ($position == 2) {
                        echo '<div class="aisle"></div>';
                    }
                    ?>
                    <div class="<?= $seatClass ?>" 
                         data-seat="<?= $i ?>"
                         <?= !$isOccupied ? 'onclick="selectSeat(this)"' : '' ?>>
                        <?= $i ?>
                    </div>
                <?php endfor; ?>
            </div>
            
            <div class="seat-legends">
                <div class="legend-item">
                    <div class="legend-box" style="background: white; border-color: #28a745;"></div>
                    <span>Boş</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background: #2563eb; border-color: #2563eb;"></div>
                    <span>Seçili</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background: #e9ecef; border-color: #adb5bd;"></div>
                    <span>Dolu</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf - Sefer Bilgileri ve Ödeme -->
    <div class="summary-panel">
        <div class="route-info">
            <div class="route-cities">
                <div class="city-name"><?= htmlspecialchars($route['origin']) ?></div>
                <div class="arrow">→</div>
                <div class="city-name"><?= htmlspecialchars($route['destination']) ?></div>
            </div>
            <div class="route-time">
                <?= $departTime->format('d.m.Y') ?> - <?= $departTime->format('H:i') ?>
            </div>
        </div>

        <div class="info-row">
            <span class="info-label">Firma:</span>
            <span class="info-value"><?= htmlspecialchars($route['firm_name']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Tarih:</span>
            <span class="info-value"><?= $departTime->format('d M Y') ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Koltuk:</span>
            <span class="info-value" id="selected-seat-display">Seçilmedi</span>
        </div>
        
        <div class="info-row" style="border-bottom: none;">
            <span class="info-label">Boş Koltuk:</span>
            <span class="info-value"><?= $route['available_seats'] ?> / <?= $route['seat_count'] ?></span>
        </div>

        <!-- İndirim Kodu -->
        <div class="coupon-section">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">İndirim Kodu</label>
            <div class="coupon-input-group">
                <input type="text" 
                       class="coupon-input" 
                       id="coupon-code-input" 
                       placeholder="INDIRIM10"
                       style="text-transform: uppercase;">
                <button type="button" class="coupon-btn" onclick="applyCoupon()">Uygula</button>
            </div>
            <small id="coupon-message" style="display: block; margin-top: 0.5rem;"></small>
        </div>

        <!-- Fiyat Detayı -->
        <div class="price-section">
            <div class="price-row">
                <span>Bilet Fiyatı:</span>
                <span id="original-price"><?= number_format($route['price_cents'] / 100, 2) ?> ₺</span>
            </div>
            <div class="price-row discount-row" id="discount-row" style="display: none;">
                <span>İndirim:</span>
                <span id="discount-amount">-0.00 ₺</span>
            </div>
            <div class="price-row total-row">
                <span>Toplam:</span>
                <span id="total-price"><?= number_format($route['price_cents'] / 100, 2) ?> ₺</span>
            </div>
        </div>

        <!-- Satın Al Formu -->
        <form method="POST" action="/buy" id="purchase-form">
            <?= csrf_field() ?>
            <input type="hidden" name="route_id" value="<?= $route['id'] ?>">
            <input type="hidden" name="seat_number" id="seat-number-input" value="">
            <input type="hidden" name="price" id="price-input" value="<?= $route['price_cents'] ?>">
            <input type="hidden" name="coupon_code" id="coupon-code-hidden" value="">
            
            <button type="submit" class="buy-button" id="buy-button" disabled>
                Koltuk Seçiniz
            </button>
        </form>
    </div>
</div>

<script>
let selectedSeat = null;
let originalPrice = <?= $route['price_cents'] ?>;
let currentPrice = originalPrice;
let appliedCoupon = null;

const coupons = {
    'INDIRIM10': { type: 'percent', value: 10, description: '%10 indirim' },
    'YENI20': { type: 'percent', value: 20, description: '%20 indirim' },
    'WELCOME50': { type: 'fixed', value: 5000, description: '50₺ indirim' }
};

function selectSeat(element) {
    if (selectedSeat) {
        selectedSeat.classList.remove('seat-selected');
        selectedSeat.classList.add('seat-available');
    }
    
    selectedSeat = element;
    element.classList.remove('seat-available');
    element.classList.add('seat-selected');
    
    const seatNumber = element.getAttribute('data-seat');
    
    document.getElementById('seat-number-input').value = seatNumber;
    document.getElementById('selected-seat-display').textContent = 'Koltuk ' + seatNumber;
    
    const buyButton = document.getElementById('buy-button');
    buyButton.disabled = false;
    buyButton.textContent = 'Satın Al (' + (currentPrice / 100).toFixed(2) + ' ₺)';
    
    if (appliedCoupon) {
        updatePriceWithDiscount();
    }
}

function applyCoupon() {
    const input = document.getElementById('coupon-code-input');
    const code = input.value.toUpperCase().trim();
    const message = document.getElementById('coupon-message');
    
    if (!code) {
        message.textContent = 'Lütfen bir kod giriniz';
        message.style.color = '#dc3545';
        return;
    }
    
    if (coupons[code]) {
        appliedCoupon = coupons[code];
        document.getElementById('coupon-code-hidden').value = code;
        message.textContent = '✅ ' + coupons[code].description + ' uygulandı!';
        message.style.color = '#28a745';
        input.disabled = true;
        updatePriceWithDiscount();
    } else {
        message.textContent = '❌ Geçersiz kod';
        message.style.color = '#dc3545';
        appliedCoupon = null;
        document.getElementById('coupon-code-hidden').value = '';
        currentPrice = originalPrice;
        updatePriceDisplay();
    }
}

function updatePriceWithDiscount() {
    if (!appliedCoupon) return;
    
    let discount = 0;
    if (appliedCoupon.type === 'percent') {
        discount = Math.floor(originalPrice * appliedCoupon.value / 100);
    } else {
        discount = appliedCoupon.value;
    }
    
    currentPrice = Math.max(0, originalPrice - discount);
    
    document.getElementById('discount-row').style.display = 'flex';
    document.getElementById('discount-amount').textContent = '-' + (discount / 100).toFixed(2) + ' ₺';
    document.getElementById('price-input').value = currentPrice;
    
    updatePriceDisplay();
}

function updatePriceDisplay() {
    document.getElementById('total-price').textContent = (currentPrice / 100).toFixed(2) + ' ₺';
    
    if (selectedSeat) {
        document.getElementById('buy-button').textContent = 'Satın Al (' + (currentPrice / 100).toFixed(2) + ' ₺)';
    }
}

document.getElementById('purchase-form').addEventListener('submit', function(e) {
    if (!selectedSeat) {
        e.preventDefault();
        alert('Lütfen bir koltuk seçiniz!');
    }
});
</script>