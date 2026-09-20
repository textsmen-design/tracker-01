<?php
/**
 * Кейс №1 — Главная (tz.md 10.1).
 */
declare(strict_types=1);

$pageTitle = 'Главная';
$myId = (int)$user['id'];

$counts = db()->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status <> 'done') AS active,
        SUM(status <> 'done' AND deadline IS NOT NULL AND deadline < CURDATE()) AS overdue,
        SUM(status <> 'done' AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AS week
     FROM tasks WHERE assignee_id = {$myId}"
)->fetch();

$deadlines = db()->prepare(
    "SELECT t.*, p.name AS project_name FROM tasks t
     JOIN projects p ON p.id = t.project_id
     WHERE t.assignee_id = ? AND t.status <> 'done' AND t.deadline IS NOT NULL
     ORDER BY t.deadline ASC LIMIT 6"
);
$deadlines->execute([$myId]);
$deadlines = $deadlines->fetchAll();

$updates = db()->prepare(
    "SELECT al.*, t.title AS task_title FROM activity_log al
     JOIN tasks t ON t.id = al.task_id
     JOIN project_members pm ON pm.project_id = t.project_id AND pm.user_id = ?
     ORDER BY al.created_at DESC LIMIT 6"
);
$updates->execute([$myId]);
$updates = $updates->fetchAll();

$myProjects = db()->prepare(
    "SELECT p.*, u.surname AS m_surname, u.name AS m_name,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status <> 'done') AS open_tasks,
        (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS members
     FROM projects p
     JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
     JOIN users u ON u.id = p.manager_id
     ORDER BY p.created_at DESC"
);
$myProjects->execute([$myId]);
$myProjects = $myProjects->fetchAll();

// Сводка по команде — только для руководителя.
$teamSummary = [];
if ($user['role'] === 'manager') {
    $teamSummary = db()->query(
        "SELECT a.name, a.surname, a.position,
            SUM(a.status <> 'done') AS active,
            SUM(a.status <> 'done' AND a.deadline IS NOT NULL AND a.deadline < CURDATE()) AS overdue
         FROM (
            SELECT DISTINCT u.id, u.name, u.surname, u.position, t.status, t.deadline
            FROM users u
            LEFT JOIN tasks t ON t.assignee_id = u.id
            JOIN project_members pm ON pm.user_id = u.id
         ) a GROUP BY a.id, a.name, a.surname, a.position LIMIT 8
        "
    )->fetchAll();
}
?>
<div class="stat-grid">
    <div class="stat"><span class="stat__num"><?= (int)$counts['active'] ?></span>активных задач</div>
    <div class="stat stat--danger"><span class="stat__num"><?= (int)$counts['overdue'] ?></span>просрочено</div>
    <div class="stat"><span class="stat__num"><?= (int)$counts['week'] ?></span>на этой неделе</div>
</div>

<div class="cols">
    <div class="col">
        <h2>Ближайшие дедлайны</h2>
        <?php if (!$deadlines): ?><p class="muted">Нет ближайших дедлайнов.</p><?php endif; ?>
        <?php foreach ($deadlines as $t): ?>
            <a class="task-link <?= is_overdue($t) ? 'task-link--overdue' : '' ?>" href="index.php?page=task&id=<?= (int)$t['id'] ?>">
                <span><?= e($t['title']) ?></span>
                <span class="task-link__meta"><?= e($t['project_name']) ?> · <?= human_date($t['deadline']) ?> · <?= e(priority_label($t['priority'])) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="col">
        <h2>Мои проекты</h2>
        <?php if (!$myProjects): ?><p class="muted">Нет проектов.</p><?php endif; ?>
        <?php foreach ($myProjects as $p): ?>
            <a class="task-link" href="index.php?page=project&id=<?= (int)$p['id'] ?>">
                <span><?= e($p['name']) ?></span>
                <span class="task-link__meta"><?= e($p['m_surname'] . ' ' . $p['m_name']) ?> · задач: <?= (int)$p['open_tasks'] ?> · участников: <?= (int)$p['members'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="col">
    <h2>Последние обновления</h2>
    <?php if (!$updates): ?><p class="muted">Обновлений нет.</p><?php endif; ?>
    <ul class="plain-list">
        <?php foreach ($updates as $a): ?>
            <li><strong><?= e($a['task_title']) ?></strong> — <?= e($a['action']) ?> <span class="muted">(<?= e((new DateTimeImmutable($a['created_at']))->format('d.m H:i')) ?>)</span></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if (!empty($teamSummary)): ?>
<div class="col">
    <h2>Сводка по команде</h2>
    <table class="table">
        <tr><th>Сотрудник</th><th>Активных</th><th>Просрочено</th></tr>
        <?php foreach ($teamSummary as $m): ?>
            <tr>
                <td><?= e($m['surname'] . ' ' . $m['name']) ?><?= $m['position'] ? ' · ' . e($m['position']) : '' ?></td>
                <td><?= (int)$m['active'] ?></td>
                <td class="<?= (int)$m['overdue'] > 0 ? 'text-danger' : '' ?>"><?= (int)$m['overdue'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>