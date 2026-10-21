<?php
/**
 * Admin Website Settings
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$pageTitle = 'Website Settings';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $settings = [
            'app_name' => trim($_POST['app_name'] ?? 'OTP Rocket'),
            'app_tagline' => trim($_POST['app_tagline'] ?? 'Instant SMS & OTP Verification'),
            'support_email' => trim($_POST['support_email'] ?? 'support@otprocket.com'),
            'support_telegram' => trim($_POST['support_telegram'] ?? '@otprocket_support'),
            'currency_code' => trim($_POST['currency_code'] ?? 'INR'),
            'currency_symbol' => trim($_POST['currency_symbol'] ?? '₹'),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'user_registration' => isset($_POST['user_registration']) ? '1' : '0',
            'order_timeout_minutes' => (int)($_POST['order_timeout_minutes'] ?? 20),
            'default_margin_pct' => (float)($_POST['default_margin_pct'] ?? 25.0),
        ];

        try {
            $stmt = $db->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
            foreach ($settings as $key => $val) {
                $valStr = (string)$val;
                $stmt->execute([':k' => $key, ':v' => $valStr, ':v2' => $valStr]);
            }
            logAdminActivity($adminId, 'update_website_settings', 'Updated general website settings');
            $message = 'Website settings updated successfully.';
        } catch (\PDOException $e) {
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

// Fetch current settings
$stmt = $db->query("SELECT key_name, value FROM application_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$appName = $currentSettings['app_name'] ?? 'OTP Rocket';
$appTagline = $currentSettings['app_tagline'] ?? 'Instant SMS & OTP Verification';
$supportEmail = $currentSettings['support_email'] ?? 'support@otprocket.com';
$supportTelegram = $currentSettings['support_telegram'] ?? '@otprocket_support';
$currencyCode = $currentSettings['currency_code'] ?? 'INR';
$currencySymbol = $currentSettings['currency_symbol'] ?? '₹';
$maintenanceMode = ($currentSettings['maintenance_mode'] ?? '0') === '1';
$userRegistration = ($currentSettings['user_registration'] ?? '1') === '1';
$orderTimeout = (int)($currentSettings['order_timeout_minutes'] ?? 20);
$defaultMargin = (float)($currentSettings['default_margin_pct'] ?? 25.0);

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Website Settings</h1>
            <p class="text-sm text-slate-500">Configure global platform details, branding, and operations</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/payment-settings.php" class="px-4 py-2 text-sm font-semibold text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors">Payment Settings</a>
            <a href="/admin/security-settings.php" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Security Settings</a>
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
        <a href="/admin/website-settings.php" class="pb-3 border-b-2 border-purple-600 text-purple-600 font-semibold text-sm">General & Branding</a>
        <a href="/admin/payment-settings.php" class="pb-3 text-slate-500 hover:text-slate-800 font-medium text-sm">Payment Gateways</a>
        <a href="/admin/security-settings.php" class="pb-3 text-slate-500 hover:text-slate-800 font-medium text-sm">Security & Auth</a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Platform Name</label>
                <input type="text" name="app_name" value="<?= htmlspecialchars($appName) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tagline / Subtitle</label>
                <input type="text" name="app_tagline" value="<?= htmlspecialchars($appTagline) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Support Email</label>
                <input type="email" name="support_email" value="<?= htmlspecialchars($supportEmail) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Support Telegram Handle</label>
                <input type="text" name="support_telegram" value="<?= htmlspecialchars($supportTelegram) ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Currency Symbol</label>
                <input type="text" name="currency_symbol" value="<?= htmlspecialchars($currencySymbol) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Currency Code</label>
                <input type="text" name="currency_code" value="<?= htmlspecialchars($currencyCode) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Order Timeout (Minutes)</label>
                <input type="number" min="5" max="60" name="order_timeout_minutes" value="<?= $orderTimeout ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                <p class="text-xs text-slate-400 mt-1">Orders auto-cancel and refund after this duration if no OTP is received.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Default Profit Margin (%)</label>
                <input type="number" step="0.1" min="0" max="500" name="default_margin_pct" value="<?= $defaultMargin ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                <p class="text-xs text-slate-400 mt-1">Applied automatically when importing provider service prices.</p>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 space-y-4">
            <h3 class="text-sm font-semibold text-slate-900">Operation Modes</h3>
            <div class="flex items-center gap-3">
                <input type="checkbox" id="user_registration" name="user_registration" value="1" <?= $userRegistration ? 'checked' : '' ?>
                       class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                <label for="user_registration" class="text-sm font-medium text-slate-700">Allow New User Registrations</label>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= $maintenanceMode ? 'checked' : '' ?>
                       class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                <label for="maintenance_mode" class="text-sm font-medium text-slate-700">Maintenance Mode (Block public user purchases)</label>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-purple-200 transition-all">
                Save Website Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
