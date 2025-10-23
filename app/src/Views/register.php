<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon">🚍</div>
                <span class="auth-logo-text">BiletGo</span>
            </div>
            <h2>Hesap Oluşturun</h2>
            <p>Hemen üye olun ve bilet almaya başlayın</p>
        </div>

        <form class="auth-form" action="/register" method="POST" id="register-form">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="ad">Ad *</label>
                    <input type="text" id="ad" name="ad" placeholder="Adınız" required
                           value="<?= htmlspecialchars($_SESSION['form_data']['ad'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="soyad">Soyad *</label>
                    <input type="text" id="soyad" name="soyad" placeholder="Soyadınız" required
                           value="<?= htmlspecialchars($_SESSION['form_data']['soyad'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">E-posta *</label>
                <input type="email" id="email" name="email" placeholder="ornek@email.com" required
                       value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="telefon">Telefon *</label>
                <input type="tel" id="telefon" name="telefon" placeholder="05XX XXX XX XX" 
                       pattern="05[0-9]{9}" required
                       value="<?= htmlspecialchars($_SESSION['form_data']['telefon'] ?? '') ?>">
                <small style="color: #6b7280; font-size: 0.85rem;">Format: 05XXXXXXXXX</small>
            </div>

            <div class="form-group">
                <label for="password">Şifre *</label>
                <input type="password" id="password" name="password" placeholder="••••••••" 
                       minlength="8" required>
                <small style="color: #6b7280; font-size: 0.85rem;">En az 8 karakter, bir büyük harf, bir küçük harf ve bir rakam içermelidir.</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Şifre Tekrar *</label>
                <input type="password" id="password_confirm" name="password_confirm" 
                       placeholder="••••••••" required>
            </div>

            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>Kullanım şartlarını kabul ediyorum</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Kayıt Ol</button>

            <div class="auth-footer">
                <p>Zaten hesabınız var mı? <a href="/login" class="link">Giriş Yapın</a></p>
            </div>
        </form>
    </div>
</div>

<script src="/js/form-validation.js"></script>

<?php unset($_SESSION['form_data']); ?>