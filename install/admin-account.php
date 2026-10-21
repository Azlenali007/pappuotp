<?php
/**
 * Step 5: Administrator Account Setup
 */
require_once __DIR__ . '/includes/installer-auth.php';

$stepNumber = 5;
$pageTitle = 'Create Super Administrator';

$dbConfig = get_active_installer_db();
if (!$dbConfig) {
    header('Location: /install/database.php');
    exit;
}

$error = '';
$name = $_POST['name'] ?? ($_SESSION['installer_admin']['name'] ?? 'Super Admin');
$username = $_POST['username'] ?? ($_SESSION['installer_admin']['username'] ?? 'admin');
$email = $_POST['email'] ?? ($_SESSION['installer_admin']['email'] ?? 'admin@otprocket.com');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($password !== $confirmPassword) {
            $error = 'Password confirmation does not match.';
        } else {
            try {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                $result = create_super_admin($pdo, $name, $username, $email, $password);
                if ($result['success']) {
                    $_SESSION['installer_admin'] = [
                        'name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'id' => $result['admin_id']
                    ];
                    record_completed_step(5);
                    installer_log('STEP5_SUCCESS', "Super Administrator account created", ['username' => $username, 'email' => $email]);
                    header('Location: /install/configuration.php');
                    exit;
                } else {
                    $error = $result['error'];
                    installer_log('STEP5_FAILED', $error);
                }
            } catch (\PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                installer_log('STEP5_ERROR', $error);
            }
        }
    }
}

require_once __DIR__ . '/includes/installer-header.php';
?>

<div class="p-6 sm:p-10 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create Super Administrator</h1>
        <p class="text-sm text-slate-500 mt-1">This account will have full, unrestricted administrative control over the platform.</p>
    </div>

    <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-start gap-2.5">
        <svg class="w-5 h-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold">Validation Error</div>
            <div class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= get_installer_csrf_token() ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="admin_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Full Name</label>
                <input type="text" id="admin_name" name="name" value="<?= htmlspecialchars($name) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="e.g. Platform Administrator">
            </div>

            <div>
                <label for="admin_username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Username</label>
                <input type="text" id="admin_username" name="username" value="<?= htmlspecialchars($username) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="e.g. admin">
                <p class="text-xs text-slate-400 mt-1">Alphanumeric, dashes, and underscores only.</p>
            </div>

            <div class="sm:col-span-2">
                <label for="admin_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" id="admin_email" name="email" value="<?= htmlspecialchars($email) ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="admin@yourdomain.com">
            </div>

            <div>
                <label for="admin_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Admin Password</label>
                <input type="password" id="admin_password" name="password" required minlength="8"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="••••••••••••">
                <div class="mt-2 space-y-1">
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div id="password-strength-meter" class="h-1.5 rounded-full transition-all duration-300 w-0 bg-rose-500"></div>
                    </div>
                    <div id="password-strength-label" class="text-xs text-slate-400">At least 8 characters recommended</div>
                </div>
            </div>

            <div>
                <label for="admin_password_confirm" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Confirm Password</label>
                <input type="password" id="admin_password_confirm" name="confirm_password" required minlength="8"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900"
                       placeholder="••••••••••••">
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100 flex items-start gap-3 text-xs text-purple-900">
            <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <div>
                <strong>Security Guarantee:</strong> Passwords are encrypted using robust bcrypt hashes (cost factor 12). Plaintext passwords are never logged, stored, or displayed.
            </div>
        </div>

        <!-- Actions -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="/install/database.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                Back
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-md shadow-purple-200 transition-all">
                <span>Save Admin & Continue</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/installer-footer.php'; ?>
