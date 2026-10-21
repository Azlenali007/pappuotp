<?php
/**
 * Admin User Details & Ledger Inspection
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/wallet-manager.php';

$db = get_db();
$userId = (int)($_GET['id'] ?? 0);

// Handle manual balance adjustment from user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adjust_balance') {
    require_csrf_token();
    $amount = (float)($_POST['amount'] ?? 0);
    $type = $_POST['adj_type'] ?? 'add';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($reason)) {
        set_flash('error', 'A mandatory administrative reason is required for balance adjustments.');
    } elseif ($amount <= 0) {
        set_flash('error', 'Amount must be greater than zero.');
    } else {
        if ($type === 'add') {
            WalletManager::addFunds($userId, $amount, "Admin credit: {$reason}", null, 'adjustment');
            log_admin_audit('wallet_adjusted', "Added ₹{$amount} to user ID {$userId}. Reason: {$reason}");
            set_flash('success', "Credited ₹ " . number_format($amount, 2) . " to user wallet.");
        } else {
            WalletManager::deductFunds($userId, $amount, "Admin debit: {$reason}", null);
            log_admin_audit('wallet_adjusted', "Deducted ₹{$amount} from user ID {$userId}. Reason: {$reason}");
            set_flash('success', "Deducted ₹ " . number_format($amount, 2) . " from user wallet.");
        }
    }
    header("Location: /admin/user-details.php?id=" . $userId);
    exit;
}

// Fetch user
$stmt = $db->prepare("SELECT u.*, COALESCE(w.balance, 0.00) as balance, w.currency FROM users u LEFT JOIN wallets w ON w.user_id = u.id WHERE u.id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: /admin/users.php");
    exit;
}

// Fetch user orders
$stmt = $db->prepare("SELECT o.*, s.name as service_name FROM orders o JOIN services s ON s.id = o.service_id WHERE o.user_id = ? ORDER BY o.id DESC LIMIT 10");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Fetch transactions
$stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{ adjustModal: false }">
        <div class="max-w-6xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/users.php" class="hover:text-purple-600">Users</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($user['name']) ?></span>
            </div>

            <!-- User Header Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-purple-600 text-white font-extrabold flex items-center justify-center text-xl shadow-md">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-extrabold text-slate-900"><?= e($user['name']) ?></h1>
                            <?= render_status_badge($user['status']) ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-1"><?= e($user['email']) ?> • Username: <span class="font-mono font-bold text-slate-700"><?= e($user['username'] ?? '—') ?></span></div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-xs text-slate-400 font-semibold uppercase">Wallet Balance</div>
                        <div class="text-2xl font-black text-slate-900 mt-0.5"><?= format_currency($user['balance']) ?></div>
                    </div>
                    <button type="button" @click="adjustModal = true" class="px-4 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                        Adjust Balance
                    </button>
                </div>
            </div>

            <!-- Balance Adjustment Modal -->
            <div x-show="adjustModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" style="display: none;">
                <div @click.away="adjustModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-100">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900">Adjust User Balance</h3>
                        <button type="button" @click="adjustModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                    </div>

                    <form action="/admin/user-details.php?id=<?= $user['id'] ?>" method="POST" class="space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="adjust_balance">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1 uppercase">Adjustment Type</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="p-3 rounded-xl border border-slate-200 flex items-center gap-2 cursor-pointer text-xs font-bold">
                                    <input type="radio" name="adj_type" value="add" checked class="text-purple-600">
                                    Credit (+)
                                </label>
                                <label class="p-3 rounded-xl border border-slate-200 flex items-center gap-2 cursor-pointer text-xs font-bold">
                                    <input type="radio" name="adj_type" value="deduct" class="text-purple-600">
                                    Debit (-)
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="amount" class="block text-xs font-semibold text-slate-700 mb-1 uppercase">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" required placeholder="100.00" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none">
                        </div>

                        <div>
                            <label for="reason" class="block text-xs font-semibold text-slate-700 mb-1 uppercase">Mandatory Reason / Note</label>
                            <textarea id="reason" name="reason" rows="2" required placeholder="Required explanation for financial audit..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="adjustModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">Apply Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders & Ledger 2-Column Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                
                <!-- Recent Orders -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Recent User Orders</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="text-slate-400 uppercase font-bold border-b border-slate-100">
                                <tr>
                                    <th class="pb-2">Order</th>
                                    <th class="pb-2">Service</th>
                                    <th class="pb-2">Number</th>
                                    <th class="pb-2">Status</th>
                                    <th class="pb-2 text-right">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="5" class="py-6 text-center text-slate-400">No orders created yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="py-2.5 font-mono font-bold text-purple-600">#<?= e($o['order_ref']) ?></td>
                                            <td class="py-2.5 font-semibold text-slate-900"><?= e($o['service_name']) ?></td>
                                            <td class="py-2.5 font-mono"><?= e($o['phone_number']) ?></td>
                                            <td class="py-2.5"><?= render_status_badge($o['status']) ?></td>
                                            <td class="py-2.5 font-bold text-right"><?= format_currency($o['cost']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Ledger Entries -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Recent Wallet Ledger</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="text-slate-400 uppercase font-bold border-b border-slate-100">
                                <tr>
                                    <th class="pb-2">Ref</th>
                                    <th class="pb-2">Type</th>
                                    <th class="pb-2">Amount</th>
                                    <th class="pb-2 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($transactions)): ?>
                                    <tr><td colspan="4" class="py-6 text-center text-slate-400">No transactions recorded.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td class="py-2.5 font-mono"><?= e($t['transaction_ref']) ?></td>
                                            <td class="py-2.5 font-bold uppercase text-[10px]"><?= e($t['type']) ?></td>
                                            <td class="py-2.5 font-bold <?= in_array($t['type'], ['deposit', 'refund']) ? 'text-emerald-600' : 'text-slate-900' ?>">
                                                <?= in_array($t['type'], ['deposit', 'refund']) ? '+' : '-' ?> <?= format_currency($t['amount']) ?>
                                            </td>
                                            <td class="py-2.5 font-mono font-bold text-right"><?= format_currency($t['balance_after']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
