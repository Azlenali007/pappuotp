<?php
/**
 * User Profile Settings
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = sanitize_string($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');

    if (empty($name) || !validate_email($email)) {
        set_flash('error', 'Please provide a valid name and email address.');
    } else {
        // Check email uniqueness
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            set_flash('error', 'This email is already in use by another account.');
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $userId]);
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $currentUser['name'] = $name;
            $currentUser['email'] = $email;
            set_flash('success', 'Profile updated successfully.');
        }
    }
    header("Location: /user/profile.php");
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
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Account Profile</h1>
                <p class="text-sm text-slate-500">Update your personal account information and preferences</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <!-- Avatar block -->
                <div class="flex items-center gap-4 pb-6 border-b border-slate-100">
                    <div class="w-16 h-16 rounded-2xl bg-purple-600 text-white font-extrabold flex items-center justify-center text-xl shadow-md shadow-purple-200">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900"><?= e($currentUser['name']) ?></h2>
                        <div class="text-xs text-slate-500">Member since <?= date('M Y', strtotime($currentUser['created_at'])) ?></div>
                    </div>
                </div>

                <form action="/user/profile.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Full Name</label>
                        <input type="text" id="name" name="name" required value="<?= e($currentUser['name']) ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?= e($currentUser['email']) ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
                    </div>

                    <div>
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Username</label>
                        <input type="text" id="username" disabled value="<?= e($currentUser['username'] ?? '—') ?>" class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-sm text-slate-500 cursor-not-allowed">
                        <span class="text-xs text-slate-400 mt-1 block">Username cannot be changed.</span>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-bold text-white bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-200 transition-all text-sm">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
