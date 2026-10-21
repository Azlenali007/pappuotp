<?php
/**
 * Wallet & Financial Ledger Manager
 * Strictly enforces atomic transactions, row locking, and immutable records
 */

require_once __DIR__ . '/../config/database.php';

class WalletManager {
    /**
     * Atomically deduct funds for order creation
     */
    public static function deductFunds(int $userId, float $amount, string $description, ?string $referenceId = null): bool {
        if ($amount <= 0) return false;
        $db = get_db();

        try {
            $db->beginTransaction();

            // Lock user wallet row
            $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $db->rollBack();
                return false;
            }

            $currentBalance = (float)$wallet['balance'];
            if ($currentBalance < $amount) {
                $db->rollBack();
                return false;
            }

            $newBalance = round($currentBalance - $amount, 2);

            // Update wallet balance
            $stmt = $db->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $stmt->execute([$newBalance, $userId]);

            // Insert immutable transaction ledger
            $txnRef = 'TXN-ORD-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $db->prepare("
                INSERT INTO wallet_transactions 
                (transaction_ref, user_id, type, amount, balance_before, balance_after, description, reference_id) 
                VALUES (?, ?, 'order_payment', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$txnRef, $userId, $amount, $currentBalance, $newBalance, $description, $referenceId]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Wallet deduction error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atomically credit funds (deposits, manual adjustments)
     */
    public static function addFunds(int $userId, float $amount, string $description, ?string $referenceId = null, string $type = 'deposit'): bool {
        if ($amount <= 0) return false;
        $db = get_db();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                // Initialize wallet if missing
                $stmt = $db->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
                $stmt->execute([$userId]);
                $currentBalance = 0.00;
            } else {
                $currentBalance = (float)$wallet['balance'];
            }

            $newBalance = round($currentBalance + $amount, 2);

            $stmt = $db->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $stmt->execute([$newBalance, $userId]);

            $prefix = ($type === 'refund') ? 'TXN-REF-' : (($type === 'adjustment') ? 'TXN-ADJ-' : 'TXN-DEP-');
            $txnRef = $prefix . strtoupper(bin2hex(random_bytes(4)));

            $stmt = $db->prepare("
                INSERT INTO wallet_transactions 
                (transaction_ref, user_id, type, amount, balance_before, balance_after, description, reference_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$txnRef, $userId, $type, $amount, $currentBalance, $newBalance, $description, $referenceId]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Wallet credit error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refund an order with idempotency check
     */
    public static function refundOrder(int $orderId, string $reason = 'Order cancelled or expired without verification'): bool {
        $db = get_db();

        try {
            $db->beginTransaction();

            // Lock order
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                return false;
            }

            // Check if already refunded or completed
            if (in_array($order['status'], ['completed', 'cancelled', 'expired'])) {
                // If already processed, prevent duplicate refund
                $stmtCheck = $db->prepare("SELECT id FROM refunds WHERE order_id = ? LIMIT 1");
                $stmtCheck->execute([$orderId]);
                if ($stmtCheck->fetch()) {
                    $db->rollBack();
                    return false;
                }
            }

            $userId = (int)$order['user_id'];
            $amount = (float)$order['cost'];

            // Lock wallet
            $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            $currentBalance = (float)$wallet['balance'];
            $newBalance = round($currentBalance + $amount, 2);

            // Update wallet
            $stmt = $db->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $stmt->execute([$newBalance, $userId]);

            // Add ledger entry
            $txnRef = 'TXN-REF-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $db->prepare("
                INSERT INTO wallet_transactions 
                (transaction_ref, user_id, type, amount, balance_before, balance_after, description, reference_id) 
                VALUES (?, ?, 'refund', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$txnRef, $userId, $amount, $currentBalance, $newBalance, 'Refund for order #' . $order['order_ref'], $order['order_ref']]);

            // Insert refund record
            $refundRef = 'REF-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $db->prepare("INSERT INTO refunds (refund_ref, order_id, user_id, amount, reason, status) VALUES (?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$refundRef, $orderId, $userId, $amount, $reason]);

            // Update order status
            $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$orderId]);

            // Add order status history
            $stmt = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, note) VALUES (?, ?, 'cancelled', ?)");
            $stmt->execute([$orderId, $order['status'], '100% Refund credited: ' . $reason]);

            // Add notification
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Order Refunded', ?, 'info', '/user/orders.php')");
            $stmt->execute([$userId, 'Refund of ₹ ' . number_format($amount, 2) . ' credited to your wallet for order ' . $order['order_ref']]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Refund processing error: " . $e->getMessage());
            return false;
        }
    }
}
