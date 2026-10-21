<?php
/**
 * Admin Ticket Reply & Resolution
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$ticketId = (int)($_GET['id'] ?? 0);
$adminId = (int)$currentAdmin['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $message = trim($_POST['message'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['open', 'answered', 'closed']) ? $_POST['status'] : 'answered';

    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO support_ticket_replies (ticket_id, sender_type, sender_id, message) VALUES (?, 'admin', ?, ?)");
        $stmt->execute([$ticketId, $adminId, $message]);
    }

    $stmt = $db->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $ticketId]);

    log_admin_audit('ticket_replied', "Admin replied to ticket ID {$ticketId} (Status: {$status})");
    set_flash('success', 'Ticket updated successfully.');
    header("Location: /admin/ticket-details.php?id=" . $ticketId);
    exit;
}

$stmt = $db->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: /admin/support.php");
    exit;
}

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
                <a href="/admin/support.php" class="hover:text-purple-600">Support</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($ticket['ticket_ref']) ?></span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900"><?= e($ticket['subject']) ?></h1>
                        <div class="text-xs text-slate-500 mt-1">Ticket #<span class="font-mono text-slate-700 font-bold"><?= e($ticket['ticket_ref']) ?></span> • User: <strong><?= e($ticket['user_name']) ?></strong> (<?= e($ticket['user_email']) ?>)</div>
                    </div>
                    <div><?= render_status_badge($ticket['status']) ?></div>
                </div>

                <!-- Messages -->
                <div class="space-y-4">
                    <?php foreach ($replies as $r): ?>
                        <div class="p-5 rounded-2xl border <?= $r['sender_type'] === 'admin' ? 'bg-purple-50/60 border-purple-100' : 'bg-white border-slate-200' ?>">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold <?= $r['sender_type'] === 'admin' ? 'text-purple-700' : 'text-slate-900' ?>">
                                    <?= $r['sender_type'] === 'admin' ? 'Super Admin' : e($ticket['user_name']) ?>
                                </span>
                                <span class="text-xs text-slate-400"><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></span>
                            </div>
                            <div class="text-sm text-slate-700 whitespace-pre-line leading-relaxed"><?= e($r['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Admin Reply Form -->
                <form action="/admin/ticket-details.php?id=<?= $ticket['id'] ?>" method="POST" class="pt-6 border-t border-slate-100 space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="message" class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Admin Response</label>
                        <textarea id="message" name="message" rows="3" placeholder="Provide customer solution..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none"></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <label for="status" class="text-xs font-bold text-slate-700 uppercase">Set Status:</label>
                            <select id="status" name="status" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none">
                                <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Answered</option>
                                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            </select>
                        </div>

                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                            Submit Response
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
