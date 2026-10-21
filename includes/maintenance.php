<?php
/**
 * Maintenance Mode Check
 */

require_once __DIR__ . '/../config/app.php';

function check_maintenance_mode(): void {
    $mode = get_setting('maintenance_mode', '0');
    if ($mode === '1') {
        // Allow logged-in admins to bypass
        if (!empty($_SESSION['admin_id'])) {
            return;
        }
        
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($uri, '/admin')) {
            return;
        }

        http_response_code(503);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Maintenance - OTP Services</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-50 min-h-screen flex items-center justify-center p-6 text-center font-sans">
            <div class="max-w-md bg-white p-8 rounded-2xl shadow-xl border border-slate-100">
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Under Scheduled Maintenance</h1>
                <p class="text-slate-600 mb-6">We are currently upgrading our systems to provide faster verification services. We will be back shortly.</p>
                <a href="/admin/login.php" class="text-xs text-purple-600 hover:underline">Admin Login</a>
            </div>
        </body>
        </html>';
        exit;
    }
}
