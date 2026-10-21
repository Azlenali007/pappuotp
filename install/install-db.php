<?php
/**
 * Step 4: Database Schema Installation Screen
 */
require_once __DIR__ . '/includes/installer-auth.php';

$stepNumber = 4;
$pageTitle = 'Install Database Schema';

$dbConfig = get_active_installer_db();
if (!$dbConfig) {
    header('Location: /install/database.php');
    exit;
}

$error = '';
$success = '';
$existingTables = $dbConfig['existing_tables'] ?? [];
$tablesCount = count($existingTables);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $host = $dbConfig['host'];
        $port = (int)$dbConfig['port'];
        $dbname = $dbConfig['dbname'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $schemaPath = dirname(__DIR__) . '/database/schema.sql';
            $result = run_schema_installation($pdo, $schemaPath);

            if ($result['success']) {
                seed_essential_data($pdo);
                $_SESSION['installer_db_installed'] = true;
                record_completed_step(4);
                installer_log('STEP4_SUCCESS', "Database schema installed successfully", [
                    'queries' => $result['queries_executed'] ?? 0
                ]);
                header('Location: /install/admin-account.php');
                exit;
            } else {
                $error = $result['error'] ?? 'Schema execution failed.';
                installer_log('STEP4_FAILED', $error);
            }
        } catch (\Throwable $e) {
            $error = 'Database installation error: ' . $e->getMessage();
            installer_log('STEP4_ERROR', $error);
        }
    }
}

require_once __DIR__ . '/includes/installer-header.php';
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Database Schema Installation</h1>
        <p class="text-sm text-slate-500 mt-1">Importing the production schema definitions, table structures, and indexing for OTP Platform.</p>
    </div>

    <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-start gap-2.5">
        <svg class="w-5 h-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold">Installation Interrupted</div>
            <div class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Target Database Details -->
    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Target Database</span>
            <div class="text-base font-bold text-slate-900 mt-0.5">
                <?= htmlspecialchars($dbConfig['dbname']) ?> <span class="text-xs font-normal text-slate-500 font-mono">on <?= htmlspecialchars($dbConfig['host']) ?>:<?= htmlspecialchars($dbConfig['port']) ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Connected
            </span>
        </div>
    </div>

    <!-- Existing Tables Condition -->
    <?php if ($tablesCount > 0): ?>
    <div class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-2">
        <div class="flex items-center gap-2 text-amber-900 font-bold text-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Existing Tables Detected (<?= $tablesCount ?> tables)</span>
        </div>
        <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
            Database <strong><?= htmlspecialchars($dbConfig['dbname']) ?></strong> already contains tables (e.g. <code><?= htmlspecialchars(implode(', ', array_slice($existingTables, 0, 5))) ?><?= $tablesCount > 5 ? '...' : '' ?></code>).
            Proceeding will verify and apply the schema to ensure all 26 required tables exist.
        </p>
    </div>
    <?php else: ?>
    <div class="p-5 rounded-2xl bg-purple-50/60 border border-purple-100 space-y-2 text-sm text-purple-900">
        <span class="font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Clean Database Ready
        </span>
        <p class="text-xs text-purple-700">The installer will now initialize 26 database tables, indexes, constraints, and base roles.</p>
    </div>
    <?php endif; ?>

    <!-- Schema Checklist -->
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Included Schema Modules</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                <span class="font-semibold text-slate-800">Users & Admin RBAC</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                <span class="font-semibold text-slate-800">Wallets & Atomic Transactions</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                <span class="font-semibold text-slate-800">Services & Country Providers</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                <span class="font-semibold text-slate-800">Live Orders & Verification SMS</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                <span class="font-semibold text-slate-800">Payments, Refunds & Webhooks</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                <span class="font-semibold text-slate-800">Support Tickets & Audit Logs</span>
            </div>
        </div>
    </div>

    <!-- Schema Progress Container (AJAX feedback) -->
    <div id="schema-progress-container" class="hidden space-y-3 p-5 rounded-2xl bg-purple-50/70 border border-purple-200/80">
        <div class="flex items-center justify-between text-xs font-bold text-purple-900">
            <span id="schema-progress-status">Initializing database schema installation...</span>
            <span id="schema-progress-percent">0%</span>
        </div>
        <div class="w-full bg-purple-200/60 h-2.5 rounded-full overflow-hidden">
            <div id="schema-progress-bar" class="bg-purple-600 h-2.5 rounded-full transition-all duration-300 w-0"></div>
        </div>
        <div id="schema-progress-detail" class="text-xs text-purple-700">Connecting to MySQL database...</div>
    </div>

    <!-- Actions -->
    <form method="POST" id="schema-install-form">
        <input type="hidden" name="csrf_token" value="<?= get_installer_csrf_token() ?>">
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="/install/database.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                Back to DB Settings
            </a>
            <button type="submit" id="install-schema-btn" class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-md shadow-purple-200 transition-all">
                <span>Execute & Install Schema</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
