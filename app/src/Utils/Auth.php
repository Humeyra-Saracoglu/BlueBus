<?php

// Kullanıcı giriş yapmış mı kontrol et
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
        header('Location: /login');
        exit;
    }
}

// Admin yetkisi var mı kontrol et
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
        $_SESSION['error'] = 'Bu sayfaya erişim yetkiniz yok.';
        header('Location: /');
        exit;
    }
}

// Firma admin yetkisi var mı kontrol et
function requireFirmAdmin() {
    if (!isset($_SESSION['user_id']) || 
        ($_SESSION['user_role'] !== 'FIRM_ADMIN' && $_SESSION['user_role'] !== 'ADMIN')) {
        $_SESSION['error'] = 'Bu sayfaya erişim yetkiniz yok.';
        header('Location: /');
        exit;
    }
}

// Kullanıcı bilgilerini al
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

// Kullanıcının kredisini güncelle
function updateUserCredit($userId) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT credit FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_credit'] = $user['credit'];
            return $user['credit'];
        }
        return 0;
    } catch (Exception $e) {
        error_log("Update credit error: " . $e->getMessage());
        return 0;
    }
}

// Kullanıcının rolünü kontrol et
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Giriş yapmış mı kontrol et
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Alias for getCurrentUser - used in controllers
function auth_user() {
    return getCurrentUser();
}
