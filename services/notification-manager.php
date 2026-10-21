<?php
/**
 * Notification Manager
 */

require_once __DIR__ . '/../config/database.php';

class NotificationManager {
    public static function send(?int $userId, string $title, string $message, string $type = 'info', ?string $link = null): bool {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $type, $link]);
    }
}
