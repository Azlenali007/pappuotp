<?php
/**
 * Admin Notifications Broadcast
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $title = sanitize_string($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['info', 'success', 'warning', 'danger']) ? $_POST['type'] : 'info';
    $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    if (!empty($title) && !empty($message)) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type]);

        log_admin_audit('broadcast_sent', "Admin sent notification: {$title}");
        set_flash('success', 'Notification dispatched.');
    }
    header("Location: /admin/notifications.php");
    exit;
}

$users = $db->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
$notifications = $db->query("SELECT n.*, u.name as user_name FROM notifications n LEFT JOIN users u ON u.id = n.user_id ORDER BY n.id DESC LIMIT 30")->fetchAll();
$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{ newModal: false }">
        <div class="max-w-6xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">System Notifications & Broadcasts</h1>
                    <p class="text-sm text-slate-500">Dispatch alerts and updates to users across the platform</p>
                </div>
                <button type="button" @click="newModal = true" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                    + Send Broadcast
                </button>
            </div>

            <div x-show="newModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" style="display: none;">
                <div @click.away="newModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                    <h3 class="text-base font-bold text-slate-900">Send Notification</h3>
                    <form action="/admin/notifications.php" method="POST" class="space-y-4">
                        <?= csrf_field() ?>
                        <div>
                            <label for="user_id" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Recipient</label>
                            <select id="user_id" name="user_id" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none">
                                <option value="">Broadcast to All Users</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="title" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Title</label>
                            <input type="text" id="title" name="title" required placeholder="Announcement title" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                        </div>
                        <div>
                            <label for="type" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Alert Level</label>
                            <select id="type" name="type" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none">
                                <option value="info">Info (Blue)</option>
                                <option value="success">Success (Green)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="danger">Danger (Red)</option>
                            </select>
                        </div>
                        <div>
                            <label for="message" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Message</label>
                            <textarea id="message" name="message" rows="3" required placeholder="Notification body..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="newModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs">Dispatch</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Title</th>
                            <th class="px-6 py-4">Target Recipient</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Sent At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($notifications as $n): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 text-xs"><?= e($n['title']) ?></div>
                                    <div class="text-xs text-slate-500 mt-0.5 truncate max-w-md"><?= e($n['message']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-700"><?= e($n['user_name'] ?? 'All Users (Broadcast)') ?></td>
                                <td class="px-6 py-4"><?= render_status_badge($n['type']) ?></td>
                                <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
