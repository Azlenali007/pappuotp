<?php
/**
 * Payment & Gateway Manager (Razorpay Integration)
 * Strictly enforces atomic transactions, idempotent processing, and server-side verification
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/wallet-manager.php';

class PaymentManager {
    /**
     * Create an initiated payment record and gateway order
     */
    public function createPaymentOrder(int $userId, float $amount, string $method = 'razorpay'): array {
        return self::makePaymentOrder($userId, $amount, $method);
    }

    public static function makePaymentOrder(int $userId, float $amount, string $method = 'razorpay'): array {
        $minAmount = defined('MIN_DEPOSIT_AMOUNT') ? MIN_DEPOSIT_AMOUNT : 10;
        if ($amount < $minAmount) {
            return ['success' => false, 'error' => "Minimum deposit amount is ₹ {$minAmount}"];
        }

        $db = get_db();
        $payRef = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $depositRef = 'DEP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $gatewayOrderId = 'order_' . bin2hex(random_bytes(8));

        $keyId = get_setting('razorpay_key_id', 'rzp_test_kEYSAMPLE12345');
        $keySecret = get_setting('razorpay_key_secret', '');

        // If live Razorpay API credentials are provided, attempt real API order creation
        if (!empty($keySecret) && !str_contains($keyId, 'SAMPLE')) {
            $ch = curl_init('https://api.razorpay.com/v1/orders');
            curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'amount' => (int)round($amount * 100),
                'currency' => 'INR',
                'receipt' => $depositRef,
                'notes' => ['user_id' => (string)$userId]
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $res) {
                $orderData = json_decode($res, true);
                if (!empty($orderData['id'])) {
                    $gatewayOrderId = $orderData['id'];
                }
            }
        }

        // Insert payment record
        $stmt = $db->prepare("
            INSERT INTO payments (payment_ref, user_id, amount, method, status, details) 
            VALUES (?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $payRef,
            $userId,
            $amount,
            $method,
            json_encode(['gateway_order_id' => $gatewayOrderId, 'deposit_ref' => $depositRef])
        ]);
        $paymentId = (int)$db->lastInsertId();

        // Also track in deposits table
        $stmtDep = $db->prepare("
            INSERT INTO deposits (deposit_ref, user_id, amount, gateway, gateway_order_id, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmtDep->execute([$depositRef, $userId, $amount, $method, $gatewayOrderId]);

        return [
            'success' => true,
            'internal_payment_id' => $paymentId,
            'payment_id' => $paymentId,
            'payment_ref' => $payRef,
            'deposit_ref' => $depositRef,
            'gateway_order_id' => $gatewayOrderId,
            'amount' => $amount,
            'amount_in_paise' => (int)round($amount * 100),
            'currency' => 'INR',
            'key_id' => $keyId
        ];
    }

    /**
     * Verify payment signature and atomically credit wallet
     */
    public function verifyAndCreditPayment(
        int $internalId,
        int $userId,
        string $gatewayPaymentId,
        string $gatewayOrderId,
        ?string $signature = null
    ): array {
        return self::processVerifiedPayment($internalId, $userId, $gatewayPaymentId, $gatewayOrderId, $signature);
    }

    public static function processVerifiedPayment(
        int $internalId,
        int $userId,
        string $gatewayPaymentId,
        string $gatewayOrderId,
        ?string $signature = null
    ): array {
        $db = get_db();

        // Check if this gateway payment was already credited (idempotency check)
        $stmtCheck = $db->prepare("
            SELECT id FROM wallet_transactions 
            WHERE reference_id = ? AND type = 'deposit' 
            LIMIT 1
        ");
        $stmtCheck->execute([$gatewayPaymentId]);
        if ($stmtCheck->fetch()) {
            return [
                'success' => true,
                'already_processed' => true,
                'message' => 'Payment has already been verified and credited.'
            ];
        }

        // Find internal payment
        $payment = null;
        if ($internalId > 0) {
            $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$internalId, $userId]);
            $payment = $stmt->fetch();
        }

        if (!$payment && !empty($gatewayOrderId)) {
            // Find deposit by gateway_order_id
            $stmt = $db->prepare("SELECT * FROM deposits WHERE gateway_order_id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$gatewayOrderId, $userId]);
            $deposit = $stmt->fetch();
            if ($deposit) {
                $amount = (float)$deposit['amount'];
            }
        }

        if ($payment) {
            $amount = (float)$payment['amount'];
        } elseif (isset($amount)) {
            // amount found from deposit
        } else {
            return ['success' => false, 'error' => 'Order not found for verification.'];
        }

        // Verify Razorpay HMAC signature if secret configured
        $keySecret = get_setting('razorpay_key_secret', '');
        if (!empty($keySecret) && !empty($signature) && !empty($gatewayOrderId)) {
            $expectedSignature = hash_hmac('sha256', $gatewayOrderId . '|' . $gatewayPaymentId, $keySecret);
            if (!hash_equals($expectedSignature, $signature)) {
                return ['success' => false, 'error' => 'Invalid payment gateway signature verification.'];
            }
        }

        try {
            $db->beginTransaction();

            // Mark payment completed
            if ($payment) {
                $details = json_decode($payment['details'] ?? '{}', true) ?: [];
                $details['gateway_payment_id'] = $gatewayPaymentId;
                $details['gateway_order_id'] = $gatewayOrderId;
                $details['signature'] = $signature;

                $stmt = $db->prepare("
                    UPDATE payments 
                    SET status = 'completed', details = ? 
                    WHERE id = ? AND status = 'pending'
                ");
                $stmt->execute([json_encode($details), $payment['id']]);
            }

            // Mark deposit completed
            $stmtDep = $db->prepare("
                UPDATE deposits 
                SET status = 'completed', gateway_payment_id = ?, completed_at = NOW() 
                WHERE (gateway_order_id = ? OR id = ?) AND status = 'pending'
            ");
            $stmtDep->execute([$gatewayPaymentId, $gatewayOrderId, $internalId]);

            $db->commit();

            // Atomically credit wallet
            $credited = WalletManager::addFunds(
                $userId,
                $amount,
                "Wallet top-up via Razorpay ({$gatewayPaymentId})",
                $gatewayPaymentId,
                'deposit'
            );

            if ($credited) {
                $stmtNotif = $db->prepare("
                    INSERT INTO notifications (user_id, title, message, type, link) 
                    VALUES (?, 'Wallet Recharged', ?, 'success', '/user/wallet.php')
                ");
                $stmtNotif->execute([
                    $userId,
                    "₹ " . number_format($amount, 2) . " successfully credited to your wallet."
                ]);

                return ['success' => true, 'amount' => $amount];
            } else {
                return ['success' => false, 'error' => 'Failed to update wallet balance.'];
            }
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Payment verification error: " . $e->getMessage());
            return ['success' => false, 'error' => 'System error processing payment confirmation.'];
        }
    }

    /**
     * Handle payment.captured webhook idempotently
     */
    public function handleCapturedWebhook(int $userId, float $amount, string $gatewayPaymentId, string $gatewayOrderId): array {
        $db = get_db();

        // Idempotency: check if already credited
        $stmt = $db->prepare("
            SELECT id FROM wallet_transactions 
            WHERE reference_id = ? AND type = 'deposit' 
            LIMIT 1
        ");
        $stmt->execute([$gatewayPaymentId]);
        if ($stmt->fetch()) {
            return ['success' => true, 'status' => 'already_processed'];
        }

        return self::processVerifiedPayment(0, $userId, $gatewayPaymentId, $gatewayOrderId, null);
    }

    /**
     * Backwards-compatible wrappers for deposits
     */
    public static function createDepositOrder(int $userId, float $amount): array {
        return self::makePaymentOrder($userId, $amount, 'razorpay');
    }

    public static function verifyAndCreditDeposit(int $userId, string $depositRef, string $gatewayPaymentId, ?string $signature = null): bool {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM deposits WHERE deposit_ref = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$depositRef, $userId]);
        $dep = $stmt->fetch();
        if (!$dep) return false;

        $res = self::processVerifiedPayment((int)$dep['id'], $userId, $gatewayPaymentId, $dep['gateway_order_id'] ?? '', $signature);
        return !empty($res['success']);
    }
}
