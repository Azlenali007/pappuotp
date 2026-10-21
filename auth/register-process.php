<?php
/**
 * User Registration Processing
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php');
    exit;
}

require_csrf_token();

$name = sanitize_string($_POST['name'] ?? '');
$email = sanitize_email($_POST['email'] ?? '');
$username = sanitize_string($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']);

if (empty($name) || empty($email) || empty($password)) {
    set_flash('error', 'Please fill in all required fields.');
    header('Location: /register.php');
    exit;
}

if (!validate_email($email)) {
    set_flash('error', 'Please provide a valid email address.');
    header('Location: /register.php');
    exit;
}

if (strlen($password) < 6) {
    set_flash('error', 'Password must be at least 6 characters.');
    header('Location: /register.php');
    exit;
}

if ($password !== $confirmPassword) {
    set_flash('error', 'Passwords do not match.');
    header('Location: /register.php');
    exit;
}

if (!$terms) {
    set_flash('error', 'You must agree to the Terms of Service to register.');
    header('Location: /register.php');
    exit;
}

$db = get_db();

// Check if email or username already exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    set_flash('error', 'An account with this email or username already exists.');
    header('Location: /register.php');
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    // Insert user
    $stmt = $db->prepare("INSERT INTO users (name, email, username, password_hash, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $email, $username ?: null, $hash]);
    $userId = (int)$db->lastInsertId();

    // Create wallet with initial welcome balance 0.00
    $stmt = $db->prepare("INSERT INTO wallets (user_id, balance, currency) VALUES (?, 0.00, 'INR')");
    $stmt->execute([$userId]);

    // Create welcome notification
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Welcome to OTP Services', 'Your account has been successfully created. Add funds to your wallet to start purchasing virtual numbers.', 'success', '/user/add-funds.php')");
    $stmt->execute([$userId]);

    $db->commit();

    // Log user in automatically
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    set_flash('success', 'Your account has been created successfully!');
    header('Location: /user/dashboard.php');
    exit;
} catch (Exception $e) {
    $db->rollBack();
    error_log("Registration error: " . $e->getMessage());
    set_flash('error', 'An unexpected error occurred during registration. Please try again.');
    header('Location: /register.php');
    exit;
}
