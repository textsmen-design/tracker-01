<?php
/**
 * Кейс №1 — Команда (tz.md 10.5): справочник сотрудников.
 */
declare(strict_types=1);

$pageTitle = 'Команда';
$myId = (int)$user['id'];

$team = db()->query(
    "SELECT u.id, u.surname, u.name, u.position, u.email, u.role, u.created_at,
        (SELECT COUNT(DISTINCT pm.project_id) FROM project_members pm WHERE pm.user_id = u.id) AS projects,
        (SELECT COUNT(*) FROM tasks t WHERE t.assignee_id = u.id AND t.status = 'done') AS done_count,
        (SELECT COUNT(*) FROM tasks t WHERE t.assignee_id = u.id AND t.status <> 'done') AS active_count
     FROM users u WHERE u.status = 'active' ORDER BY u.role, u.surname, u.name"
)->fetchAll();

$roleNames = ['admin' => 'Администратор', 'manager' => 'Руководитель', 'employee' => 'Сотрудник'];
?>
<table class="table">
    <tr>
        <th>Сотрудник</th><th>Должность</th><th>Роль</th><th>Проектов</th>
        <th>Активных задач</th><th>Выполнено</th>
    </tr>
    <?php foreach ($team as $m): ?>
        <tr>
            <td><strong><?= e($m['surname'] . ' ' . $m['name']) ?></strong><div class="muted"><?= e($m['email']) ?></div></td>
            <td><?= e($m['position']) ?></td>
            <td><span class="pill"><?= e($roleNames[$m['role']] ?? $m['role']) ?></span></td>
            <td><?= (int)$m['projects'] ?></td>
            <td><?= (int)$m['active_count'] ?></td>
            <td><?= (int)$m['done_count'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>