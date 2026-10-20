CREATE DATABASE IF NOT EXISTS washflow
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE washflow;

SET time_zone = '+08:00';

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  phone_digits VARCHAR(20),
  address VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customers_name (name),
  INDEX idx_customers_phone_digits (phone_digits)
) ENGINE=InnoDB;

CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  pricing_type ENUM('per_kg','per_piece') NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_no VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  status ENUM('pending','washing','drying','ready','claimed') NOT NULL DEFAULT 'pending',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ready_at DATETIME NULL,
  claimed_at DATETIME NULL,
  is_void TINYINT(1) NOT NULL DEFAULT 0,
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  voided_by INT NULL,
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at),
  INDEX idx_orders_customer (customer_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (voided_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  service_id INT NOT NULL,
  quantity DECIMAL(8,2) NOT NULL,
  unit_price_snapshot DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method ENUM('cash','gcash','other') NOT NULL DEFAULT 'cash',
  reference_no VARCHAR(50) NULL,
  received_by INT NOT NULL,
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_void TINYINT(1) NOT NULL DEFAULT 0,
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  voided_by INT NULL,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_paid_at (paid_at),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (received_by) REFERENCES users(id),
  FOREIGN KEY (voided_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE tracking_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  ticket_no VARCHAR(30) NULL,
  was_success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tracking_ip_time (ip_address, attempted_at),
  INDEX idx_tracking_ticket_time (ticket_no, attempted_at)
) ENGINE=InnoDB;
