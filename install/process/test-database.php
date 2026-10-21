<?php
/**
 * Process: Test Database Connection via PDO
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/installer-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.']);
    exit;
}

$host = trim($_POST['host'] ?? '127.0.0.1');
$port = trim($_POST['port'] ?? '3306');
$dbname = trim($_POST['dbname'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($host) || empty($dbname) || empty($username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide database host, database name, and username.']);
    exit;
}

$result = test_db_connection($host, $port, $dbname, $username, $password);
echo json_encode($result);
