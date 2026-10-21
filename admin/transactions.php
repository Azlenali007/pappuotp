<?php
/**
 * Admin Global Transactions Ledger
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/pagination.php';

$db = get_db();
$userId = (int)($_GET['user_id'] ?? 0);
$type = trim($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["1=1"];
$params = [];

if ($userId > 0) {
    $where[] = "t.user_id = ?";
    $params[] = $userId;
}

if ($type !== '') {
    $where[] = "t.type = ?";
    $params[] = $type;
}

$whereSql = implode(' AND ', $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM wallet_transactions t WHERE {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$paginator = paginate($total, $page, 15);

$stmt = $db->prepare("
    SELECT t.*, u.name as user_name, u.email as user_email 
    FROM wallet_transactions t 
    JOIN users u ON u.id = t.user_id 
    WHERE {$whereSql} 
    ORDER BY t.id DESC 
    LIMIT {$paginator['per_page']} OFFSET {$paginator['offset']}
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">System Financial Ledger</h1>
                <p class="text-sm text-slate-500">Immutable ledger records across all users and wallets</p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2 text-xs font-semibold bg-white p-3 rounded-2xl border border-slate-100 shadow-xs">
                <a href="/admin/transactions.php" class="px-3.5 py-1.5 rounded-xl <?= $type === '' ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">All</a>
                <a href="/admin/transactions.php?type=deposit" class="px-3.5 py-1.5 rounded-xl <?= $type === 'deposit' ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">Deposits</a>
                <a href="/admin/transactions.php?type=order_payment" class="px-3.5 py-1.5 rounded-xl <?= $type === 'order_payment' ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">Orders</a>
                <a href="/admin/transactions.php?type=refund" class="px-3.5 py-1.5 rounded-xl <?= $type === 'refund' ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">Refunds</a>
                <a href="/admin/transactions.php?type=adjustment" class="px-3.5 py-1.5 rounded-xl <?= $type === 'adjustment' ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">Adjustments</a>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Txn Ref</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Balance After</th>
                                <th class="px-6 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($transactions as $t): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-800"><?= e($t['transaction_ref']) ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 text-xs"><?= e($t['user_name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= e($t['user_email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-[10px] uppercase"><?= e($t['type']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-600"><?= e($t['description']) ?></td>
                                    <td class="px-6 py-4 font-bold text-xs <?= in_array($t['type'], ['deposit', 'refund']) ? 'text-emerald-600' : 'text-slate-900' ?>">
                                        <?= in_array($t['type'], ['deposit', 'refund']) ? '+' : '-' ?> <?= format_currency($t['amount']) ?>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-800"><?= format_currency($t['balance_after']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?= render_pagination($paginator, '/admin/transactions.php') ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
