-- =============================================
-- OCARANG CARENDERIA SELF ORDERING SYSTEM
-- Database Setup Script
-- =============================================

CREATE DATABASE IF NOT EXISTS ocarang_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ocarang_db;

-- =============================================
-- TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS admin (
    admin_id VARCHAR(50) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS menu_item (
    menu_id VARCHAR(50) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    admin_id VARCHAR(50) NULL,
    CONSTRAINT fk_menu_admin FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS orders (
    order_id VARCHAR(50) NOT NULL PRIMARY KEY,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    admin_id VARCHAR(50) NULL,
    CONSTRAINT fk_order_admin FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_item (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    menu_id VARCHAR(50) NOT NULL,
    quantity INT(11) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_orderitem_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_orderitem_menu FOREIGN KEY (menu_id) REFERENCES menu_item(menu_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment (
    payment_id VARCHAR(50) NOT NULL PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    order_id VARCHAR(50) NULL,
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- =============================================
-- TRIGGERS
-- =============================================

DELIMITER $$

-- B.1 Auto fill unit price from menu_item
DROP TRIGGER IF EXISTS trg_autofill_unit_price$$
CREATE TRIGGER trg_autofill_unit_price
BEFORE INSERT ON order_item
FOR EACH ROW
BEGIN
    DECLARE v_price DECIMAL(10,2);
    SELECT current_price INTO v_price FROM menu_item WHERE menu_id = NEW.menu_id;
    SET NEW.unit_price = v_price;
END$$

-- B.2 Update Order Status after payment inserted
DROP TRIGGER IF EXISTS trg_update_order_status$$
CREATE TRIGGER trg_update_order_status
AFTER INSERT ON payment
FOR EACH ROW
BEGIN
    UPDATE orders SET status = 'Completed' WHERE order_id = NEW.order_id;
END$$

-- B.3 Check if menu item is available before inserting order_item
DROP TRIGGER IF EXISTS trg_check_item_availability$$
CREATE TRIGGER trg_check_item_availability
BEFORE INSERT ON order_item
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(50);
    SELECT status INTO v_status FROM menu_item WHERE menu_id = NEW.menu_id;
    IF v_status != 'Available' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Menu item is not available.';
    END IF;
END$$

DELIMITER ;

-- =============================================
-- VIEWS
-- =============================================

CREATE OR REPLACE VIEW v_customer_menu AS
SELECT menu_id, name AS item_name, current_price AS price
FROM menu_item
WHERE status = 'Available';

CREATE OR REPLACE VIEW v_daily_revenue AS
SELECT
    payment_date,
    COUNT(*) AS total_transactions,
    SUM(amount) AS total_revenue
FROM payment
GROUP BY payment_date;

CREATE OR REPLACE VIEW v_order_receipt AS
SELECT
    o.order_id,
    o.status AS order_status,
    mi.name AS item_name,
    oi.quantity,
    oi.unit_price,
    (oi.quantity * oi.unit_price) AS line_total
FROM orders o
JOIN order_item oi ON o.order_id = oi.order_id
JOIN menu_item mi ON oi.menu_id = mi.menu_id;

-- =============================================
-- SEED DATA
-- =============================================

INSERT IGNORE INTO admin (admin_id, name, role) VALUES
('ADM001', 'Admin', 'admin'),
('STF001', 'Staff One', 'staff');

INSERT IGNORE INTO menu_item (menu_id, name, current_price, status, admin_id) VALUES
('MNU001', 'Adobo', 45.00, 'Available', 'ADM001'),
('MNU002', 'Sinigang', 55.00, 'Available', 'ADM001'),
('MNU003', 'Tinola', 50.00, 'Available', 'ADM001'),
('MNU004', 'Lechon Kawali', 60.00, 'Available', 'ADM001'),
('MNU005', 'Pakbet', 40.00, 'Available', 'ADM001'),
('MNU006', 'Menudo', 45.00, 'Available', 'ADM001'),
('MNU007', 'Dinuguan', 40.00, 'Unavailable', 'ADM001'),
('MNU008', 'Steamed Rice', 15.00, 'Available', 'ADM001');
