<?php
/**
 * Admin Roles & Privileges
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db();
$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-4xl mx-auto space-y-6">

            <div class="space-y-1">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">System Roles & RBAC Matrix</h1>
                <p class="text-sm text-slate-500">Access control policies established for platform security</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs divide-y divide-slate-100 overflow-hidden">
                <?php foreach ($roles as $r): ?>
                    <div class="p-6 flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900"><?= e($r['name']) ?></h3>
                                <span class="font-mono text-xs px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 font-bold"><?= e($r['slug']) ?></span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1"><?= e($r['description']) ?></p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Enforced
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
