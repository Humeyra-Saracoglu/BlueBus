PRAGMA foreign_keys = ON;

-- Firms Tablosu
CREATE TABLE IF NOT EXISTS firms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT,
  phone TEXT,
  address TEXT,
  logo_url TEXT,
  active INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Users Tablosu
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ad TEXT NOT NULL,
    soyad TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    telefon TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'USER' CHECK(role IN ('USER','FIRM_ADMIN','ADMIN')),
    firm_id INTEGER DEFAULT NULL,
    credit_cents INTEGER DEFAULT 100000,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE SET NULL
);

-- Routes (Journeys) Tablosu
CREATE TABLE IF NOT EXISTS routes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  firm_id INTEGER NOT NULL,
  origin TEXT NOT NULL,
  destination TEXT NOT NULL,
  depart_at DATETIME NOT NULL,
  price_cents INTEGER NOT NULL,
  seat_count INTEGER NOT NULL CHECK (seat_count BETWEEN 10 AND 64),
  bus_type TEXT DEFAULT '2+2' CHECK(bus_type IN ('2+2', '2+1')),
  duration_minutes INTEGER DEFAULT 360,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
);

-- Tickets Tablosu
CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    route_id INTEGER NOT NULL,
    seat_no INTEGER NOT NULL,
    price_paid_cents INTEGER NOT NULL,
    coupon_code TEXT DEFAULT NULL,
    status TEXT DEFAULT 'ACTIVE' CHECK(status IN ('ACTIVE', 'CANCELLED', 'COMPLETED')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Wallet Transactions Tablosu
CREATE TABLE IF NOT EXISTS wallet_tx (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  amount_cents INTEGER NOT NULL,
  reason TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Coupons Tablosu
CREATE TABLE IF NOT EXISTS coupons (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE,
  percent INTEGER NOT NULL CHECK (percent BETWEEN 1 AND 100),
  firm_id INTEGER DEFAULT NULL,
  usage_limit INTEGER DEFAULT NULL,
  used_count INTEGER NOT NULL DEFAULT 0,
  expires_at DATETIME DEFAULT NULL,
  active INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code);

-- Coupon Usages Tablosu
CREATE TABLE IF NOT EXISTS coupon_usages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  coupon_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  ticket_id INTEGER NOT NULL,
  discount_amount_cents INTEGER NOT NULL,
  used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

-- Indexes for Performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_routes_firm ON routes(firm_id);
CREATE INDEX IF NOT EXISTS idx_routes_depart ON routes(depart_at);
CREATE INDEX IF NOT EXISTS idx_tickets_user ON tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_route ON tickets(route_id);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status);

-- Örnek Firmalar (Önce firmalar eklenmeli - foreign key için)
INSERT OR IGNORE INTO firms (id, name, email, phone, active) VALUES 
(1, 'Metro Turizm', 'info@metro.com', '08501234567', 1),
(2, 'Pamukkale Turizm', 'info@pamukkale.com', '08502345678', 1),
(3, 'Kamil Koç', 'info@kamilkoc.com', '08503456789', 1);

-- Varsayılan Admin Kullanıcı Ekle
INSERT OR IGNORE INTO users (ad, soyad, email, telefon, password, role, firm_id, credit_cents) 
VALUES ('Admin', 'User', 'admin@biletgo.com', '05001234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN', NULL, 0);

-- Firma Admin Kullanıcı (Metro Turizm için)
INSERT OR IGNORE INTO users (ad, soyad, email, telefon, password, role, firm_id, credit_cents) 
VALUES ('Firma', 'Admin', 'firma@metro.com', '05009876543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'FIRM_ADMIN', 1, 0);

-- Test Kullanıcı (Normal kullanıcı, 1000₺ bakiye)
INSERT OR IGNORE INTO users (ad, soyad, email, telefon, password, role, firm_id, credit_cents) 
VALUES ('Test', 'Kullanıcı', 'test@test.com', '05001111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'USER', NULL, 100000);

-- Örnek Seferler (Routes) - Her gün sefer var, 30 gün için
-- Bus types: 2+2 (normal) ve 2+1 (premium with single seats)
INSERT OR IGNORE INTO routes (firm_id, origin, destination, depart_at, price_cents, seat_count, bus_type, duration_minutes) VALUES
-- Gün 1
(1, 'İstanbul', 'Ankara', datetime('now', '+1 day', '09:00'), 25000, 40, '2+2', 330),
(1, 'İstanbul', 'Ankara', datetime('now', '+1 day', '14:00'), 27000, 40, '2+2', 330),
(2, 'İstanbul', 'İzmir', datetime('now', '+1 day', '10:00'), 28000, 35, '2+1', 480),
(3, 'Ankara', 'Antalya', datetime('now', '+1 day', '15:00'), 30000, 40, '2+2', 540),
-- Gün 2
(1, 'İstanbul', 'Ankara', datetime('now', '+2 days', '09:00'), 25000, 40, '2+2', 330),
(2, 'İstanbul', 'İzmir', datetime('now', '+2 days', '11:00'), 28000, 35, '2+1', 480),
(3, 'Ankara', 'İzmir', datetime('now', '+2 days', '13:00'), 26000, 40, '2+2', 420),
-- Gün 3
(1, 'İstanbul', 'Ankara', datetime('now', '+3 days', '08:00'), 25000, 40, '2+2', 330),
(2, 'İstanbul', 'Bursa', datetime('now', '+3 days', '10:00'), 15000, 30, '2+1', 180),
(3, 'Ankara', 'Antalya', datetime('now', '+3 days', '14:00'), 30000, 40, '2+2', 540),
-- Gün 4
(1, 'İstanbul', 'Ankara', datetime('now', '+4 days', '09:30'), 25000, 40, '2+2', 330),
(2, 'İzmir', 'Ankara', datetime('now', '+4 days', '12:00'), 26000, 35, '2+1', 420),
(3, 'Bursa', 'Antalya', datetime('now', '+4 days', '16:00'), 32000, 40, '2+2', 600),
-- Gün 5
(1, 'İstanbul', 'Ankara', datetime('now', '+5 days', '07:00'), 25000, 40, '2+2', 330),
(2, 'İstanbul', 'İzmir', datetime('now', '+5 days', '09:00'), 28000, 35, '2+1', 480),
(3, 'Ankara', 'İstanbul', datetime('now', '+5 days', '18:00'), 25000, 40, '2+2', 330),
-- Gün 6-10
(1, 'İstanbul', 'Ankara', datetime('now', '+6 days', '10:00'), 25000, 40, '2+2', 330),
(2, 'İstanbul', 'İzmir', datetime('now', '+7 days', '11:00'), 28000, 35, '2+1', 480),
(1, 'İstanbul', 'Ankara', datetime('now', '+8 days', '09:00'), 25000, 40, '2+2', 330),
(3, 'Ankara', 'Antalya', datetime('now', '+9 days', '14:00'), 30000, 40, '2+2', 540),
(2, 'İstanbul', 'Bursa', datetime('now', '+10 days', '08:00'), 15000, 30, '2+1', 180),
-- Gün 11-20
(1, 'İstanbul', 'Ankara', datetime('now', '+11 days', '09:00'), 25000, 40, '2+2', 330),
(2, 'İstanbul', 'İzmir', datetime('now', '+12 days', '10:00'), 28000, 35, '2+1', 480),
(1, 'İstanbul', 'Ankara', datetime('now', '+13 days', '14:00'), 27000, 40, '2+2', 330),
(3, 'Ankara', 'Antalya', datetime('now', '+14 days', '15:00'), 30000, 40, '2+2', 540),
(2, 'İzmir', 'Ankara', datetime('now', '+15 days', '11:00'), 26000, 35, '2+1', 420),
(1, 'İstanbul', 'Ankara', datetime('now', '+16 days', '08:00'), 25000, 40, '2+2', 330),
(3, 'Bursa', 'Antalya', datetime('now', '+17 days', '16:00'), 32000, 40, '2+2', 600),
(2, 'İstanbul', 'Bursa', datetime('now', '+18 days', '09:00'), 15000, 30, '2+1', 180),
(1, 'İstanbul', 'Ankara', datetime('now', '+19 days', '10:00'), 25000, 40, '2+2', 330),
(3, 'Ankara', 'İzmir', datetime('now', '+20 days', '13:00'), 26000, 40, '2+2', 420),
-- Gün 21-30
(1, 'İstanbul', 'Ankara', datetime('now', '+21 days', '09:00'), 25000, 40, '2+2', 330),
(2, 'İstanbul', 'İzmir', datetime('now', '+22 days', '11:00'), 28000, 35, '2+1', 480),
(1, 'İstanbul', 'Ankara', datetime('now', '+23 days', '14:00'), 27000, 40, '2+2', 330),
(3, 'Ankara', 'Antalya', datetime('now', '+24 days', '15:00'), 30000, 40, '2+2', 540),
(2, 'İzmir', 'Ankara', datetime('now', '+25 days', '12:00'), 26000, 35, '2+1', 420),
(1, 'İstanbul', 'Ankara', datetime('now', '+26 days', '08:00'), 25000, 40, '2+2', 330),
(3, 'Bursa', 'Antalya', datetime('now', '+27 days', '16:00'), 32000, 40, '2+2', 600),
(2, 'İstanbul', 'Bursa', datetime('now', '+28 days', '09:00'), 15000, 30, '2+1', 180),
(1, 'İstanbul', 'Ankara', datetime('now', '+29 days', '10:00'), 25000, 40, '2+2', 330),
(3, 'Ankara', 'İstanbul', datetime('now', '+30 days', '18:00'), 25000, 40, '2+2', 330);

-- İlk birkaç seferde bazı koltuklar satılmış olsun (test amaçlı)
-- İlk sefer (route_id=1) - İstanbul-Ankara için bazı dolu koltuklar
INSERT OR IGNORE INTO tickets (user_id, route_id, seat_no, price_paid_cents, status, created_at) VALUES
(3, 1, 1, 25000, 'ACTIVE', datetime('now', '-1 hour')),
(3, 1, 4, 25000, 'ACTIVE', datetime('now', '-1 hour')),
(3, 1, 11, 25000, 'ACTIVE', datetime('now', '-1 hour')),
(3, 1, 14, 25000, 'ACTIVE', datetime('now', '-1 hour')),
(3, 1, 21, 25000, 'ACTIVE', datetime('now', '-2 hours')),
(3, 1, 22, 25000, 'ACTIVE', datetime('now', '-2 hours'));

-- İkinci sefer (route_id=2) için bazı dolu koltuklar
INSERT OR IGNORE INTO tickets (user_id, route_id, seat_no, price_paid_cents, status, created_at) VALUES
(3, 2, 3, 27000, 'ACTIVE', datetime('now', '-3 hours')),
(3, 2, 7, 27000, 'ACTIVE', datetime('now', '-3 hours')),
(3, 2, 15, 27000, 'ACTIVE', datetime('now', '-4 hours'));

-- Üçüncü sefer (route_id=3) - 2+1 düzen için bazı dolu koltuklar
INSERT OR IGNORE INTO tickets (user_id, route_id, seat_no, price_paid_cents, status, created_at) VALUES
(3, 3, 2, 28000, 'ACTIVE', datetime('now', '-5 hours')),
(3, 3, 5, 28000, 'ACTIVE', datetime('now', '-5 hours')),
(3, 3, 8, 28000, 'ACTIVE', datetime('now', '-6 hours')),
(3, 3, 11, 28000, 'ACTIVE', datetime('now', '-6 hours'));
