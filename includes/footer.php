<?php
/**
 * Public Layout Footer
 */
?>
<footer class="mt-auto bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center text-white font-bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="text-white font-bold text-lg">OTP Services</span>
                </div>
                <p class="text-sm text-slate-400">Enterprise grade SMS verification marketplace with high availability and instant automated delivery.</p>
                <div class="text-xs text-slate-500">© <?= date('Y') ?> OTP Services Platform. All rights reserved.</div>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/index.php" class="hover:text-white transition-colors">Home</a></li>
                    <li><a href="/user/services.php" class="hover:text-white transition-colors">Services Directory</a></li>
                    <li><a href="/login.php" class="hover:text-white transition-colors">User Portal</a></li>
                    <li><a href="/register.php" class="hover:text-white transition-colors">Create Account</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Admin & System</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/admin/login.php" class="hover:text-white transition-colors">Super Admin Portal</a></li>
                    <li><a href="/admin/service-importer.php" class="hover:text-white transition-colors">Service Importer</a></li>
                    <li><a href="/cron/sync-services.php" class="hover:text-white transition-colors">Sync Engine</a></li>
                    <li><a href="/cron/check-orders.php" class="hover:text-white transition-colors">Order Checker</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Security & Support</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/user/support.php" class="hover:text-white transition-colors">Help Desk & Tickets</a></li>
                    <li><span class="inline-flex items-center gap-1.5 text-xs text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> 99.98% Gateway Uptime</span></li>
                    <li><p class="text-xs text-slate-500 mt-2">Protected by 256-bit SSL encryption and strict verification policies.</p></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Global App JS -->
<script src="/assets/js/app.js"></script>
</body>
</html>
