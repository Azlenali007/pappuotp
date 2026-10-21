<?php
/**
 * Super Admin Sidebar Navigation
 * Matches Admin Dashboard Screenshot
 */

$currentScript = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile backdrop -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs md:hidden" style="display: none;"></div>

<!-- Sidebar Component -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-100 flex flex-col justify-between transition-transform duration-200 ease-in-out md:static md:translate-x-0 shrink-0">
    <div class="overflow-y-auto">
        <!-- Top Logo -->
        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <a href="/admin/dashboard.php" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="font-extrabold text-lg tracking-tight text-slate-900">OTP <span class="text-purple-600">Services</span></span>
            </a>
        </div>

        <!-- Admin Profile Card -->
        <div class="p-4 mx-3 my-3 bg-purple-50/60 rounded-2xl flex items-center gap-3 border border-purple-100/60">
            <div class="w-10 h-10 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                A
            </div>
            <div class="overflow-hidden">
                <div class="text-sm font-bold text-slate-900 truncate"><?= e($currentAdmin['name']) ?></div>
                <div class="text-xs text-purple-600 font-semibold">Super Admin</div>
            </div>
        </div>

        <!-- Nav Items -->
        <nav class="px-3 space-y-1 text-sm font-medium pb-6">
            <a href="/admin/dashboard.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['dashboard.php', 'index.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="/admin/users.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['users.php', 'user-details.php', 'user-edit.php', 'user-transactions.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Users</span>
            </a>

            <!-- Mandatory Service Importer -->
            <a href="/admin/service-importer.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['service-importer.php', 'import-history.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Service Importer</span>
            </a>

            <a href="/admin/services.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['services.php', 'service-add.php', 'service-edit.php', 'categories.php', 'countries.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Services</span>
            </a>

            <a href="/admin/providers.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['providers.php', 'provider-add.php', 'provider-edit.php', 'provider-test.php', 'provider-logs.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                <span>Providers</span>
            </a>

            <a href="/admin/orders.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['orders.php', 'order-details.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Orders</span>
            </a>

            <a href="/admin/payments.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['payments.php', 'deposit-requests.php', 'refunds.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Payments</span>
            </a>

            <a href="/admin/transactions.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= $currentScript === 'transactions.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Transactions</span>
            </a>

            <a href="/admin/wallet.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= $currentScript === 'wallet.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Wallet Adjustments</span>
            </a>

            <a href="/admin/pricing.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= $currentScript === 'pricing.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Pricing & Margins</span>
            </a>

            <a href="/admin/reports.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= $currentScript === 'reports.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Reports</span>
            </a>

            <a href="/admin/support.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['support.php', 'ticket-details.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Support</span>
            </a>

            <a href="/admin/settings.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= in_array($currentScript, ['settings.php', 'website-settings.php', 'payment-settings.php', 'security-settings.php', 'admins.php', 'roles.php']) ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Settings</span>
            </a>

            <a href="/admin/activity-logs.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors <?= $currentScript === 'activity-logs.php' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Audit Logs</span>
            </a>
        </nav>
    </div>

    <!-- Bottom Logout -->
    <div class="p-4 border-t border-slate-100">
        <a href="/admin/logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Admin Logout</span>
        </a>
    </div>
</aside>
