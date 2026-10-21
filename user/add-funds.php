<?php
/**
 * Add Funds to Wallet (Razorpay Integration)
 * Matches Reference Image Bottom-Left Exactly
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/payment-manager.php';

$db = get_db();
$userId = (int)$currentUser['id'];

// Handle payment completion POST (simulated or gateway return)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $amount = max(MIN_DEPOSIT_AMOUNT, (float)($_POST['amount'] ?? 500));
    $orderData = PaymentManager::createDepositOrder($userId, $amount);

    if ($orderData['success']) {
        // Complete payment credit with payment ID
        $paymentId = 'pay_' . strtoupper(bin2hex(random_bytes(6)));
        $credited = PaymentManager::verifyAndCreditDeposit($userId, $orderData['deposit_ref'], $paymentId);

        if ($credited) {
            set_flash('success', "₹ " . number_format($amount, 2) . " has been credited to your wallet successfully!");
            header("Location: /user/wallet.php");
            exit;
        }
    }

    set_flash('error', 'Payment transaction could not be processed.');
    header("Location: /user/add-funds.php");
    exit;
}

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{
        amount: 500,
        presetAmounts: [100, 200, 500, 1000, 2000],
        setAmount(val) {
            this.amount = val;
        }
    }">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Title Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Add Funds to Wallet</h1>
                <p class="text-sm text-slate-500">Secure instant deposit via Razorpay UPI, Cards, NetBanking, and Wallets</p>
            </div>

            <!-- Add Funds Grid (matching screenshot) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                
                <!-- Left: Amount Selection (matching screenshot) -->
                <div class="md:col-span-7 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-3 uppercase tracking-wider">Select Amount</label>
                        <!-- 5 Preset Buttons (₹ 100, ₹ 200, ₹ 500, ₹ 1,000, ₹ 2,000) -->
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2.5">
                            <template x-for="val in presetAmounts" :key="val">
                                <button type="button" 
                                    @click="setAmount(val)"
                                    :class="amount === val ? 'bg-purple-600 text-white shadow-md shadow-purple-200 border-purple-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="py-3 px-2 rounded-xl text-xs font-bold border transition-all text-center">
                                    ₹ <span x-text="val"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label for="custom_amount" class="block text-xs font-semibold text-slate-700 mb-2 uppercase tracking-wider">Custom Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold text-sm">₹</span>
                            <input type="number" id="custom_amount" min="100" max="50000" x-model.number="amount" placeholder="Enter amount" class="w-full pl-8 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                        </div>
                        <div class="text-xs text-slate-400 mt-2">Minimum amount ₹ 100 • Maximum per deposit ₹ 50,000</div>
                    </div>
                </div>

                <!-- Right: Payment Method & Submit (matching screenshot) -->
                <div class="md:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                    <h2 class="text-base font-bold text-slate-900">Payment Method</h2>

                    <!-- Razorpay Selection Card (matching screenshot) -->
                    <div class="p-4 rounded-xl border-2 border-purple-600 bg-purple-50/40 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center text-white font-black text-xs">
                                R
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 text-sm">Razorpay</div>
                                <div class="text-xs text-slate-500">Pay securely with Razorpay</div>
                            </div>
                        </div>
                        <div class="w-5 h-5 rounded-full bg-purple-600 flex items-center justify-center text-white">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>

                    <!-- Deposit Summary -->
                    <div class="pt-2 space-y-3 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Top-up Amount</span>
                            <span class="font-bold text-slate-900" x-text="'₹ ' + (amount ? amount.toFixed(2) : '0.00')"></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Gateway Fee</span>
                            <span class="font-semibold text-emerald-600">Free (0%)</span>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                            <span class="font-bold text-slate-900">Total Payable</span>
                            <span class="font-extrabold text-xl text-slate-900" x-text="'₹ ' + (amount ? amount.toFixed(2) : '0.00')"></span>
                        </div>
                    </div>

                    <form method="POST" action="/user/add-funds.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="amount" :value="amount">

                        <!-- Proceed to Pay (purple button matching screenshot) -->
                        <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-bold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95 text-sm cursor-pointer">
                            Proceed to Pay
                        </button>
                    </form>

                    <div class="flex items-center justify-center gap-2 text-xs text-slate-400 pt-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Your payment is secured by Razorpay</span>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
