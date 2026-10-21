<?php
/**
 * User Dashboard
 * Matches Reference Screenshot Exactly
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)$currentUser['id'];

// Get user statistics
$stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$totalOrders = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$successfulOrders = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingOrders = (int)$stmt->fetchColumn();

$successRate = $totalOrders > 0 ? round(($successfulOrders / $totalOrders) * 100, 1) : 100;

// Fetch Recent Orders with service details
$stmt = $db->prepare("
    SELECT o.*, s.name as service_name, s.icon as service_icon 
    FROM orders o 
    JOIN services s ON s.id = o.service_id 
    WHERE o.user_id = ? 
    ORDER BY o.id DESC 
    LIMIT 6
");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Dashboard</h1>
                <p class="text-sm text-slate-500">Welcome back, <span class="font-semibold text-slate-800"><?= e($currentUser['name']) ?></span>! Here's your account overview.</p>
            </div>

            <!-- 4 Stat Cards (matching screenshot) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- 1. Wallet Balance -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <span>Wallet Balance</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= format_currency($currentUser['balance']) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50">
                        <a href="/user/add-funds.php" class="text-xs font-bold text-purple-600 hover:text-purple-800 inline-flex items-center gap-1">
                            + Add Funds &gt;
                        </a>
                    </div>
                </div>

                <!-- 2. Total Orders -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <span>Total Orders</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= $totalOrders ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50">
                        <a href="/user/orders.php" class="text-xs font-bold text-purple-600 hover:text-purple-800 inline-flex items-center gap-1">
                            View All &gt;
                        </a>
                    </div>
                </div>

                <!-- 3. Successful Orders -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span>Successful Orders</span>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                                <?= $successfulOrders ?>
                            </div>
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                                <?= $successRate ?>%
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 text-xs text-slate-400">
                        Completed verification rate
                    </div>
                </div>

                <!-- 4. Pending Orders -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span>Pending Orders</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= $pendingOrders ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50">
                        <a href="/user/orders.php?status=pending" class="text-xs font-bold text-purple-600 hover:text-purple-800 inline-flex items-center gap-1">
                            View All &gt;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Lower Layout: Recent Orders & Quick Actions (matching screenshot) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Left: Recent Orders Table -->
                <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                Recent Orders
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </h2>
                            <a href="/user/orders.php" class="text-xs font-semibold text-purple-600 hover:text-purple-800">View All</a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3.5">Service</th>
                                        <th class="px-6 py-3.5">Number</th>
                                        <th class="px-6 py-3.5">Status</th>
                                        <th class="px-6 py-3.5">Date</th>
                                        <th class="px-6 py-3.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($recentOrders)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                                No orders created yet. Purchase your first virtual number below!
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                                            <?= get_service_svg_icon($order['service_name'], "w-4 h-4") ?>
                                                        </div>
                                                        <span class="font-semibold text-slate-900"><?= e($order['service_name']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 font-mono text-xs font-medium text-slate-700">
                                                    <?= e($order['phone_number']) ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?= render_status_badge($order['status']) ?>
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                                    <?= date('d M Y', strtotime($order['created_at'])) ?>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <a href="/user/order-details.php?id=<?= $order['id'] ?>" class="text-slate-400 hover:text-purple-600 font-bold p-1">
                                                        ⋮
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-100 text-center">
                        <a href="/user/orders.php" class="text-xs font-bold text-slate-600 hover:text-purple-600">
                            View All Orders &gt;
                        </a>
                    </div>
                </div>

                <!-- Right: Quick Actions (matching screenshot 4 purple buttons) -->
                <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Quick Actions</h2>

                    <div class="space-y-3">
                        <a href="/user/services.php" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Buy OTP
                        </a>

                        <a href="/user/add-funds.php" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add Funds
                        </a>

                        <a href="/user/orders.php" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            View Orders
                        </a>

                        <a href="/user/support.php" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Support
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
