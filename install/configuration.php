<?php
/**
 * Step 6: Application Configuration Screen
 */
$stepNumber = 6;
$pageTitle = 'Application Settings';
require_once __DIR__ . '/includes/installer-header.php';

$dbConfig = $_SESSION['installer_db'] ?? null;
if (!$dbConfig) {
    header('Location: /install/database.php');
    exit;
}

$error = '';
$siteName = $_POST['site_name'] ?? ($_SESSION['installer_config']['site_name'] ?? 'OTP Services');
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:3000';
$detectedAppUrl = "{$scheme}://{$host}";
$appUrl = $_POST['app_url'] ?? ($_SESSION['installer_config']['app_url'] ?? $detectedAppUrl);
$timezone = $_POST['timezone'] ?? ($_SESSION['installer_config']['timezone'] ?? 'UTC');
$currencyCode = $_POST['currency_code'] ?? ($_SESSION['installer_config']['currency_code'] ?? 'INR');
$currencySymbol = $_POST['currency_symbol'] ?? ($_SESSION['installer_config']['currency_symbol'] ?? '₹');
$orderTimeout = (int)($_POST['order_timeout_minutes'] ?? ($_SESSION['installer_config']['order_timeout_minutes'] ?? 20));
$profitMargin = (float)($_POST['default_profit_markup_percent'] ?? ($_SESSION['installer_config']['default_profit_markup_percent'] ?? 25.0));

$timezones = [
    'UTC' => 'UTC (Coordinated Universal Time)',
    'Asia/Kolkata' => 'Asia/Kolkata (IST +5:30)',
    'Asia/Dubai' => 'Asia/Dubai (GST +4:00)',
    'Asia/Singapore' => 'Asia/Singapore (SGT +8:00)',
    'Europe/London' => 'Europe/London (GMT/BST)',
    'America/New_York' => 'America/New_York (EST/EDT)',
    'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $siteName = trim($_POST['site_name'] ?? 'OTP Services');
        $appUrl = trim($_POST['app_url'] ?? $detectedAppUrl);
        $timezone = trim($_POST['timezone'] ?? 'UTC');
        $currencyCode = trim($_POST['currency_code'] ?? 'INR');
        $currencySymbol = trim($_POST['currency_symbol'] ?? '₹');
        $orderTimeout = (int)($_POST['order_timeout_minutes'] ?? 20);
        $profitMargin = (float)($_POST['default_profit_markup_percent'] ?? 25.0);

        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $siteSettings = [
                'site_name' => $siteName,
                'app_name' => $siteName,
                'app_url' => $appUrl,
                'timezone' => $timezone,
                'currency_code' => $currencyCode,
                'currency_symbol' => $currencySymbol,
                'order_timeout_minutes' => $orderTimeout,
                'default_profit_markup_percent' => $profitMargin,
            ];

            $result = save_installer_configuration($pdo, $dbConfig, $siteSettings);
            if ($result['success']) {
                $_SESSION['installer_config'] = $siteSettings;
                header('Location: /install/finish.php');
                exit;
            } else {
                $error = $result['error'];
            }
        } catch (\PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Application Settings</h1>
        <p class="text-sm text-slate-500 mt-1">Configure global application branding, URLs, currency, and operational rules.</p>
    </div>

    <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-start gap-2.5">
        <svg class="w-5 h-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold">Configuration Error</div>
            <div class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= get_installer_csrf_token() ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="site_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Platform Name</label>
                <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($siteName) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="e.g. OTP Services">
            </div>

            <div>
                <label for="app_url" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Base Application URL</label>
                <input type="url" id="app_url" name="app_url" value="<?= htmlspecialchars($appUrl) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="https://yourdomain.com">
            </div>

            <div>
                <label for="timezone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Default Timezone</label>
                <select id="timezone" name="timezone" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900 bg-white">
                    <?php foreach ($timezones as $tzVal => $tzLabel): ?>
                        <option value="<?= $tzVal ?>" <?= $timezone === $tzVal ? 'selected' : '' ?>><?= $tzLabel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="currency_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Currency Code & Symbol</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" id="currency_code" name="currency_code" value="<?= htmlspecialchars($currencyCode) ?>" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                           placeholder="INR">
                    <input type="text" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($currencySymbol) ?>" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                           placeholder="₹">
                </div>
            </div>

            <div>
                <label for="order_timeout_minutes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Order Timeout (Minutes)</label>
                <input type="number" id="order_timeout_minutes" name="order_timeout_minutes" value="<?= $orderTimeout ?>" min="5" max="60" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                <p class="text-xs text-slate-400 mt-1">Automatic order cancellation & refund period.</p>
            </div>

            <div>
                <label for="default_profit_markup_percent" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Default Profit Margin (%)</label>
                <input type="number" step="0.5" id="default_profit_markup_percent" name="default_profit_markup_percent" value="<?= $profitMargin ?>" min="0" max="500" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                <p class="text-xs text-slate-400 mt-1">Applied during service importer price sync.</p>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-500">
            <strong>Note on Gateways & APIs:</strong> Razorpay payment gateway credentials and provider API keys can be managed securely at any time inside the Super Admin panel under Settings.
        </div>

        <!-- Actions -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="/install/admin-account.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                Back
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-md shadow-purple-200 transition-all">
                <span>Save & Complete Installation</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
