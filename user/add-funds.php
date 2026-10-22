<?php
/**
 * Add Funds to Wallet (Razorpay Integration)
 * Matches Reference Image Bottom-Left Exactly
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../services/payment-manager.php';
require_once __DIR__ . '/../services/wallet-manager.php';

$userId = (int)$currentUser['id'];
$pageTitle = 'Add Funds - OTP Services';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$flash = get_flash();
$razorpayKeyId = get_setting('razorpay_key_id', 'rzp_test_kEYSAMPLE12345');
$userEmail = $currentUser['email'] ?? '';
$userName = $currentUser['name'] ?? 'User';
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{
        amount: 500,
        presetAmounts: [100, 200, 500, 1000, 2000],
        loading: false,
        setAmount(val) {
            this.amount = val;
        },
        async startCheckout() {
            if (this.amount < 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Minimum deposit amount is ₹10.00',
                    confirmButtonColor: '#7C3AED'
                });
                return;
            }

            this.loading = true;
            try {
                // 1. Create payment order on server
                const form = new FormData();
                form.append('amount', this.amount);
                form.append('method', 'razorpay');

                const res = await fetch('/payments/create-payment.php', {
                    method: 'POST',
                    body: form
                });
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to initialize payment');
                }

                const internalPaymentId = data.internal_payment_id || data.payment_id;
                const gatewayOrderId = data.gateway_order_id;
                const keyId = data.key_id;

                // 2. Open Razorpay Checkout modal
                const options = {
                    key: keyId,
                    amount: data.amount_in_paise,
                    currency: 'INR',
                    name: 'OTP Service Platform',
                    description: 'Wallet Top-up of ₹' + this.amount,
                    order_id: gatewayOrderId,
                    prefill: {
                        name: '<?= addslashes(e($userName)) ?>',
                        email: '<?= addslashes(e($userEmail)) ?>'
                    },
                    theme: {
                        color: '#7C3AED'
                    },
                    handler: async (response) => {
                        // 3. Verify on server
                        const verifyForm = new FormData();
                        verifyForm.append('internal_payment_id', internalPaymentId);
                        verifyForm.append('razorpay_payment_id', response.razorpay_payment_id);
                        verifyForm.append('razorpay_order_id', response.razorpay_order_id || gatewayOrderId);
                        verifyForm.append('razorpay_signature', response.razorpay_signature || '');

                        try {
                            const vRes = await fetch('/payments/verify-payment.php', {
                                method: 'POST',
                                body: verifyForm
                            });
                            const vData = await vRes.json();
                            if (vData.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Payment Successful!',
                                    text: '₹' + this.amount.toFixed(2) + ' has been added to your wallet.',
                                    confirmButtonColor: '#7C3AED'
                                }).then(() => {
                                    window.location.href = '/user/wallet.php';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Verification Failed',
                                    text: vData.error || 'Could not verify payment on server.',
                                    confirmButtonColor: '#7C3AED'
                                });
                            }
                        } catch (err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Network Error',
                                text: 'Failed to communicate with verification endpoint.',
                                confirmButtonColor: '#7C3AED'
                            });
                        }
                    },
                    modal: {
                        ondismiss: () => {
                            this.loading = false;
                        }
                    }
                };

                if (typeof Razorpay !== 'undefined') {
                    const rzp = new Razorpay(options);
                    rzp.on('payment.failed', function (response) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Failed',
                            text: response.error.description || 'Payment was declined by bank or gateway.',
                            confirmButtonColor: '#7C3AED'
                        });
                    });
                    rzp.open();
                } else {
                    // Fallback to direct verification if checkout SDK is blocked or offline
                    Swal.fire({
                        icon: 'info',
                        title: 'Checkout Gateway',
                        text: 'Razorpay client SDK is initializing or blocked by ad-blocker. Order #' + data.payment_ref + ' created.',
                        confirmButtonColor: '#7C3AED'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Error',
                    text: err.message || 'Could not initiate checkout.',
                    confirmButtonColor: '#7C3AED'
                });
            } finally {
                this.loading = false;
            }
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
                                    class="py-3 px-2 rounded-xl text-xs font-bold border transition-all text-center cursor-pointer">
                                    ₹ <span x-text="val"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label for="custom_amount" class="block text-xs font-semibold text-slate-700 mb-2 uppercase tracking-wider">Custom Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold text-sm">₹</span>
                            <input type="number" id="custom_amount" min="10" max="50000" x-model.number="amount" placeholder="Enter amount" class="w-full pl-8 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                        </div>
                        <div class="text-xs text-slate-400 mt-2">Minimum amount ₹ 10 • Maximum per deposit ₹ 50,000</div>
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
                                <div class="text-xs text-slate-500">UPI, Cards, NetBanking, Wallets</div>
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

                    <div>
                        <!-- Proceed to Pay (purple button matching screenshot) -->
                        <button type="button" @click="startCheckout()" :disabled="loading" class="w-full py-3.5 px-4 rounded-xl font-bold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95 text-sm cursor-pointer disabled:opacity-50">
                            <span x-show="!loading">Proceed to Pay</span>
                            <span x-show="loading">Connecting Gateway...</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-center gap-2 text-xs text-slate-400 pt-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Your payment is secured with 256-bit encryption</span>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<!-- Razorpay Checkout Library -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
