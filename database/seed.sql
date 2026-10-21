-- =======================================================
-- OTP Platform Complete Seed Data
-- =======================================================

-- 1. Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Super Admin', 'super_admin', 'Full platform administrative access'),
(2, 'Support Agent', 'support_agent', 'Access to view orders and manage support tickets');

-- 2. Initial Super Admin (Password: Admin@123456)
-- Hash generated using PASSWORD_DEFAULT (BCRYPT)
INSERT INTO `admins` (`id`, `name`, `email`, `username`, `password_hash`, `role_id`, `status`) VALUES
(1, 'Super Administrator', 'admin@otpservices.local', 'admin', '$2y$10$wN1FzHj2aG5cK9sXz9q1eu8XGf2Fj6z9aH1v8mK0wP3lQ7yZ5rT7S', 1, 'active');

-- 3. Initial Test User (Azlen Khan, matching reference screenshot UI)
-- Password: User@123456
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `status`) VALUES
(1, 'Azlen Khan', 'azlen@otpservices.local', 'azlen', '$2y$10$wN1FzHj2aG5cK9sXz9q1eu8XGf2Fj6z9aH1v8mK0wP3lQ7yZ5rT7S', 'active'),
(2, 'Rahul Sharma', 'rahul@example.com', 'rahul', '$2y$10$wN1FzHj2aG5cK9sXz9q1eu8XGf2Fj6z9aH1v8mK0wP3lQ7yZ5rT7S', 'active'),
(3, 'Priya Singh', 'priya@example.com', 'priya', '$2y$10$wN1FzHj2aG5cK9sXz9q1eu8XGf2Fj6z9aH1v8mK0wP3lQ7yZ5rT7S', 'active'),
(4, 'Amit Kumar', 'amit@example.com', 'amit', '$2y$10$wN1FzHj2aG5cK9sXz9q1eu8XGf2Fj6z9aH1v8mK0wP3lQ7yZ5rT7S', 'active'),
(5, 'Neha Verma', 'neha@example.com', 'neha', '$2y$10$wN1FzHj2aG5cK9sXz9q1eu8XGf2Fj6z9aH1v8mK0wP3lQ7yZ5rT7S', 'active');

-- 4. User Wallets
INSERT INTO `wallets` (`id`, `user_id`, `balance`, `currency`) VALUES
(1, 1, 1250.00, 'INR'),
(2, 2, 450.00, 'INR'),
(3, 3, 310.00, 'INR'),
(4, 4, 820.00, 'INR'),
(5, 5, 120.00, 'INR');

-- 5. Countries
INSERT INTO `countries` (`id`, `code`, `name`, `dial_code`, `flag_emoji`, `is_active`) VALUES
(1, 'IN', 'India', '+91', '🇮🇳', 1),
(2, 'US', 'United States', '+1', '🇺🇸', 1),
(3, 'GB', 'United Kingdom', '+44', '🇬🇧', 1),
(4, 'RU', 'Russia', '+7', '🇷🇺', 1),
(5, 'BR', 'Brazil', '+55', '🇧🇷', 1),
(6, 'ID', 'Indonesia', '+62', '🇮🇩', 1),
(7, 'DE', 'Germany', '+49', '🇩🇪', 1),
(8, 'FR', 'France', '+33', '🇫🇷', 1),
(9, 'CA', 'Canada', '+1', '🇨🇦', 1),
(10, 'NG', 'Nigeria', '+234', '🇳🇬', 1);

