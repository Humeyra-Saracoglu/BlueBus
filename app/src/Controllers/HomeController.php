<?php
require_once __DIR__ . '/../Utils/Auth.php';
$u = auth_user();

echo '<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">';
if ($u) {
  echo "<span>👋 Merhaba, <strong>".htmlspecialchars($u['name'])."</strong> (<em>".htmlspecialchars($u['role'])."</em>)</span>";
  echo ' | <a href="/logout">Çıkış</a>';
} else {
  echo '<a href="/login">Giriş</a> | <a href="/register">Kayıt ol</a>';
}
echo '</div>';

echo "<h1>Sefer Ara</h1>";
echo '<form method="GET" action="/routes" style="display:flex;gap:8px;">
        <input name="origin" placeholder="Nereden">
        <input name="destination" placeholder="Nereye">
        <input name="date" placeholder="GG.AA.YYYY" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}">
        <button type="submit">Ara</button>
      </form>';
