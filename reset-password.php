<?php
/**
 * Reset Password Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$token = trim($_GET['token'] ?? '');
$flash = get_flash();
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 p-8 sm:p-10">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Reset Your Password</h1>
            <p class="text-sm text-slate-500 mt-1">Please enter and confirm your new password below.</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form action="/auth/reset-password-process.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">New Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
            </div>

            <div>
                <label for="confirm_password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-semibold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all text-sm">
                Save & Update Password
            </button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
