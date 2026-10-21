<?php
/**
 * Admin Global Pricing & Markup Rules
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $markup = sanitize_float($_POST['markup'] ?? 35);
    $applyToAll = isset($_POST['apply_to_all']);

    // Update setting
    $stmt = $db->prepare("REPLACE INTO application_settings (key_name, value) VALUES ('default_profit_markup_percent', ?)");
    $stmt->execute([$markup]);

    if ($applyToAll) {
        // Recalculate selling prices for all services based on their provider cost
        $stmt = $db->prepare("UPDATE services SET price = ROUND(provider_cost * (1 + (? / 100)), 2)");
        $stmt->execute([$markup]);
        log_admin_audit('pricing_recalculated', "Admin applied {$markup}% markup across all services.");
        set_flash('success', "Updated global markup to {$markup}% and updated all service selling prices.");
    } else {
        log_admin_audit('markup_updated', "Admin changed default profit markup to {$markup}%");
        set_flash('success', "Updated default profit markup to {$markup}%.");
    }

    header("Location: /admin/pricing.php");
    exit;
}

$currentMarkup = get_setting('default_profit_markup_percent', '35');
$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-3xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Pricing & Profit Margins</h1>
                <p class="text-sm text-slate-500">Configure automated profit markup applied during provider synchronization</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <form action="/admin/pricing.php" method="POST" class="space-y-6">
                    <?= csrf_field() ?>

                    <div>
                        <label for="markup" class="block text-xs font-bold text-slate-700 mb-2 uppercase">Default Profit Markup (%)</label>
                        <div class="relative max-w-xs">
                            <input type="number" step="0.5" id="markup" name="markup" required value="<?= e($currentMarkup) ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-slate-900 pr-10 focus:outline-none">
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 font-bold">%</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Example: A service costing ₹0.50 from the provider will have a suggested selling price of ₹0.68 at 35% markup.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-purple-50/60 border border-purple-100 text-xs text-purple-900 space-y-2">
                        <label class="flex items-start gap-2.5 cursor-pointer font-bold">
                            <input type="checkbox" name="apply_to_all" class="mt-0.5 w-4 h-4 rounded text-purple-600 border-slate-300">
                            <span>Recalculate all existing service selling prices immediately</span>
                        </label>
                        <p class="text-purple-700/80 pl-6 text-[11px]">If left unchecked, existing custom selling prices will remain preserved, and the new markup will only apply to newly imported services.</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-6 py-3 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                            Save Pricing Rules
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
