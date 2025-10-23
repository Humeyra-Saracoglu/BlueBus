<?php

/**
 * Rate Limiting Utility
 * Brute force saldırılarını önlemek için kullanılır
 */

function check_rate_limit($action, $identifier, $max_attempts = 5, $window = 900) {
    $key = "rate_limit_{$action}_" . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'reset_time' => time() + $window,
            'blocked_until' => null
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    if (time() > $data['reset_time']) {
        $_SESSION[$key] = [
            'count' => 1,
            'reset_time' => time() + $window,
            'blocked_until' => null
        ];
        return true;
    }
    
    if ($data['blocked_until'] && time() < $data['blocked_until']) {
        return false;
    }
    
    if ($data['count'] >= $max_attempts) {
        $_SESSION[$key]['blocked_until'] = time() + $window;
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

function reset_rate_limit($action, $identifier) {
    $key = "rate_limit_{$action}_" . md5($identifier);
    unset($_SESSION[$key]);
}

function get_rate_limit_remaining_time($action, $identifier) {
    $key = "rate_limit_{$action}_" . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $data = $_SESSION[$key];
    
    if ($data['blocked_until'] && time() < $data['blocked_until']) {
        return $data['blocked_until'] - time();
    }
    
    return 0;
}

function get_rate_limit_remaining_attempts($action, $identifier, $max_attempts = 5) {
    $key = "rate_limit_{$action}_" . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        return $max_attempts;
    }
    
    $data = $_SESSION[$key];
    
    if (time() > $data['reset_time']) {
        return $max_attempts;
    }
    
    return max(0, $max_attempts - $data['count']);
}

function get_rate_limit_message($action, $identifier, $max_attempts = 5) {
    $remaining_time = get_rate_limit_remaining_time($action, $identifier);
    $remaining_attempts = get_rate_limit_remaining_attempts($action, $identifier, $max_attempts);
    
    if ($remaining_time > 0) {
        $minutes = ceil($remaining_time / 60);
        return "Çok fazla deneme yaptınız. Lütfen {$minutes} dakika sonra tekrar deneyin.";
    }
    
    if ($remaining_attempts <= 2 && $remaining_attempts > 0) {
        return "Dikkat! {$remaining_attempts} deneme hakkınız kaldı.";
    }
    
    return null;
}