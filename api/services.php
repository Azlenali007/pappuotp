<?php
/**
 * API: Services Search and Listing
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$db = getDB();

$categoryId = (int)($_GET['category_id'] ?? 0);
$search = trim($_GET['q'] ?? '');

$where = ["s.status = 'active'"];
$params = [];

if ($categoryId > 0) {
    $where[] = "s.category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

if ($search !== '') {
    $where[] = "(s.name LIKE :search OR s.code LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.id, s.name, s.code, s.price, s.provider_cost, s.stock_status, s.icon,
           cat.name as category_name
    FROM services s
    LEFT JOIN service_categories cat ON cat.id = s.category_id
    WHERE {$whereSql}
    ORDER BY s.name ASC
    LIMIT 100
");
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'count' => count($services),
    'services' => array_map(function($s) {
        return [
            'id' => (int)$s['id'],
            'name' => $s['name'],
            'code' => $s['code'],
            'price' => (float)$s['price'],
            'formatted_price' => '₹' . number_format((float)$s['price'], 2),
            'stock_status' => $s['stock_status'] ?? 'available',
            'icon' => $s['icon'] ?? 'shield',
            'category_name' => $s['category_name'] ?? 'General'
        ];
    }, $services)
]);
