<?php
/**
 * Admin Service Categories Management
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $slug = sanitize_string($_POST['slug'] ?? strtolower(str_replace(' ', '-', $name)));

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO service_categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        log_admin_audit('category_created', "Admin created category {$name}");
        set_flash('success', "Category {$name} created.");
    }
    header("Location: /admin/categories.php");
    exit;
}

$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM services WHERE category_id = c.id) as services_count FROM service_categories c ORDER BY c.name ASC")->fetchAll();
$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{ newModal: false }">
        <div class="max-w-5xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Service Categories</h1>
                    <p class="text-sm text-slate-500">Group services into catalog classifications</p>
                </div>
                <button type="button" @click="newModal = true" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                    + Add Category
                </button>
            </div>

            <!-- Add Modal -->
            <div x-show="newModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" style="display: none;">
                <div @click.away="newModal = false" class="bg-white rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
                    <h3 class="text-base font-bold text-slate-900">New Category</h3>
                    <form action="/admin/categories.php" method="POST" class="space-y-4">
                        <?= csrf_field() ?>
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Name</label>
                            <input type="text" id="name" name="name" required placeholder="e.g. Crypto & Wallets" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="newModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs">Create</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Category Name</th>
                            <th class="px-6 py-4">Slug</th>
                            <th class="px-6 py-4">Associated Services</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900"><?= e($cat['name']) ?></td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500"><?= e($cat['slug']) ?></td>
                                <td class="px-6 py-4 text-xs font-semibold text-purple-600"><?= $cat['services_count'] ?> services</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
