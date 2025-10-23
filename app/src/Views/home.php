<?php
if (!isset($routes)) {
    $routes = [];
}

$searchOrigin = $_GET['origin'] ?? '';
$searchDestination = $_GET['destination'] ?? '';
$searchDate = $_GET['date'] ?? date('Y-m-d');
?>

<section class="hero">
    <div class="hero-content">
        <h1>Türkiye'nin En Hızlı Otobüs Bileti Platformu</h1>
        <p>Güvenli ve kolay bilet alımı, anında onay!</p>
    </div>

    <div class="search-card">
        <form class="search-form" action="/routes" method="GET">
            <div class="form-group">
                <label for="kalkis">Nereden</label>
                <select id="kalkis" name="origin" required>
                    <option value="">Şehir Seçiniz</option>
                    <option value="İstanbul" <?= $searchOrigin === 'İstanbul' ? 'selected' : '' ?>>İstanbul</option>
                    <option value="Ankara" <?= $searchOrigin === 'Ankara' ? 'selected' : '' ?>>Ankara</option>
                    <option value="İzmir" <?= $searchOrigin === 'İzmir' ? 'selected' : '' ?>>İzmir</option>
                    <option value="Antalya" <?= $searchOrigin === 'Antalya' ? 'selected' : '' ?>>Antalya</option>
                    <option value="Bursa" <?= $searchOrigin === 'Bursa' ? 'selected' : '' ?>>Bursa</option>
                    <option value="Van" <?= $searchOrigin === 'Van' ? 'selected' : '' ?>>Van</option>
                    <option value="Eskişehir" <?= $searchOrigin === 'Eskişehir' ? 'selected' : '' ?>>Eskişehir</option>
                </select>
            </div>

            <div class="form-group">
                <label for="varis">Nereye</label>
                <select id="varis" name="destination" required>
                    <option value="">Şehir Seçiniz</option>
                    <option value="İstanbul" <?= $searchDestination === 'İstanbul' ? 'selected' : '' ?>>İstanbul</option>
                    <option value="Ankara" <?= $searchDestination === 'Ankara' ? 'selected' : '' ?>>Ankara</option>
                    <option value="İzmir" <?= $searchDestination === 'İzmir' ? 'selected' : '' ?>>İzmir</option>
                    <option value="Antalya" <?= $searchDestination === 'Antalya' ? 'selected' : '' ?>>Antalya</option>
                    <option value="Bursa" <?= $searchDestination === 'Bursa' ? 'selected' : '' ?>>Bursa</option>
                    <option value="Van" <?= $searchDestination === 'Van' ? 'selected' : '' ?>>Van</option>
                    <option value="Eskişehir" <?= $searchDestination === 'Eskişehir' ? 'selected' : '' ?>>Eskişehir</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tarih">Tarih</label>
                <input type="date" 
                    id="tarih" 
                    name="date" 
                    class="date-input-fix"
                    value="<?= htmlspecialchars($searchDate) ?>" 
                    required>
            </div>

            <div class="search-btn-container">
                <button type="submit" class="btn btn-primary">Sefer Ara</button>
            </div>
        </form>
    </div>
           
</section>

<section class="journey-list">
    <?php if (empty($routes)): ?>
        <div class="journey-card">
            <p style="text-align: center; color: #6b7280; padding: 2rem;">
                Şu anda gösterilecek sefer bulunmamaktadır.
            </p>
        </div>
    <?php else: ?>
        <?php foreach ($routes as $route): 
            try {
                $departTime = new DateTime($route['depart_at']);
                
                $durationMinutes = isset($route['duration_minutes']) && $route['duration_minutes'] > 0 
                    ? (int)$route['duration_minutes'] 
                    : 300; // Varsayılan 5 saat
                
                $arrivalTime = clone $departTime;
                $arrivalTime->modify('+' . $durationMinutes . ' minutes');
            } catch (Exception $e) {
                error_log("Date parsing error: " . $e->getMessage());
                continue; 
            }
        ?>
            <div class="journey-card">
                <div class="journey-header">
                    <div class="company-name"><?= htmlspecialchars($route['firm_name']) ?></div>
                    <div class="price"><?= number_format($route['price_cents'] / 100, 2) ?> ₺</div>
                </div>

                <div class="journey-details">
                    <div class="location">
                        <div class="location-name"><?= ucfirst(htmlspecialchars($route['origin'])) ?></div>
                        <div class="location-time"><?= $departTime->format('H:i') ?></div>
                    </div>
                    <div class="journey-arrow">→</div>
                    <div class="location">
                        <div class="location-name"><?= ucfirst(htmlspecialchars($route['destination'])) ?></div>
                        <div class="location-time"><?= $arrivalTime->format('H:i') ?></div>
                    </div>
                </div>

                <div class="journey-info">
                    <div class="info-item">
                        <span>📅</span>
                        <span><?= $departTime->format('d F Y') ?></span>
                    </div>
                    <div class="info-item">
                        <span>💺</span>
                        <span><?= $route['available_seats'] ?? 0 ?> Boş Koltuk</span>
                    </div>
                    <div class="info-item">
                        <span>⏱️</span>
                        <span><?= floor($durationMinutes / 60) ?> saat <?= $durationMinutes % 60 ?> dk</span>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/routes?id=<?= $route['id'] ?>" class="btn btn-primary">Bilet Al</a>
                    <?php else: ?>
                        <a href="/login" class="btn btn-primary">Giriş Yapın</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.search-form');
    const submitBtn = document.querySelector('.search-form button[type="submit"]');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submit edildi!');
            const origin = document.getElementById('kalkis').value;
            const destination = document.getElementById('varis').value;
            const date = document.getElementById('tarih').value;
            
            console.log('Nereden:', origin);
            console.log('Nereye:', destination);
            console.log('Tarih:', date);
            
            if (!origin || !destination || !date) {
                e.preventDefault();
                alert('Lütfen tüm alanları doldurun!');
                return false;
            }
        });
    }
    

    if (submitBtn) {
        submitBtn.style.pointerEvents = 'auto';
        submitBtn.style.cursor = 'pointer';
        submitBtn.style.zIndex = '100';
        console.log('Sefer Ara butonu aktif');
    }
});
</script>