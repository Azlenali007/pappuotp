<?php
/**
 * User Registration Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

if (is_user_logged_in()) {
    header('Location: /user/dashboard.php');
    exit;
}

$flash = get_flash();
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[600px]">
        
        <!-- Left Side: Registration Form -->
        <div class="md:col-span-7 p-8 sm:p-12 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900">OTP <span class="text-purple-600">Services</span></span>
                </div>

                <div class="space-y-1 mb-6">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Create an Account</h1>
                    <p class="text-sm text-slate-500">Sign up in seconds to access virtual OTP numbers</p>
                </div>

                <?php if ($flash): ?>
                    <div class="mb-6 p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <form action="/auth/register-process.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1 uppercase tracking-wider">Full Name</label>
                            <input type="text" id="name" name="name" required placeholder="John Doe" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
                        </div>
                        <div>
                            <label for="username" class="block text-xs font-semibold text-slate-700 mb-1 uppercase tracking-wider">Username</label>
                            <input type="text" id="username" name="username" required placeholder="johndoe" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1 uppercase tracking-wider">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="name@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1 uppercase tracking-wider">Password</label>
                            <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-xs font-semibold text-slate-700 mb-1 uppercase tracking-wider">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm bg-slate-50/50 focus:bg-white">
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 text-xs text-slate-600 pt-2">
                        <input type="checkbox" id="terms" name="terms" required class="mt-0.5 w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300">
                        <label for="terms" class="cursor-pointer">
                            I agree to the <a href="#" class="text-purple-600 hover:underline">Terms of Service</a> and <a href="#" class="text-purple-600 hover:underline">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-semibold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95 text-sm mt-3">
                        Create Account
                    </button>
                </form>
            </div>

            <div class="text-center text-sm text-slate-600 pt-6 border-t border-slate-50 mt-6">
                Already have an account? <a href="/login.php" class="font-semibold text-purple-600 hover:text-purple-800">Login</a>
            </div>
        </div>

        <!-- Right Side: Branded Panel -->
        <div class="hidden md:flex md:col-span-5 bg-gradient-to-br from-purple-700 via-purple-800 to-indigo-950 p-10 flex-col justify-between text-white relative overflow-hidden">
            <div class="space-y-2 relative z-10">
                <h2 class="text-2xl font-bold tracking-tight">Instant Verification<br>Made Effortless</h2>
                <p class="text-purple-200 text-xs tracking-wider uppercase font-semibold">Join thousands of users</p>
            </div>

            <div class="relative z-10 py-10 flex items-center justify-center">
                <div class="w-32 h-32 rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl flex items-center justify-center p-6">
                    <svg class="w-16 h-16 text-white drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>

            <div class="relative z-10 space-y-2 text-xs text-purple-200/80">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>100+ platforms supported</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Transparent pricing with no hidden fees</span>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
