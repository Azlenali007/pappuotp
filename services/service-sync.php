<?php
/**
 * Service & Stock Synchronization Worker
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../providers/custom-provider.php';

class ServiceSync {
    public static function syncAll(): array {
        $db = get_db();
        $providers = $db->query("SELECT * FROM providers WHERE status = 'active'")->fetchAll();
        $synced = 0;

        foreach ($providers as $prv) {
            $adapter = new CustomProviderAdapter($prv['api_url'], $prv['api_key']);
            $balance = $adapter->getBalance();

            // Update provider balance and last_sync
            $stmt = $db->prepare("UPDATE providers SET balance = ?, last_sync = NOW() WHERE id = ?");
            $stmt->execute([$balance, $prv['id']]);
            $synced++;
        }

        return ['success' => true, 'synced_providers' => $synced];
    }
}
