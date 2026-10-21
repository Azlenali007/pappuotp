<?php
/**
 * Reset Password Processor
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

require_csrf_token();

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password)) {
    set_flash('error', 'Token and password are required.');
    header('Location: /forgot-password.php');
    exit;
}

if ($password !== $confirmPassword) {
    set_flash('error', 'Passwords do not match.');
    header('Location: /reset-password.php?token=' . urlencode($token));
    exit;
}

if (strlen($password) < 6) {
    set_flash('error', 'Password must be at least 6 characters.');
    header('Location: /reset-password.php?token=' . urlencode($token));
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    set_flash('error', 'This password reset token has expired or is invalid.');
    header('Location: /forgot-password.php');
    exit;
}

// Update user password and mark token used
$newHash = password_hash($password, PASSWORD_BCRYPT);
$db->beginTransaction();
try {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$newHash, $reset['email']]);

    $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
    $stmt->execute([$reset['id']]);

    $db->commit();

    set_flash('success', 'Your password has been successfully reset. Please log in with your new password.');
    header('Location: /login.php');
    exit;
} catch (Exception $e) {
    $db->rollBack();
    set_flash('error', 'Failed to update password. Please try again.');
    header('Location: /forgot-password.php');
    exit;
}
