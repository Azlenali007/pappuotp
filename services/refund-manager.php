<?php
/**
 * Refund Manager
 */

require_once __DIR__ . '/wallet-manager.php';

class RefundManager {
    public static function processOrderRefund(int $orderId, string $reason): bool {
        return WalletManager::refundOrder($orderId, $reason);
    }
}
