<?php
/**
 * Admin User Management Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/pagination.php';

$db = get_db();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Status update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    require_csrf_token();
    $targetId = (int)$_POST['user_id'];
    $newStatus = in_array($_POST['status'], ['active', 'suspended', 'banned']) ? $_POST['status'] : 'active';
    
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $targetId]);

    log_admin_audit('user_status_updated', "Admin changed user ID {$targetId} status to {$newStatus}");
    set_flash('success', "User status updated to {$newStatus}.");
    header("Location: /admin/users.php");
    exit;
}

$where = ["1=1"];
$params = [];

if ($status !== '') {
    $where[] = "u.status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$paginator = paginate($total, $page, 15);

$stmt = $db->prepare("
    SELECT u.*, COALESCE(w.balance, 0.00) as balance, 
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as orders_count 
    FROM users u 
    LEFT JOIN wallets w ON w.user_id = u.id 
    WHERE {$whereSql} 
    ORDER BY u.id DESC 
    LIMIT {$paginator['per_page']} OFFSET {$paginator['offset']}
");
$stmt->execute($params);
$users = $stmt->fetchAll();

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

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Users Management</h1>
                <p class="text-sm text-slate-500">Monitor registered accounts, balances, and access privileges</p>
            </div>

            <!-- Search and Filter Bar -->
            <form method="GET" action="/admin/users.php" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="flex items-center gap-2">
                    <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                </div>

                <div class="relative w-full sm:w-72">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name or email..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                </div>
            </form>

            <!-- Users Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Wallet Balance</th>
                                <th class="px-6 py-4">Orders</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Joined</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900"><?= e($u['name']) ?></div>
                                        <div class="text-xs text-slate-400"><?= e($u['email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-700"><?= e($u['username'] ?? '—') ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-900 text-xs"><?= format_currency($u['balance']) ?></td>
                                    <td class="px-6 py-4 text-xs font-semibold text-purple-600"><?= $u['orders_count'] ?></td>
                                    <td class="px-6 py-4"><?= render_status_badge($u['status']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="/admin/user-details.php?id=<?= $u['id'] ?>" class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200">
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?= render_pagination($paginator, '/admin/users.php') ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
