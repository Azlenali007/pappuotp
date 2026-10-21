<?php
/**
 * Cron: Reconcile Payments
 * CLI usage: php cron/reconcile-payments.php
 */
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'cron_secret_otp_rocket')) {
    http_response_code(403);
    die("Access denied.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$lockFile = __DIR__ . '/../storage/reconcile_payments.lock';
$fp = fopen($lockFile, 'w+');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Reconciliation already running, skipping.\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Reconciling pending payments...\n";

$db = getDB();
// Expire pending payments older than 24 hours
$stmt = $db->prepare("
    UPDATE payments
    SET status = 'expired'
    WHERE status = 'pending'
      AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$count = $stmt->rowCount();

echo "[" . date('Y-m-d H:i:s') . "] Expired {$count} stale pending payment sessions.\n";

flock($fp, LOCK_UN);
fclose($fp);
