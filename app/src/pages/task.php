<?php
/**
 * Кейс №1 — Карточка задачи + комментарии + история (tz.md 10.4).
 */
declare(strict_types=1);

$pageTitle = 'Задача';
$myId = (int)$user['id'];
$taskId = (int)($_GET['id'] ?? 0);
$task = find_task($taskId);
if (!$task) {
    set_flash('error', 'Задача не найдена.');
    redirect('index.php?page=projects');
}
if (!can_view_task($user, $task)) {
    set_flash('error', 'Нет доступа к задаче.');
    redirect('index.php?page=projects');
}
$canViewProject = can_view_project($user, (int)$task['project_id']);
$pageTitle = $task['title'];

$project = db()->prepare('SELECT * FROM projects WHERE id = ?');
$project->execute([(int)$task['project_id']]);
$project = $project->fetch();

$asg = db()->prepare('SELECT * FROM users WHERE id = ?');
$asg->execute([(int)$task['assignee_id']]);
$asg = $asg->fetch();
$cr = db()->prepare('SELECT * FROM users WHERE id = ?');
$cr->execute([(int)$task['creator_id']]);
$cr = $cr->fetch();

$history = db()->prepare(
    "SELECT al.*, u.surname, u.name FROM activity_log al JOIN users u ON u.id = al.user_id
     WHERE al.task_id = ? ORDER BY al.created_at DESC LIMIT 30"
);
$history->execute([$taskId]);
$history = $history->fetchAll();

$comments = db()->prepare(
    "SELECT c.*, u.surname, u.name FROM comments c JOIN users u ON u.id = c.author_id WHERE c.task_id = ? ORDER BY c.created_at ASC"
);
$comments->execute([$taskId]);
$comments = $comments->fetchAll();

$canEdit   = can_manage_tasks_in($user, (int)$task['project_id']);
$canStatus = can_change_task_status($user, $task);

$statuses = statuses();
$workUsers = db()->query("SELECT id, surname, name, position, role FROM users WHERE status='active' ORDER BY surname, name")->fetchAll();
?>
<div class="task-sheet">
    <div class="task-sheet__head">
        <h1><?= e($task['title']) ?></h1>
        <div class="task-sheet__tags">
            <span class="pill pill--<?= e($task['status']) ?>"><?= e(status_label($task['status'])) ?></span>
            <span class="prio prio--<?= e($task['priority']) ?>"><?= e(priority_label($task['priority'])) ?></span>
            <?php if ($task['tags']): ?><span class="pill">#<?= e(str_replace(',', ' #', (string)$task['tags'])) ?></span><?php endif; ?>
        </div>
        <?php if (!$canStatus): ?><p class="muted">Статус меняет исполнитель, руководитель или админ.</p><?php endif; ?>
    </div>

    <div class="cols">
        <div class="col col--wide">
            <h2>Описание</h2>
            <p class="prewrap"><?= e((string)$task['description']) ?: '<span class="muted">Описание не задано.</span>' ?></p>

            <h2>Комментарии</h2>
            <form class="card create-form__body" method="post" action="index.php?page=task&id=<?= $taskId ?>">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                <label class="field"><span>Новый комментарий</span><textarea name="text" rows="2" required></textarea></label>
                <button class="btn btn--primary" type="submit">Отправить</button>
            </form>
            <div class="comments">
                <?php if (!$comments): ?><p class="muted">Комментариев пока нет.</p><?php endif; ?>
                <?php foreach ($comments as $c): ?>
                    <div class="comment">
                        <div class="comment__head">
                            <strong><?= e($c['surname'] . ' ' . $c['name']) ?></strong>
                            <span class="muted"><?= e((new DateTimeImmutable($c['created_at']))->format('d.m.Y H:i')) ?></span>
                            <?php if (can_edit_comment($user, $c)): ?>
                                <details class="comment-edit">
                                    <summary class="linklike">изменить</summary>
                                    <form class="card create-form__body" method="post" action="index.php?page=task&id=<?= (int)$c['task_id'] ?>">
                                        <input type="hidden" name="action" value="edit_comment">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                        <label class="field"><span>Текст комментария</span><textarea name="text" rows="2" required><?= e($c['text']) ?></textarea></label>
                                        <button class="btn btn--primary" type="submit">Сохранить</button>
                                    </form>
                                </details>
                                <form class="inline" method="post" action="index.php">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <button class="linklike linklike--danger" type="submit">удалить</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="prewrap"><?= e($c['text']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col col--side">
            <dl class="kv">
                <dt>Проект</dt><dd><?php if ($canViewProject): ?><a href="index.php?page=project&id=<?= (int)$project['id'] ?>"><?= e($project['name']) ?></a><?php else: ?><?= e($project['name']) ?><?php endif; ?></dd>
                <dt>Статус</dt>
                <dd>
                    <?php if ($canStatus): ?>
                        <form method="post" action="index.php" class="inline">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="task_id" value="<?= $taskId ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach ($statuses as $k => $label): ?>
                                    <option value="<?= e($k) ?>" <?= $task['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <?= e(status_label($task['status'])) ?>
                    <?php endif; ?>
                </dd>
                <dt>Исполнитель</dt><dd><?= $asg ? e($asg['surname'] . ' ' . $asg['name']) : 'Не назначен' ?></dd>
                <dt>Постановщик</dt><dd><?= $cr ? e($cr['surname'] . ' ' . $cr['name']) : '—' ?></dd>
                <dt>Приоритет</dt><dd><?= e(priority_label($task['priority'])) ?></dd>
                <dt>Дедлайн</dt><dd class="<?= is_overdue($task) ? 'text-danger' : '' ?>"><?= human_date($task['deadline']) ?></dd>
                <dt>Создана</dt><dd><?= e((new DateTimeImmutable($task['created_at']))->format('d.m.Y H:i')) ?></dd>
            </dl>

            <?php if ($canEdit): ?>
                <a class="btn btn--primary btn--block" href="index.php?page=task_form&id=<?= $taskId ?>">Редактировать</a>
            <?php endif; ?>

            <h2>История изменений</h2>
            <ul class="plain-list plain-list--small">
                <?php foreach ($history as $h): ?>
                    <li><?= e($h['surname'] . ' ' . mb_substr($h['name'], 0, 1) . '.: ' . $h['action']) ?> <span class="muted">(<?= e((new DateTimeImmutable($h['created_at']))->format('d.m H:i')) ?>)</span></li>
                <?php endforeach; ?>
                <?php if (!$history): ?><li class="muted">Истории нет.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>