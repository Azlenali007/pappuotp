<?php
/**
 * Admin Reports & Financial Analytics
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$pageTitle = 'Reports & Financial Analytics';
$db = getDB();

// Aggregated stats
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$totalSpentOnOrders = (float)$db->query("SELECT COALESCE(SUM(price), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
$totalRefunded = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'completed'")->fetchColumn();
$totalCompletedOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$totalCancelledOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Daily revenue for last 7 days
$stmt = $db->query("
    SELECT DATE(created_at) as day, COALESCE(SUM(amount), 0) as daily_rev
    FROM payments
    WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$dailyRevData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$revenuePoints = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($d));
    $found = 0;
    foreach ($dailyRevData as $row) {
        if ($row['day'] === $d) {
            $found = (float)$row['daily_rev'];
            break;
        }
    }
    $revenuePoints[] = $found;
}

// Top services
$stmt = $db->query("
    SELECT s.name, COUNT(o.id) as total_orders, COALESCE(SUM(o.price), 0) as total_revenue
    FROM orders o
    JOIN services s ON s.id = o.service_id
    WHERE o.status = 'completed'
    GROUP BY s.id, s.name
    ORDER BY total_orders DESC
    LIMIT 5
");
$topServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Financial Reports & Analytics</h1>
            <p class="text-sm text-slate-500">Revenue, order volume, margins, and platform performance</p>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Gross Deposits</span>
            <div class="text-2xl font-bold text-purple-600 mt-2">₹<?= number_format($totalRevenue, 2) ?></div>
            <span class="text-xs text-emerald-600 font-medium flex items-center gap-1 mt-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Deposited via Gateways
            </span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Order Volume Revenue</span>
            <div class="text-2xl font-bold text-slate-900 mt-2">₹<?= number_format($totalSpentOnOrders, 2) ?></div>
            <span class="text-xs text-slate-500 font-medium mt-1">From <?= $totalCompletedOrders ?> completed OTPs</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Refunds Issued</span>
            <div class="text-2xl font-bold text-rose-600 mt-2">₹<?= number_format($totalRefunded, 2) ?></div>
            <span class="text-xs text-slate-500 font-medium mt-1">Returned to wallet balances</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registered Users</span>
            <div class="text-2xl font-bold text-slate-900 mt-2"><?= number_format($totalUsers) ?></div>
            <span class="text-xs text-emerald-600 font-medium mt-1">Active customer accounts</span>
        </div>
    </div>

    <!-- Chart & Top Services Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
            <h3 class="text-base font-semibold text-slate-900 mb-4">7-Day Revenue Trend</h3>
            <div class="h-64 relative">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Top Services -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Top Verification Services</h3>
            <?php if (empty($topServices)): ?>
            <p class="text-sm text-slate-400 py-6 text-center">No orders completed yet.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($topServices as $idx => $ts): ?>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-xs"><?= $idx + 1 ?></span>
                        <div>
                            <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($ts['name']) ?></div>
                            <div class="text-xs text-slate-500"><?= $ts['total_orders'] ?> activations</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-purple-600">₹<?= number_format($ts['total_revenue'], 2) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Daily Revenue (₹)',
                    data: <?= json_encode($revenuePoints) ?>,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#7c3aed',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return '₹' + val; }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
