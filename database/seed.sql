INSERT INTO firms (name) VALUES ('BlueBus');

INSERT INTO users (name, email, password_hash, role, firm_id, credit_cents)
VALUES
('Sistem Admin', 'admin@bluebus.com', 'TO_BE_HASHED', 'ADMIN', NULL, 0),
('Firma Admin', 'firma@bluebus.com', 'TO_BE_HASHED', 'FIRM_ADMIN', 1, 0),
('Helin', 'helin@bluebus.com', 'TO_BE_HASHED', 'USER', NULL, 150000);

INSERT INTO routes (firm_id, origin, destination, depart_at, price_cents, seat_count)
VALUES
(1, 'Bursa', 'İstanbul', datetime('now','+1 day','12:00'), 25000, 40),
(1, 'Bursa', 'Ankara',  datetime('now','+2 day','09:00'), 35000, 44);

INSERT INTO coupons (code, firm_id, percent, usage_limit, expires_at)
VALUES
('INDIRIM10', NULL, 10, 100, datetime('now','+30 day'));
