-- Migration 003: Providers, Services, Categories, and Countries
-- Description: Core catalogue tables and provider mappings

CREATE TABLE IF NOT EXISTS `providers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `api_url` VARCHAR(255) NOT NULL,
  `api_key` VARCHAR(255) NULL,
  `balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `settings_json` TEXT NULL,
  `last_sync` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `category_id` INT NULL,
  `icon` VARCHAR(50) DEFAULT 'shield',
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
  `provider_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.50,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `description` TEXT NULL,
  `stock_status` ENUM('available', 'low_stock', 'out_of_stock', 'unknown') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
