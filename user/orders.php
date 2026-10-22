<?php
/**
 * User Orders Management Page
 * Handles order creation POST and displays orders list
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../services/order-manager.php';
require_once __DIR__ . '/../includes/pagination.php';

$db = get_db();
$userId = (int)$currentUser['id'];

// Handle POST order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_order') {
    require_csrf_token();
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $countryCode = trim($_POST['country_code'] ?? 'IN');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $result = OrderManager::createOrder($userId, $serviceId, $countryCode, $quantity);

    if ($result['success']) {
        set_flash('success', 'Virtual number successfully allocated!');
        header('Location: /user/order-details.php?id=' . $result['order_id']);
        exit;
    } else {
        set_flash('error', $result['error'] ?? 'Failed to create order.');
        header('Location: /user/buy-number.php?service_id=' . $serviceId);
        exit;
    }
}

$pageTitle = 'Orders - OTP Services';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Filtering
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["o.user_id = ?"];
$params = [$userId];

if ($statusFilter !== '') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(o.order_ref LIKE ? OR o.phone_number LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Count total
$stmtCount = $db->prepare("SELECT COUNT(*) FROM orders o JOIN services s ON s.id = o.service_id WHERE {$whereSql}");
$stmtCount->execute($params);
$totalOrders = (int)$stmtCount->fetchColumn();

$paginator = paginate($totalOrders, $page, 10);

// Fetch orders
$stmt = $db->prepare("
    SELECT o.*, s.name as service_name, s.icon as service_icon 
    FROM orders o 
    JOIN services s ON s.id = o.service_id 
    WHERE {$whereSql} 
    ORDER BY o.id DESC 
    LIMIT {$paginator['per_page']} OFFSET {$paginator['offset']}
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Flash Message -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Order History</h1>
                    <p class="text-sm text-slate-500">Track and view your SMS verification codes</p>
                </div>
                <a href="/user/services.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Order
                </a>
            </div>

            <!-- Filter Tabs & Search -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Status Pills -->
                <div class="flex flex-wrap gap-1.5 text-xs font-semibold">
                    <a href="/user/orders.php<?= $search ? '?search=' . urlencode($search) : '' ?>" class="px-3.5 py-2 rounded-xl transition-colors <?= $statusFilter === '' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' ?>">All</a>
                    <a href="/user/orders.php?status=pending<?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3.5 py-2 rounded-xl transition-colors <?= $statusFilter === 'pending' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' ?>">Pending</a>
                    <a href="/user/orders.php?status=completed<?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3.5 py-2 rounded-xl transition-colors <?= $statusFilter === 'completed' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' ?>">Completed</a>
                    <a href="/user/orders.php?status=cancelled<?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3.5 py-2 rounded-xl transition-colors <?= $statusFilter === 'cancelled' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' ?>">Cancelled</a>
                </div>

                <!-- Search Input -->
                <form method="GET" action="/user/orders.php" class="relative max-w-xs w-full">
                    <?php if ($statusFilter): ?>
                        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                    <?php endif; ?>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search orders..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                </form>
            </div>

            <!-- Orders Table (matching screenshot) -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Service</th>
                                <th class="px-6 py-4">Phone Number</th>
                                <th class="px-6 py-4">OTP / Code</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                        No orders matching your filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900">
                                            #<?= e($order['order_ref']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-6 h-6 rounded-md bg-slate-50 flex items-center justify-center shrink-0">
                                                    <?= get_service_svg_icon($order['service_name'], "w-4 h-4") ?>
                                                </div>
                                                <span class="font-bold text-slate-900"><?= e($order['service_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800">
                                            <?= e($order['phone_number']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($order['otp_code']): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 font-mono font-bold text-xs border border-purple-200">
                                                    <?= e($order['otp_code']) ?>
                                                </span>
                                            <?php elseif ($order['status'] === 'pending'): ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs text-amber-600 font-medium animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    Waiting for SMS...
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= render_status_badge($order['status']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-slate-900 text-xs">
                                            <?= format_currency($order['cost']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                            <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="/user/order-details.php?id=<?= $order['id'] ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition-all shadow-2xs">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?= render_pagination($paginator, '/user/orders.php') ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
