<?php
/**
 * Admin Add New Service
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$categories = $db->query("SELECT * FROM service_categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $code = sanitize_string($_POST['code'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $price = sanitize_float($_POST['price'] ?? 1.00);
    $providerCost = sanitize_float($_POST['provider_cost'] ?? 0.50);
    $description = sanitize_string($_POST['description'] ?? '');

    if (empty($name) || empty($code)) {
        set_flash('error', 'Name and code are required.');
    } else {
        $stmt = $db->prepare("INSERT INTO services (name, code, category_id, price, provider_cost, description, status, stock_status) VALUES (?, ?, ?, ?, ?, ?, 'active', 'available')");
        $stmt->execute([$name, $code, $categoryId, $price, $providerCost, $description]);

        log_admin_audit('service_created', "Admin created new service: {$name} ({$code})");
        set_flash('success', "Service {$name} added successfully.");
        header("Location: /admin/services.php");
        exit;
    }
}

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-2xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/services.php" class="hover:text-purple-600">Services</a>
                <span>/</span>
                <span class="text-slate-900">Add New</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <h1 class="text-xl font-extrabold text-slate-900">Add Verification Service</h1>

                <form action="/admin/service-add.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Service Display Name</label>
                            <input type="text" id="name" name="name" required placeholder="e.g. OpenAI OTP" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                        </div>
                        <div>
                            <label for="code" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Provider Code</label>
                            <input type="text" id="code" name="code" required placeholder="e.g. openai" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="category_id" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Category</label>
                        <select id="category_id" name="category_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="provider_cost" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Provider Cost (₹)</label>
                            <input type="number" step="0.01" id="provider_cost" name="provider_cost" required value="0.45" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none">
                        </div>
                        <div>
                            <label for="price" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Selling Price (₹)</label>
                            <input type="number" step="0.01" id="price" name="price" required value="0.80" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-purple-600 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Description</label>
                        <textarea id="description" name="description" rows="2" placeholder="Brief service description..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="/admin/services.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">Create Service</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
