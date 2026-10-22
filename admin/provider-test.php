<?php
/**
 * Admin Test Provider API Connection
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin-functions.php';
require_once __DIR__ . '/../providers/custom-provider.php';

$db = get_db();
$providerId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM providers WHERE id = ? LIMIT 1");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    set_flash('error', 'The requested provider could not be found.');
    header("Location: /admin/providers.php");
    exit;
}

// Perform real cURL handshake and diagnostics
$adapter = new CustomProviderAdapter($provider['api_url'], $provider['api_key']);
$diag = $adapter->testConnection();

// Update provider last_sync and balance if connection succeeded
if (!empty($diag['connected'])) {
    $stmt = $db->prepare("UPDATE providers SET balance = ?, last_sync = NOW() WHERE id = ?");
    $stmt->execute([(float)$diag['balance'], $providerId]);
    log_admin_audit('provider_tested', "Provider '{$provider['name']}' tested: Connected ({$diag['latency_ms']}ms, balance: {$diag['balance']})");
} else {
    $stmt = $db->prepare("UPDATE providers SET last_sync = NOW() WHERE id = ?");
    $stmt->execute([$providerId]);
    log_admin_audit('provider_tested', "Provider '{$provider['name']}' tested: Connection failed (HTTP {$diag['http_code']})");
}

$pageTitle = 'API Connection Test - ' . $provider['name'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/60">
        <div class="max-w-3xl mx-auto space-y-6">

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="/admin/providers.php" class="hover:text-purple-600">Providers</a>
                <span>/</span>
                <span class="text-slate-900"><?= e($provider['name']) ?></span>
                <span>/</span>
                <span class="text-slate-500">Diagnostics</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 sm:p-8 space-y-6">
                <!-- Header with Live Connection Badge -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-4">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900">Diagnostics: <?= e($provider['name']) ?></h1>
                        <div class="text-xs font-mono text-slate-400 mt-0.5 truncate max-w-md"><?= e($provider['api_url']) ?></div>
                    </div>

                    <div>
                        <?php if (!empty($diag['connected'])): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Connected (<?= $diag['latency_ms'] ?>ms)
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Connection Failed (<?= $diag['http_code'] ? 'HTTP ' . $diag['http_code'] : 'Unreachable' ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($diag['connected'])): ?>
                    <!-- Success Diagnostic Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 rounded-2xl bg-emerald-50/40 border border-emerald-100/60 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium block">Carrier Balance</span>
                            <strong class="text-emerald-800 text-sm mt-0.5 block"><?= format_currency($diag['balance']) ?></strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block">HTTP Latency</span>
                            <strong class="text-slate-900 text-sm mt-0.5 block"><?= $diag['latency_ms'] ?> ms</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block">HTTP Status</span>
                            <strong class="text-slate-900 text-sm mt-0.5 block"><?= $diag['http_code'] ?> OK</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block">Available Services</span>
                            <strong class="text-slate-900 text-sm mt-0.5 block"><?= $diag['services_count'] ?> platforms</strong>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Failure Diagnostic Alert -->
                    <div class="p-5 rounded-2xl bg-rose-50 border border-rose-200 text-xs space-y-2 text-rose-800">
                        <div class="flex items-center gap-2 font-bold text-sm text-rose-900">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Connection Handshake Unsuccessful
                        </div>
                        <p class="leading-relaxed">
                            <?= e($diag['error'] ?? 'The server could not establish a connection to the external provider API.') ?>
                        </p>
                        <div class="pt-2 text-[11px] text-rose-600 font-medium">
                            • Check if the API Endpoint URL is spelled correctly and reachable over HTTPS.<br>
                            • If this provider requires an API token, verify that the token has been entered in Provider Settings.<br>
                            • Verify that the provider account is active and has permissions enabled.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Raw Handshake Data (Sanitized: Secrets never displayed) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Raw API Handshake Response</h3>
                        <span class="text-[11px] text-slate-400">Tokens & passwords are masked</span>
                    </div>
                    <pre class="p-4 rounded-xl bg-slate-900 text-emerald-400 font-mono text-xs overflow-x-auto max-h-60 leading-relaxed"><?php
                        $sanitizedResponse = [
                            'status' => $diag['connected'] ? '200 Connected' : 'Failed',
                            'provider_name' => $provider['name'],
                            'api_url' => $provider['api_url'],
                            'http_code' => $diag['http_code'],
                            'latency_ms' => $diag['latency_ms'],
                            'carrier_balance' => $diag['balance'],
                            'services_detected' => $diag['services_count'],
                            'error_message' => $diag['error'] ?? null,
                            'raw_payload' => $diag['raw_response']
                        ];
                        echo htmlspecialchars(json_encode($sanitizedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                    ?></pre>
                </div>

                <!-- Action Controls -->
                <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a href="/admin/providers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                            &larr; Back to Providers
                        </a>
                        <a href="/admin/provider-test.php?id=<?= $provider['id'] ?>" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 transition-colors">
                            Re-test Connection
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="/admin/provider-edit.php?id=<?= $provider['id'] ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            Edit Configuration
                        </a>
                        <?php if (!empty($diag['connected'])): ?>
                            <a href="/admin/service-importer.php?provider_id=<?= $provider['id'] ?>" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 shadow-md transition-all">
                                Import Services Now &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
