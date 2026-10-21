<?php
/**
 * User Wallet Overview Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)$currentUser['id'];

// Get recent transactions
$stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

// Get total spent on orders
$stmt = $db->prepare("SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'order_payment'");
$stmt->execute([$userId]);
$totalSpent = (float)$stmt->fetchColumn();

// Get total deposits
$stmt = $db->prepare("SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'deposit'");
$stmt->execute([$userId]);
$totalDeposited = (float)$stmt->fetchColumn();

// Get total refunds
$stmt = $db->prepare("SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'refund'");
$stmt->execute([$userId]);
$totalRefunded = (float)$stmt->fetchColumn();

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

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Wallet Overview</h1>
                    <p class="text-sm text-slate-500">Manage your balance, add funds, and view financial activity</p>
                </div>
                <a href="/user/add-funds.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Funds
                </a>
            </div>

            <!-- 4 Financial Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Current Balance -->
                <div class="bg-gradient-to-br from-purple-700 to-indigo-900 text-white rounded-2xl p-6 shadow-md shadow-purple-200 flex flex-col justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-purple-200 font-semibold">Available Balance</div>
                        <div class="text-3xl font-extrabold mt-2"><?= format_currency($currentUser['balance']) ?></div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-purple-500/30 flex justify-between items-center text-xs">
                        <span>Currency: <?= e($currentUser['currency']) ?></span>
                        <a href="/user/add-funds.php" class="font-bold underline hover:text-purple-200">+ Top Up</a>
                    </div>
                </div>

                <!-- Total Deposited -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Deposited</div>
                        <div class="text-2xl font-bold text-slate-900 mt-2"><?= format_currency($totalDeposited) ?></div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 text-xs text-slate-400">
                        Via Razorpay & Bank
                    </div>
                </div>

                <!-- Total Spent -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Spent</div>
                        <div class="text-2xl font-bold text-slate-900 mt-2"><?= format_currency($totalSpent) ?></div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 text-xs text-slate-400">
                        Virtual number orders
                    </div>
                </div>

                <!-- Total Refunded -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Refunded</div>
                        <div class="text-2xl font-bold text-emerald-600 mt-2"><?= format_currency($totalRefunded) ?></div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-50 text-xs text-slate-400">
                        100% guarantee credits
                    </div>
                </div>
            </div>

            <!-- Recent Ledger Activity -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Recent Wallet Activity</h2>
                    <a href="/user/transactions.php" class="text-xs font-semibold text-purple-600 hover:text-purple-800">All Transactions &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5">Ref</th>
                                <th class="px-6 py-3.5">Type</th>
                                <th class="px-6 py-3.5">Description</th>
                                <th class="px-6 py-3.5">Amount</th>
                                <th class="px-6 py-3.5">Balance After</th>
                                <th class="px-6 py-3.5">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">No transactions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800">
                                            <?= e($txn['transaction_ref']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($txn['type'] === 'deposit'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">+ Deposit</span>
                                            <?php elseif ($txn['type'] === 'refund'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">+ Refund</span>
                                            <?php elseif ($txn['type'] === 'order_payment'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Order</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">Adjustment</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-700">
                                            <?= e($txn['description']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-xs <?= in_array($txn['type'], ['deposit', 'refund']) ? 'text-emerald-600' : 'text-slate-900' ?>">
                                            <?= in_array($txn['type'], ['deposit', 'refund']) ? '+' : '-' ?> <?= format_currency($txn['amount']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                            <?= format_currency($txn['balance_after']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap">
                                            <?= date('d M Y, H:i', strtotime($txn['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
