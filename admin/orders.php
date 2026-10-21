<?php
/**
 * Admin Orders Management Page
 * Matches Reference Image "Admin Orders Page" Exactly
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/pagination.php';

$db = get_db();

// Filters
$serviceId = (int)($_GET['service_id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Fetch Services for dropdown
$allServices = $db->query("SELECT id, name FROM services ORDER BY name ASC")->fetchAll();

$where = ["1=1"];
$params = [];

if ($serviceId > 0) {
    $where[] = "o.service_id = ?";
    $params[] = $serviceId;
}

if ($status !== '') {
    $where[] = "o.status = ?";
    $params[] = $status;
}

if ($startDate !== '') {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate !== '') {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $endDate;
}

if ($search !== '') {
    $where[] = "(o.order_ref LIKE ? OR o.phone_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Count
$stmtCount = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id JOIN services s ON s.id = o.service_id WHERE {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$paginator = paginate($total, $page, 15);

// Fetch orders
$stmt = $db->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email, s.name as service_name, s.icon as service_icon 
    FROM orders o 
    JOIN users u ON u.id = o.user_id 
    JOIN services s ON s.id = o.service_id 
    WHERE {$whereSql} 
    ORDER BY o.id DESC 
    LIMIT {$paginator['per_page']} OFFSET {$paginator['offset']}
");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Title Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Orders</h1>
                <p class="text-sm text-slate-500">Manage and track all verification orders across providers</p>
            </div>

            <!-- Filters Bar (matching screenshot) -->
            <form method="GET" action="/admin/orders.php" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Service Dropdown -->
                <div class="sm:col-span-3">
                    <select name="service_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                        <option value="">All Services</option>
                        <?php foreach ($allServices as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $serviceId === (int)$s['id'] ? 'selected' : '' ?>>
                                <?= e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Dropdown -->
                <div class="sm:col-span-2">
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="sm:col-span-4 flex items-center gap-2">
                    <input type="date" name="start_date" value="<?= e($startDate) ?>" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
                    <span class="text-xs text-slate-400 font-bold">&rarr;</span>
                    <input type="date" name="end_date" value="<?= e($endDate) ?>" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
                </div>

                <!-- Search -->
                <div class="sm:col-span-3 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search..." class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                    <?php if ($serviceId || $status || $startDate || $endDate || $search): ?>
                        <a href="/admin/orders.php" class="absolute right-2 top-2 text-[10px] font-bold text-slate-400 hover:text-slate-700">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Table (matching screenshot) -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Service</th>
                                <th class="px-6 py-4">Number</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400">No orders matching query.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-900">
                                            #<?= e($o['order_ref']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900 text-xs"><?= e($o['user_name']) ?></div>
                                            <div class="text-[11px] text-slate-400"><?= e($o['user_email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-5 h-5 rounded-md bg-slate-50 flex items-center justify-center shrink-0">
                                                    <?= get_service_svg_icon($o['service_name'], "w-3.5 h-3.5") ?>
                                                </div>
                                                <span class="font-semibold text-slate-800 text-xs"><?= e($o['service_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-700">
                                            <?= e($o['phone_number']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= render_status_badge($o['status']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-slate-900 text-xs">
                                            <?= format_currency($o['cost']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                            <?= date('d M Y', strtotime($o['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="/admin/order-details.php?id=<?= $o['id'] ?>" class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200 transition-all shadow-2xs">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?= render_pagination($paginator, '/admin/orders.php') ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
