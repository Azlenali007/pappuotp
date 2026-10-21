<?php
/**
 * Cron: Sync Services & Stock from Providers
 * CLI usage: php cron/sync-services.php
 */
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'cron_secret_otp_rocket')) {
    http_response_code(403);
    die("Access denied.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../services/service-sync.php';

$lockFile = __DIR__ . '/../storage/sync_services.lock';
$fp = fopen($lockFile, 'w+');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Sync already running, skipping.\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Starting service & stock synchronization...\n";

try {
    $syncer = new ServiceSync();
    $result = $syncer->syncAllActiveProviders();
    echo "[" . date('Y-m-d H:i:s') . "] Sync completed successfully: " . json_encode($result) . "\n";
} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Sync error: " . $e->getMessage() . "\n";
}

flock($fp, LOCK_UN);
fclose($fp);
