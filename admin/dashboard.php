<?php
/**
 * Super Admin Dashboard
 * Displays authentic operational metrics, live carrier orders, and verified revenue charts
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();

// Authentic Metrics
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE type = 'deposit'")->fetchColumn();
$pendingOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Compute real 7-day revenue trend from transactions ledger
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('M j', strtotime($dateStr));
    $stmtDay = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) 
        FROM wallet_transactions 
        WHERE type = 'deposit' AND DATE(created_at) = ?
    ");
    $stmtDay->execute([$dateStr]);
    $chartData[] = (float)$stmtDay->fetchColumn();
}

// Fetch Recent Orders with user & service info
$stmt = $db->query("
    SELECT o.*, u.name as user_name, s.name as service_name, s.icon as service_icon 
    FROM orders o 
    JOIN users u ON u.id = o.user_id 
    JOIN services s ON s.id = o.service_id 
    ORDER BY o.id DESC 
    LIMIT 6
");
$recentOrders = $stmt->fetchAll();

$pageTitle = 'Dashboard - Super Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Title Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Admin Dashboard</h1>
                <p class="text-sm text-slate-500">Live overview of your verification platform, carrier gateways, and verified revenue</p>
            </div>

            <!-- 4 Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- 1. Total Users -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <span>Total Users</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= number_format($totalUsers) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 flex items-center gap-1 text-xs font-semibold text-slate-400">
                        <span>Registered accounts</span>
                    </div>
                </div>

                <!-- 2. Total Orders -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <span>Total Orders</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= number_format($totalOrders) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 flex items-center gap-1 text-xs font-semibold text-slate-400">
                        <span>Lifetime allocations</span>
                    </div>
                </div>

                <!-- 3. Total Revenue -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span>Total Deposits</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= format_currency($totalRevenue) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 flex items-center gap-1 text-xs font-semibold text-slate-400">
                        <span>Verified credited funds</span>
                    </div>
                </div>

                <!-- 4. Pending Orders -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span>Pending Orders</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                            <?= number_format($pendingOrders) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 flex items-center gap-1 text-xs font-semibold text-amber-600">
                        <span>Awaiting verification SMS</span>
                    </div>
                </div>
            </div>

            <!-- Chart & Recent Orders Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left: Revenue Overview Smooth Chart -->
                <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900">Revenue Overview (7 Days)</h2>
                        <span class="text-xs text-slate-400 font-semibold"><?= reset($chartLabels) ?> – <?= end($chartLabels) ?></span>
                    </div>

                    <div class="h-64 relative">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Right: Recent Orders Table -->
                <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-1.5">
                            Recent Orders
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </h2>
                        <a href="/admin/orders.php" class="text-xs font-semibold text-purple-600 hover:text-purple-800">View All</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="text-[10px] uppercase font-bold text-slate-400 tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="pb-3">User</th>
                                    <th class="pb-3">Service</th>
                                    <th class="pb-3">Status</th>
                                    <th class="pb-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-slate-400">No orders placed yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $ro): ?>
                                        <tr class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-3 font-semibold text-slate-900"><?= e($ro['user_name']) ?></td>
                                            <td class="py-3 text-slate-600"><?= e($ro['service_name']) ?></td>
                                            <td class="py-3"><?= render_status_badge($ro['status']) ?></td>
                                            <td class="py-3 font-bold text-slate-900 text-right"><?= format_currency($ro['cost']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-3 border-t border-slate-50 text-center">
                        <a href="/admin/orders.php" class="text-xs font-bold text-slate-600 hover:text-purple-600">
                            View All Orders &rarr;
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(124, 58, 237, 0.35)');
    gradient.addColorStop(1, 'rgba(124, 58, 237, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Revenue (₹)',
                data: <?= json_encode($chartData) ?>,
                borderColor: '#7C3AED',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#7C3AED'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    grid: { color: 'rgba(226, 232, 240, 0.6)' },
                    ticks: {
                        callback: val => '₹ ' + val
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
