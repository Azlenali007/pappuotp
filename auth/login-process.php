<?php
/**
 * Login Processing Script
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

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if (empty($login) || empty($password)) {
    set_flash('error', 'Please provide both your username/email and password.');
    header('Location: /login.php');
    exit;
}

$db = get_db();

// Check login attempts / rate limiting
$stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmt->execute([$ip]);
if ($stmt->fetchColumn() >= 10) {
    set_flash('error', 'Too many failed login attempts. Please wait 15 minutes before trying again.');
    header('Location: /login.php');
    exit;
}

// Find user by email or username
$stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) LIMIT 1");
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Record failed attempt
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
    $stmt->execute([$ip, $login]);

    set_flash('error', 'Invalid login credentials. Please check your username/password.');
    header('Location: /login.php');
    exit;
}

if ($user['status'] !== 'active') {
    set_flash('error', 'Your account has been ' . htmlspecialchars($user['status']) . '. Please contact support.');
    header('Location: /login.php');
    exit;
}

// Successful login
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];

// Clear login attempts
$stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
$stmt->execute([$ip]);

// Activity log
$stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'user_login', ?)");
$stmt->execute([$user['id'], $ip]);

set_flash('success', 'Welcome back, ' . $user['name'] . '!');
header('Location: /user/dashboard.php');
exit;
