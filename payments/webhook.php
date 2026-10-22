<?php
/**
 * Razorpay Webhook Handler
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../services/payment-manager.php';
require_once __DIR__ . '/../services/wallet-manager.php';

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

$db = get_db();
$secret = get_setting('razorpay_webhook_secret', '');

// Verify webhook signature if secret configured
if (!empty($secret) && !empty($signature)) {
    $expectedSignature = hash_hmac('sha256', $rawBody, $secret);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook signature']);
        exit;
    }
}

$event = json_decode($rawBody, true);
if (!$event || !isset($event['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$eventId = $event['id'] ?? ('wh_' . bin2hex(random_bytes(8)));

// Log webhook payload
try {
    $stmt = $db->prepare("
        INSERT INTO webhook_events (event_id, gateway, event_type, payload, status, created_at)
        VALUES (?, 'razorpay', ?, ?, 'processed', NOW())
    ");
    $stmt->execute([
        $eventId,
        $event['event'],
        $rawBody
    ]);
} catch (\Throwable $e) {
    // Already logged or ignore duplicate event log
}

if ($event['event'] === 'payment.captured') {
    $paymentEntity = $event['payload']['payment']['entity'] ?? [];
    $gatewayPaymentId = $paymentEntity['id'] ?? '';
    $gatewayOrderId = $paymentEntity['order_id'] ?? '';
    $notes = $paymentEntity['notes'] ?? [];
    $userId = (int)($notes['user_id'] ?? 0);
    $amount = ((float)($paymentEntity['amount'] ?? 0)) / 100;

    if ($userId > 0 && $amount > 0) {
        $paymentManager = new PaymentManager();
        $paymentManager->handleCapturedWebhook($userId, $amount, $gatewayPaymentId, $gatewayOrderId);
    }
}

echo json_encode(['status' => 'ok']);
