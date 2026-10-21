<?php
/**
 * User Services Catalogue Page
 * Matches Reference Image "User Services Page"
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

// Filter parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$countryCode = trim($_GET['country'] ?? '');

// Fetch Categories & Countries for dropdowns
$categories = $db->query("SELECT * FROM service_categories ORDER BY name ASC")->fetchAll();
$countries = $db->query("SELECT * FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Build query for active services
$sql = "SELECT s.*, c.name as category_name FROM services s LEFT JOIN service_categories c ON c.id = s.category_id WHERE s.status = 'active'";
$params = [];

if ($search !== '') {
    $sql .= " AND (s.name LIKE ? OR s.code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categorySlug !== '') {
    $sql .= " AND c.slug = ?";
    $params[] = $categorySlug;
}

$sql .= " ORDER BY s.id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Title Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">OTP Services</h1>
                <p class="text-sm text-slate-500">Select a service and get OTP instantly</p>
            </div>

            <!-- Filter Controls Bar (matching screenshot) -->
            <form method="GET" action="/user/services.php" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                
                <!-- Category Select -->
                <div class="sm:col-span-3">
                    <select name="category" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Country Select -->
                <div class="sm:col-span-3">
                    <select name="country" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['code']) ?>" <?= $countryCode === $c['code'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?> (<?= e($c['dial_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="sm:col-span-6 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search services..." class="w-full pl-9 pr-20 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all">
                    <?php if ($search || $categorySlug || $countryCode): ?>
                        <a href="/user/services.php" class="absolute right-2 top-2 px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-200/60 rounded-lg">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Services Grid (matching screenshot) -->
            <?php if (empty($services)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-slate-100 shadow-xs space-y-3">
                    <img src="/assets/images/empty-state.svg" class="w-24 h-24 mx-auto opacity-75" alt="Empty">
                    <h3 class="font-bold text-slate-900 text-base">No services found</h3>
                    <p class="text-sm text-slate-500">Try adjusting your filters or search keywords.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs hover:shadow-md hover:border-purple-200 transition-all flex flex-col justify-between group">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center p-2.5 group-hover:scale-105 transition-transform">
                                        <?= get_service_svg_icon($service['name'], "w-7 h-7") ?>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Available
                                    </span>
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-purple-600 transition-colors">
                                        <?= e($service['name']) ?>
                                    </h3>
                                    <div class="text-sm text-slate-500 mt-1">
                                        From <span class="font-bold text-slate-900"><?= format_currency($service['price']) ?></span>
                                    </div>
                                </div>

                                <?php if ($service['description']): ?>
                                    <p class="text-xs text-slate-500 line-clamp-2">
                                        <?= e($service['description']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-50">
                                <a href="/user/buy-number.php?service_id=<?= $service['id'] ?><?= $countryCode ? '&country=' . urlencode($countryCode) : '' ?>" class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-600 hover:text-white transition-all shadow-xs">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
