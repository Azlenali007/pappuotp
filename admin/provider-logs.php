<?php
/**
 * Admin Provider Error Logs & Sync Records
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$logs = $db->query("SELECT h.*, p.name as provider_name FROM import_history h JOIN providers p ON p.id = h.provider_id ORDER BY h.id DESC LIMIT 30")->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">API Sync & Provider Logs</h1>
                    <p class="text-sm text-slate-500">Inspection of automated cron synchronization and error responses</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs divide-y divide-slate-100 overflow-hidden">
                <?php if (empty($logs)): ?>
                    <div class="p-12 text-center text-slate-400 text-sm">No provider error logs recorded. Gateways operational.</div>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <div class="p-5 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900 text-sm"><?= e($l['provider_name']) ?></span>
                                    <?= render_status_badge($l['status']) ?>
                                </div>
                                <span class="text-xs text-slate-400"><?= date('d M Y, H:i', strtotime($l['created_at'])) ?></span>
                            </div>
                            <pre class="p-3 bg-slate-50 rounded-xl text-xs font-mono text-slate-700 whitespace-pre-wrap"><?= e($l['log_details'] ?: 'Synchronization succeeded without errors.') ?></pre>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
