<?php
/**
 * OTP Manager
 * Real-time verification poller and status processor
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/order-manager.php';
require_once __DIR__ . '/../providers/custom-provider.php';

class OtpManager {
    /**
     * Check real-time OTP from provider and update order status
     */
    public function checkAndProcessOtp(int $orderId): array {
        return self::pollAndProcess($orderId);
    }

    public static function pollAndProcess(int $orderId): array {
        $db = get_db();
        $stmt = $db->prepare("
            SELECT o.*, p.api_url as provider_api_url, p.api_key as provider_api_key 
            FROM orders o 
            LEFT JOIN providers p ON p.id = o.provider_id 
            WHERE o.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return ['received' => false, 'status' => 'not_found', 'error' => 'Order not found'];
        }

        // If already completed
        if ($order['status'] === 'completed') {
            return [
                'received' => true,
                'status' => 'completed',
                'otp_code' => $order['otp_code'],
                'full_sms' => $order['full_sms']
            ];
        }

        // If already terminated
        if (in_array($order['status'], ['cancelled', 'expired', 'failed'])) {
            return [
                'received' => false,
                'status' => $order['status']
            ];
        }

        // Check if expired
        if (!empty($order['expires_at']) && strtotime($order['expires_at']) <= time()) {
            OrderManager::executeCancelOrder((int)$order['id'], (int)$order['user_id'], 'Order expired after timeout without SMS arrival');
            return [
                'received' => false,
                'status' => 'expired'
            ];
        }

        // If no provider or no provider_order_id
        if (empty($order['provider_order_id']) || empty($order['provider_api_url'])) {
            return [
                'received' => false,
                'status' => 'pending'
            ];
        }

        // Query real carrier gateway
        try {
            $adapter = new CustomProviderAdapter($order['provider_api_url'], $order['provider_api_key'] ?: null, 6);
            $check = $adapter->checkStatus($order['provider_order_id']);

            if (!empty($check['success'])) {
                // If code received
                if (!empty($check['otp_code'])) {
                    OrderManager::fulfillOtp((int)$order['id'], (string)$check['otp_code'], $check['full_sms'] ?? null);
                    return [
                        'received' => true,
                        'status' => 'completed',
                        'otp_code' => $check['otp_code'],
                        'full_sms' => $check['full_sms'] ?? null
                    ];
                }

                // If provider cancelled or released number
                if (in_array($check['status'], ['cancelled', 'expired', 'failed'])) {
                    OrderManager::executeCancelOrder((int)$order['id'], (int)$order['user_id'], 'Carrier released number before verification arrival.');
                    return [
                        'received' => false,
                        'status' => 'cancelled'
                    ];
                }
            }
        } catch (\Throwable $e) {
            error_log("OtpManager poll error for order #{$orderId}: " . $e->getMessage());
        }

        return [
            'received' => false,
            'status' => 'pending'
        ];
    }

    /**
     * Get order status for view
     */
    public static function getOrderStatus(int $orderId, int $userId): ?array {
        return OrderManager::fetchOrderById($orderId, $userId);
    }
}
