<?php
/**
 * Admin Support Tickets Management
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$tickets = $db->query("SELECT t.*, u.name as user_name, u.email as user_email FROM support_tickets t JOIN users u ON u.id = t.user_id ORDER BY (t.status = 'open') DESC, t.id DESC")->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Support Tickets</h1>
                <p class="text-sm text-slate-500">Respond to customer requests and resolve verification disputes</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Ref</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Subject</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Priority</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Created</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($tickets as $t): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs font-bold text-purple-600"><?= e($t['ticket_ref']) ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 text-xs"><?= e($t['user_name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= e($t['user_email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-900 text-xs"><?= e($t['subject']) ?></td>
                                    <td class="px-6 py-4 text-xs"><?= e($t['department']) ?></td>
                                    <td class="px-6 py-4 text-xs font-bold uppercase"><?= e($t['priority']) ?></td>
                                    <td class="px-6 py-4"><?= render_status_badge($t['status']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="/admin/ticket-details.php?id=<?= $t['id'] ?>" class="px-3 py-1 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition-all">
                                            Reply
                                        </a>
                                    </td>
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
