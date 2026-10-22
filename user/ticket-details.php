<?php
/**
 * User Ticket Conversation Details
 */

require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$ticketId = (int)($_GET['id'] ?? 0);
$userId = (int)$currentUser['id'];

// Fetch ticket
$stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$ticketId, $userId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: /user/support.php");
    exit;
}

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

// Fetch replies
$stmt = $db->prepare("SELECT * FROM support_ticket_replies WHERE ticket_id = ? ORDER BY id ASC");
$stmt->execute([$ticketId]);
$replies = $stmt->fetchAll();

$pageTitle = 'Ticket #' . ($ticket['ticket_ref'] ?? $ticketId) . ' - Support';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

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
                <a href="/user/support.php" class="hover:text-purple-600">Support Tickets</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($ticket['ticket_ref']) ?></span>
            </div>

            <!-- Ticket Overview Card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-slate-400 font-mono mb-1">
                            <span><?= e($ticket['ticket_ref']) ?></span>
                            <span>•</span>
                            <span><?= date('M j, Y H:i', strtotime($ticket['created_at'])) ?></span>
                        </div>
                        <h1 class="text-xl font-extrabold text-slate-900"><?= e($ticket['subject']) ?></h1>
                    </div>
                    <div>
                        <?= render_status_badge($ticket['status']) ?>
                    </div>
                </div>

                <!-- Initial Ticket Message -->
                <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-100/60 text-sm text-slate-700 leading-relaxed">
                    <div class="text-xs font-bold text-purple-700 mb-1">Original Inquiry</div>
                    <?= nl2br(e($ticket['message'])) ?>
                </div>

                <!-- Conversation History -->
                <div class="space-y-4 pt-2">
                    <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Conversation History</h2>

                    <?php if (empty($replies)): ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-500 text-center">
                            Awaiting agent response. Our support team typically answers within a few minutes.
                        </div>
                    <?php else: ?>
                        <?php foreach ($replies as $rep): ?>
                            <div class="p-4 rounded-xl <?= $rep['sender_type'] === 'admin' ? 'bg-purple-50 border border-purple-100 ml-4 sm:ml-8' : 'bg-slate-50 border border-slate-100 mr-4 sm:mr-8' ?> text-sm space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold <?= $rep['sender_type'] === 'admin' ? 'text-purple-700' : 'text-slate-900' ?>">
                                        <?= $rep['sender_type'] === 'admin' ? 'Super Admin Support' : 'You' ?>
                                    </span>
                                    <span class="text-slate-400"><?= time_ago($rep['created_at']) ?></span>
                                </div>
                                <div class="text-slate-700 leading-relaxed whitespace-pre-line"><?= e($rep['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Reply Form -->
                <?php if ($ticket['status'] !== 'closed'): ?>
                    <form action="/user/ticket-details.php?id=<?= $ticket['id'] ?>" method="POST" class="pt-4 border-t border-slate-100 space-y-3">
                        <?= csrf_field() ?>
                        <label for="message" class="block text-xs font-bold text-slate-700 uppercase">Add Reply</label>
                        <textarea id="message" name="message" rows="3" required placeholder="Type your response or question here..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white transition-all"></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md transition-all cursor-pointer">
                                Send Reply
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-500 text-center">
                        This support ticket is marked as resolved and closed.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
