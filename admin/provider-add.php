<?php
/**
 * Admin Add Custom Provider
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';
require_once __DIR__ . '/../providers/custom-provider.php';

$db = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $rawSlug = sanitize_string($_POST['slug'] ?? '');
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');

    if (empty($name)) {
        $errors[] = 'Provider name is required.';
    }

    if (empty($apiUrl) || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'A valid API Endpoint URL (starting with http:// or https://) is required.';
    }

    if (empty($errors)) {
        // Generate unique slug
        $baseSlug = $rawSlug ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) {
            $baseSlug = 'provider-' . time();
        }

        $slug = $baseSlug;
        $suffix = 1;
        while (true) {
            $check = $db->prepare("SELECT id FROM providers WHERE slug = ? LIMIT 1");
            $check->execute([$slug]);
            if (!$check->fetch()) {
                break;
            }
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        // Test connection to provider
        $balance = 0.00;
        $connectionNote = '';
        try {
            $adapter = new CustomProviderAdapter($apiUrl, $apiKey ?: null, 5);
            $testBalance = $adapter->getBalance();
            if ($testBalance >= 0) {
                $balance = $testBalance;
                $connectionNote = ' Handshake successful.';
            }
        } catch (\Throwable $e) {
            $connectionNote = ' Warning: Handshake check timed out or endpoint unreachable.';
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO providers (name, slug, api_url, api_key, balance, status, last_sync) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$name, $slug, $apiUrl, $apiKey ?: null, $balance]);

            $newId = (int)$db->lastInsertId();
            log_admin_audit('provider_created', "Admin created provider {$name} (ID: {$newId})");
            set_flash('success', "Provider '{$name}' registered successfully.{$connectionNote}");
            header("Location: /admin/providers.php");
            exit;
        } catch (PDOException $e) {
            error_log("Provider insert error: " . $e->getMessage());
            $errors[] = 'Database error while saving provider: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add OTP Provider - Super Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-2xl mx-auto space-y-6">

            <?php if (!empty($errors)): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border bg-rose-50 text-rose-700 border-rose-200 shadow-xs space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <div>• <?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900">Add New OTP Provider Gateway</h1>
                    <p class="text-xs text-slate-500 mt-1">Configure an external carrier or virtual SMS number provider.</p>
                </div>

                <form action="/admin/provider-add.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Provider Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. DaisySMS / SMS-Activate / 5SIM" value="<?= e($_POST['name'] ?? '') ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Slug Identifier (Optional)</label>
                        <input type="text" id="slug" name="slug" placeholder="e.g. daisy-sms (auto-generated if left blank)" value="<?= e($_POST['slug'] ?? '') ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="api_url" class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">API Endpoint Base URL</label>
                        <input type="url" id="api_url" name="api_url" required placeholder="https://api.provider.com/v1" value="<?= e($_POST['api_url'] ?? '') ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="api_key" class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">API Token / Secret Key</label>
                        <input type="password" id="api_key" name="api_key" placeholder="Enter provider API secret key..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                        <span class="text-xs text-slate-400 mt-1 block">Keys are encrypted/stored securely on the server and never exposed to the client.</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="/admin/providers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md transition-all">Register Provider</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
