<?php
/**
 * User Notifications Center
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)$currentUser['id'];

// Mark all as read POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL");
    $stmt->execute([$userId]);
    set_flash('success', 'All notifications marked as read.');
    header("Location: /user/notifications.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-4xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Notifications</h1>
                    <p class="text-sm text-slate-500">Live order delivery updates and security alerts</p>
                </div>
                <form method="POST" action="/user/notifications.php">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-xs font-semibold text-purple-600 hover:text-purple-800 bg-purple-50 px-3.5 py-2 rounded-xl border border-purple-100 hover:bg-purple-100 transition-colors">
                        Mark all as read
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs divide-y divide-slate-100 overflow-hidden">
                <?php if (empty($notifications)): ?>
                    <div class="p-12 text-center text-slate-400 text-sm">
                        No notifications yet. You're all caught up!
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="p-5 flex items-start gap-4 hover:bg-slate-50/60 transition-colors <?= $n['is_read'] ? 'opacity-70' : 'bg-purple-50/20' ?>">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-slate-900"><?= e($n['title']) ?></h3>
                                    <span class="text-xs text-slate-400"><?= time_ago($n['created_at']) ?></span>
                                </div>
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= e($n['message']) ?></p>
                                <?php if ($n['link']): ?>
                                    <a href="<?= e($n['link']) ?>" class="inline-block mt-2 text-xs font-semibold text-purple-600 hover:text-purple-800">
                                        View Details &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
