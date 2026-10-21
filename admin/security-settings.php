<?php
/**
 * Admin Security Settings
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$pageTitle = 'Security & Authentication Settings';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $settings = [
            'login_max_attempts' => (int)($_POST['login_max_attempts'] ?? 5),
            'login_lockout_minutes' => (int)($_POST['login_lockout_minutes'] ?? 15),
            'session_lifetime_minutes' => (int)($_POST['session_lifetime_minutes'] ?? 120),
            'force_ssl' => isset($_POST['force_ssl']) ? '1' : '0',
            'api_rate_limit_per_min' => (int)($_POST['api_rate_limit_per_min'] ?? 60),
            'admin_ip_whitelist' => trim($_POST['admin_ip_whitelist'] ?? ''),
        ];

        try {
            $stmt = $db->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
            foreach ($settings as $key => $val) {
                $valStr = (string)$val;
                $stmt->execute([':k' => $key, ':v' => $valStr, ':v2' => $valStr]);
            }
            logAdminActivity($adminId, 'update_security_settings', 'Updated authentication policies and IP rules');
            $message = 'Security settings updated successfully.';
        } catch (\PDOException $e) {
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

// Fetch settings
$stmt = $db->query("SELECT key_name, value FROM application_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$loginMaxAttempts = (int)($currentSettings['login_max_attempts'] ?? 5);
$lockoutMinutes = (int)($currentSettings['login_lockout_minutes'] ?? 15);
$sessionLifetime = (int)($currentSettings['session_lifetime_minutes'] ?? 120);
$forceSsl = ($currentSettings['force_ssl'] ?? '0') === '1';
$rateLimit = (int)($currentSettings['api_rate_limit_per_min'] ?? 60);
$ipWhitelist = $currentSettings['admin_ip_whitelist'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Security & Authentication Settings</h1>
            <p class="text-sm text-slate-500">Configure brute-force defenses, session lifetimes, and IP restrictions</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/activity-logs.php" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Audit Logs</a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <div class="flex border-b border-slate-200 gap-6">
        <a href="/admin/website-settings.php" class="pb-3 text-slate-500 hover:text-slate-800 font-medium text-sm">General & Branding</a>
        <a href="/admin/payment-settings.php" class="pb-3 text-slate-500 hover:text-slate-800 font-medium text-sm">Payment Gateways</a>
        <a href="/admin/security-settings.php" class="pb-3 border-b-2 border-purple-600 text-purple-600 font-semibold text-sm">Security & Auth</a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Max Login Attempts (Before Lockout)</label>
                <input type="number" min="3" max="20" name="login_max_attempts" value="<?= $loginMaxAttempts ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Lockout Duration (Minutes)</label>
                <input type="number" min="5" max="1440" name="login_lockout_minutes" value="<?= $lockoutMinutes ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Session Expiry (Minutes)</label>
                <input type="number" min="15" max="10080" name="session_lifetime_minutes" value="<?= $sessionLifetime ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">API Rate Limit (Reqs / Min / IP)</label>
                <input type="number" min="10" max="1000" name="api_rate_limit_per_min" value="<?= $rateLimit ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Admin IP Whitelist (Optional, comma-separated)</label>
                <input type="text" name="admin_ip_whitelist" value="<?= htmlspecialchars($ipWhitelist) ?>" placeholder="e.g. 192.168.1.100, 10.0.0.1 (Leave empty to allow all)"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="force_ssl" name="force_ssl" value="1" <?= $forceSsl ? 'checked' : '' ?>
                       class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                <label for="force_ssl" class="text-sm font-semibold text-slate-700">Enforce HTTPS Redirects</label>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-purple-200 transition-all">
                Save Security Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
