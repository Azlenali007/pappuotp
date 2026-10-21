<?php
/**
 * Process: Save Final Configuration
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/installer-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token invalid or expired.']);
    exit;
}

$dbConfig = $_SESSION['installer_db'] ?? null;
if (!$dbConfig) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Database configuration missing.']);
    exit;
}

$siteName = trim($_POST['site_name'] ?? 'OTP Rocket');
$appUrl = trim($_POST['app_url'] ?? 'http://127.0.0.1:3000');
$timezone = trim($_POST['timezone'] ?? 'UTC');
$currencyCode = trim($_POST['currency_code'] ?? 'INR');
$currencySymbol = trim($_POST['currency_symbol'] ?? '₹');
$orderTimeout = (int)($_POST['order_timeout_minutes'] ?? 20);
$profitMargin = (float)($_POST['default_profit_markup_percent'] ?? 25.0);

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $siteSettings = [
        'site_name' => $siteName,
        'app_name' => $siteName,
        'app_url' => $appUrl,
        'timezone' => $timezone,
        'currency_code' => $currencyCode,
        'currency_symbol' => $currencySymbol,
        'order_timeout_minutes' => $orderTimeout,
        'default_profit_markup_percent' => $profitMargin,
    ];

    $result = save_installer_configuration($pdo, $dbConfig, $siteSettings);

    if ($result['success']) {
        $_SESSION['installer_config'] = $siteSettings;
        echo json_encode(['success' => true, 'message' => 'Configuration saved successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to save configuration.']);
    }
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
