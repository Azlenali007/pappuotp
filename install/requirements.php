<?php
/**
 * Step 2: Server Requirements Check
 */
$stepNumber = 2;
$pageTitle = 'Server Environment Requirements';
require_once __DIR__ . '/includes/installer-header.php';

$requirements = [];
$allCriticalPassed = true;

// 1. PHP Version
$phpVersion = PHP_VERSION;
$phpPass = version_compare($phpVersion, '8.2.0', '>=');
$requirements[] = [
    'name' => 'PHP Version',
    'required' => '>= 8.2.0',
    'current' => $phpVersion,
    'status' => $phpPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Required for modern PHP features, types, and security.'
];
if (!$phpPass) $allCriticalPassed = false;

// 2. PDO Extension
$pdoPass = extension_loaded('pdo');
$requirements[] = [
    'name' => 'PDO Extension',
    'required' => 'Enabled',
    'current' => $pdoPass ? 'Enabled' : 'Disabled',
    'status' => $pdoPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Essential for prepared statement database queries.'
];
if (!$pdoPass) $allCriticalPassed = false;

// 3. PDO MySQL Driver
$pdoMysqlPass = extension_loaded('pdo_mysql');
$requirements[] = [
    'name' => 'PDO MySQL Driver',
    'required' => 'Enabled',
    'current' => $pdoMysqlPass ? 'Enabled' : 'Disabled',
    'status' => $pdoMysqlPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Required to connect to MySQL / MariaDB databases.'
];
if (!$pdoMysqlPass) $allCriticalPassed = false;

// 4. cURL Extension
$curlPass = extension_loaded('curl');
$requirements[] = [
    'name' => 'cURL Extension',
    'required' => 'Enabled',
    'current' => $curlPass ? 'Enabled' : 'Disabled',
    'status' => $curlPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Required for OTP provider API calls and payment webhooks.'
];
if (!$curlPass) $allCriticalPassed = false;

// 5. OpenSSL Extension
$opensslPass = extension_loaded('openssl');
$requirements[] = [
    'name' => 'OpenSSL Extension',
    'required' => 'Enabled',
    'current' => $opensslPass ? 'Enabled' : 'Disabled',
    'status' => $opensslPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Required for encrypted tokens and payment gateway signatures.'
];
if (!$opensslPass) $allCriticalPassed = false;

// 6. JSON Extension
$jsonPass = extension_loaded('json');
$requirements[] = [
    'name' => 'JSON Extension',
    'required' => 'Enabled',
    'current' => $jsonPass ? 'Enabled' : 'Disabled',
    'status' => $jsonPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Required for REST APIs and service synchronization.'
];
if (!$jsonPass) $allCriticalPassed = false;

// 7. Directory Permissions: storage/
$storagePath = dirname(__DIR__) . '/storage';
$storageWritable = is_dir($storagePath) && is_writable($storagePath);
$requirements[] = [
    'name' => 'Directory: storage/',
    'required' => 'Writable (0755 or 0775)',
    'current' => $storageWritable ? 'Writable' : 'Not Writable',
    'status' => $storageWritable ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Used for system logs, session locks, and cache.'
];
if (!$storageWritable) $allCriticalPassed = false;

// 8. Directory Permissions: config/
$configPath = dirname(__DIR__) . '/config';
$configWritable = is_dir($configPath) && is_writable($configPath);
$requirements[] = [
    'name' => 'Directory: config/',
    'required' => 'Writable (for auto-generation)',
    'current' => $configWritable ? 'Writable' : 'Read-Only (Manual config)',
    'status' => $configWritable ? 'passed' : 'warning',
    'critical' => false,
    'note' => 'Allows the installer to auto-generate config/db-config.php.'
];

// 9. Required Cryptographic & Session Functions
$cryptoPass = function_exists('password_hash') && function_exists('random_bytes');
$requirements[] = [
    'name' => 'Password Hashing & Randomness',
    'required' => 'Available',
    'current' => $cryptoPass ? 'Available' : 'Unavailable',
    'status' => $cryptoPass ? 'passed' : 'failed',
    'critical' => true,
    'note' => 'Required for secure bcrypt hashing and CSRF tokens.'
];
if (!$cryptoPass) $allCriticalPassed = false;
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">System & Server Requirements</h1>
            <p class="text-sm text-slate-500 mt-1">Verifying your hosting environment meets the prerequisites to run OTP Platform smoothly.</p>
        </div>
        <a href="/install/requirements.php" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Recheck
        </a>
    </div>

    <!-- Requirements Table -->
    <div class="border border-slate-200/90 rounded-2xl overflow-hidden bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 font-semibold text-xs uppercase tracking-wider">
                        <th class="py-3 px-5">Requirement</th>
                        <th class="py-3 px-5">Target</th>
                        <th class="py-3 px-5">Current Server Value</th>
                        <th class="py-3 px-5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($requirements as $req): ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-3.5 px-5">
                            <div class="font-bold text-slate-900"><?= htmlspecialchars($req['name']) ?></div>
                            <div class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($req['note']) ?></div>
                        </td>
                        <td class="py-3.5 px-5 text-xs font-mono text-slate-600"><?= htmlspecialchars($req['required']) ?></td>
                        <td class="py-3.5 px-5 text-xs font-mono font-medium text-slate-800"><?= htmlspecialchars($req['current']) ?></td>
                        <td class="py-3.5 px-5 text-right">
                            <?php if ($req['status'] === 'passed'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Passed
                                </span>
                            <?php elseif ($req['status'] === 'warning'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Warning
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Failed
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alert / Notice -->
    <?php if ($allCriticalPassed): ?>
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>All essential server requirements are met! You are ready to proceed to Database Setup.</span>
    </div>
    <?php else: ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>One or more critical server requirements failed. Please enable the missing extensions in PHP or adjust permissions before continuing.</span>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <a href="/install/index.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
            Back
        </a>
        <a href="<?= $allCriticalPassed ? '/install/database.php' : '#' ?>"
           class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl font-semibold text-sm shadow-md transition-all <?= $allCriticalPassed ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-purple-200' : 'bg-slate-200 text-slate-400 cursor-not-allowed pointer-events-none' ?>">
            <span>Next: Database Setup</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
