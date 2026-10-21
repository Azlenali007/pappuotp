<?php
/**
 * Admin Payment Settings
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$pageTitle = 'Payment Gateway Settings';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $settings = [
            'razorpay_enabled' => isset($_POST['razorpay_enabled']) ? '1' : '0',
            'razorpay_key_id' => trim($_POST['razorpay_key_id'] ?? ''),
            'razorpay_key_secret' => trim($_POST['razorpay_key_secret'] ?? ''),
            'razorpay_webhook_secret' => trim($_POST['razorpay_webhook_secret'] ?? ''),
            'min_deposit_amount' => (float)($_POST['min_deposit_amount'] ?? 50.0),
            'max_deposit_amount' => (float)($_POST['max_deposit_amount'] ?? 10000.0),
            'upi_qr_enabled' => isset($_POST['upi_qr_enabled']) ? '1' : '0',
            'upi_id' => trim($_POST['upi_id'] ?? ''),
        ];

        try {
            $stmt = $db->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
            foreach ($settings as $key => $val) {
                $valStr = (string)$val;
                $stmt->execute([':k' => $key, ':v' => $valStr, ':v2' => $valStr]);
            }
            logAdminActivity($adminId, 'update_payment_settings', 'Updated payment gateway credentials and deposit rules');
            $message = 'Payment gateway settings updated successfully.';
        } catch (\PDOException $e) {
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

// Fetch settings
$stmt = $db->query("SELECT key_name, value FROM application_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$razorpayEnabled = ($currentSettings['razorpay_enabled'] ?? '1') === '1';
$razorpayKeyId = $currentSettings['razorpay_key_id'] ?? 'rzp_test_mockkey12345';
$razorpayKeySecret = $currentSettings['razorpay_key_secret'] ?? 'mock_secret_abcdef123456';
$razorpayWebhookSecret = $currentSettings['razorpay_webhook_secret'] ?? 'whsec_test_123456';
$minDeposit = (float)($currentSettings['min_deposit_amount'] ?? 50.0);
$maxDeposit = (float)($currentSettings['max_deposit_amount'] ?? 10000.0);
$upiEnabled = ($currentSettings['upi_qr_enabled'] ?? '1') === '1';
$upiId = $currentSettings['upi_id'] ?? 'otprocket@upi';

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payment Gateway Settings</h1>
            <p class="text-sm text-slate-500">Manage Razorpay API keys, webhooks, and deposit limits</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/payments.php" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">View Payments</a>
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
        <a href="/admin/payment-settings.php" class="pb-3 border-b-2 border-purple-600 text-purple-600 font-semibold text-sm">Payment Gateways</a>
        <a href="/admin/security-settings.php" class="pb-3 text-slate-500 hover:text-slate-800 font-medium text-sm">Security & Auth</a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="border-b border-slate-100 pb-4">
            <h3 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                <img src="/assets/images/payment/razorpay.svg" class="h-5" alt="Razorpay">
                Razorpay Integration
            </h3>
            <p class="text-xs text-slate-500 mt-1">Accept UPI, Credit/Debit cards, Net Banking, and Wallets</p>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" id="razorpay_enabled" name="razorpay_enabled" value="1" <?= $razorpayEnabled ? 'checked' : '' ?>
                   class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
            <label for="razorpay_enabled" class="text-sm font-semibold text-slate-700">Enable Razorpay Online Gateway</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Razorpay Key ID</label>
                <input type="text" name="razorpay_key_id" value="<?= htmlspecialchars($razorpayKeyId) ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Razorpay Key Secret</label>
                <input type="password" name="razorpay_key_secret" value="<?= htmlspecialchars($razorpayKeySecret) ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Razorpay Webhook Secret</label>
                <input type="password" name="razorpay_webhook_secret" value="<?= htmlspecialchars($razorpayWebhookSecret) ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                <p class="text-xs text-slate-400 mt-1">Webhook URL to configure in Razorpay Dashboard: <code class="text-purple-600 bg-purple-50 px-2 py-0.5 rounded">https://yourdomain.com/payments/webhook.php</code></p>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Deposit Limits & Manual UPI</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Minimum Deposit Amount (₹)</label>
                    <input type="number" step="1" min="1" name="min_deposit_amount" value="<?= $minDeposit ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Maximum Deposit Amount (₹)</label>
                    <input type="number" step="1" min="10" name="max_deposit_amount" value="<?= $maxDeposit ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                </div>

                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <input type="checkbox" id="upi_qr_enabled" name="upi_qr_enabled" value="1" <?= $upiEnabled ? 'checked' : '' ?>
                               class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                        <label for="upi_qr_enabled" class="text-sm font-semibold text-slate-700">Enable Direct UPI QR Deposit</label>
                    </div>
                    <input type="text" name="upi_id" value="<?= htmlspecialchars($upiId) ?>" placeholder="e.g. company@upi"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-purple-200 transition-all">
                Save Payment Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
