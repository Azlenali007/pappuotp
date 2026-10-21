<?php
/**
 * API: User Notifications
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../services/notification-manager.php';

initSession();

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$notifManager = new NotificationManager();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'mark_read') {
    $notifId = (int)($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $notifManager->markAsRead($notifId, $userId);
    } else {
        $notifManager->markAllAsRead($userId);
    }
    echo json_encode(['success' => true]);
    exit;
}

$notifications = $notifManager->getUserNotifications($userId, 10);
$unreadCount = $notifManager->getUnreadCount($userId);

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
