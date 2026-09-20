<?php
/**
 * Кейс №1 — Мои задачи (tz.md 10.2): список/доска, фильтры, поиск.
 */
declare(strict_types=1);

$pageTitle = 'Мои задачи';
$myId = (int)$user['id'];

$fStatus = $_GET['status'] ?? '';
$fProject = (int)($_GET['project'] ?? 0);
$fPriority = $_GET['priority'] ?? '';
$fOverdue = ($_GET['overdue'] ?? '') === '1';
$q = trim((string)($_GET['q'] ?? ''));
$view = ($_GET['view'] ?? 'list') === 'board' ? 'board' : 'list';

$sql = "SELECT t.*, p.name AS project_name FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.assignee_id = ?";
$args = [$myId];
if ($fStatus !== '' && isset(statuses()[$fStatus])) { $sql .= ' AND t.status = ?'; $args[] = $fStatus; }
if ($fProject > 0) { $sql .= ' AND t.project_id = ?'; $args[] = $fProject; }
if ($fPriority !== '' && isset(priorities()[$fPriority])) { $sql .= ' AND t.priority = ?'; $args[] = $fPriority; }
if ($fOverdue) { $sql .= " AND t.status <> 'done' AND t.deadline IS NOT NULL AND t.deadline < CURDATE()"; }
if ($q !== '') { $sql .= ' AND t.title LIKE ?'; $args[] = '%' . $q . '%'; }
$sql .= ' ORDER BY t.deadline IS NULL, t.deadline ASC, t.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($args);
$tasks = $stmt->fetchAll();

$projects = db()->prepare('SELECT DISTINCT p.id, p.name FROM projects p JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? ORDER BY p.name');
$projects->execute([$myId]);
$projects = $projects->fetchAll();
$statuses = statuses();
$priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

/** Канбан-колонки для "доски" (tz.md 12). */
usort($tasks, fn(array $a, array $b): int => ($priorityOrder[$a['priority']] ?? 0) <=> ($priorityOrder[$b['priority']] ?? 0));
?>
<form class="filters" method="get" action="index.php">
    <input type="hidden" name="page" value="my_tasks">
    <input type="text" name="q" placeholder="Поиск по названию" value="<?= e($q) ?>">
    <select name="status">
        <option value="">Статус: все</option>
        <?php foreach ($statuses as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="project">
        <option value="0">Проект: все</option>
        <?php foreach ($projects as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $fProject === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="priority">
        <option value="">Приоритет: все</option>
        <?php foreach (priorities() as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $fPriority === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="filters__check"><input type="checkbox" name="overdue" value="1" <?= $fOverdue ? 'checked' : '' ?>> только просроченные</label>
    <label class="filters__check">
        Вид:
        <select name="view" onchange="this.form.submit()">
            <option value="list" <?= $view === 'list' ? 'selected' : '' ?>>список</option>
            <option value="board" <?= $view === 'board' ? 'selected' : '' ?>>доска</option>
        </select>
    </label>
    <button class="btn" type="submit">Применить</button>
</form>

<?php if ($view === 'board'): ?>
    <?php
    $byStatus = [];
    foreach ($statuses as $k => $label) { $byStatus[$k] = []; }
    foreach ($tasks as $t) { $byStatus[$t['status']][] = $t; }
    ?>
    <div class="board" id="myBoard">
        <?php foreach ($statuses as $k => $label): ?>
            <div class="board__col" data-status="<?= e($k) ?>">
                <h3><?= e($label) ?><span class="badge"><?= count($byStatus[$k]) ?></span></h3>
                <?php foreach ($byStatus[$k] as $t): ?>
                    <?php include __DIR__ . '/../partials/task_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <table class="table">
        <tr>
            <th>Задача</th><th>Проект</th><th>Статус</th><th>Приоритет</th><th>Дедлайн</th><th>Постановщик</th>
        </tr>
        <?php if (!$tasks): ?><tr><td colspan="6" class="muted">Задач не найдено.</td></tr><?php endif; ?>
        <?php foreach ($tasks as $t): ?>
            <tr class="<?= is_overdue($t) ? 'row-overdue' : '' ?>">
                <td><a class="task-link" href="index.php?page=task&id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a></td>
                <td><?= e($t['project_name']) ?></td>
                <td><span class="pill pill--<?= e($t['status']) ?>"><?= e(status_label($t['status'])) ?></span></td>
                <td><span class="prio prio--<?= e($t['priority']) ?>"><?= e(priority_label($t['priority'])) ?></span></td>
                <td><?= human_date($t['deadline']) ?></td>
                <td><?php
                    $creator = db()->prepare('SELECT surname, name FROM users WHERE id = ?');
                    $creator->execute([(int)$t['creator_id']]);
                    $c = $creator->fetch();
                    echo $c ? e($c['surname'] . ' ' . $c['name']) : '—';
                    ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>