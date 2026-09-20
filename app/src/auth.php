<?php
/**
 * Tracker_01 — аутентификация и роли.
 * Авторизация по email+паролю; сессия сохраняется после обновления страницы.
 * Саморегистрации нет: пользователей создаёт администратор (tz.md разд. 9).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = (int)($_SESSION['user_id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND status = \'active\'');
            $stmt->execute([$id]);
            $user = $stmt->fetch() ?: null;
        } else {
            $user = null;
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('index.php?page=login');
    }
    return $user;
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function try_login(string $email, string $password): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND status = \'active\'');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        return $user;
    }
    return null;
}