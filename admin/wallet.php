<?php
/**
 * Admin Wallet Adjustments Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/wallet-manager.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $userId = (int)$_POST['user_id'];
    $amount = (float)($_POST['amount'] ?? 0);
    $type = $_POST['adj_type'] ?? 'add';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($reason)) {
        set_flash('error', 'Mandatory reason is required for administrative financial adjustments.');
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
    header("Location: /admin/wallet.php");
    exit;
}

$users = $db->query("SELECT u.id, u.name, u.email, COALESCE(w.balance, 0.00) as balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id ORDER BY u.name ASC")->fetchAll();
$adjustments = $db->query("SELECT * FROM wallet_transactions WHERE type = 'adjustment' ORDER BY id DESC LIMIT 30")->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-6xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Wallet Adjustments</h1>
                <p class="text-sm text-slate-500">Perform authorized administrative credits or debits with audit reasoning</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <h2 class="text-base font-bold text-slate-900">New Balance Adjustment</h2>

                <form action="/admin/wallet.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="user_id" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Select User</label>
                            <select id="user_id" name="user_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none">
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= format_currency($u['balance']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase">Action</label>
                            <select name="adj_type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none">
                                <option value="add">Credit (+)</option>
                                <option value="deduct">Debit (-)</option>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" required placeholder="50.00" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="reason" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Mandatory Audit Reason</label>
                        <textarea id="reason" name="reason" rows="2" required placeholder="State exact reason for adjustments (e.g. promotional credit, dispute resolution)..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">Apply Adjustment</button>
                    </div>
                </form>
            </div>

            <!-- Recent Adjustments Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Recent Manual Adjustments</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Transaction Ref</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Balance After</th>
                                <th class="px-6 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($adjustments)): ?>
                                <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-xs">No manual adjustments recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($adjustments as $a): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-800"><?= e($a['transaction_ref']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-700"><?= e($a['description']) ?></td>
                                        <td class="px-6 py-4 font-bold text-xs <?= $a['amount'] > 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                                            <?= format_currency($a['amount']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-slate-600"><?= format_currency($a['balance_after']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($a['created_at'])) ?></td>
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
