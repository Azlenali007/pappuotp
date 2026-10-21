<?php
/**
 * Step 1: Installer Welcome Screen
 */
$stepNumber = 1;
$pageTitle = 'Welcome to OTP Platform Setup';
require_once __DIR__ . '/includes/installer-header.php';

// Detect whether database already has existing tables
$hasExistingData = false;
$existingTablesCount = 0;
try {
    require_once dirname(__DIR__) . '/config/database.php';
    $db = Database::getConnection();
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $existingTablesCount = count($tables);
    if ($existingTablesCount > 0) {
        $hasExistingData = true;
    }
} catch (\Throwable $e) {
    // If connection not configured yet, it's definitely a fresh installation
    $hasExistingData = false;
}
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Hero / Title -->
    <div class="flex items-start gap-5">
        <div class="w-14 h-14 rounded-2xl bg-purple-100/80 border border-purple-200 flex items-center justify-center shrink-0 text-purple-600">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Welcome to OTP Services Platform
            </h1>
            <p class="text-sm sm:text-base text-slate-500">
                This guided installer will help you configure your database connection, set up the database schema, establish the Super Administrator account, and configure platform settings.
            </p>
        </div>
    </div>

    <!-- Fresh vs Existing Installation Notice -->
    <?php if ($hasExistingData): ?>
    <div class="p-5 rounded-2xl bg-purple-50/70 border border-purple-200/80 flex items-start gap-3.5">
        <div class="p-1 rounded-lg bg-purple-600 text-white shrink-0 mt-0.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="space-y-1 text-sm">
            <span class="font-bold text-purple-900">Existing Database Detected</span>
            <p class="text-purple-700 leading-relaxed text-xs sm:text-sm">
                Found <strong><?= $existingTablesCount ?> existing tables</strong> in your configured MySQL database. The installer will protect your existing user balances, orders, and logs during schema verification.
            </p>
        </div>
    </div>
    <?php else: ?>
    <div class="p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex items-start gap-3.5">
        <div class="p-1 rounded-lg bg-emerald-600 text-white shrink-0 mt-0.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="space-y-1 text-sm">
            <span class="font-bold text-emerald-900">Fresh Installation Mode</span>
            <p class="text-emerald-700 leading-relaxed text-xs sm:text-sm">
                Ready to initialize clean database tables, service catalogs, roles, and settings for a brand-new production deployment.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Prerequisites Cards -->
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Prerequisites & System Checklist</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 space-y-1">
                <div class="text-xs font-semibold text-slate-400">Environment</div>
                <div class="text-sm font-bold text-slate-800">PHP 8.2 or Higher</div>
                <div class="text-xs text-slate-500">PDO, cURL, OpenSSL</div>
            </div>
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 space-y-1">
                <div class="text-xs font-semibold text-slate-400">Database</div>
                <div class="text-sm font-bold text-slate-800">MySQL 8.0+ / MariaDB</div>
                <div class="text-xs text-slate-500">InnoDB & UTF8MB4</div>
            </div>
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 space-y-1">
                <div class="text-xs font-semibold text-slate-400">Permissions</div>
                <div class="text-sm font-bold text-slate-800">Writable Directories</div>
                <div class="text-xs text-slate-500">storage/ & config/</div>
            </div>
        </div>
    </div>

    <!-- Data Safety Warning -->
    <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 flex items-start gap-3 text-xs sm:text-sm text-amber-800">
        <svg class="w-5 h-5 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p>
            <strong>Data Safety Notice:</strong> If you are updating an existing deployment with active customer wallets or orders, ensure you have taken a full database backup before proceeding.
        </p>
    </div>

    <!-- Actions -->
    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-400">Estimated setup time: ~2 minutes</span>
        <a href="/install/requirements.php" class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-lg shadow-purple-200 transition-all group">
            <span>Start Installation</span>
            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
