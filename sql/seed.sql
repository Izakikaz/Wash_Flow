USE washflow;
SET time_zone = '+08:00';

-- Development-only accounts. Both passwords must be changed at first login.
INSERT INTO users
  (id, full_name, username, password_hash, role, must_change_password)
VALUES
  (1, 'WashFlow Administrator', 'admin',
   '$2y$10$icHXs8NliFLADZSKRTh87OUNxaGjVJn/RNH/Od5gIyYGwlbYTdvya',
   'admin', 1),
  (2, 'WashFlow Staff', 'staff',
   '$2y$10$FLshv.zlP8KgpQzFjuNjDOHuIJVVQXO.CnBhIgncJm0R7Y/OHtfrG',
   'staff', 1);

INSERT INTO services (id, name, pricing_type, unit_price, is_active) VALUES
  (1, 'Wash and Fold', 'per_kg', 70.00, 1),
  (2, 'Wash and Iron', 'per_kg', 90.00, 1),
  (3, 'Premium Wash', 'per_kg', 120.00, 1),
  (4, 'Comforter Cleaning', 'per_piece', 250.00, 1),
  (5, 'Blanket Cleaning', 'per_piece', 180.00, 1),
  (6, 'Shirt Ironing', 'per_piece', 35.00, 1),
  (7, 'Dry Cleaning', 'per_piece', 200.00, 1),
  (8, 'Express Wash', 'per_kg', 100.00, 1);

INSERT INTO customers (id, name, phone, phone_digits, address) VALUES
  (1, 'Maria Santos', '0917-555-0101', '09175550101', 'Poblacion, Cebu City'),
  (2, 'Jose Reyes', '0928-555-0102', '09285550102', 'Basak, Lapu-Lapu City'),
  (3, 'Ana Cruz', '0906-555-0103', '09065550103', 'Lahug, Cebu City'),
  (4, 'Mark Garcia', '0918-555-0104', '09185550104', 'Mandaue City'),
  (5, 'Liza Mendoza', '0995-555-0105', '09955550105', 'Talamban, Cebu City'),
  (6, 'Carlo Dela Cruz', '0922-555-0106', '09225550106', 'Talisay City'),
  (7, 'Rina Flores', '0916-555-0107', '09165550107', 'Banilad, Cebu City'),
  (8, 'Paolo Ramos', '0935-555-0108', '09355550108', 'Cordova');

INSERT INTO orders
  (id, ticket_no, customer_id, status, total_amount, payment_status,
   created_by, created_at, ready_at, claimed_at, is_void, voided_at, void_reason, voided_by)
