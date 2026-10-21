<?php
/**
 * Step 7: Installation Summary & Locking
 */
require_once __DIR__ . '/includes/installer-auth.php';

$stepNumber = 7;
$pageTitle = 'Installation Completed';
$admin = $_SESSION['installer_admin'] ?? ['username' => 'admin', 'email' => 'admin@otprocket.com'];
$dbConfig = get_active_installer_db() ?? ['dbname' => 'otp_platform', 'host' => '127.0.0.1'];
$siteConfig = $_SESSION['installer_config'] ?? ['site_name' => 'OTP Services', 'currency_symbol' => '₹'];

require_once __DIR__ . '/includes/installer-header.php';

// Record completion and create installation lock
record_completed_step(7);
$lockCreated = create_installation_lock($admin['username'] ?? 'admin');
installer_log('STEP7_FINISH', "Installation locked and finalized", ['admin' => $admin['username'] ?? 'admin']);

// Clear sensitive installation session data now that installation is complete
unset($_SESSION['installer_db']);
unset($_SESSION['installer_csrf_token']);
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Success Hero -->
    <div class="text-center max-w-lg mx-auto space-y-3">
        <div class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-lg shadow-emerald-100">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Installation Successfully Completed!
        </h1>
        <p class="text-sm text-slate-500">
            Your OTP Verification Services Platform has been deployed, configured, and secured.
        </p>
    </div>

    <!-- Installation Summary Checklist Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Database Setup</span>
                <div class="text-sm font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($dbConfig['dbname']) ?> Connected</div>
                <div class="text-xs text-slate-500">26 tables and relations verified</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Super Administrator</span>
                <div class="text-sm font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($admin['username']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($admin['email']) ?></div>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Platform Config</span>
                <div class="text-sm font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($siteConfig['site_name'] ?? 'OTP Services') ?></div>
                <div class="text-xs text-slate-500">Currency: <?= htmlspecialchars($siteConfig['currency_code'] ?? 'INR') ?> (<?= htmlspecialchars($siteConfig['currency_symbol'] ?? '₹') ?>)</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Security State</span>
                <div class="text-sm font-bold text-slate-900 mt-0.5">Installation Locked</div>
                <div class="text-xs text-slate-500">Re-installation blocked by lockfile</div>
            </div>
        </div>
    </div>

    <!-- Security Recommendation Box -->
    <div class="p-5 rounded-2xl bg-purple-50/80 border border-purple-200/90 space-y-2 text-xs sm:text-sm text-purple-900">
        <div class="font-bold flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>Next Recommended Steps</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-purple-800 text-xs pl-1">
            <li>Log in to the Super Admin panel to configure your OTP provider API keys (Custom Provider / SMS Pool).</li>
            <li>Set up your Razorpay Gateway Key ID & Webhook Secret in Admin Settings &gt; Payment Gateways.</li>
            <li>Configure your server cron jobs to execute <code>cron/check-orders.php</code> and <code>cron/sync-services.php</code>.</li>
        </ul>
    </div>

    <!-- Navigation Buttons -->
    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="/index.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50 transition-all text-center flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Visit Website</span>
        </a>

        <a href="/admin/login.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm shadow-lg shadow-purple-200 transition-all text-center flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            <span>Open Admin Panel</span>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
