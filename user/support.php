<?php
/**
 * User Support Desk & Tickets
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';

$db = get_db();
$userId = (int)$currentUser['id'];

// Create new ticket POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $subject = sanitize_string($_POST['subject'] ?? '');
    $department = sanitize_string($_POST['department'] ?? 'Support');
    $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        set_flash('error', 'Subject and message are required to open a support ticket.');
        header("Location: /user/support.php");
        exit;
    }

    $ticketRef = 'TCK-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO support_tickets (ticket_ref, user_id, subject, department, priority, status) VALUES (?, ?, ?, ?, ?, 'open')");
        $stmt->execute([$ticketRef, $userId, $subject, $department, $priority]);
        $ticketId = (int)$db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO support_ticket_replies (ticket_id, sender_type, sender_id, message) VALUES (?, 'user', ?, ?)");
        $stmt->execute([$ticketId, $userId, $message]);

        // Insert notification for admin awareness
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (NULL, 'New Support Ticket', ?, 'info', ?)");
        $stmt->execute(["New ticket #{$ticketRef} opened by user {$currentUser['name']}: {$subject}", "/admin/ticket-details.php?id={$ticketId}"]);

        $db->commit();
        set_flash('success', "Support ticket #{$ticketRef} opened successfully.");
        header("Location: /user/ticket-details.php?id=" . $ticketId);
        exit;
    } catch (\Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Support ticket creation error: " . $e->getMessage());
        set_flash('error', 'Failed to create support ticket. Please try again.');
        header("Location: /user/support.php");
        exit;
    }
}

$pageTitle = 'Support Desk - OTP Services';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$stmt = $db->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$tickets = $stmt->fetchAll();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60" x-data="{ newTicketOpen: false }">
        <div class="max-w-7xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Support Desk</h1>
                    <p class="text-sm text-slate-500">24/7 dedicated assistance for verification numbers and billing inquiries</p>
                </div>
                <button type="button" @click="newTicketOpen = !newTicketOpen" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 shadow-md shadow-purple-200 transition-all self-start sm:self-auto cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Open New Ticket
                </button>
            </div>

            <!-- New Ticket Drawer / Box -->
            <div x-show="newTicketOpen" x-transition class="bg-white rounded-2xl border border-slate-100 shadow-md p-6 sm:p-8 space-y-5" style="display: none;">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h2 class="text-base font-bold text-slate-900">Create Support Request</h2>
                    <button type="button" @click="newTicketOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form action="/user/support.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label for="subject" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Subject</label>
                            <input type="text" id="subject" name="subject" required placeholder="Brief description of the issue" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                        </div>
                        <div>
                            <label for="department" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Department</label>
                            <select id="department" name="department" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600">
                                <option value="Support">General Support</option>
                                <option value="Billing">Billing & Wallet</option>
                                <option value="Provider">API & Carrier</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Message</label>
                        <textarea id="message" name="message" rows="4" required placeholder="Provide order reference or specific problem..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="newTicketOpen = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md shadow-purple-200">Submit Ticket</button>
                    </div>
                </form>
            </div>

            <!-- Tickets List -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/60 text-xs uppercase font-semibold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Ticket Ref</th>
                                <th class="px-6 py-4">Subject</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Priority</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">No support tickets found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-purple-600"><?= e($t['ticket_ref']) ?></td>
                                        <td class="px-6 py-4 font-bold text-slate-900"><?= e($t['subject']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-600"><?= e($t['department']) ?></td>
                                        <td class="px-6 py-4 text-xs uppercase font-semibold text-slate-500"><?= e($t['priority']) ?></td>
                                        <td class="px-6 py-4"><?= render_status_badge($t['status']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="/user/ticket-details.php?id=<?= $t['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition-all">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
