-- =====================================================
-- Coffee Shop Management System - Database Schema
-- Import this file via phpMyAdmin (creates DB + tables)
-- =====================================================

CREATE DATABASE IF NOT EXISTS coffee_shop_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE coffee_shop_db;

-- ---------------------------------------------------
-- users
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- categories
-- ---------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- products
-- ---------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- gallery (shop / product / event / promotion images)
-- ---------------------------------------------------
CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT DEFAULT NULL,
    type ENUM('product', 'shop', 'event', 'promotion') NOT NULL DEFAULT 'shop',
    title VARCHAR(150) DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- cart
-- ---------------------------------------------------
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- promo_codes (admin-managed discount codes)
-- ---------------------------------------------------
CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    usage_limit INT DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- orders
-- ---------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled')
        NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'unpaid',
    order_type ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
    delivery_address TEXT DEFAULT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    promo_code_id INT DEFAULT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- order_details (line items, price snapshot at order time)
-- ---------------------------------------------------
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- payments (supports multiple attempts per order - dummy gateway)
-- ---------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    method ENUM('cash', 'online_banking', 'card') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'success', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- contact_messages
-- ---------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Seed data (safe to re-run test data on later, this is
-- just enough to unblock Menu/Cart phases)
-- =====================================================

INSERT INTO categories (category_name) VALUES
    ('Coffee'),
    ('Non-Coffee'),
    ('Food & Pastry');

INSERT INTO products (category_id, name, description, price, image, status) VALUES
    (1, 'Espresso', 'Rich and bold single shot of espresso.', 3.50, NULL, 'active'),
    (1, 'Cappuccino', 'Espresso with steamed milk and foam.', 4.50, NULL, 'active'),
    (1, 'Caffe Latte', 'Smooth espresso with steamed milk.', 4.80, NULL, 'active'),
    (2, 'Matcha Latte', 'Premium matcha with steamed milk.', 5.00, NULL, 'active'),
    (2, 'Hot Chocolate', 'Rich chocolate topped with whipped cream.', 4.20, NULL, 'active'),
    (3, 'Butter Croissant', 'Freshly baked flaky croissant.', 3.80, NULL, 'active'),
    (3, 'Blueberry Muffin', 'Soft muffin loaded with blueberries.', 3.50, NULL, 'active');

INSERT INTO promo_codes (code, discount_type, discount_value, usage_limit, expiry_date, status) VALUES
    ('WELCOME10', 'percentage', 10.00, NULL, NULL, 'active');

INSERT INTO gallery (product_id, type, title, image, description) VALUES
    (NULL, 'shop', 'Our Cozy Interior', 'shop-1.svg', 'Warm seating area at our main outlet.'),
    (NULL, 'shop', 'The Coffee Bar', 'shop-2.svg', 'Where every cup is crafted by hand.'),
    (NULL, 'event', 'Live Music Night', 'event-1.svg', 'Acoustic sessions every Friday evening.'),
    (NULL, 'promotion', 'Buy 1 Free 1 Mondays', 'promo-1.svg', 'Every coffee is buy-1-free-1 on Mondays.');

-- Note: no admin user is seeded here with a hardcoded password hash.
-- Create your first admin account by registering normally through
-- auth/register.php, then promoting that account to 'admin' via
-- phpMyAdmin (see the testing notes for auth/register.php).
