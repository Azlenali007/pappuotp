<?php
/**
 * Admin Services Management
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

// Handle toggle status POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'], $_POST['status'])) {
    require_csrf_token();
    $serviceId = (int)$_POST['service_id'];
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $stmt = $db->prepare("UPDATE services SET status = ? WHERE id = ?");
    $stmt->execute([$status, $serviceId]);

    log_admin_audit('service_status_toggled', "Admin set service ID {$serviceId} to {$status}");
    set_flash('success', "Service status updated to {$status}.");
    header("Location: /admin/services.php");
    exit;
}

$stmt = $db->query("
    SELECT s.*, c.name as category_name,
           (SELECT COUNT(*) FROM orders WHERE service_id = s.id) as total_orders
    FROM services s 
    LEFT JOIN service_categories c ON c.id = s.category_id 
    ORDER BY s.id ASC
");
$services = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Services Directory</h1>
                    <p class="text-sm text-slate-500">Configure prices, provider mappings, and active availability</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin/service-importer.php" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50">
                        Import from Provider
                    </a>
                    <a href="/admin/service-add.php" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                        + Add Service
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Service</th>
                                <th class="px-6 py-4">Code</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Selling Price</th>
                                <th class="px-6 py-4">Provider Cost</th>
                                <th class="px-6 py-4">Profit / Order</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($services as $srv): 
                                $profit = (float)$srv['price'] - (float)$srv['provider_cost'];
                            ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                                <?= get_service_svg_icon($srv['name'], "w-5 h-5") ?>
                                            </div>
                                            <span class="font-bold text-slate-900"><?= e($srv['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-600"><?= e($srv['code']) ?></td>
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-600"><?= e($srv['category_name'] ?? 'General') ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-900 text-xs"><?= format_currency($srv['price']) ?></td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500"><?= format_currency($srv['provider_cost']) ?></td>
                                    <td class="px-6 py-4 font-bold text-xs text-emerald-600">+<?= format_currency($profit) ?></td>
                                    <td class="px-6 py-4">
                                        <form method="POST" action="/admin/services.php" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="service_id" value="<?= $srv['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $srv['status'] === 'active' ? 'inactive' : 'active' ?>">
                                            <button type="submit" class="cursor-pointer">
                                                <?= render_status_badge($srv['status']) ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a href="/admin/service-edit.php?id=<?= $srv['id'] ?>" class="text-xs font-semibold text-purple-600 hover:text-purple-800">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
