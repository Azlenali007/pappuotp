<?php
/**
 * Create Payment Order for Gateway Checkout
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../services/payment-manager.php';

initSession();

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$amount = (float)($_POST['amount'] ?? 0);
$method = trim($_POST['method'] ?? 'razorpay');

if ($amount < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Minimum deposit amount is ₹10.00']);
    exit;
}

$paymentManager = new PaymentManager();
$result = $paymentManager->createPaymentOrder($userId, $amount, $method);

if ($result['success']) {
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to initialize payment']);
}
