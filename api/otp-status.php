<?php
/**
 * API: Light OTP status polling
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
$orderId = (int)($_GET['order_id'] ?? 0);

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

if (in_array($order['status'], ['pending', 'waiting'])) {
    $otpManager = new OtpManager();
    $otpCheck = $otpManager->checkAndProcessOtp($orderId);
    if ($otpCheck['received'] ?? false) {
        $order = $orderManager->getOrderById($orderId, $userId);
    }
}

$code = $order['otp_code'] ?? $order['sms_code'] ?? null;

echo json_encode([
    'success' => true,
    'status' => $order['status'],
    'otp_code' => $code,
    'sms_code' => $code,
    'full_sms' => $order['full_sms'] ?? null,
    'is_completed' => ($order['status'] === 'completed'),
    'is_cancelled' => ($order['status'] === 'cancelled'),
    'is_expired' => ($order['status'] === 'expired'),
]);
