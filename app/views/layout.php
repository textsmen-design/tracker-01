<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="shell" id="shell">
    <aside class="sidebar">
        <div class="sidebar__brand">
            <a href="index.php?page=dashboard">
                <img class="brand__mark" src="assets/img/logo-mark.svg" alt="" width="28" height="28">
                <span class="brand">
                    <?= e(APP_NAME) ?>
                    <span class="brand__tag">трекер задач</span>
                </span>
            </a>
        </div>
        <nav class="sidebar__nav">
            <a href="index.php?page=dashboard" class="<?= $activeNav === 'dashboard' ? 'is-active' : '' ?>">Главная</a>
            <a href="index.php?page=my_tasks" class="<?= $activeNav === 'my_tasks' ? 'is-active' : '' ?>">Мои задачи</a>
            <a href="index.php?page=projects" class="<?= $activeNav === 'projects' ? 'is-active' : '' ?>">Проекты</a>
            <a href="index.php?page=team" class="<?= $activeNav === 'team' ? 'is-active' : '' ?>">Команда</a>
            <a href="index.php?page=notifications" class="<?= $activeNav === 'notifications' ? 'is-active' : '' ?>">Уведомления<?php if (($unreadCount ?? 0) > 0): ?><span class="nav-badge"><?= (int)$unreadCount ?></span><?php endif; ?></a>
            <?php if (can_manage_users($user)): ?>
                <a href="index.php?page=admin_users" class="<?= $activeNav === 'admin_users' ? 'is-active' : '' ?>">Пользователи</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar__foot">
            <a href="index.php?page=profile" class="<?= $activeNav === 'profile' ? 'is-active' : '' ?>">Настройки</a>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="linklike" type="submit">Выйти</button>
            </form>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="topbar__burger" type="button" id="navToggle" aria-label="Меню" aria-expanded="false">☰</button>
            <div class="topbar__title"><?= e($pageTitle) ?></div>
            <a class="bell" href="index.php?page=notifications"
               title="Уведомления">🔔<?php if (($unreadCount ?? 0) > 0): ?><span class="bell__badge"><?= (int)$unreadCount ?></span><?php endif; ?></a>
        </header>
        <?php foreach ($flash as $f): ?>
            <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['text']) ?></div>
        <?php endforeach; ?>
        <main class="content">
            <?= $content ?>
        </main>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
