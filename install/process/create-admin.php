<?php
/**
 * Process: Create Initial Super Admin Account
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/installer-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token invalid or expired.']);
    exit;
}

$dbConfig = $_SESSION['installer_db'] ?? null;
if (!$dbConfig) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Database configuration missing. Please complete database setup first.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
    exit;
}

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $result = create_super_admin($pdo, $name, $username, $email, $password);

    if ($result['success']) {
        $_SESSION['installer_admin'] = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'id' => $result['admin_id']
        ];
        echo json_encode(['success' => true, 'message' => 'Super Administrator created successfully!']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to create administrator account.']);
    }
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
