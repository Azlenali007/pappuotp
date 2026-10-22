<?php
/**
 * Super Admin Audit Logs
 * Immutable audit trail of administrative actions and security events
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';

$db = get_db();

$search = trim($_GET['search'] ?? '');
$actorType = trim($_GET['actor_type'] ?? 'all'); // all, admin, user
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$whereClauses = ['1=1'];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(l.action LIKE :search OR l.details LIKE :search OR l.ip_address LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($actorType === 'admin') {
    $whereClauses[] = "l.admin_id IS NOT NULL";
} elseif ($actorType === 'user') {
    $whereClauses[] = "l.user_id IS NOT NULL";
}

$whereSql = implode(' AND ', $whereClauses);

// Count total matching logs
$countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs l WHERE {$whereSql}");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalLogs / $perPage));

// Fetch real audit logs joined with actors
$stmt = $db->prepare("
    SELECT l.*, 
           a.username AS admin_username, 
           a.name AS admin_name,
           u.email AS user_email,
           u.name AS user_name
    FROM activity_logs l
    LEFT JOIN admins a ON a.id = l.admin_id
    LEFT JOIN users u ON u.id = l.user_id
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
$logs = $stmt->fetchAll();

// Function to sanitize and mask sensitive data from logs before rendering
function mask_audit_details(?string $details): string {
    if ($details === null || $details === '') {
        return '<span class="text-slate-400 italic">No details recorded</span>';
    }
    // Mask API keys, tokens, secret values, and passwords
    $masked = preg_replace('/(api[_-]?key|secret|token|password|auth|bearer)[\s=:"]+([^\s,;"]+)/i', '$1: [REDACTED]', $details);
    return htmlspecialchars($masked, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Audit Logs - Super Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Title Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Audit Logs</h1>
                    <p class="text-sm text-slate-500">Immutable, chronological record of administrative actions, service changes, and security events</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 text-xs font-bold border border-purple-200">
                        Total Records: <?= number_format($totalLogs) ?>
                    </span>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6">
                <form method="GET" action="/admin/audit-logs.php" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by action, keyword, details, or IP address..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-medium text-slate-900">
                    </div>

                    <div class="w-full sm:w-48">
                        <select name="actor_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-sm font-semibold text-slate-700 bg-white">
                            <option value="all" <?= $actorType === 'all' ? 'selected' : '' ?>>All Actors</option>
                            <option value="admin" <?= $actorType === 'admin' ? 'selected' : '' ?>>Admin Only</option>
                            <option value="user" <?= $actorType === 'user' ? 'selected' : '' ?>>User / System</option>
                        </select>
                    </div>

                    <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl shadow-md shadow-purple-200 transition-colors">
                        Filter
                    </button>

                    <?php if ($search !== '' || $actorType !== 'all'): ?>
                        <a href="/admin/audit-logs.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl text-center transition-colors">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Audit Logs Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-bold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Actor</th>
                                <th class="px-6 py-4">Action</th>
                                <th class="px-6 py-4">Details</th>
                                <th class="px-6 py-4">IP Address</th>
                                <th class="px-6 py-4">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        No audit records found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs text-slate-400 font-bold">
                                            #<?= (int)$log['id'] ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (!empty($log['admin_id'])): ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs">A</span>
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-xs"><?= e($log['admin_username'] ?? 'Super Admin') ?></div>
                                                        <div class="text-[10px] text-purple-600 font-semibold uppercase">Admin</div>
                                                    </div>
                                                </div>
                                            <?php elseif (!empty($log['user_id'])): ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">U</span>
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-xs"><?= e($log['user_email'] ?? 'User #' . $log['user_id']) ?></div>
                                                        <div class="text-[10px] text-blue-600 font-semibold uppercase">Customer</div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">
                                                    System / Worker
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-50 text-purple-700 border border-purple-100">
                                                <?= e($log['action']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 max-w-md">
                                            <div class="text-xs text-slate-700 font-medium break-words leading-relaxed">
                                                <?= mask_audit_details($log['details']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-slate-500 whitespace-nowrap">
                                            <?= e($log['ip_address'] ?? '127.0.0.1') ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap font-medium">
                                            <div><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></div>
                                            <div class="text-[10px] text-slate-400"><?= time_ago($log['created_at']) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 bg-slate-50/60 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-500 font-medium">
                            Showing <span class="font-bold text-slate-800"><?= number_format($offset + 1) ?></span> to 
                            <span class="font-bold text-slate-800"><?= number_format(min($offset + $perPage, $totalLogs)) ?></span> of 
                            <span class="font-bold text-slate-800"><?= number_format($totalLogs) ?></span> records
                        </div>

                        <div class="flex items-center gap-1.5">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?><?= $actorType !== 'all' ? '&actor_type=' . urlencode($actorType) : '' ?>"
                                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?= $i ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?><?= $actorType !== 'all' ? '&actor_type=' . urlencode($actorType) : '' ?>"
                                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors <?= $i === $page ? 'bg-purple-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?><?= $actorType !== 'all' ? '&actor_type=' . urlencode($actorType) : '' ?>"
                                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
