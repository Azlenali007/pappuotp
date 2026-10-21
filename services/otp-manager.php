<?php
/**
 * OTP Manager
 */

require_once __DIR__ . '/../config/database.php';

class OtpManager {
    public static function getOrderStatus(int $orderId, int $userId): ?array {
        $db = get_db();
        $stmt = $db->prepare("
            SELECT o.*, s.name as service_name, s.icon as service_icon, p.name as provider_name 
            FROM orders o 
            JOIN services s ON s.id = o.service_id 
            JOIN providers p ON p.id = o.provider_id 
            WHERE o.id = ? AND o.user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$orderId, $userId]);
        return $stmt->fetch() ?: null;
    }
}
