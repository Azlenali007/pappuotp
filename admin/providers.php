<?php
/**
 * Admin Providers Management
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['provider_id'], $_POST['status'])) {
    require_csrf_token();
    $pid = (int)$_POST['provider_id'];
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $stmt = $db->prepare("UPDATE providers SET status = ? WHERE id = ?");
    $stmt->execute([$status, $pid]);
    log_admin_audit('provider_toggled', "Admin set provider ID {$pid} to {$status}");
    set_flash('success', "Provider status updated to {$status}.");
    header("Location: /admin/providers.php");
    exit;
}

$pageTitle = 'OTP Providers - Super Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$providers = $db->query("SELECT * FROM providers ORDER BY id ASC")->fetchAll();
$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">OTP Providers</h1>
                    <p class="text-sm text-slate-500">Carrier gateways and virtual number allocation APIs</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin/provider-logs.php" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50">API Error Logs</a>
                    <a href="/admin/provider-add.php" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">+ Add Provider</a>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Provider Name</th>
                                <th class="px-6 py-4">API Endpoint</th>
                                <th class="px-6 py-4">Provider Balance</th>
                                <th class="px-6 py-4">Last Sync</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($providers as $p): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-900"><?= e($p['name']) ?></td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500 truncate max-w-xs"><?= e($p['api_url']) ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-900 text-xs"><?= format_currency($p['balance']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= $p['last_sync'] ? time_ago($p['last_sync']) : 'Never' ?></td>
                                    <td class="px-6 py-4"><?= render_status_badge($p['status']) ?></td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a href="/admin/provider-test.php?id=<?= $p['id'] ?>" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 hover:bg-purple-100">Test API</a>
                                        <a href="/admin/provider-edit.php?id=<?= $p['id'] ?>" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200">Edit</a>
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
