<?php
/**
 * Admin Panel Helper Functions
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!function_exists('log_admin_audit')) {
    function log_admin_audit(string $action, ?string $details = null): void {
        $adminId = $_SESSION['admin_id'] ?? null;
        $db = get_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $details, $ip]);
    }
}

if (!function_exists('logAdminActivity')) {
    function logAdminActivity($adminId, string $action, ?string $details = null): void {
        if (!$adminId) {
            $adminId = $_SESSION['admin_id'] ?? null;
        }
        $db = get_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $details, $ip]);
    }
}

if (!function_exists('render_status_badge')) {
    function render_status_badge(string $status): string {
        $classes = [
            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'waiting' => 'bg-amber-50 text-amber-700 border-amber-200',
            'open' => 'bg-blue-50 text-blue-700 border-blue-200',
            'answered' => 'bg-purple-50 text-purple-700 border-purple-200',
            'inactive' => 'bg-slate-100 text-slate-600 border-slate-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
            'expired' => 'bg-rose-50 text-rose-700 border-rose-200',
            'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
        ];
        $cls = $classes[strtolower($status)] ?? 'bg-slate-100 text-slate-700 border-slate-200';
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border ' . $cls . '">' . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
