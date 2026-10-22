<?php
/**
 * Admin Edit Provider
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();
$providerId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM providers WHERE id = ? LIMIT 1");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    header("Location: /admin/providers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

    if (!empty($name) && !empty($apiUrl)) {
        if (!empty($apiKey)) {
            $stmt = $db->prepare("UPDATE providers SET name = ?, api_url = ?, api_key = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $apiUrl, $apiKey, $status, $providerId]);
        } else {
            $stmt = $db->prepare("UPDATE providers SET name = ?, api_url = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $apiUrl, $status, $providerId]);
        }

        log_admin_audit('provider_updated', "Admin updated provider ID {$providerId} ({$name})");
        set_flash('success', "Provider '{$name}' updated successfully.");
        header("Location: /admin/providers.php");
        exit;
    } else {
        set_flash('error', 'Name and API URL cannot be empty.');
    }
}

$pageTitle = 'Edit Provider - Super Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-2xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/providers.php" class="hover:text-purple-600">Providers</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($provider['name']) ?></span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <h1 class="text-xl font-extrabold text-slate-900">Edit Provider Configuration</h1>

                <form action="/admin/provider-edit.php?id=<?= $provider['id'] ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Name</label>
                        <input type="text" id="name" name="name" required value="<?= e($provider['name']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="api_url" class="block text-xs font-bold text-slate-700 mb-1 uppercase">API Endpoint URL</label>
                        <input type="url" id="api_url" name="api_url" required value="<?= e($provider['api_url']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="api_key" class="block text-xs font-bold text-slate-700 mb-1 uppercase">API Secret (Leave blank to keep current)</label>
                        <input type="password" id="api_key" name="api_key" placeholder="••••••••" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Status</label>
                        <select id="status" name="status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                            <option value="active" <?= $provider['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $provider['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="/admin/providers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md transition-all">Update Provider</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
