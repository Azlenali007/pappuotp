<?php
/**
 * Admin Panel Helper Functions
 */

require_once __DIR__ . '/../../config/database.php';

function log_admin_audit(string $action, ?string $details = null): void {
    $adminId = $_SESSION['admin_id'] ?? null;
    $db = get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $db->prepare("INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$adminId, $action, $details, $ip]);
}
