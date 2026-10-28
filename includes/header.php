<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · RoBot Camp</title>
    <!-- Prototype Tailwind as allowed by the specification. Local CSS keeps the UI usable offline. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <script defer src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
<script defer src="assets/js/scroll-position.js?v=<?= filemtime(__DIR__.'/../assets/js/scroll-position.js') ?>"></script><link rel="stylesheet" href="assets/css/academy-2026.css?v=<?= filemtime(__DIR__.'/../assets/css/academy-2026.css') ?>"><?php if($user || ($_GET['page']??'') === 'login'): ?><link rel="stylesheet" href="assets/css/rra.css?v=<?= filemtime(__DIR__.'/../assets/css/rra.css') ?>"><script defer src="assets/js/rra.js?v=<?= filemtime(__DIR__.'/../assets/js/rra.js') ?>"></script><?php endif ?></head>
<body class="<?= $user?'rra-app':(($_GET['page']??'')==='login'?'rra-auth':'') ?>">
<?php if ($user): ?>
<div class="app-shell">
<?php (static function(array $user):void { require __DIR__.'/rra-navigation.php'; })($user); ?><div class="workspace">
        <main id="main-content" class="main-content">
        <?php (static function(array $user):void { require __DIR__.'/workspace-tabs.php'; })($user); ?>
        <?php if (isset($_SESSION['flash'])): ?><div class="alert success" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif ?>
<?php else: ?><main class="login-shell"><?php endif ?>
