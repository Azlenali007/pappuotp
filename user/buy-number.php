<?php
/**
 * Create New Order / Buy Number Page
 * Matches Reference Image "Create New Order"
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../services/order-manager.php';

$db = get_db();
$serviceId = (int)($_GET['service_id'] ?? 2); // Default WhatsApp OTP
$selectedCountry = trim($_GET['country'] ?? 'IN');

// Fetch Service
$stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    // Fallback to first available active service
    $service = $db->query("SELECT * FROM services WHERE status = 'active' ORDER BY id ASC LIMIT 1")->fetch();
    $serviceId = $service['id'];
}

// Fetch all available countries
$countries = $db->query("SELECT * FROM countries WHERE is_active = 1 ORDER BY (code = 'IN') DESC, name ASC")->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{
        serviceId: <?= $service['id'] ?>,
        serviceName: '<?= addslashes(e($service['name'])) ?>',
        unitPrice: <?= (float)$service['price'] ?>,
        country: '<?= e($selectedCountry) ?>',
        quantity: 1,
        get totalAmount() {
            return (this.unitPrice * this.quantity).toFixed(2);
        }
    }">
        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Page Title -->
            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create New Order</h1>
                <p class="text-sm text-slate-500">Instant virtual number allocation from certified carriers.</p>
            </div>

            <!-- Two-Column Order Creation Layout (matching screenshot) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Form Details -->
                <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                    
                    <!-- Selected Service Header (matching screenshot) -->
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-purple-50/70 border border-purple-100">
                        <div class="w-12 h-12 rounded-xl bg-white shadow-xs flex items-center justify-center shrink-0">
                            <?= get_service_svg_icon($service['name'], "w-7 h-7") ?>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900"><?= e($service['name']) ?></h2>
                            <div class="text-xs text-purple-700 font-semibold mt-0.5">
                                Price: <?= format_currency($service['price']) ?>
                            </div>
                        </div>
                    </div>

                    <form id="orderForm" action="/user/orders.php" method="POST" class="space-y-5">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_order">
                        <input type="hidden" name="service_id" :value="serviceId">

                        <!-- Country Selection (matching screenshot) -->
                        <div>
                            <label for="country_code" class="block text-xs font-semibold text-slate-700 mb-2 uppercase tracking-wider">Country</label>
                            <select id="country_code" name="country_code" x-model="country" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?= e($c['code']) ?>" <?= $selectedCountry === $c['code'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?> (<?= e($c['dial_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Phone Number Note / Mode -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2 uppercase tracking-wider">Phone Number Assignment</label>
                            <div class="flex items-center gap-2">
                                <div class="px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 font-mono">
                                    <span x-text="country === 'IN' ? '+91' : (country === 'US' || country === 'CA' ? '+1' : '+')"></span>
                                </div>
                                <div class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-400 font-mono">
                                    Allocated instantly via Provider API upon confirmation
                                </div>
                            </div>
                        </div>

                        <!-- Quantity (matching screenshot) -->
                        <div>
                            <label for="quantity" class="block text-xs font-semibold text-slate-700 mb-2 uppercase tracking-wider">Quantity</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="5" x-model="quantity" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                        </div>

                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-500 space-y-1">
                            <div class="font-semibold text-slate-700">Fair Use & Refund Guarantee:</div>
                            <p>If the verification code is not received within the 20-minute window, you may cancel at any time for an immediate 100% wallet refund.</p>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Order Summary Card (matching screenshot) -->
                <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-4">Order Summary</h2>

                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Service</span>
                            <span class="font-bold text-slate-900" x-text="serviceName"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Country</span>
                            <span class="font-semibold text-slate-800 uppercase" x-text="country"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Quantity</span>
                            <span class="font-semibold text-slate-800" x-text="quantity"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Price</span>
                            <span class="font-semibold text-purple-600" x-text="'₹ ' + unitPrice.toFixed(2)"></span>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                            <span class="font-extrabold text-base text-slate-900">Total Amount</span>
                            <span class="font-extrabold text-xl text-slate-900" x-text="'₹ ' + totalAmount"></span>
                        </div>
                    </div>

                    <!-- Balance check -->
                    <?php if ($currentUser['balance'] < $service['price']): ?>
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 space-y-2">
                            <div class="font-bold">Insufficient Wallet Balance</div>
                            <p>Your balance is <?= format_currency($currentUser['balance']) ?>. Please add funds to proceed.</p>
                            <a href="/user/add-funds.php" class="inline-block px-3 py-1.5 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700 text-xs">
                                Top Up Wallet
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Create Order Button (matching purple button in screenshot) -->
                    <button type="button" onclick="document.getElementById('orderForm').submit()" class="w-full py-3.5 px-4 rounded-xl font-bold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all active:scale-95 text-sm cursor-pointer">
                        Create Order
                    </button>

                    <div class="text-center text-xs text-slate-400">
                        Current Balance: <span class="font-bold text-slate-700"><?= format_currency($currentUser['balance']) ?></span>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
