-- Migration 005: Admin Fixes, Settings, Audit Indexes, and Compatibility Views
-- Description: Ensures application_settings defaults, provider_services view compatibility, activity_logs performance indexes, and webhook_events table

-- 1. Webhook Events Table (Required by Platform Architecture)
CREATE TABLE IF NOT EXISTS `webhook_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` VARCHAR(100) NOT NULL UNIQUE,
  `provider` VARCHAR(50) NOT NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `payload` TEXT NOT NULL,
  `status` ENUM('received', 'processed', 'ignored', 'failed') DEFAULT 'received',
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_webhook_event_id` (`event_id`),
  INDEX `idx_webhook_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Indexes for Activity / Audit Logs
ALTER TABLE `activity_logs` ADD INDEX `idx_activity_logs_action` (`action`);
ALTER TABLE `activity_logs` ADD INDEX `idx_activity_logs_admin` (`admin_id`);
ALTER TABLE `activity_logs` ADD INDEX `idx_activity_logs_created` (`created_at`);

-- 3. Provider Services View (Compatibility with provider adapters and mapping queries)
CREATE OR REPLACE VIEW `provider_services` AS 
SELECT 
  `id`,
  `provider_id`,
  `service_id`,
  `provider_service_code`,
  `provider_country_code`,
  `provider_cost`,
  `last_checked`,
  `is_available`
FROM `provider_service_mappings`;

-- 4. Default Application Settings Seed
INSERT IGNORE INTO `application_settings` (`key_name`, `value`) VALUES
('site_name', 'OTP Services'),
('app_name', 'OTP Services'),
('site_tagline', 'Instant Virtual Number & SMS Verification'),
('app_tagline', 'Instant Virtual Number & SMS Verification'),
('support_email', 'support@otprocket.com'),
('support_telegram', '@otprocket_support'),
('currency_code', 'INR'),
('currency_symbol', '₹'),
('order_timeout_minutes', '20'),
('default_margin_pct', '25.0'),
('default_profit_markup_percent', '25.0'),
('min_deposit_amount', '50.00'),
('max_deposit_amount', '10000.00'),
('maintenance_mode', '0'),
('user_registration', '1'),
('razorpay_enabled', '1'),
('razorpay_key_id', 'rzp_test_mockkey12345'),
('razorpay_key_secret', 'mock_secret_abcdef123456'),
('razorpay_webhook_secret', 'whsec_test_123456'),
('upi_qr_enabled', '1'),
('upi_id', 'otprocket@upi'),
('upi_merchant_name', 'OTP Services Merchant'),
('deposit_instructions', 'Scan the official QR code using any UPI app (GPay, PhonePe, Paytm). Enter the UTR transaction reference number after completing payment.'),
('login_max_attempts', '5'),
('login_lockout_minutes', '15'),
('session_lifetime_minutes', '120'),
('force_ssl', '0'),
('api_rate_limit_per_min', '60'),
('admin_ip_whitelist', ''),
('password_min_length', '8'),
('require_strong_passwords', '1');
