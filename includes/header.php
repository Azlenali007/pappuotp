<?php
/**
 * Public Layout Header
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/maintenance.php';

check_maintenance_mode();

$pageTitle = $pageTitle ?? (get_setting('site_name', 'OTP Services') . ' - Instant Virtual Numbers');
$pageDesc = $pageDesc ?? 'Get instant OTP SMS verification numbers for WhatsApp, Google, Facebook, Telegram and all major platforms.';
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <!-- Tailwind CSS Output -->
    <link rel="stylesheet" href="/assets/css/output.css">
    <!-- Alpine.js for lightweight reactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <!-- SweetAlert2 for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="min-h-full flex flex-col bg-slate-50 text-slate-800 antialiased selection:bg-purple-500 selection:text-white font-sans">
