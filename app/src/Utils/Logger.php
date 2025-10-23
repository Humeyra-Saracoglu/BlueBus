<?php

/**
 * Security Logging Utility
 */

function log_security_event($event, $details = '', $level = 'INFO') {
    $user_id = $_SESSION['user_id'] ?? 'Guest';
    $user_email = $_SESSION['user_email'] ?? 'N/A';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $request_uri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    
    $log_message = sprintf(
        "[%s] [%s] %s | User: %s (%s) | IP: %s | URI: %s | Details: %s | UA: %s\n",
        date('Y-m-d H:i:s'),
        $level,
        $event,
        $user_id,
        $user_email,
        $ip,
        $request_uri,
        $details,
        substr($user_agent, 0, 100)
    );
    
    $log_file = __DIR__ . '/../../logs/security.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    error_log($log_message, 3, $log_file);
    
    if ($level === 'CRITICAL' || $level === 'ERROR') {
        error_log("[SECURITY] $event: $details");
    }
}

function log_login_success($email) {
    log_security_event('LOGIN_SUCCESS', "User logged in: $email", 'INFO');
}

function log_login_failed($email, $reason = 'Invalid credentials') {
    log_security_event('LOGIN_FAILED', "Failed login attempt for: $email | Reason: $reason", 'WARNING');
}

function log_csrf_failed($action = 'Unknown') {
    log_security_event('CSRF_FAILED', "CSRF token mismatch on action: $action", 'WARNING');
}

function log_rate_limit_exceeded($action, $identifier) {
    log_security_event('RATE_LIMIT_EXCEEDED', "Rate limit exceeded for $action by: $identifier", 'WARNING');
}

function log_unauthorized_access($resource) {
    log_security_event('UNAUTHORIZED_ACCESS', "Unauthorized access attempt to: $resource", 'WARNING');
}

function log_ticket_purchase($ticket_id, $route_id, $amount) {
    log_security_event('TICKET_PURCHASE', "Ticket #$ticket_id purchased for route #$route_id | Amount: " . ($amount/100) . "₺", 'INFO');
}

function log_ticket_cancel($ticket_id, $refund_amount) {
    log_security_event('TICKET_CANCEL', "Ticket #$ticket_id cancelled | Refund: " . ($refund_amount/100) . "₺", 'INFO');
}

function log_registration($email) {
    log_security_event('REGISTRATION', "New user registered: $email", 'INFO');
}

function log_suspicious_activity($description) {
    log_security_event('SUSPICIOUS_ACTIVITY', $description, 'WARNING');
}

function log_sql_injection_attempt($input) {
    $sanitized = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $input), 0, 100);
    log_security_event('SQL_INJECTION_ATTEMPT', "Possible SQL injection: $sanitized", 'CRITICAL');
}

function log_xss_attempt($input) {
    $sanitized = substr(htmlspecialchars($input), 0, 100);
    log_security_event('XSS_ATTEMPT', "Possible XSS attempt: $sanitized", 'CRITICAL');
}