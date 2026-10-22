<?php
/**
 * Order Manager
 * Handles Virtual Number allocation, countdown timers, OTP fulfillment, and cancellations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/wallet-manager.php';
require_once __DIR__ . '/../providers/custom-provider.php';

class OrderManager {
    /**
     * Get order details by ID with normalized schema field aliases
     */
    public function getOrderById(int $orderId, ?int $userId = null): ?array {
        return self::fetchOrderById($orderId, $userId);
    }

    public static function fetchOrderById(int $orderId, ?int $userId = null): ?array {
        $db = get_db();
        $sql = "
            SELECT 
                o.*,
                o.cost as price,
                o.otp_code as sms_code,
                s.name as service_name,
                s.code as service_code,
                s.icon as service_icon,
                p.name as provider_name,
                p.api_url as provider_api_url,
                p.api_key as provider_api_key
            FROM orders o 
            LEFT JOIN services s ON s.id = o.service_id 
            LEFT JOIN providers p ON p.id = o.provider_id 
            WHERE o.id = ?
        ";
        $params = [$orderId];

        if ($userId !== null && $userId > 0) {
            $sql .= " AND o.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        // Ensure both naming conventions are populated
        $order['price'] = (float)($order['cost'] ?? 0);
        $order['cost'] = (float)($order['cost'] ?? 0);
        $order['sms_code'] = $order['otp_code'] ?? null;

        return $order;
    }

    /**
     * Create Order and Allocate Phone Number
     */
    public static function createOrder(int $userId, int $serviceId, string $countryCode, int $quantity = 1): array {
        $db = get_db();

        // 1. Fetch Service
        $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();

        if (!$service) {
            return ['success' => false, 'error' => 'Selected service is currently unavailable.'];
        }

        $price = (float)$service['price'];
        $totalCost = round($price * $quantity, 2);

        // 2. Fetch Active Provider
        $provider = $db->query("SELECT * FROM providers WHERE status = 'active' ORDER BY id ASC LIMIT 1")->fetch();
        if (!$provider) {
            return ['success' => false, 'error' => 'No active OTP provider gateway configured.'];
        }

        // 3. Deduct Wallet Balance Atomically
        $orderRef = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $deducted = WalletManager::deductFunds(
            $userId, 
            $totalCost, 
            "Order payment for {$service['name']} ({$countryCode})", 
            $orderRef
        );

        if (!$deducted) {
            return ['success' => false, 'error' => 'Insufficient wallet balance. Please add funds.'];
        }

        // 4. Request Phone Number from Provider
        $providerAdapter = new CustomProviderAdapter($provider['api_url'], $provider['api_key']);
        $alloc = $providerAdapter->buyNumber($service['code'], $countryCode);

        if (empty($alloc['success'])) {
            // Refund immediately if provider fails
            WalletManager::addFunds($userId, $totalCost, "Instant refund: Provider out of numbers for {$service['name']}", $orderRef, 'refund');
            $errMsg = $alloc['error'] ?? 'The provider currently has no available numbers for this service. Your funds were refunded.';
            return ['success' => false, 'error' => $errMsg];
        }

        $phoneNumber = $alloc['phone_number'];
        $providerOrderId = $alloc['provider_order_id'];
        $timeoutMinutes = (int)get_setting('order_timeout_minutes', '20');
        $expiresAt = date('Y-m-d H:i:s', time() + ($timeoutMinutes * 60));

        // 5. Store in Database
        $stmt = $db->prepare("
            INSERT INTO orders 
            (order_ref, user_id, service_id, country_code, phone_number, provider_id, provider_order_id, cost, status, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $orderRef,
            $userId,
            $serviceId,
            $countryCode,
            $phoneNumber,
            $provider['id'],
            $providerOrderId,
            $totalCost,
            $expiresAt
        ]);

        $orderId = (int)$db->lastInsertId();

        // 6. Record Status History & Notification
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, note) VALUES (?, NULL, 'pending', 'Virtual number allocated')");
        $stmt->execute([$orderId]);

        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Order Created', ?, 'info', ?)");
        $stmt->execute([
            $userId, 
            "Virtual number {$phoneNumber} allocated for {$service['name']}. Awaiting SMS code.",
            "/user/order-details.php?id={$orderId}"
        ]);

        return [
            'success' => true,
            'order_id' => $orderId,
            'order_ref' => $orderRef,
            'phone_number' => $phoneNumber,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * User/System authorized cancellation with 100% refund
     * Supports both instance and static calls, and returns standardized result array
     */
    public function cancelOrder(int $orderId, int $userId, string $reason = 'User requested cancellation'): array {
        return self::executeCancelOrder($orderId, $userId, $reason);
    }

    public static function executeCancelOrder(int $orderId, int $userId, string $reason = 'User requested cancellation'): array {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending', 'waiting') LIMIT 1");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if (!$order) {
            return [
                'success' => false,
                'error' => 'Order not eligible for cancellation or already completed/cancelled.'
            ];
        }

        // Cancel with provider if order has provider_order_id
        if (!empty($order['provider_order_id']) && !empty($order['provider_id'])) {
            $stmtP = $db->prepare("SELECT api_url, api_key FROM providers WHERE id = ? LIMIT 1");
            $stmtP->execute([$order['provider_id']]);
            $provider = $stmtP->fetch();
            if ($provider) {
                try {
                    $adapter = new CustomProviderAdapter($provider['api_url'], $provider['api_key']);
                    $adapter->cancelNumber($order['provider_order_id']);
                } catch (\Throwable $e) {
                    error_log("Provider cancel call error: " . $e->getMessage());
                }
            }
        }

        $refunded = WalletManager::refundOrder($orderId, $reason);

        if ($refunded) {
            return [
                'success' => true,
                'message' => 'Order cancelled and funds refunded successfully.'
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to process refund for this order.'
        ];
    }

    /**
     * Fulfill verification code for order
     */
    public static function fulfillOtp(int $orderId, string $otpCode, ?string $fullSms = null): bool {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND status IN ('pending', 'waiting') LIMIT 1");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return false;
        }

        if (!$fullSms) {
            $fullSms = "Your verification code is: {$otpCode}. Do not share this code with anyone.";
        }

        $stmt = $db->prepare("UPDATE orders SET status = 'completed', otp_code = ?, full_sms = ? WHERE id = ?");
        $stmt->execute([$otpCode, $fullSms, $orderId]);

        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, note) VALUES (?, 'pending', 'completed', 'OTP delivered successfully')");
        $stmt->execute([$orderId]);

        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'OTP Code Received!', ?, 'success', ?)");
        $stmt->execute([
            $order['user_id'],
            "Your verification code for {$order['phone_number']} is {$otpCode}.",
            "/user/order-details.php?id={$orderId}"
        ]);

        return true;
    }
}
