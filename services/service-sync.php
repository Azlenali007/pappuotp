<?php
/**
 * Service & Stock Synchronization Worker
 * Queries real carrier provider endpoints to synchronize balance, availability, and services
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../providers/custom-provider.php';

class ServiceSync {
    /**
     * Instance wrapper called by cron/sync-services.php
     */
    public function syncAllActiveProviders(): array {
        return self::syncAll();
    }

    /**
     * Synchronize all active providers
     */
    public static function syncAll(): array {
        $db = get_db();
        $providers = $db->query("SELECT * FROM providers WHERE status = 'active'")->fetchAll();
        $synced = 0;
        $errors = [];

        foreach ($providers as $prv) {
            try {
                $adapter = new CustomProviderAdapter($prv['api_url'], $prv['api_key'], 10);
                $balance = $adapter->getBalance();

                // Update provider balance and last_sync
                $stmt = $db->prepare("UPDATE providers SET balance = ?, last_sync = NOW() WHERE id = ?");
                $stmt->execute([$balance, $prv['id']]);

                // Try fetching services
                $services = $adapter->getServices();
                if (!empty($services) && is_array($services)) {
                    foreach ($services as $srv) {
                        $code = sanitize_string($srv['code'] ?? '');
                        $name = sanitize_string($srv['name'] ?? '');
                        $cost = (float)($srv['cost'] ?? 0);
                        if (!empty($code) && !empty($name)) {
                            // Find or create service
                            $stmtFind = $db->prepare("SELECT id FROM services WHERE code = ? LIMIT 1");
                            $stmtFind->execute([$code]);
                            $serviceId = $stmtFind->fetchColumn();

                            if (!$serviceId) {
                                $stmtIns = $db->prepare("INSERT INTO services (code, name, category, price, status) VALUES (?, ?, 'general', ?, 'active')");
                                $stmtIns->execute([$code, $name, round($cost * 1.3, 2)]);
                                $serviceId = (int)$db->lastInsertId();
                            }

                            // Record provider_service relationship
                            $stmtPs = $db->prepare("
                                INSERT INTO provider_services (provider_id, service_id, provider_cost, stock_status, last_checked) 
                                VALUES (?, ?, ?, 'available', NOW()) 
                                ON DUPLICATE KEY UPDATE provider_cost = VALUES(provider_cost), stock_status = 'available', last_checked = NOW()
                            ");
                            $stmtPs->execute([$prv['id'], $serviceId, $cost]);
                        }
                    }
                }

                $synced++;
            } catch (\Throwable $e) {
                $errors[] = "Provider {$prv['name']}: " . $e->getMessage();
                error_log("ServiceSync error for provider {$prv['id']}: " . $e->getMessage());
            }
        }

        // Record sync log if table exists
        try {
            $stmtLog = $db->prepare("
                INSERT INTO sync_logs (provider_id, sync_type, status, details, created_at) 
                VALUES (NULL, 'services_and_stock', ?, ?, NOW())
            ");
            $logStatus = empty($errors) ? 'success' : (count($errors) < count($providers) ? 'partial' : 'failed');
            $stmtLog->execute([$logStatus, json_encode(['synced' => $synced, 'errors' => $errors])]);
        } catch (\Throwable $e) {
            // sync_logs optional
        }

        return [
            'success' => true,
            'synced_providers' => $synced,
            'total_active' => count($providers),
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
