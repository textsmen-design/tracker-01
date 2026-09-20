<?php
/**
 * Кейс №1 — Форма создания/редактирования задачи (tz.md 13).
 */
declare(strict_types=1);

$pageTitle = 'Новая задача';
$myId = (int)$user['id'];
$taskId = (int)($_GET['id'] ?? 0);
$presetProject = (int)($_GET['project_id'] ?? 0);

$task = null;
if ($taskId > 0) {
    $task = find_task($taskId);
    if (!$task || !can_manage_tasks_in($user, (int)$task['project_id'])) {
        set_flash('error', 'Нет прав на редактирование этой задачи.');
        redirect('index.php?page=projects');
    }
    $pageTitle = 'Редактирование задачи';
}

/** Доступные проекты: свои + (для админа/менеджера) все. */
if (can_manage_projects($user)) {
    $projects = db()->query("SELECT * FROM projects WHERE status <> 'archived' ORDER BY name")->fetchAll();
} else {
    $stmt = db()->prepare(
        "SELECT p.* FROM projects p JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
         WHERE p.status <> 'archived' ORDER BY p.name"
    );
    $stmt->execute([$myId]);
    $projects = $stmt->fetchAll();
}

$projectId = $task ? (int)$task['project_id'] : $presetProject;

/** Создавать задачи может админ или руководитель в своём проекте; сотруднику форма недоступна. */
if (!$task) {
    $canAny = false;
    foreach ($projects as $p) { if (can_manage_tasks_in($user, (int)$p['id'])) { $canAny = true; break; } }
    if (!$canAny) {
        set_flash('error', 'Нет прав на создание задач.');
        redirect('index.php?page=projects');
    }
}

/** MVP: исполнителем может стать любой активный пользователь; фильтр по участникам проекта — можно добавить позже. */
$assignees = db()->query("SELECT id, surname, name, position, role FROM users WHERE status='active' ORDER BY surname, name")->fetchAll();

$today = today()->format('Y-m-d');
?>
<form class="card" method="post" action="index.php?page=task_form">
    <input type="hidden" name="action" value="save_task">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $taskId ?>">

    <label class="field"><span>Проект *</span>
        <select name="project_id" required>
            <option value="0">— выберите проект —</option>
            <?php foreach ($projects as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= $projectId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="field"><span>Название задачи *</span>
        <input type="text" name="title" required maxlength="200" value="<?= e($task['title'] ?? '') ?>">
    </label>

    <label class="field"><span>Описание</span>
        <textarea name="description" rows="5"><?= e($task['description'] ?? '') ?></textarea>
    </label>

    <div class="form-grid">
        <label class="field"><span>Исполнитель</span>
            <select name="assignee_id">
                <option value="0">— не назначать —</option>
                <?php foreach ($assignees as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= ($task['assignee_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>>
                        <?= e($a['surname'] . ' ' . $a['name'] . ($a['position'] ? ' — ' . $a['position'] : '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Статус</span>
            <select name="status">
                <?php foreach (statuses() as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($task['status'] ?? 'backlog') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Приоритет</span>
            <select name="priority">
                <?php foreach (priorities() as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($task['priority'] ?? 'medium') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Дедлайн</span>
            <input type="date" name="deadline" min="<?= e($today) ?>" value="<?= e($task['deadline'] ?? '') ?>">
        </label>
    </div>

    <label class="field"><span>Теги (через запятую)</span>
        <input type="text" name="tags" maxlength="200" placeholder="срочно, верстка" value="<?= e($task['tags'] ?? '') ?>">
    </label>

    <div class="toolbar">
        <button class="btn btn--primary" type="submit"><?= $task ? 'Сохранить изменения' : 'Создать задачу' ?></button>
        <a class="btn" href="<?= $task ? 'index.php?page=task&id=' . $taskId : 'index.php?page=projects' ?>">Отмена</a>
    </div>
</form>