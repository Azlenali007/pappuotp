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
                $diag = $adapter->testConnection();
                $balance = (float)($diag['balance'] ?? 0.00);

                // Update provider balance and last_sync
                $stmt = $db->prepare("UPDATE providers SET balance = ?, last_sync = NOW() WHERE id = ?");
                $stmt->execute([$balance, $prv['id']]);

                // Sync services if provider is connected
                if (!empty($diag['connected'])) {
                    $services = $diag['services'] ?? [];
                    foreach ($services as $srv) {
                        $code = trim($srv['code'] ?? '');
                        $name = trim($srv['name'] ?? $code);
                        $cost = (float)($srv['cost'] ?? 0.50);
                        if (!empty($code) && !empty($name)) {
                            // Find or create service
                            $stmtFind = $db->prepare("SELECT id FROM services WHERE code = ? LIMIT 1");
                            $stmtFind->execute([$code]);
                            $serviceId = $stmtFind->fetchColumn();

                            if (!$serviceId) {
                                $stmtIns = $db->prepare("INSERT INTO services (name, code, price, provider_cost, status, stock_status) VALUES (?, ?, ?, ?, 'active', 'available')");
                                $stmtIns->execute([$name, $code, round($cost * 1.35, 2), $cost]);
                                $serviceId = (int)$db->lastInsertId();
                            }

                            // Record provider mapping
                            $stmtPs = $db->prepare("
                                INSERT INTO provider_service_mappings 
                                (provider_id, service_id, provider_service_code, provider_country_code, provider_cost, is_available, last_checked) 
                                VALUES (?, ?, ?, 'IN', ?, 1, NOW()) 
                                ON DUPLICATE KEY UPDATE 
                                    provider_cost = VALUES(provider_cost), 
                                    is_available = 1, 
                                    last_checked = NOW()
                            ");
                            $stmtPs->execute([$prv['id'], $serviceId, $code, $cost]);
                        }
                    }
                } else if (!empty($diag['error'])) {
                    $errors[] = "Provider {$prv['name']}: " . $diag['error'];
                }

                $synced++;
            } catch (\Throwable $e) {
                $errors[] = "Provider {$prv['name']}: " . $e->getMessage();
                error_log("ServiceSync error for provider {$prv['id']}: " . $e->getMessage());
            }
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
