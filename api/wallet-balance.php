<?php
/**
 * API: Get Current Wallet Balance
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../services/wallet-manager.php';

initSession();

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$walletManager = new WalletManager();
$balance = $walletManager->getBalance($userId);

echo json_encode([
    'success' => true,
    'user_id' => $userId,
    'balance' => $balance,
    'formatted' => '₹' . number_format($balance, 2),
    'currency' => 'INR'
]);
