<?php

function getDbConnection() {
    try {
        $dbPath = __DIR__ . '/../database/app.db';

        if (!file_exists($dbPath)) {
            $dbPath = '/var/www/html/database/app.db';
        }

        if (!file_exists($dbPath)) {
            error_log("Database file not found at: " . $dbPath);
            die("Veritabanı dosyası bulunamadı: " . $dbPath);
        }

        if (!is_writable(dirname($dbPath))) {
            error_log("Database directory not writable: " . dirname($dbPath));
            die("Veritabanı klasörüne yazma izni yok.");
        }
        
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
    }
}