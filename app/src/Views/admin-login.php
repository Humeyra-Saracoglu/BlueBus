<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    🔐
                </div>
                <span class="auth-logo-text">BiletGo</span>
            </div>
            <h2>Admin Girişi</h2>
            <p>Yönetim paneline giriş yapın</p>
            <span class="admin-badge">ADMIN PANEL</span>
        </div>

        <form class="auth-form" action="/admin-login.php" method="POST">
            <div class="form-group">
                <label for="admin-email">Admin E-posta</label>
                <input type="email" id="admin-email" name="email" 
                       placeholder="admin@biletgo.com" required
                       value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="admin-password">Admin Şifre</label>
                <input type="password" id="admin-password" name="password" 
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-admin btn-block">Admin Girişi Yap</button>

            <div class="auth-footer">
                <p><a href="/login.php" class="link">← Kullanıcı Girişine Dön</a></p>
                <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 8px; font-size: 0.85rem;">
                    <strong style="color: #92400e;">Demo Admin Bilgileri:</strong><br>
                    <span style="color: #92400e;">Email: admin@biletgo.com</span><br>
                    <span style="color: #92400e;">Şifre: admin123</span>
                </div>
            </div>
        </form>
    </div>
</div>

<?php unset($_SESSION['form_data']); ?>