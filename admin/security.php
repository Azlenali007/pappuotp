<?php
/**
 * Admin Security & Authentication Settings
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token. Please try again.');
        header('Location: /admin/security.php');
        exit;
    }

    $loginMaxAttempts = max(1, (int)($_POST['login_max_attempts'] ?? 5));
    $loginLockoutMinutes = max(1, (int)($_POST['login_lockout_minutes'] ?? 15));
    $sessionLifetimeMinutes = max(15, (int)($_POST['session_lifetime_minutes'] ?? 120));
    $forceSsl = isset($_POST['force_ssl']) ? '1' : '0';
    $apiRateLimit = max(10, (int)($_POST['api_rate_limit_per_min'] ?? 60));
    $adminIpWhitelist = trim($_POST['admin_ip_whitelist'] ?? '');
    $passwordMinLength = max(6, (int)($_POST['password_min_length'] ?? 8));
    $requireStrongPasswords = isset($_POST['require_strong_passwords']) ? '1' : '0';

    $settings = [
        'login_max_attempts' => (string)$loginMaxAttempts,
        'login_lockout_minutes' => (string)$loginLockoutMinutes,
        'session_lifetime_minutes' => (string)$sessionLifetimeMinutes,
        'force_ssl' => $forceSsl,
        'api_rate_limit_per_min' => (string)$apiRateLimit,
        'admin_ip_whitelist' => $adminIpWhitelist,
        'password_min_length' => (string)$passwordMinLength,
        'require_strong_passwords' => $requireStrongPasswords,
    ];

    try {
        $stmt = $db->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        foreach ($settings as $k => $v) {
            $stmt->execute([':k' => $k, ':v' => $v, ':v2' => $v]);
        }
        log_admin_audit('update_security_settings', 'Updated authentication defenses and security policies');
        set_flash('success', 'Security settings have been updated successfully.');
    } catch (\PDOException $e) {
        set_flash('error', 'Failed to save settings: ' . $e->getMessage());
    }

    header('Location: /admin/security.php');
    exit;
}

// Fetch current settings
$stmt = $db->query("SELECT key_name, value FROM application_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$loginMaxAttempts = (int)($currentSettings['login_max_attempts'] ?? 5);
$lockoutMinutes = (int)($currentSettings['login_lockout_minutes'] ?? 15);
$sessionLifetime = (int)($currentSettings['session_lifetime_minutes'] ?? 120);
$forceSsl = ($currentSettings['force_ssl'] ?? '0') === '1';
$rateLimit = (int)($currentSettings['api_rate_limit_per_min'] ?? 60);
$ipWhitelist = $currentSettings['admin_ip_whitelist'] ?? '';
$passwordMinLength = (int)($currentSettings['password_min_length'] ?? 8);
$requireStrong = ($currentSettings['require_strong_passwords'] ?? '1') === '1';

$pageTitle = 'Security Settings - Super Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Title Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Security & Authentication Settings</h1>
                    <p class="text-sm text-slate-500">Configure brute-force protection, session durations, rate limits, and access whitelists</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/admin/audit-logs.php" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Audit Logs</a>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <?php if ($flash['type'] === 'error'): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        <?php endif; ?>
                    </svg>
                    <span><?= e($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs Navigation -->
            <div class="flex border-b border-slate-200 gap-6">
                <a href="/admin/settings.php" class="pb-3 text-slate-500 hover:text-slate-900 font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    General & Branding
                </a>
                <a href="/admin/payment-settings.php" class="pb-3 text-slate-500 hover:text-slate-900 font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Payment Gateways
                </a>
                <a href="/admin/security.php" class="pb-3 border-b-2 border-purple-600 text-purple-600 font-bold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Security & Auth
                </a>
            </div>

            <!-- Form Card -->
            <form method="POST" action="/admin/security.php" class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <?= csrf_field() ?>

                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-bold text-slate-900">Authentication Defense & Lockout Policy</h2>
                    <p class="text-xs text-slate-500 mt-1">Defend against automated credential stuffing and brute-force guessing</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Max Login Attempts (Before Lockout)</label>
                        <input type="number" min="3" max="20" name="login_max_attempts" value="<?= $loginMaxAttempts ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">Failed attempts allowed from a single IP before temporary block</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Lockout Duration (Minutes)</label>
                        <input type="number" min="5" max="1440" name="login_lockout_minutes" value="<?= $lockoutMinutes ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">Time an attacker must wait before login attempts are re-enabled</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Admin Session Lifetime (Minutes)</label>
                        <input type="number" min="15" max="1440" name="session_lifetime_minutes" value="<?= $sessionLifetime ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">Inactivity period before requiring re-authentication</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">API Rate Limit (Requests/Minute)</label>
                        <input type="number" min="10" max="600" name="api_rate_limit_per_min" value="<?= $rateLimit ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">Throttling per IP across public polling and order endpoints</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Minimum Password Length</label>
                        <input type="number" min="6" max="32" name="password_min_length" value="<?= $passwordMinLength ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="require_strong_passwords" value="1" <?= $requireStrong ? 'checked' : '' ?>
                                   class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                            <div>
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Require Strong Passwords</span>
                                <p class="text-xs text-slate-400">Must include mixed case, numbers, and special characters</p>
                            </div>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Admin IP Whitelist (Optional)</label>
                        <textarea name="admin_ip_whitelist" rows="2" placeholder="e.g. 192.168.1.100, 10.0.0.1 (Leave empty to allow all IP addresses)"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-mono text-slate-900"><?= e($ipWhitelist) ?></textarea>
                        <p class="text-xs text-slate-400 mt-1">Comma-separated IPv4 or IPv6 addresses allowed to access the Super Admin panel</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer">
                        <input type="checkbox" name="force_ssl" value="1" <?= $forceSsl ? 'checked' : '' ?>
                               class="mt-1 w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                        <div>
                            <div class="text-sm font-bold text-slate-800">Enforce Strict HTTPS / SSL</div>
                            <div class="text-xs text-slate-500">Redirect all HTTP requests to secure HTTPS and transmit session cookies with the Secure flag</div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl shadow-md shadow-purple-200 transition-all">
                        Save Security Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
