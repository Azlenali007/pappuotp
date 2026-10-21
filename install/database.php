<?php
/**
 * Step 3: Database Connection Configuration
 */
require_once __DIR__ . '/includes/installer-auth.php';

$stepNumber = 3;
$pageTitle = 'Database Setup';
$error = '';

$currentDb = get_active_installer_db();
$host = $currentDb['host'] ?? '127.0.0.1';
$port = (string)($currentDb['port'] ?? '3306');
$dbname = $currentDb['dbname'] ?? 'otp_platform';
$username = $currentDb['username'] ?? 'otp_user';
$password = $currentDb['password'] ?? 'otp_password';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please try again.';
    } else {
        $host = trim($_POST['host'] ?? '127.0.0.1');
        $port = trim($_POST['port'] ?? '3306');
        $dbname = trim($_POST['dbname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $test = test_db_connection($host, $port, $dbname, $username, $password);
        if ($test['success']) {
            $_SESSION['installer_db'] = [
                'host' => $host,
                'port' => $port,
                'dbname' => $dbname,
                'username' => $username,
                'password' => $password,
                'existing_tables' => $test['existing_tables'] ?? [],
                'tables_count' => $test['tables_count'] ?? 0
            ];
            record_completed_step(3);
            installer_log('STEP3_ADVANCE', "Database credentials validated; proceeding to Step 4", ['dbname' => $dbname]);
            header('Location: /install/install-db.php');
            exit;
        } else {
            $error = $test['message'];
        }
    }
}

require_once __DIR__ . '/includes/installer-header.php';
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Database Configuration</h1>
        <p class="text-sm text-slate-500 mt-1">Provide credentials to connect to your MySQL or MariaDB database server.</p>
    </div>

    <!-- Alert / Error message -->
    <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-start gap-2.5">
        <svg class="w-5 h-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold">Connection Error</div>
            <div class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Live Test Status Result Div (populated by AJAX) -->
    <div id="db-test-result" class="hidden"></div>

    <!-- Database Form -->
    <form method="POST" id="db-form" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= get_installer_csrf_token() ?>">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="sm:col-span-2">
                <label for="db_host" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Host</label>
                <input type="text" id="db_host" name="host" value="<?= htmlspecialchars($host) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="127.0.0.1 or localhost">
            </div>

            <div>
                <label for="db_port" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Port</label>
                <input type="number" id="db_port" name="port" value="<?= htmlspecialchars($port) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="3306">
            </div>

            <div class="sm:col-span-3">
                <label for="db_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Name</label>
                <input type="text" id="db_name" name="dbname" value="<?= htmlspecialchars($dbname) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="e.g. otp_platform">
                <p class="text-xs text-slate-400 mt-1.5">Note: Ensure the database has already been created in cPanel, phpMyAdmin, or MySQL CLI.</p>
            </div>

            <div class="sm:col-span-3 sm:grid sm:grid-cols-2 gap-5 space-y-5 sm:space-y-0">
                <div>
                    <label for="db_user" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Username</label>
                    <input type="text" id="db_user" name="username" value="<?= htmlspecialchars($username) ?>" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                           placeholder="e.g. otp_user">
                </div>

                <div>
                    <label for="db_pass" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Password</label>
                    <input type="password" id="db_pass" name="password" value="<?= htmlspecialchars($password) ?>"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                           placeholder="••••••••••••">
                </div>
            </div>
        </div>

        <!-- Connection Test Button -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-500 text-center sm:text-left">
                Verify credentials before writing tables or saving settings.
            </div>
            <button type="button" id="test-db-btn" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Test Database Connection
            </button>
        </div>

        <!-- Actions -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="/install/requirements.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                Back
            </a>
            <button type="submit" id="save-db-btn" class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-md shadow-purple-200 transition-all">
                <span>Save & Continue to Schema Installation</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
