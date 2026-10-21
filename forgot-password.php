<?php
/**
 * Forgot Password Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$flash = get_flash();
$devLink = $_SESSION['dev_reset_link'] ?? null;
unset($_SESSION['dev_reset_link']);
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 p-8 sm:p-10">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Forgot Password?</h1>
            <p class="text-sm text-slate-500 mt-1">Enter your registered email address to receive a secure recovery link.</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($devLink): ?>
            <div class="mb-6 p-4 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs">
                <div class="font-bold mb-1">Local Testing Password Reset Link:</div>
                <a href="<?= e($devLink) ?>" class="text-purple-600 underline break-all"><?= e($devLink) ?></a>
            </div>
        <?php endif; ?>

        <form action="/auth/forgot-password-process.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="name@example.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-semibold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all text-sm">
                Send Reset Link
            </button>
        </form>

        <div class="text-center text-sm text-slate-600 pt-6 border-t border-slate-50 mt-6">
            Remembered your credentials? <a href="/login.php" class="font-semibold text-purple-600 hover:text-purple-800">Back to Login</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
