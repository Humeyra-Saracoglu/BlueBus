## BiletGo

# 🎟️ Bilet Satın Alma Platformu

PHP + SQLite + Docker tabanlı bir otobüs bileti satın alma platformu.  
Kullanıcılar sefer arayıp koltuk seçebilir, cüzdan bakiyesiyle bilet satın alabilir, PDF çıktısını indirebilir ve iptal/iade işlemlerini yapabilir.  
Yöneticiler (Admin ve Firma Admin) rota ve kupon yönetimi yapabilir.

---

## 🚀 Özellikler

### 👤 Kullanıcı
- Üye olma, giriş/çıkış
- Sefer arama (kalkış, varış, tarih)
- Sefer detayı ve koltuk seçimi
- Cüzdandan ödeme (bakiye düşümü)
- Kupon indirimi (% oranlı)
- Cüzdan bakiyesi görüntüleme & yükleme
- Satın alınan biletlerin listesi
- Bilet iptali (≥ 1 saat kala)
- PDF bilet indirme (`tickets/download?id=`)

### 🏢 Firma Admin
- Sadece kendi firmasına ait seferleri görür.
- Sefer ekleme, silme, güncelleme.
- Firma bazlı kupon oluşturma.

### 🛠️ Admin
- Tüm firmalar üzerinde tam kontrol.
- Firma oluşturma ve firma admin atama.
- Kupon yönetimi (tüm firmalara açık kuponlar).
- Tüm rotaları görüntüleme ve silme.

---

## 🧱 Teknolojiler

| Katman | Teknoloji |
|--------|------------|
| Backend | PHP 8.3 |
| Veritabanı | SQLite |
| PDF | [FPDF](https://github.com/Setasign/FPDF#) |
| Sunucu | Apache |
| Ortam | Docker / Docker Compose |

---

## ⚙️ Kurulum (Docker)

### Gereksinimler
- Docker
- Docker Compose

### 1. Docker ile Başlatma

```bash
# Projeyi çalıştır
docker compose up -d --build

# Container'ı kontrol et
docker compose ps
```

### 2. Veritabanını İlklendir

```bash
# Container içinde init scriptini çalıştır
docker compose exec web php init.php
```

### 3. Uygulamayı Aç

Tarayıcıda şu adresi aç: 
👉 **http://localhost:8080**

## Test Kullanıcıları

| Rol | Email | Şifre | Bakiye |
|-----|-------|-------|--------|
| Admin | admin@biletgo.com | password | - |
| Firma Admin | firma@metro.com | password | - |
| Normal Kullanıcı | test@test.com | password | 1.000 ₺ |

## indirim kodları
Global Kuponlar:
- ✅ WELCOME10  → %10 indirim (100 kullanım)
- ✅ YENI20     → %20 indirim (50 kullanım)
- ✅ SİBERVATAN50    → %50 indirim (10 kullanım)

Firma Kuponları:
- ✅ METRO15    → %15 Metro Turizm
- ✅ METRO25    → %25 Metro Turizm
- ✅ PAMUKKALE10 → %10 Pamukkale
- ✅ KAMILKOC20  → %20 Kamil Koç

## Durdurma

```bash
# Container'ları durdur
docker compose down

# Container'ları durdur ve volume'leri sil
docker compose down -v
```

## Sorun Giderme

Veritabanı sorunları yaşıyorsan:

```bash
# Database dosyasını sil ve yeniden oluştur
rm -rf database/app.db
docker compose restart
docker compose exec web php init.php
```

**Foreign Key Hatası alıyorsan:** Veritabanını sıfırla (yukarıdaki komutları kullan)
