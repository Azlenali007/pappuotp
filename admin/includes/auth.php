<?php
/**
 * Super Admin Authorization Gatekeeper
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin_login();

$currentAdmin = get_current_admin_data();
if (!$currentAdmin) {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}
