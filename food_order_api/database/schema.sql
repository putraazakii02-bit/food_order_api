-- database/schema.sql
-- Struktur Database untuk FoodieGo API (Tugas Pemrograman Mobile - Pak Bonda)

CREATE DATABASE IF NOT EXISTS `food_order_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `food_order_db`;

-- 1. Table Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) DEFAULT '081234567890',
    `avatar_url` TEXT,
    `membership_tier` VARCHAR(50) DEFAULT 'GOLD',
    `points` INT DEFAULT 1250,
    `vouchers_count` INT DEFAULT 4,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table Categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon_name` VARCHAR(100) DEFAULT 'restaurant_menu',
    `color_hex` VARCHAR(20) DEFAULT '#FF5722',
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table Restaurants
CREATE TABLE IF NOT EXISTS `restaurants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `tagline` VARCHAR(255) DEFAULT 'Cita Rasa Nusantara Autentik',
    `image_url` TEXT NOT NULL,
    `rating` DECIMAL(3,2) DEFAULT 4.8,
    `reviews_count` INT DEFAULT 428,
    `distance` VARCHAR(50) DEFAULT '1.2 km',
    `delivery_time` VARCHAR(50) DEFAULT '15-20 min',
    `address` VARCHAR(255) DEFAULT 'Jl. Ketintang Baru No. 8, Surabaya',
    `phone` VARCHAR(30) DEFAULT '08123456789',
    `is_open` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table Foods (Menu Makanan/Minuman)
CREATE TABLE IF NOT EXISTS `foods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `restaurant_name` VARCHAR(150) NOT NULL,
    `image_url` TEXT NOT NULL,
    `price` INT NOT NULL,
    `original_price` INT DEFAULT NULL,
    `rating` DECIMAL(3,2) DEFAULT 4.8,
    `description` TEXT,
    `category` VARCHAR(100) NOT NULL,
    `delivery_time` VARCHAR(50) DEFAULT '15-25 min',
    `distance` VARCHAR(50) DEFAULT '1.5 km',
    `reviews_count` INT DEFAULT 180,
    `discount_badge` VARCHAR(50) DEFAULT NULL,
    `is_popular` TINYINT(1) DEFAULT 0,
    `is_available` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table Food Toppings
CREATE TABLE IF NOT EXISTS `food_toppings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `food_id` INT DEFAULT NULL, -- NULL jika topping berlaku umum
    `name` VARCHAR(100) NOT NULL,
    `price` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table Delivery Addresses
CREATE TABLE IF NOT EXISTS `addresses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `label` VARCHAR(50) DEFAULT 'Rumah',
    `recipient_name` VARCHAR(100) DEFAULT 'Ahmad Fauzan',
    `phone` VARCHAR(30) DEFAULT '081234567890',
    `full_address` TEXT NOT NULL,
    `notes` TEXT,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table Promo Banners
CREATE TABLE IF NOT EXISTS `banners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `subtitle` VARCHAR(255) DEFAULT '',
    `image_url` TEXT NOT NULL,
    `promo_code` VARCHAR(50) DEFAULT 'MAKANHEMAT',
    `action_url` VARCHAR(255) DEFAULT '',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Table Vouchers
CREATE TABLE IF NOT EXISTS `vouchers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(100) NOT NULL,
    `discount_amount` INT NOT NULL,
    `min_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Table Cart Items
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `spicy_level` VARCHAR(50) DEFAULT 'Sedang 🌶️',
    `toppings` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Table Orders
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `restaurant_id` INT NOT NULL,
    `address_id` INT DEFAULT NULL,
    `delivery_address_text` TEXT NOT NULL,
    `delivery_service` VARCHAR(100) DEFAULT 'Kurir Express Instan',
    `subtotal` INT NOT NULL,
    `discount_voucher` INT DEFAULT 0,
    `delivery_fee` INT DEFAULT 8000,
    `service_fee` INT DEFAULT 1000,
    `total_amount` INT NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'GoPay',
    `payment_status` VARCHAR(50) DEFAULT 'PAID',
    `order_status` VARCHAR(50) DEFAULT 'CONFIRMED',
    `notes` TEXT,
    `estimated_delivery_time` VARCHAR(50) DEFAULT '15-20 min',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Table Order Items
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `food_name` VARCHAR(150) NOT NULL,
    `food_price` INT NOT NULL,
    `quantity` INT NOT NULL,
    `spicy_level` VARCHAR(50) DEFAULT 'Sedang 🌶️',
    `toppings` TEXT,
    `subtotal_item` INT NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Table Order Status History (Tracking Steps)
CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `step` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `is_completed` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 0,
    `timestamp_text` VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
