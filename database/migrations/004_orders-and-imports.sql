-- Migration 004: Orders, Order Status History, and Service Import Logs
-- Description: Tracking active virtual number allocations, verification codes, and provider synchronization logs

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_ref` VARCHAR(64) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `country_code` VARCHAR(10) NOT NULL,
  `phone_number` VARCHAR(50) NOT NULL,
  `provider_id` INT NOT NULL,
  `provider_order_id` VARCHAR(100) NULL,
  `cost` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled', 'expired', 'failed') NOT NULL DEFAULT 'pending',
  `otp_code` VARCHAR(50) NULL,
  `full_sms` TEXT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `import_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `provider_id` INT NOT NULL,
  `total_fetched` INT DEFAULT 0,
  `imported` INT DEFAULT 0,
  `updated` INT DEFAULT 0,
  `skipped` INT DEFAULT 0,
  `failed` INT DEFAULT 0,
  `status` ENUM('success', 'partial', 'failed') DEFAULT 'success',
  `log_details` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
