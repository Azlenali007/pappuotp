<?php
/**
 * Admin Deposit Requests
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$deposits = $db->query("SELECT d.*, u.name as user_name, u.email as user_email FROM deposits d JOIN users u ON u.id = d.user_id ORDER BY d.id DESC LIMIT 50")->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Deposit Requests</h1>
                <p class="text-sm text-slate-500">Track online checkout orders and gateway fulfillment</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Ref</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Gateway</th>
                                <th class="px-6 py-4">Gateway Payment ID</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($deposits as $d): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-900"><?= e($d['deposit_ref']) ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 text-xs"><?= e($d['user_name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= e($d['user_email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-xs uppercase font-semibold text-slate-600"><?= e($d['gateway']) ?></td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-700"><?= e($d['gateway_payment_id'] ?: 'Pending') ?></td>
                                    <td class="px-6 py-4 font-bold text-xs text-emerald-600"><?= format_currency($d['amount']) ?></td>
                                    <td class="px-6 py-4"><?= render_status_badge($d['status']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($d['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
