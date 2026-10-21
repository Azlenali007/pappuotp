<?php
/**
 * User Payment & Deposit History
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)$currentUser['id'];

$stmt = $db->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$userId]);
$deposits = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Deposit & Payment History</h1>
                    <p class="text-sm text-slate-500">Record of all online wallet deposits</p>
                </div>
                <a href="/user/add-funds.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200">
                    Add Funds
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Deposit Ref</th>
                                <th class="px-6 py-4">Gateway</th>
                                <th class="px-6 py-4">Gateway Payment ID</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">No deposits found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $dep): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900"><?= e($dep['deposit_ref']) ?></td>
                                        <td class="px-6 py-4 text-xs font-semibold uppercase text-slate-700"><?= e($dep['gateway']) ?></td>
                                        <td class="px-6 py-4 font-mono text-xs text-slate-600"><?= e($dep['gateway_payment_id'] ?: 'Pending') ?></td>
                                        <td class="px-6 py-4 font-bold text-xs text-emerald-600"><?= format_currency($dep['amount']) ?></td>
                                        <td class="px-6 py-4"><?= render_status_badge($dep['status']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($dep['created_at'])) ?></td>
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
