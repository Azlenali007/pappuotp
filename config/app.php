<?php
/**
 * Application Bootstrapper & Environment Config
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

// Safe Session Settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Fetch application setting from database with caching
 */
function get_setting(string $key, ?string $default = null): ?string {
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $db = get_db();
            $stmt = $db->query("SELECT `key_name`, `value` FROM `application_settings`");
            while ($row = $stmt->fetch()) {
                $settings[$row['key_name']] = $row['value'];
            }
        } catch (Exception $e) {
            // Table might not exist yet during migration
        }
    }
    return $settings[$key] ?? $default;
}

/**
 * Format currency with symbol
 */
function format_currency(float|int|string $amount): string {
    $sym = get_setting('currency_symbol', DEFAULT_CURRENCY_SYMBOL);
    return $sym . ' ' . number_format((float)$amount, 2);
}

/**
 * Return current base url
 */
function base_url(string $path = ''): string {
    $path = ltrim($path, '/');
    return '/' . $path;
}
