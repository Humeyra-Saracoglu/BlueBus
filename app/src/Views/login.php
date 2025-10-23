<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon">🚍</div>
                <span class="auth-logo-text">BiletGo</span>
            </div>
            <h2>Hoş Geldiniz</h2>
            <p>Hesabınıza giriş yapın</p>
        </div>

        <form class="auth-form" action="/login" method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" placeholder="ornek@email.com" required 
                       value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    <span>Beni Hatırla</span>
                </label>
                <a href="/sifremi-unuttum.php" class="link">Şifremi Unuttum?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>

            <div class="auth-footer">
                <p>Hesabınız yok mu? <a href="/register" class="link">Kayıt Olun</a></p>
            </div>
        </form>
    </div>
</div>

<?php unset($_SESSION['form_data']); ?>