<?php
/**
 * Admin Edit Service Details & Selling Price
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$serviceId = (int)($_GET['id'] ?? 0);

$categories = $db->query("SELECT * FROM service_categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $price = sanitize_float($_POST['price'] ?? 1.00);
    $providerCost = sanitize_float($_POST['provider_cost'] ?? 0.50);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $stockStatus = in_array($_POST['stock_status'] ?? '', ['available', 'low_stock', 'out_of_stock']) ? $_POST['stock_status'] : 'available';

    $stmt = $db->prepare("UPDATE services SET name = ?, category_id = ?, price = ?, provider_cost = ?, status = ?, stock_status = ? WHERE id = ?");
    $stmt->execute([$name, $categoryId, $price, $providerCost, $status, $stockStatus, $serviceId]);

    log_admin_audit('service_updated', "Admin updated service ID {$serviceId} price to ₹{$price}");
    set_flash('success', "Service {$name} updated successfully.");
    header("Location: /admin/services.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM services WHERE id = ? LIMIT 1");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    header("Location: /admin/services.php");
    exit;
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-2xl mx-auto space-y-6">

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/services.php" class="hover:text-purple-600">Services</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($service['name']) ?></span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
                        <?= get_service_svg_icon($service['name'], "w-7 h-7") ?>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900">Edit Service: <?= e($service['name']) ?></h1>
                        <span class="text-xs text-slate-400 font-mono">Provider Code: <?= e($service['code']) ?></span>
                    </div>
                </div>

                <form action="/admin/service-edit.php?id=<?= $service['id'] ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Display Name</label>
                        <input type="text" id="name" name="name" required value="<?= e($service['name']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                    </div>

                    <div>
                        <label for="category_id" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Category</label>
                        <select id="category_id" name="category_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $service['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="provider_cost" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Provider Cost (₹)</label>
                            <input type="number" step="0.01" id="provider_cost" name="provider_cost" required value="<?= $service['provider_cost'] ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none">
                        </div>
                        <div>
                            <label for="price" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Selling Price (₹)</label>
                            <input type="number" step="0.01" id="price" name="price" required value="<?= $service['price'] ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-purple-600 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Status</label>
                            <select id="status" name="status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                                <option value="active" <?= $service['status'] === 'active' ? 'selected' : '' ?>>Active (Visible to users)</option>
                                <option value="inactive" <?= $service['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                            </select>
                        </div>
                        <div>
                            <label for="stock_status" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Stock Status</label>
                            <select id="stock_status" name="stock_status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                                <option value="available" <?= $service['stock_status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="low_stock" <?= $service['stock_status'] === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                <option value="out_of_stock" <?= $service['stock_status'] === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="/admin/services.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">Update Service</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
