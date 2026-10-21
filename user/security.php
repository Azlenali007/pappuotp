<?php
/**
 * User Security & Password Settings
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Verify current password
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch();

    if (!password_verify($currentPass, $userRow['password_hash'])) {
        set_flash('error', 'Your current password is incorrect.');
    } elseif ($newPass !== $confirmPass) {
        set_flash('error', 'New password and confirmation do not match.');
    } elseif (strlen($newPass) < 6) {
        set_flash('error', 'New password must be at least 6 characters long.');
    } else {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        set_flash('success', 'Your password has been successfully updated.');
    }
    header("Location: /user/security.php");
    exit;
}

$flash = get_flash();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-2xl mx-auto space-y-6">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> shadow-xs">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Security & Password</h1>
                <p class="text-sm text-slate-500">Protect your account with robust credentials and session controls</p>
            </div>

            <!-- Password Change Card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <h2 class="text-base font-bold text-slate-900">Change Password</h2>

                <form action="/user/security.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="••••••••" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">New Password</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="••••••••" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-bold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all text-sm">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Two-Factor Authentication Status Card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Two-Factor Authentication (2FA)</h3>
                        <p class="text-xs text-slate-500 mt-1">Add an extra layer of security when logging into your account.</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active
                    </span>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
