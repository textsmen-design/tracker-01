<?php
/**
 * Tracker_01 — точка входа (front controller).
 * Роутинг по $_GET['page']; POST-действия — через src/actions.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/permissions.php';

start_session();

$page   = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action !== '') {
    require_once __DIR__ . '/../src/actions.php';
    handle_action($action);
    exit;
}

$user   = current_user();
$flash  = get_flash();

if ($page === 'login') {
    $pageTitle = 'Вход';
    ob_start();
    include __DIR__ . '/../views/login.php';
    $content = ob_get_clean();
    include __DIR__ . '/../views/fullpage.php';
    exit;
}

if (!$user) {
    redirect('index.php?page=login');
}

$allowedPages = [
    'dashboard', 'my_tasks', 'projects', 'project', 'task', 'task_form',
    'team', 'notifications', 'profile', 'admin_users',
];

if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $pageTitle = '404 — не найдено';
    $activeNav = '';
    ob_start();
    include __DIR__ . '/../views/404.php';
    $content = ob_get_clean();
    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([(int)$user['id']]);
    $unreadCount = (int)$stmt->fetchColumn();
    include __DIR__ . '/../views/layout.php';
    exit;
}

/** Активный пункт бокового меню (у связанных страниц — общий раздел). */
$activeNavMap = [
    'dashboard'    => 'dashboard',
    'my_tasks'     => 'my_tasks',
    'projects'     => 'projects',
    'project'      => 'projects',
    'task'         => 'projects',
    'task_form'    => 'projects',
    'team'         => 'team',
    'notifications' => 'notifications',
    'admin_users'  => 'admin_users',
    'profile'      => 'profile',
];
$activeNav = $activeNavMap[$page] ?? '';

// Страницы-обработчики: выполняют запросы и включают нужный view.
$pageFile = __DIR__ . '/../src/pages/' . $page . '.php';
ob_start();
include $pageFile;
$content = ob_get_clean();

$stmt = db()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([(int)$user['id']]);
$unreadCount = (int)$stmt->fetchColumn();

$pageTitle = $pageTitle ?? APP_NAME;
include __DIR__ . '/../views/layout.php';