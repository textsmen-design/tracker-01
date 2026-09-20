<?php
/**
 * Кейс №1 — Страница проекта: вкладки Канбан / Задачи / Участники / Активность.
 */
declare(strict_types=1);

$pageTitle = 'Проект';
$myId     = (int)$user['id'];
$projectId = (int)($_GET['id'] ?? 0);

if ($projectId <= 0 || !can_view_project($user, $projectId)) {
    set_flash('error', 'Нет доступа к проекту.');
    redirect('index.php?page=projects');
}

$stmt = db()->prepare(
    "SELECT p.*, u.name AS m_name, u.surname AS m_surname FROM projects p
     JOIN users u ON u.id = p.manager_id WHERE p.id = ?"
);
$stmt->execute([$projectId]);
$project = $stmt->fetch() ?: null;
if (!$project) {
    set_flash('error', 'Проект не найден.');
    redirect('index.php?page=projects');
}
$pageTitle = $project['name'];

$tab = ($_GET['tab'] ?? 'kanban') === 'tasks' ? 'tasks' : (($_GET['tab'] ?? 'kanban') === 'members' ? 'members' : (($_GET['tab'] ?? 'kanban') === 'activity' ? 'activity' : 'kanban'));

/** Подзапрос исполнителя для карточек. */
$statuses = statuses();
$priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

$tasks = db()->prepare("SELECT * FROM tasks WHERE project_id = ?");
$tasks->execute([$projectId]);
$tasks = $tasks->fetchAll();
usort($tasks, fn(array $a, array $b): int => ($priorityOrder[$a['priority']] ?? 0) <=> ($priorityOrder[$b['priority']] ?? 0));

if ($tab === 'kanban') {
    $byStatus = [];
    foreach ($statuses as $k => $label) { $byStatus[$k] = []; }
    foreach ($tasks as $t) { $byStatus[$t['status']][] = $t; }
}

$members = db()->prepare(
    "SELECT u.id, u.surname, u.name, u.position, u.role
     FROM project_members pm JOIN users u ON u.id = pm.user_id
     WHERE pm.project_id = ? ORDER BY u.role, u.surname"
);
$members->execute([$projectId]);
$members = $members->fetchAll();

$activity = db()->prepare(
    "SELECT al.*, t.title AS task_title FROM activity_log al
     JOIN tasks t ON t.id = al.task_id WHERE t.project_id = ? ORDER BY al.created_at DESC LIMIT 30"
);
$activity->execute([$projectId]);
$activity = $activity->fetchAll();

$allUsers = db()->query("SELECT id, surname, name, role, position FROM users WHERE status='active' ORDER BY surname, name")->fetchAll();
$canManage = can_manage_tasks_in($user, $projectId);
?>
<div class="project-head">
    <h1 class="project-head__name"><?= e($project['name']) ?></h1>
    <p class="muted"><?= e((string)$project['description']) ?></p>
    <p class="muted">Руководитель: <?= e($project['m_surname'] . ' ' . $project['m_name']) ?> · Начало: <?= human_date($project['start_date']) ?> · Завершение: <?= human_date($project['end_date']) ?></p>
</div>

<div class="tabs">
    <a class="<?= $tab === 'kanban' ? 'is-active' : '' ?>" href="index.php?page=project&id=<?= $projectId ?>&tab=kanban">Канбан</a>
    <a class="<?= $tab === 'tasks' ? 'is-active' : '' ?>" href="index.php?page=project&id=<?= $projectId ?>&tab=tasks">Задачи</a>
    <a class="<?= $tab === 'members' ? 'is-active' : '' ?>" href="index.php?page=project&id=<?= $projectId ?>&tab=members">Участники</a>
    <a class="<?= $tab === 'activity' ? 'is-active' : '' ?>" href="index.php?page=project&id=<?= $projectId ?>&tab=activity">Активность</a>
    <?php if ($canManage): ?>
        <a class="tabs__action" href="index.php?page=task_form&project_id=<?= $projectId ?>">+ Новая задача</a>
    <?php endif; ?>
</div>

<?php if ($tab === 'kanban'): ?>
    <?php if (!$tasks): ?><p class="muted">В проекте пока нет задач.</p><?php endif; ?>
    <div class="board" data-project="<?= $projectId ?>">
        <?php foreach ($statuses as $k => $label): ?>
            <div class="board__col" data-status="<?= e($k) ?>">
                <h3><?= e($label) ?><span class="badge"><?= count($byStatus[$k]) ?></span></h3>
                <?php foreach ($byStatus[$k] as $t): ?>
                    <?php include __DIR__ . '/../partials/task_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($tab === 'tasks'): ?>
    <table class="table">
        <tr><th>Задача</th><th>Статус</th><th>Приоритет</th><th>Исполнитель</th><th>Дедлайн</th></tr>
        <?php foreach ($tasks as $t): ?>
            <tr class="<?= is_overdue($t) ? 'row-overdue' : '' ?>">
                <td><a class="task-link" href="index.php?page=task&id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a></td>
                <td><span class="pill pill--<?= e($t['status']) ?>"><?= e(status_label($t['status'])) ?></span></td>
                <td><span class="prio prio--<?= e($t['priority']) ?>"><?= e(priority_label($t['priority'])) ?></span></td>
                <td><?php
                    $ass = db()->prepare('SELECT surname, name FROM users WHERE id = ?');
                    $ass->execute([(int)$t['assignee_id']]);
                    $a = $ass->fetch();
                    echo $a ? e($a['surname'] . ' ' . $a['name']) : '—';
                    ?></td>
                <td><?= human_date($t['deadline']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if ($tab === 'members'): ?>
    <h2>Участники (<?= count($members) ?>)</h2>
    <table class="table">
        <tr><th>Сотрудник</th><th>Должность</th><th>Роль в проекте</th></tr>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= e($m['surname'] . ' ' . $m['name']) ?></td>
                <td><?= e($m['position']) ?></td>
                <td><?= (int)$m['id'] === (int)$project['manager_id'] ? 'Руководитель' : 'Участник' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($canManage): ?>
    <form class="card create-form__body" method="post" action="index.php?page=project">
        <input type="hidden" name="action" value="add_member">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">
        <label class="field"><span>Добавить участника</span>
            <select name="user_id">
                <?php foreach ($allUsers as $u):
                    $already = false;
                    foreach ($members as $m) { if ((int)$m['id'] === (int)$u['id']) { $already = true; break; } }
                    if ($already) continue; ?>
                    <option value="<?= (int)$u['id'] ?>"><?= e($u['surname'] . ' ' . $u['name'] . ($u['position'] ? ' — ' . $u['position'] : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn--primary" type="submit">Добавить</button>
    </form>
    <?php endif; ?>
<?php endif; ?>

<?php if ($tab === 'activity'): ?>
    <ul class="plain-list">
        <?php foreach ($activity as $a): ?>
            <li><strong><?= e($a['task_title']) ?></strong> — <?= e($a['action']) ?> <span class="muted">(<?= e((new DateTimeImmutable($a['created_at']))->format('d.m H:i')) ?>)</span></li>
        <?php endforeach; ?>
        <?php if (!$activity): ?><li class="muted">Активности пока нет.</li><?php endif; ?>
    </ul>
<?php endif; ?>