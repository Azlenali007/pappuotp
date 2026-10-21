<?php
/**
 * User Authentication Enforcement Middleware
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/maintenance.php';

check_maintenance_mode();
require_user_login();

$currentUser = get_current_user_data();
if (!$currentUser) {
    session_destroy();
    header('Location: /login.php');
    exit;
}
