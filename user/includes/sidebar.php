<?php
/**
 * User Panel Sidebar Navigation
 * Matches Reference Screenshot Exactly
 */

$currentScript = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile backdrop -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs md:hidden" style="display: none;"></div>

<!-- Sidebar Component -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-100 flex flex-col justify-between transition-transform duration-200 ease-in-out md:static md:translate-x-0 shrink-0">
    <div>
        <!-- Top Logo -->
        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <a href="/user/dashboard.php" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="font-extrabold text-lg tracking-tight text-slate-900">OTP <span class="text-purple-600">Services</span></span>
            </a>
        </div>

        <!-- User Profile Card (matching screenshot) -->
        <div class="p-4 mx-3 my-3 bg-purple-50/60 rounded-2xl flex items-center gap-3 border border-purple-100/60">
            <div class="w-10 h-10 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-sm font-bold text-slate-900 truncate"><?= e($currentUser['name']) ?></div>
                <div class="text-xs text-purple-600 font-medium">User</div>
            </div>
        </div>

        <!-- Nav Items -->
        <nav class="px-3 space-y-1 text-sm font-medium">
            <a href="/user/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $currentScript === 'dashboard.php' || $currentScript === 'index.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="/user/services.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= in_array($currentScript, ['services.php', 'service-details.php', 'buy-number.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Services</span>
            </a>

            <a href="/user/orders.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= in_array($currentScript, ['orders.php', 'order-details.php', 'active-orders.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Orders</span>
            </a>

            <a href="/user/wallet.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= in_array($currentScript, ['wallet.php', 'add-funds.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Wallet</span>
            </a>

            <a href="/user/transactions.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= in_array($currentScript, ['transactions.php', 'payment-history.php', 'refunds.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Transactions</span>
            </a>

            <a href="/user/notifications.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors <?= $currentScript === 'notifications.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>Notifications</span>
                </div>
                <?php if ($unreadNotifsCount > 0): ?>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $currentScript === 'notifications.php' ? 'bg-white text-purple-600' : 'bg-red-500 text-white' ?>"><?= $unreadNotifsCount ?></span>
                <?php endif; ?>
            </a>

            <a href="/user/profile.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $currentScript === 'profile.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Profile</span>
            </a>

            <a href="/user/security.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $currentScript === 'security.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Security</span>
            </a>

            <a href="/user/support.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= in_array($currentScript, ['support.php', 'ticket-details.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Support</span>
            </a>
        </nav>
    </div>

    <!-- Bottom Logout Button (matching screenshot) -->
    <div class="p-4 border-t border-slate-100">
        <a href="/logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
