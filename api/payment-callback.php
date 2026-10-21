<?php
/**
 * API: Payment Callback & Verification
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../services/payment-manager.php';
require_once __DIR__ . '/../services/wallet-manager.php';
require_once __DIR__ . '/../services/notification-manager.php';

initSession();

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$paymentManager = new PaymentManager();

// Parse input (supports JSON or form-urlencoded)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$paymentId = (int)($input['internal_payment_id'] ?? 0);
$gatewayPaymentId = trim($input['razorpay_payment_id'] ?? '');
$gatewayOrderId = trim($input['razorpay_order_id'] ?? '');
$gatewaySignature = trim($input['razorpay_signature'] ?? '');

if ($paymentId <= 0 || empty($gatewayPaymentId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing payment details']);
    exit;
}

$verifyResult = $paymentManager->verifyAndCreditPayment(
    $paymentId,
    $userId,
    $gatewayPaymentId,
    $gatewayOrderId,
    $gatewaySignature
);

if ($verifyResult['success']) {
    $walletManager = new WalletManager();
    $newBalance = $walletManager->getBalance($userId);
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified and wallet credited successfully!',
        'new_balance' => $newBalance,
        'formatted_balance' => '₹' . number_format($newBalance, 2)
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $verifyResult['error'] ?? 'Payment verification failed'
    ]);
}
