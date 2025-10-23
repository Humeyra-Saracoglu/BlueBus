<?php if (session_status() === PHP_SESSION_NONE) {
    session_start();
} ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiletGo - Otobüs Bileti</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <header>
        <nav>
            <div class="logo" onclick="window.location.href='/'">
                <div class="logo-icon">🚍</div>
                <span class="logo-text">BiletGo</span>
            </div>
            <ul class="nav-links">
                <li><a href="/">Ana Sayfa</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <!-- Firma Admin için özel link -->
                    <?php if ($_SESSION['user_role'] === 'FIRM_ADMIN'): ?>
                        <li><a href="/firm-admin">🏢 Sefer Yönetimi</a></li>
                    <?php endif; ?>
                    
                    <!-- Admin için özel link -->
                    <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
                        <li><a href="/admin">⚙️ Admin Paneli</a></li>
                    <?php endif; ?>
                    
                    <!-- Normal kullanıcı için linkler -->
                    <?php if ($_SESSION['user_role'] === 'USER'): ?>
                        <li><a href="/tickets">Biletlerim</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/wallet">Hesabım</a></li>
                    
                    <div class="user-info">
                        <span style="font-weight: 600;">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                        <a href="/logout" class="btn btn-outline">Çıkış</a>
                    </div>
                <?php else: ?>
                    <li><a href="/login" class="btn btn-primary">Giriş Yap</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            ✓ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            ✕ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
        <div class="alert alert-error" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li style="margin-bottom: 0.5rem;">✕ <?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>