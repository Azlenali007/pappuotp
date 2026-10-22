<?php
/**
 * Admin Website & General Settings
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token. Please try again.');
        header('Location: /admin/settings.php');
        exit;
    }

    $appName = trim($_POST['app_name'] ?? 'OTP Services');
    $appTagline = trim($_POST['app_tagline'] ?? 'Instant SMS & OTP Verification');
    $supportEmail = trim($_POST['support_email'] ?? 'support@otprocket.com');
    $supportTelegram = trim($_POST['support_telegram'] ?? '@otprocket_support');
    $currencyCode = strtoupper(trim($_POST['currency_code'] ?? 'INR'));
    $currencySymbol = trim($_POST['currency_symbol'] ?? '₹');
    $orderTimeout = max(1, (int)($_POST['order_timeout_minutes'] ?? 20));
    $defaultMargin = max(0.0, (float)($_POST['default_margin_pct'] ?? 25.0));
    $minDeposit = max(1.0, (float)($_POST['min_deposit_amount'] ?? 50.0));
    $maxDeposit = max($minDeposit, (float)($_POST['max_deposit_amount'] ?? 10000.0));
    $maintenanceMode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $userRegistration = isset($_POST['user_registration']) ? '1' : '0';

    $settings = [
        'app_name' => $appName,
        'site_name' => $appName,
        'app_tagline' => $appTagline,
        'site_tagline' => $appTagline,
        'support_email' => $supportEmail,
        'support_telegram' => $supportTelegram,
        'currency_code' => $currencyCode,
        'currency_symbol' => $currencySymbol,
        'order_timeout_minutes' => (string)$orderTimeout,
        'default_margin_pct' => (string)$defaultMargin,
        'default_profit_markup_percent' => (string)$defaultMargin,
        'min_deposit_amount' => (string)$minDeposit,
        'max_deposit_amount' => (string)$maxDeposit,
        'maintenance_mode' => $maintenanceMode,
        'user_registration' => $userRegistration,
    ];

    try {
        $stmt = $db->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        foreach ($settings as $k => $v) {
            $stmt->execute([':k' => $k, ':v' => $v, ':v2' => $v]);
        }
        log_admin_audit('update_settings', 'Updated platform general settings and branding');
        set_flash('success', 'Website settings have been saved successfully.');
    } catch (\PDOException $e) {
        set_flash('error', 'Failed to save settings: ' . $e->getMessage());
    }

    header('Location: /admin/settings.php');
    exit;
}

// Fetch current settings
$stmt = $db->query("SELECT key_name, value FROM application_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$appName = $currentSettings['site_name'] ?? $currentSettings['app_name'] ?? 'OTP Services';
$appTagline = $currentSettings['site_tagline'] ?? $currentSettings['app_tagline'] ?? 'Instant Virtual Number & SMS Verification';
$supportEmail = $currentSettings['support_email'] ?? 'support@otprocket.com';
$supportTelegram = $currentSettings['support_telegram'] ?? '@otprocket_support';
$currencyCode = $currentSettings['currency_code'] ?? 'INR';
$currencySymbol = $currentSettings['currency_symbol'] ?? '₹';
$orderTimeout = (int)($currentSettings['order_timeout_minutes'] ?? 20);
$defaultMargin = (float)($currentSettings['default_profit_markup_percent'] ?? $currentSettings['default_margin_pct'] ?? 25.0);
$minDeposit = (float)($currentSettings['min_deposit_amount'] ?? 50.0);
$maxDeposit = (float)($currentSettings['max_deposit_amount'] ?? 10000.0);
$maintenanceMode = ($currentSettings['maintenance_mode'] ?? '0') === '1';
$userRegistration = ($currentSettings['user_registration'] ?? '1') === '1';

$pageTitle = 'Website Settings - Super Admin';
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
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Website Settings</h1>
                    <p class="text-sm text-slate-500">Configure global platform details, branding, and operations</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/admin/payment-settings.php" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Payment Gateways</a>
                    <a href="/admin/security.php" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Security & Auth</a>
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
                <a href="/admin/settings.php" class="pb-3 border-b-2 border-purple-600 text-purple-600 font-bold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    General & Branding
                </a>
                <a href="/admin/payment-settings.php" class="pb-3 text-slate-500 hover:text-slate-900 font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Payment Gateways
                </a>
                <a href="/admin/security.php" class="pb-3 text-slate-500 hover:text-slate-900 font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Security & Auth
                </a>
            </div>

            <!-- Settings Form Card -->
            <form method="POST" action="/admin/settings.php" class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <?= csrf_field() ?>

                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-bold text-slate-900">Platform Identity & Contact</h2>
                    <p class="text-xs text-slate-500 mt-1">Branding details displayed across the user panel and customer communications</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Platform Name</label>
                        <input type="text" name="app_name" value="<?= e($appName) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tagline / Subtitle</label>
                        <input type="text" name="app_tagline" value="<?= e($appTagline) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Support Email</label>
                        <input type="email" name="support_email" value="<?= e($supportEmail) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Support Telegram Handle</label>
                        <input type="text" name="support_telegram" value="<?= e($supportTelegram) ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="<?= e($currencySymbol) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Currency Code</label>
                        <input type="text" name="currency_code" value="<?= e($currencyCode) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h2 class="text-base font-bold text-slate-900">Orders & Wallet Parameters</h2>
                        <p class="text-xs text-slate-500 mt-1">Order lifecycles, provider markup, and customer deposit thresholds</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Order Timeout (Minutes)</label>
                            <input type="number" min="5" max="60" name="order_timeout_minutes" value="<?= $orderTimeout ?>" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                            <p class="text-xs text-slate-400 mt-1">Orders auto-cancel and refund to wallet after this duration if no OTP arrives.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Default Profit Markup (%)</label>
                            <input type="number" step="0.1" min="0" max="500" name="default_margin_pct" value="<?= $defaultMargin ?>" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                            <p class="text-xs text-slate-400 mt-1">Automatic profit margin calculated on provider wholesale cost during imports.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Minimum Wallet Deposit (<?= e($currencySymbol) ?>)</label>
                            <input type="number" step="0.01" min="1" name="min_deposit_amount" value="<?= $minDeposit ?>" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Maximum Wallet Deposit (<?= e($currencySymbol) ?>)</label>
                            <input type="number" step="0.01" min="10" name="max_deposit_amount" value="<?= $maxDeposit ?>" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6 space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Platform Operation Controls</h2>

                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer">
                        <input type="checkbox" name="user_registration" value="1" <?= $userRegistration ? 'checked' : '' ?>
                               class="mt-1 w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                        <div>
                            <div class="text-sm font-bold text-slate-800">Allow New User Registrations</div>
                            <div class="text-xs text-slate-500">Enable public account signups. Uncheck to restrict access to existing users only.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= $maintenanceMode ? 'checked' : '' ?>
                               class="mt-1 w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                        <div>
                            <div class="text-sm font-bold text-slate-800">Maintenance Mode</div>
                            <div class="text-xs text-slate-500">Temporarily prevent new virtual number orders while system upgrades or synchronization run.</div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl shadow-md shadow-purple-200 transition-all">
                        Save Website Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
