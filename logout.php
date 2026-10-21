<?php
/**
 * User Logout
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

set_flash('success', 'You have been safely logged out.');
header('Location: /login.php');
exit;
