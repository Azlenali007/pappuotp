<?php
/**
 * Admin Add Custom Provider
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $slug = sanitize_string($_POST['slug'] ?? strtolower(str_replace(' ', '-', $name)));
    $apiUrl = sanitize_string($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');

    if (empty($name) || empty($apiUrl)) {
        set_flash('error', 'Name and API URL are required.');
    } else {
        $stmt = $db->prepare("INSERT INTO providers (name, slug, api_url, api_key, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $slug, $apiUrl, $apiKey ?: null]);

        log_admin_audit('provider_created', "Admin created provider: {$name}");
        set_flash('success', "Provider {$name} registered successfully.");
        header("Location: /admin/providers.php");
        exit;
    }
}

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
                <span class="text-slate-900">Add Provider</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <h1 class="text-xl font-extrabold text-slate-900">Add New OTP Provider Gateway</h1>

                <form action="/admin/provider-add.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Provider Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. DaisySMS / SMS-Activate / 5SIM" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Slug Identifier</label>
                        <input type="text" id="slug" name="slug" placeholder="e.g. daisy-sms" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                    </div>

                    <div>
                        <label for="api_url" class="block text-xs font-bold text-slate-700 mb-1 uppercase">API Endpoint Base URL</label>
                        <input type="url" id="api_url" name="api_url" required placeholder="https://api.provider.com/v1" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none">
                    </div>

                    <div>
                        <label for="api_key" class="block text-xs font-bold text-slate-700 mb-1 uppercase">API Token / Secret Key</label>
                        <input type="password" id="api_key" name="api_key" placeholder="Enter provider API secret..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none">
                        <span class="text-xs text-slate-400 mt-1 block">Keys are stored securely on the server and never exposed to the client.</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="/admin/providers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">Register Provider</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
