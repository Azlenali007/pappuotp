<?php
/**
 * Service Importer Service
 * Handles provider service fetching, duplicate detection, and catalogue importing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../providers/custom-provider.php';
require_once __DIR__ . '/pricing-manager.php';

class ServiceImporter {
    /**
     * Preview services from provider
     */
    public static function preview(int $providerId): array {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM providers WHERE id = ? LIMIT 1");
        $stmt->execute([$providerId]);
        $provider = $stmt->fetch();

        if (!$provider) {
            return ['success' => false, 'error' => 'Selected provider was not found in database.', 'items' => []];
        }

        try {
            $adapter = new CustomProviderAdapter($provider['api_url'], $provider['api_key']);
            $services = $adapter->getServices();

            if (empty($services)) {
                return [
                    'success' => false,
                    'error' => "The provider API ('" . htmlspecialchars($provider['name']) . "') returned no services or could not be reached. Verify that the API URL and API Key are valid.",
                    'provider' => $provider,
                    'items' => []
                ];
            }

            // Check which services already exist locally
            $existingCodes = [];
            $stmt = $db->query("SELECT code FROM services");
            while ($row = $stmt->fetch()) {
                $existingCodes[] = $row['code'];
            }

            $previewItems = [];
            foreach ($services as $srv) {
                $code = trim($srv['code'] ?? '');
                $name = trim($srv['name'] ?? $code);
                if (empty($code)) continue;

                $isExisting = in_array($code, $existingCodes);
                $cost = (float)($srv['cost'] ?? 0.50);

                $previewItems[] = [
                    'code'          => $code,
                    'name'          => $name,
                    'category'      => $srv['category'] ?? 'general',
                    'provider_cost' => $cost,
                    'suggested_price' => PricingManager::calculateSellingPrice($cost),
                    'is_existing'   => $isExisting,
                    'action_type'   => $isExisting ? 'update' : 'create'
                ];
            }

            return [
                'success' => true,
                'provider' => $provider,
                'items' => $previewItems
            ];
        } catch (\Throwable $e) {
            error_log("ServiceImporter preview exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error querying provider services: ' . $e->getMessage(),
                'provider' => $provider,
                'items' => []
            ];
        }
    }

    /**
     * Import selected services
     */
    public static function import(int $providerId, array $selectedCodes, bool $approveImmediately = false): array {
        $preview = self::preview($providerId);
        if (!$preview['success']) {
            return $preview;
        }

        $db = get_db();
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $logs = [];

        $db->beginTransaction();
        try {
            foreach ($preview['items'] as $item) {
                if (!in_array($item['code'], $selectedCodes)) {
                    $skipped++;
                    continue;
                }

                try {
                    // Check if exists in services
                    $stmt = $db->prepare("SELECT id, price FROM services WHERE code = ? LIMIT 1");
                    $stmt->execute([$item['code']]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        // Update provider cost only, preserve custom selling price!
                        $stmt = $db->prepare("UPDATE services SET provider_cost = ? WHERE id = ?");
                        $stmt->execute([$item['provider_cost'], $existing['id']]);
                        $serviceId = (int)$existing['id'];
                        $updated++;
                        $logs[] = "Updated service: {$item['name']} (Code: {$item['code']})";
                    } else {
                        // Create new service
                        $status = $approveImmediately ? 'active' : 'inactive';
                        $stmt = $db->prepare("
                            INSERT INTO services (name, code, price, provider_cost, status, stock_status) 
                            VALUES (?, ?, ?, ?, ?, 'available')
                        ");
                        $stmt->execute([
                            $item['name'],
                            $item['code'],
                            $item['suggested_price'],
                            $item['provider_cost'],
                            $status
                        ]);
                        $serviceId = (int)$db->lastInsertId();
                        $imported++;
                        $logs[] = "Imported new service: {$item['name']} ({$status})";
                    }

                    // Record mapping into provider_service_mappings
                    $stmtMap = $db->prepare("
                        INSERT INTO provider_service_mappings 
                        (provider_id, service_id, provider_service_code, provider_country_code, provider_cost, is_available, last_checked) 
                        VALUES (?, ?, ?, 'IN', ?, 1, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                            provider_cost = VALUES(provider_cost), 
                            is_available = 1, 
                            last_checked = NOW()
                    ");
                    $stmtMap->execute([$providerId, $serviceId, $item['code'], $item['provider_cost']]);

                } catch (\Throwable $e) {
                    $failed++;
                    $logs[] = "Failed item {$item['code']}: " . $e->getMessage();
                }
            }

            // Save Import History
            $stmt = $db->prepare("
                INSERT INTO import_history 
                (provider_id, total_fetched, imported, updated, skipped, failed, status, log_details) 
                VALUES (?, ?, ?, ?, ?, ?, 'success', ?)
            ");
            $stmt->execute([
                $providerId,
                count($preview['items']),
                $imported,
                $updated,
                $skipped,
                $failed,
                implode("\n", $logs)
            ]);

            $db->commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
                'logs' => $logs
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
