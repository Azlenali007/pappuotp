<?php
/**
 * User Panel Header
 */

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'User Portal - OTP Services';
$unreadNotifsCount = 0;
try {
    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
    $stmt->execute([$currentUser['id']]);
    $unreadNotifsCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="stylesheet" href="/assets/css/output.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="h-full flex overflow-hidden font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false }">
