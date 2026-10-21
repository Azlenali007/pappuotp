<?php
/**
 * Process: Execute Schema Installation
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
    echo json_encode(['success' => false, 'error' => 'Database configuration missing from session. Please go back to Step 3.']);
    exit;
}

$host = $dbConfig['host'];
$port = (int)$dbConfig['port'];
$dbname = $dbConfig['dbname'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $schemaPath = dirname(__DIR__, 2) . '/database/schema.sql';
    $result = run_schema_installation($pdo, $schemaPath);

    if ($result['success']) {
        // Seed essential roles & categories safely
        seed_essential_data($pdo);

        $_SESSION['installer_db_installed'] = true;
        echo json_encode([
            'success' => true,
            'message' => 'Database schema installed successfully!',
            'queries_count' => $result['queries_executed'] ?? 26
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Schema execution failed.'
        ]);
    }
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error during installation: ' . $e->getMessage()
    ]);
}
