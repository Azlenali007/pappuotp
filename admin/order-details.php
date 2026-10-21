<?php
/**
 * Admin Order Inspection & Management
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/wallet-manager.php';
require_once __DIR__ . '/../services/order-manager.php';

$db = get_db();
$orderId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'admin_cancel_refund') {
        if (WalletManager::refundOrder($orderId, 'Super Admin manual cancellation & refund')) {
            log_admin_audit('order_cancelled', "Admin cancelled order ID {$orderId} and refunded funds.");
            set_flash('success', 'Order cancelled and funds returned to user wallet.');
        } else {
            set_flash('error', 'Could not process refund.');
        }
    }

    if ($action === 'admin_deliver_otp') {
        $manualOtp = trim($_POST['manual_otp'] ?? rand(100000, 999999));
        OrderManager::fulfillOtp($orderId, $manualOtp, "Manual verification delivered by Super Admin: {$manualOtp}");
        log_admin_audit('order_fulfilled', "Admin delivered OTP {$manualOtp} for order ID {$orderId}");
        set_flash('success', "OTP {$manualOtp} fulfilled.");
    }

    header("Location: /admin/order-details.php?id=" . $orderId);
    exit;
}

$stmt = $db->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email, s.name as service_name, p.name as provider_name 
    FROM orders o 
    JOIN users u ON u.id = o.user_id 
    JOIN services s ON s.id = o.service_id 
    JOIN providers p ON p.id = o.provider_id 
    WHERE o.id = ? 
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: /admin/orders.php");
    exit;
}

// Fetch status history
$stmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
$stmt->execute([$orderId]);
$history = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-4xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/orders.php" class="hover:text-purple-600">Orders</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($order['order_ref']) ?></span>
            </div>

            <!-- Details Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900">Order #<?= e($order['order_ref']) ?></h1>
                        <div class="text-xs text-slate-500 mt-1">User: <span class="font-bold text-slate-800"><?= e($order['user_name']) ?></span> (<?= e($order['user_email']) ?>)</div>
                    </div>
                    <div>
                        <?= render_status_badge($order['status']) ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Service</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block"><?= e($order['service_name']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Allocated Phone</span>
                        <strong class="text-slate-900 text-sm font-mono mt-0.5 block"><?= e($order['phone_number']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Provider Gateway</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block"><?= e($order['provider_name']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Order Cost</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block"><?= format_currency($order['cost']) ?></strong>
                    </div>
                </div>

                <?php if ($order['otp_code']): ?>
                    <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100 text-center space-y-1">
                        <div class="text-xs uppercase tracking-wider text-purple-700 font-bold">Delivered Verification Code</div>
                        <div class="text-4xl font-mono font-black text-purple-900 tracking-widest"><?= e($order['otp_code']) ?></div>
                        <?php if ($order['full_sms']): ?>
                            <div class="text-xs text-purple-700 font-mono mt-2 bg-white/70 p-2 rounded-lg"><?= e($order['full_sms']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Status History Trail -->
                <div class="space-y-3 pt-2">
                    <h3 class="text-sm font-bold text-slate-900">Lifecycle Audit Trail</h3>
                    <div class="space-y-2 border border-slate-100 rounded-2xl p-4 divide-y divide-slate-100 text-xs">
                        <?php foreach ($history as $h): ?>
                            <div class="pt-2 first:pt-0 flex items-center justify-between text-slate-600">
                                <div>
                                    <span class="font-bold uppercase text-slate-800"><?= e($h['new_status']) ?>:</span>
                                    <span><?= e($h['note']) ?></span>
                                </div>
                                <span class="text-slate-400"><?= date('H:i:s, d M Y', strtotime($h['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Admin Action Buttons -->
                <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100">
                    <a href="/admin/orders.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        &larr; Back to Orders
                    </a>

                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="flex items-center gap-3">
                            <!-- Admin deliver OTP -->
                            <form method="POST" action="/admin/order-details.php?id=<?= $order['id'] ?>" class="flex items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="admin_deliver_otp">
                                <input type="text" name="manual_otp" placeholder="Enter code" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs w-28">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700">Fulfill OTP</button>
                            </form>

                            <!-- Admin cancel & refund -->
                            <form method="POST" action="/admin/order-details.php?id=<?= $order['id'] ?>" onsubmit="return confirm('Cancel this order and refund user wallet?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="admin_cancel_refund">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 font-bold text-xs hover:bg-rose-100 border border-rose-200">Cancel & Refund</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
