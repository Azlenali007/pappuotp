<?php
/**
 * Order Manager
 * Handles Virtual Number allocation, countdown timers, OTP fulfillment, and cancellations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/wallet-manager.php';
require_once __DIR__ . '/../providers/custom-provider.php';

class OrderManager {
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
            return ['success' => false, 'error' => 'The provider currently has no available numbers for this service. Your funds were refunded.'];
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
     * User authorized cancellation with 100% refund
     */
    public static function cancelOrder(int $orderId, int $userId): bool {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if (!$order) {
            return false;
        }

        return WalletManager::refundOrder($orderId, 'User requested cancellation');
    }

    /**
     * Fulfill verification code for order
     */
    public static function fulfillOtp(int $orderId, string $otpCode, ?string $fullSms = null): bool {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending' LIMIT 1");
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
