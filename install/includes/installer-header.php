<?php
/**
 * Installer Master Header
 */
require_once __DIR__ . '/installer-auth.php';

$currentStep = $stepNumber ?? 1;
$steps = [
    1 => ['name' => 'Welcome', 'url' => '/install/index.php'],
    2 => ['name' => 'Requirements', 'url' => '/install/requirements.php'],
    3 => ['name' => 'Database', 'url' => '/install/database.php'],
    4 => ['name' => 'Install Schema', 'url' => '#'],
    5 => ['name' => 'Admin Account', 'url' => '/install/admin-account.php'],
    6 => ['name' => 'Configuration', 'url' => '/install/configuration.php'],
    7 => ['name' => 'Finish', 'url' => '/install/finish.php'],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Installation Wizard') ?> - OTP Services Platform</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <!-- Tailwind CSS Output -->
    <link rel="stylesheet" href="/assets/css/output.css">
    <link rel="stylesheet" href="/install/assets/css/installer.css">
</head>
<body class="min-h-full bg-slate-50 text-slate-800 antialiased font-sans flex flex-col justify-between selection:bg-purple-500 selection:text-white">
    <!-- Top Branding Navigation -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-purple-600 flex items-center justify-center shadow-md shadow-purple-200">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900">OTP <span class="text-purple-600">Platform</span></span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 ml-2">Setup Wizard</span>
                </div>
            </div>
            <div class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                Step <?= $currentStep ?> of 7
            </div>
        </div>
    </header>

    <!-- Main Installer Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <!-- Progress Stepper (Mobile & Desktop) -->
        <div class="mb-8">
            <div class="hidden sm:flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-200 z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-purple-600 z-0 transition-all duration-300" style="width: <?= (($currentStep - 1) / 6) * 100 ?>%;"></div>

                <?php foreach ($steps as $num => $st): ?>
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-200 <?= $num < $currentStep ? 'installer-step-completed' : ($num === $currentStep ? 'installer-step-active ring-4 ring-purple-100' : 'installer-step-pending') ?>">
                            <?php if ($num < $currentStep): ?>
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            <?php else: ?>
                                <?= $num ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-[11px] font-semibold mt-1.5 <?= $num === $currentStep ? 'text-purple-700' : ($num < $currentStep ? 'text-slate-700' : 'text-slate-400') ?>">
                            <?= $st['name'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mobile Progress Bar -->
            <div class="sm:hidden space-y-2">
                <div class="flex justify-between text-xs font-bold text-slate-700">
                    <span>Step <?= $currentStep ?>: <?= $steps[$currentStep]['name'] ?></span>
                    <span class="text-purple-600"><?= round((($currentStep) / 7) * 100) ?>%</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                    <div class="bg-purple-600 h-2 rounded-full transition-all" style="width: <?= (($currentStep) / 7) * 100 ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Step Content Card -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl shadow-purple-500/5 overflow-hidden">
