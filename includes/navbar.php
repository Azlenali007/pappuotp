<?php
/**
 * Public Layout Navbar
 */

$currentUser = get_current_user_data();
?>
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <div class="flex items-center gap-3">
                <a href="/index.php" class="flex items-center gap-2 group">
                    <div class="w-9 h-9 rounded-xl bg-purple-600 flex items-center justify-center shadow-md shadow-purple-200 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span class="font-extrabold text-xl tracking-tight text-slate-900">OTP <span class="text-purple-600">Services</span></span>
                </a>
            </div>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="/index.php" class="hover:text-purple-600 transition-colors">Home</a>
                <a href="/user/services.php" class="hover:text-purple-600 transition-colors flex items-center gap-1">
                    Services
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </a>
                <a href="/index.php#pricing" class="hover:text-purple-600 transition-colors">Pricing</a>
                <a href="/index.php#how-it-works" class="hover:text-purple-600 transition-colors">About</a>
                <a href="/user/support.php" class="hover:text-purple-600 transition-colors">Contact</a>
            </nav>

            <!-- Auth Buttons -->
            <div class="flex items-center gap-3">
                <?php if ($currentUser): ?>
                    <a href="/user/dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Dashboard (<?= format_currency($currentUser['balance']) ?>)
                    </a>
                    <a href="/logout.php" class="text-sm font-medium text-slate-500 hover:text-slate-900">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-purple-600 transition-colors">Login</a>
                    <a href="/register.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
