<?php

$dbDir = '/var/www/html/database';
$dbFile = $dbDir . '/app.db';
$schemaFile = $dbDir . '/schema.sql';

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
    echo "✓ Database klasörü oluşturuldu.\n";
}

chmod($dbDir, 0777);

if (!file_exists($schemaFile)) {
    die("✗ HATA: schema.sql dosyası bulunamadı: $schemaFile\n");
}

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Veritabanı bağlantısı başarılı.\n";

    $sql = file_get_contents($schemaFile);
    
    if ($sql === false) {
        die("✗ HATA: schema.sql dosyası okunamadı.\n");
    }

    $db->exec($sql);
    
    echo "✓ Veritabanı tabloları oluşturuldu.\n";
    echo "✓ Örnek veriler yüklendi.\n";
    
    // Database dosya izinlerini ayarla
    chmod($dbFile, 0666);
    
    echo "\n";
    echo "=================================\n";
    echo "  VERİTABANI HAZIR!\n";
    echo "=================================\n";
    echo "\n";
    echo "Test Kullanıcıları:\n";
    echo "-------------------\n";
    echo "Admin:\n";
    echo "  Email: admin@biletgo.com\n";
    echo "  Şifre: password\n";
    echo "\n";
    echo "Firma Admin (Metro Turizm):\n";
    echo "  Email: firma@metro.com\n";
    echo "  Şifre: password\n";
    echo "\n";
    echo "Normal Kullanıcı:\n";
    echo "  Email: test@test.com\n";
    echo "  Şifre: password\n";
    echo "  Bakiye: 1.000 ₺\n";
    echo "\n";
    
} catch (PDOException $e) {
    die("✗ HATA: " . $e->getMessage() . "\n");
}
