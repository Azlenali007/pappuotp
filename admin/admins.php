<?php
/**
 * Super Admin Security & Credentials
 * Strictly enforces single Super Admin policy (Section 4)
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$adminId = (int)$currentAdmin['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($name) || !validate_email($email)) {
        set_flash('error', 'Valid name and email are required.');
    } else {
        if (!empty($newPass)) {
            if ($newPass !== $confirmPass) {
                set_flash('error', 'New passwords do not match.');
                header("Location: /admin/admins.php");
                exit;
            }
            if (strlen($newPass) < 8) {
                set_flash('error', 'Password must be at least 8 characters long.');
                header("Location: /admin/admins.php");
                exit;
            }
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE admins SET name = ?, email = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hash, $adminId]);
            log_admin_audit('admin_password_changed', 'Super Admin updated password');
        } else {
            $stmt = $db->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $adminId]);
            log_admin_audit('admin_profile_updated', 'Super Admin updated name/email');
        }

        set_flash('success', 'Super Admin profile updated successfully.');
    }
    header("Location: /admin/admins.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-3xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Super Admin Credentials</h1>
                <p class="text-sm text-slate-500">Security governance and single administrator account configuration</p>
            </div>

            <!-- Single Admin Rule Advisory Card -->
            <div class="p-5 rounded-2xl bg-purple-50/70 border border-purple-100 flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div class="text-xs text-purple-900 leading-relaxed">
                    <strong class="block font-bold mb-0.5">Strict Single Super Admin Governance:</strong>
                    This system enforces exactly ONE administrative account. Staff role additions and public registrations are permanently disabled for compliance and data integrity.
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <form action="/admin/admins.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Admin Name</label>
                            <input type="text" id="name" name="name" required value="<?= e($admin['name']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                        </div>
                        <div>
                            <label for="username" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Username</label>
                            <input type="text" id="username" disabled value="<?= e($admin['username']) ?>" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm font-mono text-slate-500 cursor-not-allowed">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Administrative Email</label>
                        <input type="email" id="email" name="email" required value="<?= e($admin['email']) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                    </div>

                    <div class="pt-4 border-t border-slate-100 space-y-4">
                        <h3 class="text-xs font-bold text-slate-700 uppercase">Change Security Password (Optional)</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="new_password" class="block text-xs font-bold text-slate-700 mb-1 uppercase">New Password</label>
                                <input type="password" id="new_password" name="new_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-xs font-bold text-slate-700 mb-1 uppercase">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                            Save Admin Settings
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
