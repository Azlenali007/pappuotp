<?php
/**
 * Payment & Gateway Manager (Razorpay Integration)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/wallet-manager.php';

class PaymentManager {
    public static function createDepositOrder(int $userId, float $amount): array {
        if ($amount < MIN_DEPOSIT_AMOUNT) {
            return ['success' => false, 'error' => 'Minimum deposit amount is ₹ ' . MIN_DEPOSIT_AMOUNT];
        }

        $db = get_db();
        $depositRef = 'DEP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $gatewayOrderId = 'order_' . bin2hex(random_bytes(8));

        $stmt = $db->prepare("
            INSERT INTO deposits (deposit_ref, user_id, amount, gateway, gateway_order_id, status) 
            VALUES (?, ?, ?, 'razorpay', ?, 'pending')
        ");
        $stmt->execute([$depositRef, $userId, $amount, $gatewayOrderId]);

        return [
            'success' => true,
            'deposit_ref' => $depositRef,
            'gateway_order_id' => $gatewayOrderId,
            'amount' => $amount,
            'key_id' => get_setting('razorpay_key_id', 'rzp_test_kEYSAMPLE12345')
        ];
    }

    public static function verifyAndCreditDeposit(int $userId, string $depositRef, string $gatewayPaymentId, ?string $signature = null): bool {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM deposits WHERE deposit_ref = ? AND user_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$depositRef, $userId]);
        $deposit = $stmt->fetch();

        if (!$deposit) {
            return false;
        }

        $amount = (float)$deposit['amount'];

        $db->beginTransaction();
        try {
            // Mark deposit completed
            $stmt = $db->prepare("UPDATE deposits SET status = 'completed', gateway_payment_id = ?, completed_at = NOW() WHERE id = ?");
            $stmt->execute([$gatewayPaymentId, $deposit['id']]);

            // Record payment record
            $payRef = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $db->prepare("INSERT INTO payments (payment_ref, user_id, amount, method, status, details) VALUES (?, ?, ?, 'razorpay', 'completed', ?)");
            $stmt->execute([$payRef, $userId, $amount, json_encode(['payment_id' => $gatewayPaymentId, 'deposit_ref' => $depositRef])]);

            $db->commit();

            // Credit wallet using WalletManager
            WalletManager::addFunds($userId, $amount, "Wallet top-up via Razorpay ({$gatewayPaymentId})", $gatewayPaymentId, 'deposit');

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Payment credit error: " . $e->getMessage());
            return false;
        }
    }
}
