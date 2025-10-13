<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.use_strict_mode', '1');
  ini_set('session.cookie_httponly', '1');
  session_start();
}

function auth_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function auth_login(array $u): void {
  $_SESSION['user'] = [
    'id'      => (int)$u['id'],
    'name'    => (string)$u['name'],
    'role'    => (string)$u['role'],
    'firm_id' => $u['firm_id'] !== null ? (int)$u['firm_id'] : null,
  ];
}

function auth_logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function require_login(): void {
  if (!auth_user()) {
    header('Location: /login');
    exit;
  }
}

function require_role(array $roles): void {
  $u = auth_user();
  if (!$u || !in_array($u['role'], $roles, true)) {
    http_response_code(403);
    echo '403 - Yetkisiz erişim';
    exit;
  }
}
