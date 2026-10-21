<?php
/**
 * User Panel Top Navbar
 */
?>
<header class="h-16 bg-white border-b border-slate-100 flex items-center justify-between px-4 sm:px-8 shrink-0">
    <!-- Left: Mobile Toggle & Search input -->
    <div class="flex items-center gap-4 flex-1 max-w-lg">
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <form action="/user/services.php" method="GET" class="relative w-full">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input type="text" name="search" placeholder="Search services..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
        </form>
    </div>

    <!-- Right: Balance, Notifications & User avatar -->
    <div class="flex items-center gap-4">
        <!-- Live Wallet Balance pill -->
        <a href="/user/wallet.php" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-100 text-xs font-bold transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span id="nav-wallet-balance"><?= format_currency($currentUser['balance']) ?></span>
        </a>

        <!-- Notifications Bell -->
        <a href="/user/notifications.php" class="relative p-2 text-slate-500 hover:text-purple-600 hover:bg-slate-50 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <?php if ($unreadNotifsCount > 0): ?>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            <?php endif; ?>
        </a>

        <!-- User Avatar Dropdown -->
        <a href="/user/profile.php" class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-50 transition-colors">
            <div class="w-8 h-8 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
            </div>
        </a>
    </div>
</header>
