<?php
/**
 * Application Constants
 * OTP Services Platform
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// App Information
define('APP_NAME', 'OTP Services');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'Fast & Reliable OTP Services Marketplace');

// Environment & URLs
define('DEFAULT_CURRENCY', 'INR');
define('DEFAULT_CURRENCY_SYMBOL', '₹');

// Order & Session Rules
define('DEFAULT_ORDER_TIMEOUT_MINUTES', 20);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('MIN_DEPOSIT_AMOUNT', 100);
define('MAX_DEPOSIT_AMOUNT', 50000);

// Paths
define('PATH_LOGS', APP_ROOT . '/storage/logs');
define('PATH_CACHE', APP_ROOT . '/storage/cache');
define('PATH_EXPORTS', APP_ROOT . '/storage/exports');
define('PATH_UPLOADS', APP_ROOT . '/storage/private');
