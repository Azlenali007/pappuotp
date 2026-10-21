<?php
/**
 * Mandatory Service Importer Interface
 * Matches Section 8 Requirements
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/service-importer.php';

$db = get_db();
$providers = $db->query("SELECT * FROM providers ORDER BY id ASC")->fetchAll();
$selectedProviderId = (int)($_GET['provider_id'] ?? ($providers[0]['id'] ?? 1));

$previewData = null;
if ($selectedProviderId > 0) {
    $previewData = ServiceImporter::preview($selectedProviderId);
}

// Handle Import POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_services') {
    require_csrf_token();
    $providerId = (int)$_POST['provider_id'];
    $selectedCodes = $_POST['services'] ?? [];
    $approveImmediately = isset($_POST['approve_immediately']);

    if (empty($selectedCodes)) {
        set_flash('error', 'Please select at least one service to import.');
    } else {
        $result = ServiceImporter::import($providerId, $selectedCodes, $approveImmediately);
        if ($result['success']) {
            log_admin_audit('services_imported', "Imported {$result['imported']} new, updated {$result['updated']} existing services.");
            set_flash('success', "Import Complete! Imported: {$result['imported']} new, Updated: {$result['updated']} existing, Skipped: {$result['skipped']}.");
        } else {
            set_flash('error', 'Import failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }
    header("Location: /admin/service-importer.php?provider_id=" . $providerId);
    exit;
}

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{
        selectAll: false,
        toggleAll() {
            let boxes = document.querySelectorAll('.service-checkbox');
            boxes.forEach(b => b.checked = this.selectAll);
        }
    }">
        <div class="max-w-7xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Title & Import History Link -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Service Importer</h1>
                    <p class="text-sm text-slate-500">Synchronize supported services and provider cost prices from connected APIs</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin/import-history.php" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs">
                        View Import History
                    </a>
                </div>
            </div>

            <!-- Provider Selection Bar -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <label for="provider_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Target Provider:</label>
                    <form method="GET" action="/admin/service-importer.php" class="inline">
                        <select id="provider_id" name="provider_id" onchange="this.form.submit()" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                            <?php foreach ($providers as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $selectedProviderId === (int)$p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['name']) ?> (<?= format_currency($p['balance']) ?> balance)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="text-xs text-slate-500">
                    Auto duplicate detection active • Preserves existing selling prices
                </div>
            </div>

            <!-- Preview Table & Import Form -->
            <?php if ($previewData && $previewData['success']): ?>
                <form action="/admin/service-importer.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="import_services">
                    <input type="hidden" name="provider_id" value="<?= $selectedProviderId ?>">

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                        <!-- Top actions bar -->
                        <div class="p-4 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700 uppercase">
                                    <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-4 h-4 rounded text-purple-600 border-slate-300">
                                    Select All Services
                                </label>
                                <span class="text-xs text-slate-400">|</span>
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600">
                                    <input type="checkbox" name="approve_immediately" checked class="w-4 h-4 rounded text-purple-600 border-slate-300">
                                    Activate imported services immediately
                                </label>
                            </div>

                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-200 transition-all cursor-pointer">
                                Import Selected Services
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4 w-12 text-center"></th>
                                        <th class="px-6 py-4">Service Name</th>
                                        <th class="px-6 py-4">Provider Code</th>
                                        <th class="px-6 py-4">Category</th>
                                        <th class="px-6 py-4">Provider Cost</th>
                                        <th class="px-6 py-4">Suggested Price</th>
                                        <th class="px-6 py-4">Status / Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($previewData['items'] as $item): ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-6 py-4 text-center">
                                                <input type="checkbox" name="services[]" value="<?= e($item['code']) ?>" class="service-checkbox w-4 h-4 rounded text-purple-600 border-slate-300">
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-slate-900"><?= e($item['name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600">
                                                <?= e($item['code']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-xs font-medium text-slate-500 uppercase">
                                                <?= e($item['category']) ?>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs font-bold text-slate-800">
                                                <?= format_currency($item['provider_cost']) ?>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs font-bold text-purple-600">
                                                <?= format_currency($item['suggested_price']) ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($item['is_existing']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        Exists (Will Update Cost)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        New Service
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="bg-white p-12 rounded-2xl text-center border border-slate-100 shadow-xs space-y-3">
                    <p class="text-sm text-slate-500">Could not retrieve services from provider. Please check provider API configuration.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
