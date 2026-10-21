<?php
/**
 * Admin User Edit Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'suspended', 'banned']) ? $_POST['status'] : 'active';

    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?");
    $stmt->execute([$name, $email, $status, $userId]);

    log_admin_audit('user_updated', "Admin updated user details for ID {$userId}");
    set_flash('success', 'User updated successfully.');
    header("Location: /admin/user-details.php?id=" . $userId);
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: /admin/users.php");
    exit;
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-2xl mx-auto space-y-6">

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/users.php" class="hover:text-purple-600">Users</a>
                <span>/</span>
                <a href="/admin/user-details.php?id=<?= $user['id'] ?>" class="hover:text-purple-600"><?= e($user['name']) ?></a>
                <span>/</span>
                <span class="text-slate-900">Edit</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <h1 class="text-xl font-extrabold text-slate-900">Edit User Account</h1>

                <form action="/admin/user-edit.php?id=<?= $user['id'] ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Full Name</label>
                        <input type="text" id="name" name="name" required value="<?= e($user['name']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?= e($user['email']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Account Status</label>
                        <select id="status" name="status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="/admin/user-details.php?id=<?= $user['id'] ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">Save Changes</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
