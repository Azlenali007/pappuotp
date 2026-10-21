<?php
/**
 * Verify Gateway Payment & Credit Wallet
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../services/payment-manager.php';
require_once __DIR__ . '/../services/wallet-manager.php';

initSession();

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$paymentManager = new PaymentManager();

$internalId = (int)($_POST['internal_payment_id'] ?? 0);
$razorpayPaymentId = trim($_POST['razorpay_payment_id'] ?? '');
$razorpayOrderId = trim($_POST['razorpay_order_id'] ?? '');
$razorpaySignature = trim($_POST['razorpay_signature'] ?? '');

$result = $paymentManager->verifyAndCreditPayment(
    $internalId,
    $userId,
    $razorpayPaymentId,
    $razorpayOrderId,
    $razorpaySignature
);

if ($result['success']) {
    $wallet = new WalletManager();
    $balance = $wallet->getBalance($userId);
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully and funds added to your wallet!',
        'balance' => $balance,
        'formatted' => '₹' . number_format($balance, 2)
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Verification failed']);
}
