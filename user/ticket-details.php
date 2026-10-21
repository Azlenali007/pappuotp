<?php
/**
 * User Ticket Conversation Details
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$ticketId = (int)($_GET['id'] ?? 0);
$userId = (int)$currentUser['id'];

// Handle reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $message = trim($_POST['message'] ?? '');

    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO support_ticket_replies (ticket_id, sender_type, sender_id, message) VALUES (?, 'user', ?, ?)");
        $stmt->execute([$ticketId, $userId, $message]);

        // Set status to open
        $stmt = $db->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?");
        $stmt->execute([$ticketId]);

        set_flash('success', 'Your reply has been posted.');
    }
    header("Location: /user/ticket-details.php?id=" . $ticketId);
    exit;
}

// Fetch ticket
$stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$ticketId, $userId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: /user/support.php");
    exit;
}

// Fetch replies
$stmt = $db->prepare("SELECT * FROM support_ticket_replies WHERE ticket_id = ? ORDER BY id ASC");
$stmt->execute([$ticketId]);
$replies = $stmt->fetchAll();

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

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/user/support.php" class="hover:text-purple-600">Support</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($ticket['ticket_ref']) ?></span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-2">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900"><?= e($ticket['subject']) ?></h1>
                        <div class="text-xs text-slate-400 mt-1">Ticket #<span class="font-mono text-slate-700 font-semibold"><?= e($ticket['ticket_ref']) ?></span> • <?= e($ticket['department']) ?></div>
                    </div>
                    <div>
                        <?= render_status_badge($ticket['status']) ?>
                    </div>
                </div>

                <!-- Messages Thread -->
                <div class="space-y-4 pt-2">
                    <?php foreach ($replies as $reply): ?>
                        <div class="p-5 rounded-2xl border <?= $reply['sender_type'] === 'admin' ? 'bg-purple-50/60 border-purple-100' : 'bg-white border-slate-200' ?>">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold <?= $reply['sender_type'] === 'admin' ? 'text-purple-700' : 'text-slate-800' ?>">
                                    <?= $reply['sender_type'] === 'admin' ? 'Support Agent (Admin)' : e($currentUser['name']) ?>
                                </span>
                                <span class="text-xs text-slate-400"><?= date('d M Y, H:i', strtotime($reply['created_at'])) ?></span>
                            </div>
                            <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line"><?= e($reply['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reply Form -->
                <form action="/user/ticket-details.php?id=<?= $ticket['id'] ?>" method="POST" class="pt-6 border-t border-slate-100 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label for="message" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Post Reply</label>
                        <textarea id="message" name="message" rows="3" required placeholder="Type your response..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md shadow-purple-200">Send Reply</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
