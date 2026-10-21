<?php
/**
 * User Transactions Ledger Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/pagination.php';

$db = get_db();
$userId = (int)$currentUser['id'];

$typeFilter = trim($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["user_id = ?"];
$params = [$userId];

if ($typeFilter !== '') {
    $where[] = "type = ?";
    $params[] = $typeFilter;
}

$whereSql = implode(' AND ', $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$paginator = paginate($total, $page, 15);

$stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE {$whereSql} ORDER BY id DESC LIMIT {$paginator['per_page']} OFFSET {$paginator['offset']}");
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Transaction Ledger</h1>
                    <p class="text-sm text-slate-500">Immutable record of all debits, credits, and refunds</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/user/payment-history.php" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Deposits</a>
                    <a href="/user/refunds.php" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Refunds</a>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-1.5 text-xs font-semibold bg-white p-3 rounded-2xl border border-slate-100 shadow-xs">
                <a href="/user/transactions.php" class="px-3.5 py-1.5 rounded-xl transition-colors <?= $typeFilter === '' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' ?>">All Types</a>
                <a href="/user/transactions.php?type=deposit" class="px-3.5 py-1.5 rounded-xl transition-colors <?= $typeFilter === 'deposit' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' ?>">Deposits</a>
                <a href="/user/transactions.php?type=order_payment" class="px-3.5 py-1.5 rounded-xl transition-colors <?= $typeFilter === 'order_payment' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' ?>">Order Payments</a>
                <a href="/user/transactions.php?type=refund" class="px-3.5 py-1.5 rounded-xl transition-colors <?= $typeFilter === 'refund' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' ?>">Refunds</a>
                <a href="/user/transactions.php?type=adjustment" class="px-3.5 py-1.5 rounded-xl transition-colors <?= $typeFilter === 'adjustment' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' ?>">Adjustments</a>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Transaction Ref</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Balance Before</th>
                                <th class="px-6 py-4">Balance After</th>
                                <th class="px-6 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">No transactions recorded for this filter.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900"><?= e($txn['transaction_ref']) ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($txn['type'] === 'deposit'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Deposit</span>
                                            <?php elseif ($txn['type'] === 'refund'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">Refund</span>
                                            <?php elseif ($txn['type'] === 'order_payment'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Order</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">Adjustment</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-700"><?= e($txn['description']) ?></td>
                                        <td class="px-6 py-4 font-bold text-xs <?= in_array($txn['type'], ['deposit', 'refund']) ? 'text-emerald-600' : 'text-slate-900' ?>">
                                            <?= in_array($txn['type'], ['deposit', 'refund']) ? '+' : '-' ?> <?= format_currency($txn['amount']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-slate-500"><?= format_currency($txn['balance_before']) ?></td>
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-900"><?= format_currency($txn['balance_after']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i:s', strtotime($txn['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?= render_pagination($paginator, '/user/transactions.php') ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
