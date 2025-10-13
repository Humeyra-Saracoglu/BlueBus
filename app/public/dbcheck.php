<?php
declare(strict_types=1);
$dsn = 'sqlite:' . __DIR__ . '/../database/app.db';
$pdo = new PDO($dsn, null, null, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$c1 = $pdo->query("SELECT count(*) AS c FROM routes")->fetch()['c'] ?? 0;
$c2 = $pdo->query("SELECT count(*) AS c FROM users")->fetch()['c'] ?? 0;
echo "OK ✅ routes=$c1, users=$c2";
