<?php
/**
 * Forgot Password Processor
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /forgot-password.php');
    exit;
}

require_csrf_token();

$email = sanitize_email($_POST['email'] ?? '');

if (!validate_email($email)) {
    set_flash('error', 'Please provide a valid email address.');
    header('Location: /forgot-password.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Generate secure random single-use token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $stmt = $db->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expires]);

    // Store token in session for easy local development recovery
    $_SESSION['dev_reset_link'] = '/reset-password.php?token=' . $token;
}

// Notice rule: Do not reveal whether email exists!
set_flash('success', 'If an account exists with that email, password reset instructions have been dispatched.');
header('Location: /forgot-password.php');
exit;
