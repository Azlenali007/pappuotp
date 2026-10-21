<?php
/**
 * Session & Authentication Helper
 */

require_once __DIR__ . '/../config/app.php';

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function is_user_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_data(): ?array {
    $uid = current_user_id();
    if (!$uid) return null;

    $db = get_db();
    $stmt = $db->prepare("
        SELECT u.*, w.balance, w.currency 
        FROM users u 
        LEFT JOIN wallets w ON w.user_id = u.id 
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->execute([$uid]);
    return $stmt->fetch() ?: null;
}

function require_user_login(): void {
    if (!is_user_logged_in()) {
        set_flash('error', 'Please log in to access this page.');
        header('Location: /login.php');
        exit;
    }
}

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function current_admin_id(): ?int {
    return $_SESSION['admin_id'] ?? null;
}

function get_current_admin_data(): ?array {
    $aid = current_admin_id();
    if (!$aid) return null;

    $db = get_db();
    $stmt = $db->prepare("SELECT a.*, r.name as role_name, r.slug as role_slug FROM admins a LEFT JOIN roles r ON r.id = a.role_id WHERE a.id = ? LIMIT 1");
    $stmt->execute([$aid]);
    return $stmt->fetch() ?: null;
}

function require_admin_login(): void {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}
