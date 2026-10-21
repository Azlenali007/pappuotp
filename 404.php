<?php
/**
 * 404 Not Found Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow flex items-center justify-center py-20 px-4 text-center">
    <div class="max-w-md mx-auto space-y-6">
        <div class="w-20 h-20 rounded-3xl bg-purple-100 text-purple-600 flex items-center justify-center mx-auto shadow-inner">
            <span class="text-3xl font-black">404</span>
        </div>
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold text-slate-900">Page Not Found</h1>
            <p class="text-slate-600 text-sm">The verification page or service endpoint you were looking for does not exist or has been moved.</p>
        </div>
        <div class="pt-4 flex justify-center gap-4">
            <a href="/index.php" class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md transition-all">Back to Home</a>
            <a href="/user/dashboard.php" class="px-6 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 text-slate-700 hover:bg-slate-50 transition-all">User Dashboard</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