VALUES
  (1, CONCAT('WF-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-A2B3'), 1, 'pending', 350.00, 'unpaid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (2, CONCAT('WF-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-F4G5'), 2, 'washing', 500.00, 'partial', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (3, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-H6J7'), 3, 'drying', 590.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (4, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-K8M9'), 4, 'ready', 250.00, 'partial', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), NULL, 0, NULL, NULL, NULL),
  (5, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-N2P3'), 5, 'claimed', 285.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), NULL, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), 0, NULL, NULL, NULL),
  (6, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-Q4R5'), 6, 'claimed', 450.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), NULL, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), 0, NULL, NULL, NULL),
  (7, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '%Y%m%d'), '-S6T7'), 7, 'claimed', 180.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY), NULL, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL),
  (8, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '%Y%m%d'), '-U8V9'), 8, 'pending', 245.00, 'partial', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (9, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 4 DAY), '%Y%m%d'), '-W2X3'), 1, 'washing', 785.00, 'unpaid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (10, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 4 DAY), '%Y%m%d'), '-Y4Z5'), 2, 'drying', 155.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (11, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '%Y%m%d'), '-A6B7'), 3, 'ready', 460.00, 'unpaid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 DAY), DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 DAY), NULL, 0, NULL, NULL, NULL),
  (12, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '%Y%m%d'), '-C8D9'), 4, 'claimed', 500.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 DAY), NULL, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), 0, NULL, NULL, NULL),
  (13, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '%Y%m%d'), '-E2F3'), 5, 'pending', 620.00, 'unpaid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 DAY), NULL, NULL, 1, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), 'Duplicate order entered by mistake', 1),
  (14, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '%Y%m%d'), '-G4H5'), 6, 'washing', 360.00, 'partial', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (15, CONCAT('WF-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-J6K7'), 7, 'ready', 210.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 DAY), DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 DAY), NULL, 0, NULL, NULL, NULL),
  (16, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-M8N9'), 8, 'pending', 420.00, 'unpaid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (17, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-P2Q3'), 1, 'drying', 700.00, 'partial', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), NULL, NULL, 0, NULL, NULL, NULL),
  (18, CONCAT('WF-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '%Y%m%d'), '-R4S5'), 2, 'claimed', 315.00, 'paid', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY), NULL, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL);

INSERT INTO order_items (id, order_id, service_id, quantity, unit_price_snapshot, subtotal) VALUES
  (1, 1, 1, 5.00, 70.00, 350.00),
  (2, 2, 2, 4.00, 90.00, 360.00),
  (3, 2, 6, 4.00, 35.00, 140.00),
  (4, 3, 1, 1.00, 70.00, 70.00),
  (5, 3, 4, 1.00, 250.00, 250.00),
  (6, 3, 7, 1.00, 200.00, 200.00),
  (7, 3, 6, 2.00, 35.00, 70.00),
  (8, 4, 1, 1.00, 70.00, 70.00),
  (9, 4, 5, 1.00, 180.00, 180.00),
  (10, 5, 2, 2.00, 90.00, 180.00),
  (11, 5, 6, 3.00, 35.00, 105.00),
  (12, 6, 2, 3.00, 90.00, 270.00),
  (13, 6, 5, 1.00, 180.00, 180.00),
  (14, 7, 5, 1.00, 180.00, 180.00),
  (15, 8, 1, 2.50, 70.00, 175.00),
  (16, 8, 6, 2.00, 35.00, 70.00),
  (17, 9, 8, 5.00, 100.00, 500.00),
  (18, 9, 4, 1.00, 250.00, 250.00),
  (19, 9, 6, 1.00, 35.00, 35.00),
  (20, 10, 3, 1.00, 120.00, 120.00),
  (21, 10, 6, 1.00, 35.00, 35.00),
  (22, 11, 1, 3.00, 70.00, 210.00),
  (23, 11, 4, 1.00, 250.00, 250.00),
  (24, 12, 2, 2.00, 90.00, 180.00),
  (25, 12, 4, 1.00, 250.00, 250.00),
  (26, 12, 6, 2.00, 35.00, 70.00),
  (27, 13, 3, 2.00, 120.00, 240.00),
  (28, 13, 5, 1.00, 180.00, 180.00),
  (29, 13, 7, 1.00, 200.00, 200.00),
  (30, 14, 2, 2.00, 90.00, 180.00),
  (31, 14, 5, 1.00, 180.00, 180.00),
  (32, 15, 1, 3.00, 70.00, 210.00),
  (33, 16, 6, 6.00, 35.00, 210.00),
  (34, 16, 1, 3.00, 70.00, 210.00),
  (35, 17, 2, 5.00, 90.00, 450.00),
  (36, 17, 5, 1.00, 180.00, 180.00),
  (37, 17, 6, 2.00, 35.00, 70.00),
  (38, 18, 8, 1.00, 100.00, 100.00),
  (39, 18, 5, 1.00, 180.00, 180.00),
  (40, 18, 6, 1.00, 35.00, 35.00);

INSERT INTO payments
  (id, order_id, amount, method, reference_no, received_by, paid_at,
   is_void, voided_at, void_reason, voided_by)
VALUES
  (1, 2, 200.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 DAY), 0, NULL, NULL, NULL),
  (2, 3, 590.00, 'gcash', 'GC-ORDER-003', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), 0, NULL, NULL, NULL),
  (3, 4, 100.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY), 0, NULL, NULL, NULL),
  (4, 5, 285.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL),
  (5, 6, 450.00, 'gcash', 'GC-ORDER-006', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL),
  (6, 7, 180.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL),
  (7, 8, 100.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY), 0, NULL, NULL, NULL),
  (8, 10, 155.00, 'other', 'BANK-ORDER-010', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), 0, NULL, NULL, NULL),
  (9, 12, 500.00, 'gcash', 'GC-ORDER-012', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), 0, NULL, NULL, NULL),
  (10, 14, 150.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 DAY), 0, NULL, NULL, NULL),
  (11, 14, 50.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 DAY), 1, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY), 'Entered against the wrong order', 1),
  (12, 15, 210.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 DAY), 0, NULL, NULL, NULL),
  (13, 17, 300.00, 'gcash', 'GC-ORDER-017', 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL),
  (14, 18, 315.00, 'cash', NULL, 2, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY), 0, NULL, NULL, NULL);
