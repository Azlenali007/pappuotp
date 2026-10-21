<?php
/**
 * Super Admin Logout
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/includes/admin-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_id'])) {
    log_admin_audit('admin_logout', 'Super admin session terminated');
}

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_user']);

header('Location: /admin/login.php');
exit;
