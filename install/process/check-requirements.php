<?php
/**
 * Process: Check Requirements AJAX Endpoint
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/installer-auth.php';

$phpPass = version_compare(PHP_VERSION, '8.2.0', '>=');
$pdoPass = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$curlPass = extension_loaded('curl');
$jsonPass = extension_loaded('json');
$opensslPass = extension_loaded('openssl');
$storageWritable = is_writable(dirname(__DIR__, 2) . '/storage');

$allPassed = $phpPass && $pdoPass && $curlPass && $jsonPass && $opensslPass && $storageWritable;

echo json_encode([
    'success' => true,
    'all_passed' => $allPassed,
    'details' => [
        'php_version' => ['version' => PHP_VERSION, 'passed' => $phpPass],
        'pdo' => $pdoPass,
        'curl' => $curlPass,
        'json' => $jsonPass,
        'openssl' => $opensslPass,
        'storage_writable' => $storageWritable,
    ]
]);
