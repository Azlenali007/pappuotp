<?php
/**
 * Super Admin Login
 * Protected by Secure Credentials
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_admin_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $login = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
    $stmt->execute([$login, $login]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_user'] = $admin['username'];

        // Record last login and audit log
        $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (?, 'admin_login', 'Super admin session established', ?)");
        $stmt->execute([$admin['id'], $ip]);

        header('Location: /admin/dashboard.php');
        exit;
    } else {
        set_flash('error', 'Invalid administrator credentials. Access logged.');
        header('Location: /admin/login.php');
        exit;
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - OTP Services</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="stylesheet" href="/assets/css/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="h-full flex items-center justify-center p-4 font-sans text-slate-800 antialiased selection:bg-purple-600 selection:text-white">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border border-slate-100 space-y-6">
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-purple-900/30">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Super Admin Portal</h1>
            <p class="text-xs text-slate-500 font-medium">Authorized administrative credentials required</p>
        </div>

        <?php if ($flash): ?>
            <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form action="/admin/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Admin Username</label>
                <input type="text" id="username" name="username" required value="admin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Security Password</label>
                <input type="password" id="password" name="password" required value="Admin@123456" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-600/20 focus:border-purple-600 focus:bg-white">
            </div>

            <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-100 text-[11px] text-purple-800">
                <span class="font-bold">Initial Super Admin:</span> <code>admin</code> / <code>Admin@123456</code>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl font-bold text-white bg-purple-600 hover:bg-purple-700 shadow-lg shadow-purple-600/30 transition-all active:scale-95 text-sm cursor-pointer">
                Authenticate & Enter
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="/index.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Return to Public Website</a>
        </div>
    </div>

</body>
</html>
