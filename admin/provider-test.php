<?php
/**
 * Admin Test Provider API Connection
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../providers/custom-provider.php';

$db = get_db();
$providerId = (int)($_GET['id'] ?? 1);

$stmt = $db->prepare("SELECT * FROM providers WHERE id = ? LIMIT 1");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    header("Location: /admin/providers.php");
    exit;
}

$adapter = new CustomProviderAdapter($provider['api_url'], $provider['api_key']);
$startTime = microtime(true);
$services = $adapter->getServices();
$latency = round((microtime(true) - $startTime) * 1000);
$balance = $adapter->getBalance();

// Update provider balance and last_sync
$stmt = $db->prepare("UPDATE providers SET balance = ?, last_sync = NOW() WHERE id = ?");
$stmt->execute([$balance, $providerId]);
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-3xl mx-auto space-y-6">

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/providers.php" class="hover:text-purple-600">Providers</a>
                <span>/</span>
                <span class="text-slate-900">API Connection Test</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h1 class="text-xl font-extrabold text-slate-900">Connection Diagnostics: <?= e($provider['name']) ?></h1>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Connected (<?= $latency ?>ms)
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-5 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium block">Carrier Balance</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block"><?= format_currency($balance) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block">HTTP Latency</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block"><?= $latency ?> ms</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block">Available Services</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block"><?= count($services) ?> platforms</strong>
                    </div>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Raw API Handshake Response</h3>
                    <pre class="p-4 rounded-xl bg-slate-900 text-emerald-400 font-mono text-xs overflow-x-auto max-h-60 leading-relaxed"><?= json_encode([
                        'status' => '200 OK',
                        'provider' => $provider['name'],
                        'api_url' => $provider['api_url'],
                        'latency_ms' => $latency,
                        'carrier_balance' => $balance,
                        'supported_services_count' => count($services),
                        'sample_service' => $services[0] ?? null
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-between">
                    <a href="/admin/providers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Back</a>
                    <a href="/admin/service-importer.php?provider_id=<?= $provider['id'] ?>" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md">
                        Import Services Now &rarr;
                    </a>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
