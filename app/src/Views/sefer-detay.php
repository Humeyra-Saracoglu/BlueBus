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
let discountAmount = 0;
const routeId = <?= $route['id'] ?>;

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
    
    updateBuyButton();
}

function updateBuyButton() {
    const buyButton = document.getElementById('buy-button');
    buyButton.disabled = false;
    buyButton.textContent = 'Satın Al (' + (currentPrice / 100).toFixed(2) + ' ₺)';
}

async function applyCoupon() {
    const input = document.getElementById('coupon-code-input');
    const code = input.value.toUpperCase().trim();
    const message = document.getElementById('coupon-message');
    const button = document.querySelector('.coupon-btn');
    
    if (!code) {
        message.textContent = '⚠️ Lütfen bir kupon kodu giriniz';
        message.style.color = '#f59e0b';
        return;
    }

    button.textContent = 'Kontrol ediliyor...';
    button.disabled = true;
    message.textContent = '';
    
    try {
        const response = await fetch('/api/check-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: code,
                route_id: routeId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            appliedCoupon = {
                code: code,
                percent: data.discount_percent
            };
     
            document.getElementById('coupon-code-hidden').value = code;

            discountAmount = Math.round(originalPrice * data.discount_percent / 100);
            currentPrice = originalPrice - discountAmount;
 
            message.textContent = '✅ ' + data.message;
            message.style.color = '#10b981';

            input.disabled = true;
            button.textContent = '✓ Uygulandı';
            button.style.background = '#10b981';
            
            updatePriceDisplay();
            updateBuyButton();
            
        } else {
            message.textContent = '❌ ' + data.message;
            message.style.color = '#ef4444';
            
            button.textContent = 'Uygula';
            button.disabled = false;
            
            appliedCoupon = null;
            discountAmount = 0;
            currentPrice = originalPrice;
            document.getElementById('coupon-code-hidden').value = '';
            updatePriceDisplay();
        }
        
    } catch (error) {
        console.error('Kupon kontrolü hatası:', error);
        message.textContent = '❌ Bir hata oluştu, lütfen tekrar deneyin';
        message.style.color = '#ef4444';
        
        button.textContent = 'Uygula';
        button.disabled = false;
    }
}

function updatePriceDisplay() {
    const discountRow = document.getElementById('discount-row');
    const discountAmountEl = document.getElementById('discount-amount');
    
    if (discountAmount > 0) {
        discountRow.style.display = 'flex';
        discountAmountEl.textContent = '- ' + (discountAmount / 100).toFixed(2) + ' ₺';
        discountAmountEl.style.color = '#10b981';
    } else {
        discountRow.style.display = 'none';
    }
    
    const totalPriceEl = document.getElementById('total-price');
    totalPriceEl.textContent = (currentPrice / 100).toFixed(2) + ' ₺';
    
    if (discountAmount > 0) {
        totalPriceEl.style.color = '#10b981';
        totalPriceEl.style.fontWeight = 'bold';
    } else {
        totalPriceEl.style.color = '';
        totalPriceEl.style.fontWeight = '';
    }
}

document.getElementById('coupon-code-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        applyCoupon();
    }
});

document.getElementById('purchase-form').addEventListener('submit', function(e) {
    if (!selectedSeat) {
        e.preventDefault();
        alert('Lütfen bir koltuk seçiniz!');
    }
});
</script>
</script>