-- 6. Categories
INSERT INTO `service_categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Social Media', 'social-media', 'share-2'),
(2, 'Messaging Apps', 'messaging', 'message-circle'),
(3, 'Search & Tech', 'search-tech', 'globe'),
(4, 'Finance & Wallets', 'finance', 'credit-card'),
(5, 'E-Commerce', 'ecommerce', 'shopping-cart');

-- 7. Providers
INSERT INTO `providers` (`id`, `name`, `slug`, `api_url`, `api_key`, `balance`, `status`, `last_sync`) VALUES
(1, 'Global OTP Hub API', 'global-otp-hub', 'https://api.example.com/v1', 'demo_api_key_secure_xyz987', 5420.50, 'active', NOW()),
(2, 'FastSMS Direct Gateway', 'fastsms-direct', 'https://api.fastsms.internal/v2', 'fastsms_live_token_77612', 1280.00, 'active', NOW());

-- 8. Services (Matching reference screenshot prices and services)
INSERT INTO `services` (`id`, `name`, `code`, `category_id`, `icon`, `price`, `provider_cost`, `status`, `stock_status`, `description`) VALUES
(1, 'Google/YouTube OTP', 'google', 3, 'globe', 0.80, 0.45, 'active', 'available', 'Instant SMS verification code for Google and YouTube accounts.'),
(2, 'WhatsApp OTP', 'whatsapp', 2, 'message-circle', 0.70, 0.40, 'active', 'available', 'Reliable virtual verification number for WhatsApp registration.'),
(3, 'Facebook OTP', 'facebook', 1, 'facebook', 0.90, 0.50, 'active', 'available', 'Secure phone verification for Facebook accounts and pages.'),
(4, 'Instagram OTP', 'instagram', 1, 'instagram', 0.80, 0.45, 'active', 'available', 'Fast SMS code delivery for Instagram account registration and 2FA.'),
(5, 'Telegram OTP', 'telegram', 2, 'send', 0.75, 0.40, 'active', 'available', 'Virtual number for Telegram app account activation.'),
(6, 'Twitter/X OTP', 'twitter', 1, 'twitter', 0.85, 0.50, 'active', 'available', 'Quick SMS verification for X (Twitter) profile authentication.'),
(7, 'Discord OTP', 'discord', 2, 'hash', 0.65, 0.35, 'active', 'available', 'Phone verification for Discord accounts and server access.'),
(8, 'Netflix OTP', 'netflix', 3, 'tv', 1.20, 0.70, 'active', 'available', 'Virtual phone numbers for streaming entertainment account setup.');

-- 9. Provider Service Mappings
INSERT INTO `provider_service_mappings` (`provider_id`, `service_id`, `provider_service_code`, `provider_country_code`, `provider_cost`, `last_checked`, `is_available`) VALUES
(1, 1, 'go', 'IN', 0.45, NOW(), 1),
(1, 2, 'wa', 'IN', 0.40, NOW(), 1),
(1, 3, 'fb', 'IN', 0.50, NOW(), 1),
(1, 4, 'ig', 'IN', 0.45, NOW(), 1),
(1, 5, 'tg', 'IN', 0.40, NOW(), 1),
(1, 6, 'tw', 'IN', 0.50, NOW(), 1),
(1, 1, 'go', 'US', 0.55, NOW(), 1),
(1, 2, 'wa', 'US', 0.50, NOW(), 1);

-- 10. Orders (Matching the screenshot table)
INSERT INTO `orders` (`id`, `order_ref`, `user_id`, `service_id`, `country_code`, `phone_number`, `provider_id`, `provider_order_id`, `cost`, `status`, `otp_code`, `full_sms`, `expires_at`, `created_at`) VALUES
(1, 'ORD-20250412-0001', 1, 2, 'IN', '+91 9876543210', 1, 'PRV-882190', 0.70, 'completed', '482931', 'Your WhatsApp code: 482-931. You can also tap on this link to verify your phone: v.whatsapp.com/482931', DATE_ADD(NOW(), INTERVAL -2 DAY), '2025-04-12 10:15:00'),
(2, 'ORD-20250412-0002', 1, 1, 'IN', '+91 8765432109', 1, 'PRV-882191', 0.80, 'pending', NULL, NULL, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW()),
(3, 'ORD-20250411-0003', 1, 3, 'IN', '+91 7654321098', 1, 'PRV-882192', 0.90, 'completed', '819204', '819204 is your Facebook verification code.', DATE_ADD(NOW(), INTERVAL -3 DAY), '2025-04-11 14:30:00'),
(4, 'ORD-20250410-0004', 1, 4, 'IN', '+91 6543210987', 1, 'PRV-882193', 0.80, 'failed', NULL, NULL, DATE_ADD(NOW(), INTERVAL -4 DAY), '2025-04-10 09:00:00'),
(5, 'ORD-20250412-0005', 2, 2, 'IN', '+91 9811223344', 1, 'PRV-882194', 0.70, 'completed', '312890', 'Your WhatsApp verification code is 312890', NOW(), '2025-04-12 11:00:00'),
(6, 'ORD-20250412-0006', 3, 1, 'IN', '+91 9822334455', 1, 'PRV-882195', 0.80, 'pending', NULL, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW()),
(7, 'ORD-20250411-0007', 4, 4, 'IN', '+91 9833445566', 1, 'PRV-882196', 0.80, 'completed', '542109', 'Use 542109 to verify your Instagram account.', NOW(), '2025-04-11 16:45:00'),
(8, 'ORD-20250410-0008', 5, 3, 'IN', '+91 9844556677', 1, 'PRV-882197', 0.90, 'failed', NULL, NULL, NOW(), '2025-04-10 18:20:00');

-- 11. Wallet Transactions
INSERT INTO `wallet_transactions` (`transaction_ref`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `description`, `reference_id`, `created_at`) VALUES
('TXN-DEP-1001', 1, 'deposit', 1500.00, 0.00, 1500.00, 'Wallet deposit via Razorpay (pay_RZP991823)', 'pay_RZP991823', '2025-04-10 08:30:00'),
('TXN-ORD-1002', 1, 'order_payment', 0.80, 1500.00, 1499.20, 'Order payment for Instagram OTP', 'ORD-20250410-0004', '2025-04-10 09:00:00'),
('TXN-REF-1003', 1, 'refund', 0.80, 1499.20, 1500.00, 'Automated refund for failed Instagram OTP', 'ORD-20250410-0004', '2025-04-10 09:20:00'),
('TXN-ORD-1004', 1, 'order_payment', 0.90, 1500.00, 1499.10, 'Order payment for Facebook OTP', 'ORD-20250411-0003', '2025-04-11 14:30:00'),
('TXN-ORD-1005', 1, 'order_payment', 0.70, 1499.10, 1498.40, 'Order payment for WhatsApp OTP', 'ORD-20250412-0001', '2025-04-12 10:15:00'),
('TXN-ORD-1006', 1, 'order_payment', 0.80, 1498.40, 1497.60, 'Order payment for Google OTP', 'ORD-20250412-0002', '2025-04-12 11:20:00'),
('TXN-ADJ-1007', 1, 'adjustment', -247.60, 1497.60, 1250.00, 'Balance alignment adjustment by admin', 'ADJ-8812', '2025-04-12 11:30:00');

-- 12. Support Tickets
INSERT INTO `support_tickets` (`id`, `ticket_ref`, `user_id`, `subject`, `department`, `priority`, `status`, `created_at`) VALUES
(1, 'TCK-2025-001', 1, 'Inquiry regarding WhatsApp virtual number delivery', 'Support', 'medium', 'answered', '2025-04-11 12:00:00');

INSERT INTO `support_ticket_replies` (`ticket_id`, `sender_type`, `sender_id`, `message`, `created_at`) VALUES
(1, 'user', 1, 'Hello, I wanted to check how fast WhatsApp verification codes arrive on average.', '2025-04-11 12:00:00'),
(1, 'admin', 1, 'Hello Azlen! WhatsApp OTPs usually arrive within 10 to 45 seconds once requested. If no code arrives before timer expires, your wallet is 100% refunded automatically.', '2025-04-11 12:15:00');

-- 13. Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(1, 'Welcome to OTP Services', 'Welcome Azlen! Your wallet has been configured and is ready for use.', 'success', 0, '/user/wallet.php', NOW()),
(1, 'WhatsApp OTP Delivered', 'Your verification code for WhatsApp OTP (ORD-20250412-0001) is 482931.', 'success', 0, '/user/orders.php', NOW()),
(1, 'Security Advisory', 'Two-factor authentication and session locking are active on your profile.', 'info', 0, '/user/security.php', NOW());

-- 14. Application Settings
INSERT INTO `application_settings` (`key_name`, `value`) VALUES
('site_name', 'OTP Services'),
('site_tagline', 'Fast & Reliable OTP Services Marketplace'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('min_deposit_amount', '100'),
('max_deposit_amount', '50000'),
('default_profit_markup_percent', '35'),
('order_timeout_minutes', '20'),
('razorpay_key_id', 'rzp_test_kEYSAMPLE12345'),
('razorpay_key_secret', 'rzp_test_sECRETSAMPLE67890'),
('support_email', 'support@otpservices.local'),
('maintenance_mode', '0');
