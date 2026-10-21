<?php
/**
 * Public Landing Page
 * Matches Reference Image Exactly
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db();

// Fetch popular active services
$services = [];
try {
    $stmt = $db->query("SELECT * FROM services WHERE status = 'active' ORDER BY id ASC LIMIT 8");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    // Database empty fallback
}
?>

<main class="flex-grow">
    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28 bg-gradient-to-b from-purple-50/50 via-white to-slate-50">
        <!-- Background Ambient Glow -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-purple-200/40 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute bottom-10 left-10 w-72 h-72 bg-indigo-100/50 rounded-full blur-2xl pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left Column (Copy & Actions) -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="space-y-4">
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span>
                            Live Virtual Numbers Available 24/7
                        </span>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                            Fast & Reliable<br>
                            <span class="text-purple-600">OTP Services</span>
                        </h1>

                        <p class="text-lg text-slate-600 max-w-xl leading-relaxed">
                            Get OTPs for all major platforms instantly. Secure, Fast and Affordable virtual verification numbers with guaranteed automated refunds if no SMS arrives.
                        </p>
                    </div>

                    <!-- Feature Checkmark Pills (matching screenshot) -->
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-y-3 gap-x-6 text-sm font-medium text-slate-700">
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>Instant Delivery</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>100% Secure</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>Best Prices</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>24/7 Support</span>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="/register.php" class="px-8 py-3.5 rounded-xl text-base font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-lg shadow-purple-200 transition-all active:scale-95">
                            Get Started
                        </a>
                        <a href="/user/services.php" class="px-8 py-3.5 rounded-xl text-base font-semibold bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-sm transition-all active:scale-95">
                            View Services
                        </a>
                    </div>
                </div>

                <!-- Right Column (Hero Graphic matching reference image phone with 482931) -->
                <div class="lg:col-span-5 relative flex items-center justify-center">
                    <div class="relative w-full max-w-sm sm:max-w-md mx-auto">
                        <!-- Floating Service Icons -->
                        <div class="absolute -top-4 -left-4 w-12 h-12 rounded-2xl bg-white shadow-xl shadow-slate-200 flex items-center justify-center p-2.5 animate-bounce" style="animation-duration: 3s;">
                            <?= get_service_svg_icon('google') ?>
                        </div>
                        <div class="absolute top-1/4 -right-4 w-12 h-12 rounded-2xl bg-white shadow-xl shadow-slate-200 flex items-center justify-center p-2.5 animate-bounce" style="animation-duration: 3.5s; animation-delay: 0.5s;">
                            <?= get_service_svg_icon('whatsapp') ?>
                        </div>
                        <div class="absolute -bottom-4 -left-2 w-12 h-12 rounded-2xl bg-white shadow-xl shadow-slate-200 flex items-center justify-center p-2.5 animate-bounce" style="animation-duration: 4s; animation-delay: 1s;">
                            <?= get_service_svg_icon('facebook') ?>
                        </div>
                        <div class="absolute bottom-1/4 -right-2 w-12 h-12 rounded-2xl bg-white shadow-xl shadow-slate-200 flex items-center justify-center p-2.5 animate-bounce" style="animation-duration: 3.2s; animation-delay: 1.5s;">
                            <?= get_service_svg_icon('instagram') ?>
                        </div>

                        <!-- 3D Smartphone Mockup Card -->
                        <div class="relative bg-slate-900 p-4 rounded-[40px] shadow-2xl shadow-purple-900/30 border-4 border-slate-800">
                            <!-- Speaker / Camera notch -->
                            <div class="w-24 h-4 bg-slate-800 rounded-full mx-auto mb-4"></div>
                            
                            <!-- Phone Screen -->
                            <div class="bg-gradient-to-b from-purple-50 to-white rounded-[28px] p-6 text-center space-y-6">
                                <div class="w-16 h-16 bg-purple-100 rounded-2xl mx-auto flex items-center justify-center shadow-inner">
                                    <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center text-white">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <div class="text-xs uppercase tracking-wider font-bold text-purple-600">SMS Verification Code</div>
                                    <h3 class="text-lg font-bold text-slate-800">WhatsApp OTP Received</h3>
                                    <div class="text-xs text-slate-500">Virtual Number: +91 9876543210</div>
                                </div>

                                <!-- OTP Box (matching "Your OTP is 482931") -->
                                <div class="bg-purple-600 text-white py-4 px-6 rounded-2xl shadow-lg shadow-purple-300 space-y-1">
                                    <div class="text-xs font-medium text-purple-200">Your OTP is</div>
                                    <div class="text-3xl font-mono font-black tracking-widest">482931</div>
                                </div>

                                <div class="flex items-center justify-center gap-2 text-xs font-semibold text-emerald-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Verified & Authorized Delivery
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Popular Services (matching screenshot grid with prices) -->
    <section class="py-16 bg-white border-t border-slate-100" id="services">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-2 mb-10">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Popular Services</h2>
                <p class="text-slate-600 text-sm">Choose from our wide range of OTP services</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($services as $service): ?>
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md hover:border-purple-200 transition-all group flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <?= get_service_svg_icon($service['name'], "w-7 h-7") ?>
                            </div>

                            <div>
                                <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-purple-600 transition-colors">
                                    <?= e($service['name']) ?>
                                </h3>
                                <div class="text-sm text-slate-500 mt-1">
                                    From <span class="font-semibold text-slate-900"><?= format_currency($service['price']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-50">
                            <a href="/user/buy-number.php?service_id=<?= $service['id'] ?>" class="w-full inline-flex items-center justify-center py-2 px-4 rounded-xl text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-600 hover:text-white transition-all">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-10 text-center">
                <a href="/user/services.php" class="inline-flex items-center gap-2 text-sm font-semibold text-purple-600 hover:text-purple-800">
                    Explore all available countries and platforms
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-slate-50/70 border-t border-slate-100" id="how-it-works">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14 space-y-3">
                <h2 class="text-3xl font-extrabold text-slate-900">How It Works</h2>
                <p class="text-slate-600 text-sm">Three easy steps to receive your SMS verification code safely.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-xs relative">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white font-extrabold flex items-center justify-center mb-6">1</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Select Service & Country</h3>
                    <p class="text-sm text-slate-600">Choose WhatsApp, Google, Telegram, or any platform and choose your preferred country code.</p>
                </div>

                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-xs relative">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white font-extrabold flex items-center justify-center mb-6">2</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Copy Allocated Number</h3>
                    <p class="text-sm text-slate-600">A clean virtual phone number is instantly generated and assigned to your order with a 20-minute countdown.</p>
                </div>

                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-xs relative">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white font-extrabold flex items-center justify-center mb-6">3</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Receive OTP Code</h3>
                    <p class="text-sm text-slate-600">The verification SMS appears in real time. If no code arrives, 100% of your funds are automatically refunded to your wallet.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Summary Section -->
    <section class="py-16 bg-white border-t border-slate-100" id="pricing">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-r from-purple-900 to-indigo-950 rounded-3xl p-8 sm:p-12 text-white relative overflow-hidden">
                <div class="max-w-2xl space-y-4 relative z-10">
                    <h2 class="text-3xl font-extrabold tracking-tight">Transparent, Pay-As-You-Go Pricing</h2>
                    <p class="text-purple-200 text-sm leading-relaxed">
                        No monthly subscriptions. Simply top up your wallet with Razorpay or bank transfer and pay only for numbers that receive verification codes.
                    </p>
                    <div class="pt-4 flex flex-wrap gap-4">
                        <a href="/register.php" class="px-6 py-3 rounded-xl bg-purple-500 hover:bg-purple-600 font-semibold text-white shadow-md transition-all">
                            Create Free Account
                        </a>
                        <a href="/login.php" class="px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 font-semibold text-white transition-all">
                            Sign In to Wallet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
