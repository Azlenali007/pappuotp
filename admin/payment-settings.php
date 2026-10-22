<?php
/**
 * Admin Payment Gateway Settings
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token. Please try again.');
        header('Location: /admin/payment-settings.php');
        exit;
    }

    $razorpayEnabled = isset($_POST['razorpay_enabled']) ? '1' : '0';
    $razorpayKeyId = trim($_POST['razorpay_key_id'] ?? '');
    $razorpayKeySecret = trim($_POST['razorpay_key_secret'] ?? '');
    $razorpayWebhookSecret = trim($_POST['razorpay_webhook_secret'] ?? '');
    $upiQrEnabled = isset($_POST['upi_qr_enabled']) ? '1' : '0';
    $upiId = trim($_POST['upi_id'] ?? '');
    $upiMerchantName = trim($_POST['upi_merchant_name'] ?? '');
    $minDeposit = max(1.0, (float)($_POST['min_deposit_amount'] ?? 50.0));
    $maxDeposit = max($minDeposit, (float)($_POST['max_deposit_amount'] ?? 10000.0));
    $depositInstructions = trim($_POST['deposit_instructions'] ?? '');

    $settings = [
        'razorpay_enabled' => $razorpayEnabled,
        'razorpay_key_id' => $razorpayKeyId,
        'razorpay_key_secret' => $razorpayKeySecret,
        'razorpay_webhook_secret' => $razorpayWebhookSecret,
        'upi_qr_enabled' => $upiQrEnabled,
        'upi_id' => $upiId,
        'upi_merchant_name' => $upiMerchantName,
        'min_deposit_amount' => (string)$minDeposit,
        'max_deposit_amount' => (string)$maxDeposit,
        'deposit_instructions' => $depositInstructions,
    ];

    try {
        $stmt = $db->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        foreach ($settings as $k => $v) {
            $stmt->execute([':k' => $k, ':v' => $v, ':v2' => $v]);
        }
        log_admin_audit('update_payment_settings', 'Updated payment gateway configurations and limits');
        set_flash('success', 'Payment gateway settings updated successfully.');
    } catch (\PDOException $e) {
        set_flash('error', 'Failed to save settings: ' . $e->getMessage());
    }

    header('Location: /admin/payment-settings.php');
    exit;
}

// Fetch current settings
$stmt = $db->query("SELECT key_name, value FROM application_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$razorpayEnabled = ($currentSettings['razorpay_enabled'] ?? '1') === '1';
$razorpayKeyId = $currentSettings['razorpay_key_id'] ?? 'rzp_test_mockkey12345';
$razorpayKeySecret = $currentSettings['razorpay_key_secret'] ?? 'mock_secret_abcdef123456';
$razorpayWebhookSecret = $currentSettings['razorpay_webhook_secret'] ?? 'whsec_test_123456';
$upiQrEnabled = ($currentSettings['upi_qr_enabled'] ?? '1') === '1';
$upiId = $currentSettings['upi_id'] ?? 'otprocket@upi';
$upiMerchantName = $currentSettings['upi_merchant_name'] ?? 'OTP Services Merchant';
$minDeposit = (float)($currentSettings['min_deposit_amount'] ?? 50.0);
$maxDeposit = (float)($currentSettings['max_deposit_amount'] ?? 10000.0);
$depositInstructions = $currentSettings['deposit_instructions'] ?? 'Scan the official QR code using any UPI app (GPay, PhonePe, Paytm). Enter the UTR transaction reference number after completing payment.';

$pageTitle = 'Payment Settings - Super Admin';
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
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Payment Gateway Settings</h1>
                    <p class="text-sm text-slate-500">Configure online payment gateways, UPI QR parameters, and deposit limits</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/admin/payments.php" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">View Payments</a>
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
                <a href="/admin/settings.php" class="pb-3 text-slate-500 hover:text-slate-900 font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    General & Branding
                </a>
                <a href="/admin/payment-settings.php" class="pb-3 border-b-2 border-purple-600 text-purple-600 font-bold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Payment Gateways
                </a>
                <a href="/admin/security.php" class="pb-3 text-slate-500 hover:text-slate-900 font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Security & Auth
                </a>
            </div>

            <!-- Form Card -->
            <form method="POST" action="/admin/payment-settings.php" class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <?= csrf_field() ?>

                <!-- Razorpay Section -->
                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Razorpay Gateway Integration
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Accept cards, net banking, wallets, and instant UPI transfers</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="razorpay_enabled" value="1" <?= $razorpayEnabled ? 'checked' : '' ?>
                                   class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                            <span class="text-xs font-bold text-slate-700">Enable Razorpay</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Razorpay Key ID</label>
                        <input type="text" name="razorpay_key_id" value="<?= e($razorpayKeyId) ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-mono text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">API Key from your Razorpay Dashboard</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Razorpay Key Secret</label>
                        <input type="password" name="razorpay_key_secret" value="<?= e($razorpayKeySecret) ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-mono text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">Kept confidential on the server, never sent to clients</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Razorpay Webhook Secret</label>
                        <input type="password" name="razorpay_webhook_secret" value="<?= e($razorpayWebhookSecret) ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-mono text-slate-900">
                        <p class="text-xs text-slate-400 mt-1">Used to verify authentic payment signatures on <code class="text-purple-600">/api/payment-webhook.php</code></p>
                    </div>
                </div>

                <!-- UPI QR Section -->
                <div class="border-t border-slate-100 pt-6">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                    Direct UPI QR / Manual Deposits
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">Accept zero-fee peer-to-peer UPI payments with manual reference confirmation</p>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="upi_qr_enabled" value="1" <?= $upiQrEnabled ? 'checked' : '' ?>
                                       class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                                <span class="text-xs font-bold text-slate-700">Enable UPI QR</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">VPA / UPI ID</label>
                            <input type="text" name="upi_id" value="<?= e($upiId) ?>"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Merchant Display Name</label>
                            <input type="text" name="upi_merchant_name" value="<?= e($upiMerchantName) ?>"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-900">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Customer Deposit Instructions</label>
                            <textarea name="deposit_instructions" rows="3"
                                      class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm text-slate-900"><?= e($depositInstructions) ?></textarea>
                            <p class="text-xs text-slate-400 mt-1">Guidance shown to users on the wallet recharge screen</p>
                        </div>
                    </div>
                </div>

                <!-- Limits Section -->
                <div class="border-t border-slate-100 pt-6">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h2 class="text-base font-bold text-slate-900">Wallet Recharge Limits</h2>
                        <p class="text-xs text-slate-500 mt-1">Enforce minimum and maximum top-up bounds per transaction</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Minimum Deposit Amount</label>
                            <input type="number" step="0.01" min="1" name="min_deposit_amount" value="<?= $minDeposit ?>" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Maximum Deposit Amount</label>
                            <input type="number" step="0.01" min="10" name="max_deposit_amount" value="<?= $maxDeposit ?>" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl shadow-md shadow-purple-200 transition-all">
                        Save Payment Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
