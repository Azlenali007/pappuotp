<?php
/**
 * Cron: Expire Stale Orders & Issue Instant Refunds
 * CLI usage: php cron/cleanup-expired.php
 */
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'cron_secret_otp_rocket')) {
    http_response_code(403);
    die("Access denied.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../services/order-manager.php';

$lockFile = __DIR__ . '/../storage/cleanup_expired.lock';
$fp = fopen($lockFile, 'w+');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Order cleanup already running, skipping.\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Expiring stale orders...\n";

$db = getDB();
$stmt = $db->query("
    SELECT id, user_id, order_ref, price
    FROM orders
    WHERE status IN ('pending', 'waiting')
      AND expires_at < NOW()
    ORDER BY id ASC
    LIMIT 100
");
$expiredOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderManager = new OrderManager();
$expiredCount = 0;

foreach ($expiredOrders as $eo) {
    $res = $orderManager->cancelOrder($eo['id'], $eo['user_id'], 'Order expired after 20 minutes without SMS arrival');
    if ($res['success']) {
        $expiredCount++;
        echo "[" . date('Y-m-d H:i:s') . "] Expired and refunded order #{$eo['id']} ({$eo['order_ref']})\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Completed cleanup: {$expiredCount} orders expired and refunded.\n";

flock($fp, LOCK_UN);
fclose($fp);
