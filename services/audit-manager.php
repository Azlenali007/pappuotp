<?php
/**
 * Audit Log Manager
 */

require_once __DIR__ . '/../config/database.php';

class AuditManager {
    public static function logAdminAction(int $adminId, string $action, ?string $details = null): bool {
        $db = get_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$adminId, $action, $details, $ip]);
    }

    public static function logUserAction(int $userId, string $action, ?string $details = null): bool {
        $db = get_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $details, $ip]);
    }
}
