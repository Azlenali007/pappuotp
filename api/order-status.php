<?php
/**
 * API: Check Order Status & Retrieve OTP
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../services/order-manager.php';
require_once __DIR__ . '/../services/otp-manager.php';

initSession();

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

$orderManager = new OrderManager();
$order = $orderManager->getOrderById($orderId, $userId);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Handle cancellation request via API
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'cancel' && in_array($order['status'], ['pending', 'waiting'])) {
    $cancelResult = $orderManager->cancelOrder($orderId, $userId, 'Cancelled by user via API');
    if ($cancelResult['success']) {
        $order = $orderManager->getOrderById($orderId, $userId);
        echo json_encode([
            'success' => true,
            'message' => 'Order cancelled and funds refunded to your wallet.',
            'order' => $order
        ]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $cancelResult['error'] ?? 'Cancellation failed']);
        exit;
    }
}

// If order is waiting/pending, check provider or trigger OTP retrieval
if (in_array($order['status'], ['pending', 'waiting'])) {
    $otpManager = new OtpManager();
    $otpCheck = $otpManager->checkAndProcessOtp($orderId);
    if ($otpCheck['received'] ?? false) {
        $order = $orderManager->getOrderById($orderId, $userId);
    }
}

// Calculate remaining countdown
$expiresAt = strtotime($order['expires_at'] ?? 'now');
$secondsRemaining = max(0, $expiresAt - time());

echo json_encode([
    'success' => true,
    'order' => [
        'id' => (int)$order['id'],
        'order_ref' => $order['order_ref'],
        'status' => $order['status'],
        'phone_number' => $order['phone_number'],
        'service_name' => $order['service_name'] ?? 'Service',
        'sms_code' => $order['sms_code'],
        'full_sms' => $order['full_sms'],
        'expires_at' => $order['expires_at'],
        'seconds_remaining' => $secondsRemaining,
        'price' => (float)$order['price'],
    ]
]);
