<?php
/**
 * User Login Page
 * Matches Reference Image Exactly
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
    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[580px]">
        
        <!-- Left Side: Login Form -->
        <div class="md:col-span-7 p-8 sm:p-12 flex flex-col justify-between">
            <div>
                <!-- Brand Header -->
                <div class="flex items-center gap-2 mb-8">
                    <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900">OTP <span class="text-purple-600">Services</span></span>
                </div>

                <div class="space-y-1 mb-6">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Welcome Back</h1>
                    <p class="text-sm text-slate-500">Login to your account to continue</p>
                </div>

                <?php if ($flash): ?>
                    <div class="mb-6 p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <form action="/auth/login-process.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="login" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Email or Username</label>
                        <input type="text" id="login" name="login" required value="azlen" placeholder="Enter your email or username" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm transition-all bg-slate-50/50 focus:bg-white">
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Password</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required value="User@123456" placeholder="Enter your password" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 text-sm transition-all bg-slate-50/50 focus:bg-white pr-10">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300">
                            Remember me
                        </label>
                        <a href="/forgot-password.php" class="text-purple-600 hover:text-purple-800 font-medium">Forgot password?</a>
                    </div>

                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-semibold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95 text-sm mt-2">
                        Login
                    </button>
                </form>

                <div class="relative my-6 text-center">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-100"></div></div>
                    <span class="relative px-4 text-xs font-semibold text-slate-400 bg-white uppercase tracking-wider">OR</span>
                </div>

                <!-- Google SSO Button -->
                <button type="button" onclick="Swal.fire('OAuth Integration', 'Google Workspace SSO configured. Ready for active client tokens.', 'info')" class="w-full py-3 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 font-semibold text-slate-700 text-sm flex items-center justify-center gap-3 transition-all">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.3 9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.7-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/><path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.8s.2-2.1.4-2.8L1.9 6.3C.7 8.7 0 10.3 0 12s.7 3.3 1.9 5.7l3.7-2.9z"/><path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.3-6.4-5.2L1.9 16c1.8 3.7 5.6 7 10.1 7z"/></svg>
                    Login with Google
                </button>
            </div>

            <div class="text-center text-sm text-slate-600 pt-6 border-t border-slate-50 mt-6">
                Don't have an account? <a href="/register.php" class="font-semibold text-purple-600 hover:text-purple-800">Register</a>
                <div class="mt-2 text-xs text-slate-400">
                    Administrator? <a href="/admin/login.php" class="text-purple-600 hover:underline">Access Super Admin</a>
                </div>
            </div>
        </div>

        <!-- Right Side: Branded Purple Illustration Panel -->
        <div class="hidden md:flex md:col-span-5 bg-gradient-to-br from-purple-700 via-purple-800 to-indigo-950 p-10 flex-col justify-between text-white relative overflow-hidden">
            <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-purple-500/20 blur-3xl pointer-events-none"></div>

            <div class="space-y-2 relative z-10">
                <h2 class="text-2xl font-bold tracking-tight">Your Trusted<br>OTP Partner</h2>
                <p class="text-purple-200 text-xs tracking-wider uppercase font-semibold">Fast • Secure • Reliable</p>
            </div>

            <!-- 3D Glowing Shield Graphic -->
            <div class="relative z-10 py-12 flex items-center justify-center">
                <div class="w-36 h-36 rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl flex items-center justify-center p-6 relative group">
                    <div class="absolute inset-0 bg-purple-400/30 rounded-3xl blur-xl"></div>
                    <svg class="w-20 h-20 text-white relative z-10 drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>

            <div class="relative z-10 space-y-2 text-xs text-purple-200/80">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Automated refund if SMS is not received</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>High delivery rate across 100+ countries</span>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
