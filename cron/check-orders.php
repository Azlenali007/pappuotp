<?php
/**
 * Cron: Check Active Orders for Received OTPs
 * CLI usage: php cron/check-orders.php
 */
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'cron_secret_otp_rocket')) {
    http_response_code(403);
    die("Access denied.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../services/otp-manager.php';
require_once __DIR__ . '/../services/order-manager.php';

$lockFile = __DIR__ . '/../storage/check_orders.lock';
$fp = fopen($lockFile, 'w+');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Order check already running, skipping.\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Checking active orders...\n";

$db = getDB();
$stmt = $db->query("SELECT id, order_ref, user_id, provider_order_id FROM orders WHERE status IN ('pending', 'waiting') ORDER BY id ASC LIMIT 50");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$otpManager = new OtpManager();
$processed = 0;
$received = 0;

foreach ($orders as $o) {
    $processed++;
    $res = $otpManager->checkAndProcessOtp($o['id']);
    if ($res['received'] ?? false) {
        $received++;
        echo "[" . date('Y-m-d H:i:s') . "] Order #{$o['id']} received OTP!\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Checked {$processed} orders, {$received} received OTP.\n";

flock($fp, LOCK_UN);
fclose($fp);
