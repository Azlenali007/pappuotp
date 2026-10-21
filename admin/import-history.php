<?php
/**
 * Admin Service Import History Logs
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

$stmt = $db->query("
    SELECT h.*, p.name as provider_name 
    FROM import_history h 
    JOIN providers p ON p.id = h.provider_id 
    ORDER BY h.id DESC 
    LIMIT 50
");
$history = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Import History</h1>
                    <p class="text-sm text-slate-500">Audit log of all catalog import and synchronization runs</p>
                </div>
                <a href="/admin/service-importer.php" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                    + Run New Import
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Provider</th>
                                <th class="px-6 py-4">Fetched</th>
                                <th class="px-6 py-4">Imported</th>
                                <th class="px-6 py-4">Updated</th>
                                <th class="px-6 py-4">Skipped</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400">No import history yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-900">#<?= $h['id'] ?></td>
                                        <td class="px-6 py-4 font-bold text-slate-900"><?= e($h['provider_name']) ?></td>
                                        <td class="px-6 py-4 text-xs font-semibold"><?= $h['total_fetched'] ?></td>
                                        <td class="px-6 py-4 text-xs font-bold text-emerald-600">+<?= $h['imported'] ?></td>
                                        <td class="px-6 py-4 text-xs font-bold text-purple-600"><?= $h['updated'] ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-400"><?= $h['skipped'] ?></td>
                                        <td class="px-6 py-4"><?= render_status_badge($h['status']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($h['created_at'])) ?></td>
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
