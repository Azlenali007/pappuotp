<?php
/**
 * Live Order Details & Verification Receiver
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/order-manager.php';
require_once __DIR__ . '/../services/wallet-manager.php';

$db = get_db();
$orderId = (int)($_GET['id'] ?? 0);
$userId = (int)$currentUser['id'];

// Handle action: cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel_order') {
        if (OrderManager::cancelOrder($orderId, $userId)) {
            set_flash('success', 'Order cancelled successfully and funds refunded to your wallet.');
        } else {
            set_flash('error', 'Unable to cancel this order. It may have already expired or completed.');
        }
        header("Location: /user/order-details.php?id=" . $orderId);
        exit;
    }

    if ($action === 'simulate_otp') {
        $simCode = rand(100000, 999999);
        OrderManager::fulfillOtp($orderId, (string)$simCode, "Your verification code is {$simCode}. Valid for 10 minutes.");
        set_flash('success', "Simulated verification code {$simCode} delivered!");
        header("Location: /user/order-details.php?id=" . $orderId);
        exit;
    }
}

// Fetch order
$stmt = $db->prepare("
    SELECT o.*, s.name as service_name, s.icon as service_icon, p.name as provider_name 
    FROM orders o 
    JOIN services s ON s.id = o.service_id 
    JOIN providers p ON p.id = o.provider_id 
    WHERE o.id = ? AND o.user_id = ? 
    LIMIT 1
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: /user/orders.php");
    exit;
}

$expiresTimestamp = strtotime($order['expires_at']);
$secondsRemaining = max(0, $expiresTimestamp - time());

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{
        orderId: <?= $order['id'] ?>,
        status: '<?= e($order['status']) ?>',
        otpCode: '<?= e($order['otp_code'] ?? '') ?>',
        fullSms: '<?= addslashes(e($order['full_sms'] ?? '')) ?>',
        secondsLeft: <?= $secondsRemaining ?>,
        timer: null,
        copied: false,
        copiedCode: false,
        get formattedTime() {
            if (this.secondsLeft <= 0) return 'Expired';
            let m = Math.floor(this.secondsLeft / 60);
            let s = this.secondsLeft % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },
        init() {
            if (this.status === 'pending' && this.secondsLeft > 0) {
                this.timer = setInterval(() => {
                    if (this.secondsLeft > 0) {
                        this.secondsLeft--;
                    }
                }, 1000);

                // Poll for OTP status every 3.5 seconds
                this.pollStatus();
            }
        },
        pollStatus() {
            if (this.status !== 'pending') return;
            fetch('/api/order-status.php?order_id=' + this.orderId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.status) {
                        this.status = data.status;
                        if (data.otp_code) {
                            this.otpCode = data.otp_code;
                            this.fullSms = data.full_sms || '';
                            Swal.fire({
                                icon: 'success',
                                title: 'OTP Code Received!',
                                text: 'Your verification code is: ' + data.otp_code,
                                confirmButtonColor: '#7C3AED'
                            });
                        } else if (data.status === 'pending') {
                            setTimeout(() => this.pollStatus(), 3500);
                        }
                    }
                })
                .catch(() => {
                    setTimeout(() => this.pollStatus(), 5000);
                });
        },
        copyNumber(num) {
            navigator.clipboard.writeText(num);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        copyOtp(code) {
            navigator.clipboard.writeText(code);
            this.copiedCode = true;
            setTimeout(() => this.copiedCode = false, 2000);
        }
    }">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/user/orders.php" class="hover:text-purple-600">Orders</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($order['order_ref']) ?></span>
            </div>

            <!-- Main Order Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-10 space-y-8">
                
                <!-- Card Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center border border-purple-100 shrink-0">
                            <?= get_service_svg_icon($order['service_name'], "w-8 h-8") ?>
                        </div>
                        <div>
                            <h1 class="text-xl font-extrabold text-slate-900"><?= e($order['service_name']) ?></h1>
                            <div class="text-xs text-slate-400 mt-0.5">Order Ref: <span class="font-mono text-slate-600 font-semibold"><?= e($order['order_ref']) ?></span></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold px-3 py-1.5 rounded-full" :class="{
                            'bg-emerald-50 text-emerald-700 border border-emerald-200': status === 'completed',
                            'bg-amber-50 text-amber-700 border border-amber-200 animate-pulse': status === 'pending',
                            'bg-slate-100 text-slate-600 border border-slate-200': status === 'cancelled',
                            'bg-rose-50 text-rose-700 border border-rose-200': status === 'failed' || status === 'expired'
                        }" x-text="status.toUpperCase()"></span>
                    </div>
                </div>

                <!-- Number Display Area -->
                <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 rounded-2xl p-6 sm:p-8 text-white space-y-6 shadow-xl">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-wider font-semibold text-purple-300">Allocated Virtual Phone Number</div>
                            <div class="text-2xl sm:text-4xl font-mono font-black tracking-wide mt-1 text-white flex items-center gap-3">
                                <span><?= e($order['phone_number']) ?></span>
                                <button type="button" @click="copyNumber('<?= e($order['phone_number']) ?>')" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all text-xs flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Timer Box -->
                        <div x-show="status === 'pending'" class="text-right sm:text-right">
                            <div class="text-xs font-semibold text-purple-200">Time Remaining</div>
                            <div class="text-2xl font-mono font-black text-amber-300" x-text="formattedTime"></div>
                        </div>
                    </div>

                    <!-- Live OTP Box (When received) -->
                    <div x-show="otpCode !== ''" class="pt-4 border-t border-white/10">
                        <div class="bg-purple-600 rounded-xl p-6 text-center space-y-2 shadow-lg shadow-purple-900/50">
                            <div class="text-xs font-semibold text-purple-200 uppercase tracking-widest">Received Verification Code</div>
                            <div class="text-4xl sm:text-5xl font-mono font-black tracking-widest text-white select-all" x-text="otpCode"></div>
                            <button type="button" @click="copyOtp(otpCode)" class="mt-2 inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-white text-purple-700 font-bold text-xs hover:bg-purple-50 transition-all shadow-sm">
                                <span x-text="copiedCode ? 'Code Copied!' : 'Copy Code'"></span>
                            </button>
                            <div x-show="fullSms !== ''" class="mt-3 text-xs text-purple-100 bg-purple-700/60 p-3 rounded-lg font-mono text-left" x-text="fullSms"></div>
                        </div>
                    </div>

                    <!-- Waiting Animation Box (When pending) -->
                    <div x-show="status === 'pending' && otpCode === ''" class="p-6 rounded-xl bg-white/5 border border-white/10 text-center space-y-3">
                        <div class="inline-flex items-center gap-2 text-sm text-purple-200">
                            <span class="w-3 h-3 rounded-full bg-amber-400 animate-ping"></span>
                            <span>Listening for SMS verification code...</span>
                        </div>
                        <p class="text-xs text-slate-300 max-w-md mx-auto">
                            Enter this phone number into <strong class="text-white"><?= e($order['service_name']) ?></strong>. The verification SMS will appear here automatically within seconds.
                        </p>
                    </div>
                </div>

                <!-- Order Metadata Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Service Cost</div>
                        <div class="font-bold text-slate-900 mt-0.5"><?= format_currency($order['cost']) ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Country</div>
                        <div class="font-bold text-slate-900 mt-0.5"><?= e($order['country_code']) ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Created At</div>
                        <div class="font-bold text-slate-900 mt-0.5"><?= date('H:i:s, d M', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Provider Gateway</div>
                        <div class="font-bold text-slate-900 mt-0.5"><?= e($order['provider_name']) ?></div>
                    </div>
                </div>

                <!-- Action Buttons: Cancel / Simulate -->
                <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100">
                    <div>
                        <a href="/user/orders.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                            &larr; Back to Orders
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <?php if ($order['status'] === 'pending'): ?>
                            <!-- Simulate Arrival Button (Instant interactive testing) -->
                            <form method="POST" action="/user/order-details.php?id=<?= $order['id'] ?>" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="simulate_otp">
                                <button type="submit" class="px-4 py-2.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-bold transition-all border border-purple-200 cursor-pointer">
                                    Simulate OTP Arrival
                                </button>
                            </form>

                            <!-- Cancel & Refund -->
                            <form method="POST" action="/user/order-details.php?id=<?= $order['id'] ?>" onsubmit="return confirm('Cancel this order now? 100% of the cost will be immediately refunded to your wallet.')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel_order">
                                <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold transition-all border border-rose-200 cursor-pointer">
                                    Cancel & 100% Refund
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
