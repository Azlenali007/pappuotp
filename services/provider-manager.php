<?php
/**
 * Provider Manager
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../providers/custom-provider.php';

class ProviderManager {
    public static function getActiveProvider(): ?array {
        $db = get_db();
        $stmt = $db->query("SELECT * FROM providers WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        return $stmt->fetch() ?: null;
    }

    public static function getAdapter(int $providerId): ?CustomProviderAdapter {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM providers WHERE id = ? LIMIT 1");
        $stmt->execute([$providerId]);
        $provider = $stmt->fetch();
        if (!$provider) return null;

        return new CustomProviderAdapter($provider['api_url'], $provider['api_key']);
    }
}
