<?php
/**
 * Admin Activity & Audit Logs
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$pageTitle = 'Activity & Audit Logs';
$db = getDB();

$actionFilter = trim($_GET['action'] ?? '');
$adminFilter = (int)($_GET['admin_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$whereClauses = ['1=1'];
$params = [];

if ($actionFilter) {
    $whereClauses[] = "l.action LIKE :action";
    $params[':action'] = '%' . $actionFilter . '%';
}
if ($adminFilter > 0) {
    $whereClauses[] = "l.admin_id = :admin_id";
    $params[':admin_id'] = $adminFilter;
}

$whereSql = implode(' AND ', $whereClauses);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs l WHERE {$whereSql}");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// Fetch logs
$stmt = $db->prepare("
    SELECT l.*, a.username AS admin_username
    FROM activity_logs l
    LEFT JOIN admins a ON a.id = l.admin_id
    WHERE {$whereSql}
    ORDER BY l.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Activity & Audit Logs</h1>
            <p class="text-sm text-slate-500">Immutable record of administrative operations and security events</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="action" value="<?= htmlspecialchars($actionFilter) ?>" placeholder="Filter by action name..."
                       class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm">
            </div>
            <button type="submit" class="px-5 py-2 bg-purple-600 text-white rounded-xl text-sm font-semibold hover:bg-purple-700 transition-colors">
                Filter Logs
            </button>
            <?php if ($actionFilter || $adminFilter): ?>
            <a href="/admin/activity-logs.php" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-200 text-center transition-colors">
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 font-semibold text-xs uppercase tracking-wider">
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Admin</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Details</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">No activity logs recorded yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-3.5 px-4 font-mono text-xs text-slate-400">#<?= $log['id'] ?></td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-900">
                                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                                <?= htmlspecialchars($log['admin_username'] ?? 'System/Admin') ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-mono text-xs px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 font-semibold">
                                <?= htmlspecialchars($log['action']) ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 text-xs max-w-xs truncate" title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                            <?= htmlspecialchars($log['details'] ?? '-') ?>
                        </td>
                        <td class="py-3.5 px-4 font-mono text-xs text-slate-500">
                            <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-500 whitespace-nowrap">
                            <?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-600">
            <div>Page <?= $page ?> of <?= $totalPages ?> (<?= $totalLogs ?> records)</div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&action=<?= urlencode($actionFilter) ?>" class="px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-semibold hover:bg-slate-200">Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&action=<?= urlencode($actionFilter) ?>" class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-semibold hover:bg-purple-700">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
