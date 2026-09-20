<?php
/**
 * Tracker_01 — проверка прав по ролям (tz.md разд. 7.1 «Матрица прав»).
 * Роли: admin / manager / employee.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function role_of(array $user): string
{
    return $user['role'];
}

function is_admin(array $user): bool
{
    return role_of($user) === 'admin';
}

/** Пользователь видит проект, если он его участник или админ/менеджер(который видит свои). */
function can_view_project(array $user, int $projectId): bool
{
    if (is_admin($user)) {
        return true;
    }
    $stmt = db()->prepare(
        'SELECT 1 FROM project_members pm
         WHERE pm.project_id = ? AND pm.user_id = ?'
    );
    $stmt->execute([$projectId, (int)$user['id']]);
    return (bool)$stmt->fetchColumn();
}

/** Управление проектом (создание/редактирование/архивация). */
function can_manage_projects(array $user): bool
{
    return is_admin($user);
}

/**
 * Пользователь видит задачу, если видит её проект, либо он исполнитель/постановщик.
 * Благодаря этому задача, назначенная сотруднику, всегда открывается без «тупика».
 */
function can_view_task(array $user, array $task): bool
{
    if (can_view_project($user, (int)$task['project_id'])) {
        return true;
    }
    $id = (int)$user['id'];
    return (int)$task['assignee_id'] === $id || (int)$task['creator_id'] === $id;
}

/** Управлять задачами в проекте: админ (все) или менеджер, который входит в проект. */
function can_manage_tasks_in(array $user, int $projectId): bool
{
    if (is_admin($user)) {
        return true;
    }
    return role_of($user) === 'manager' && can_view_project($user, $projectId);
}

/** Изменять статус задачи: админ/менеджер (проект) или сотрудник — исполнитель. */
function can_change_task_status(array $user, array $task): bool
{
    if (can_manage_tasks_in($user, (int)$task['project_id'])) {
        return true;
    }
    return (int)$task['assignee_id'] === (int)$user['id'];
}

/** Редактировать комментарий: свой; админ — любой. */
function can_edit_comment(array $user, array $comment): bool
{
    return is_admin($user) || (int)$comment['author_id'] === (int)$user['id'];
}

/** Управление пользователями — только админ. */
function can_manage_users(array $user): bool
{
    return is_admin($user);
}

/** Сотрудник может создавать задачи в проекте, если он его участник. */
function can_create_task_in(array $user, int $projectId): bool
{
    return can_view_project($user, $projectId);
}