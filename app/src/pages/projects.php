<?php
/**
 * Кейс №1 — Проекты (tz.md 10.3).
 */
declare(strict_types=1);

$pageTitle = 'Проекты';
$myId = (int)$user['id'];

if ($user['role'] === 'admin') {
    $projects = db()->query(
        "SELECT p.*, u.name AS m_name, u.surname AS m_surname,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status <> 'done') AS open_tasks,
            (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS members
         FROM projects p JOIN users u ON u.id = p.manager_id ORDER BY p.created_at DESC"
    )->fetchAll();
} else {
    $stmt = db()->prepare(
        "SELECT p.*, u.name AS m_name, u.surname AS m_surname,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status <> 'done') AS open_tasks,
            (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS members
         FROM projects p JOIN users u ON u.id = p.manager_id
         JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
         ORDER BY p.created_at DESC"
    );
    $stmt->execute([$myId]);
    $projects = $stmt->fetchAll();
}

$projectStatuses = ['planned' => 'Планируется', 'active' => 'Активный', 'paused' => 'Приостановлен', 'completed' => 'Завершён', 'archived' => 'Архивный'];
?>
<div class="toolbar">
    <?php if (can_manage_projects($user)): ?>
        <details class="create-form">
            <summary class="btn btn--primary">Создать проект</summary>
            <form class="card create-form__body" method="post" action="index.php?page=projects">
                <input type="hidden" name="action" value="save_project">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="status" value="planned">
                <label class="field"><span>Название *</span><input type="text" name="name" required></label>
                <label class="field"><span>Описание</span><textarea name="description" rows="2"></textarea></label>
                <label class="field"><span>Руководитель *</span>
                    <select name="manager_id" required>
                        <?php
                        $mgr = db()->query("SELECT id, surname, name FROM users WHERE role IN ('admin','manager') AND status='active' ORDER BY surname, name");
                        foreach ($mgr as $m) { echo '<option value="' . (int)$m['id'] . '">' . e($m['surname'] . ' ' . $m['name']) . '</option>'; }
                        ?>
                    </select>
                </label>
                <label class="field"><span>Дата начала</span><input type="date" name="start_date"></label>
                <label class="field"><span>Плановая дата завершения</span><input type="date" name="end_date"></label>
                <button class="btn btn--primary" type="submit">Сохранить</button>
            </form>
        </details>
    <?php endif; ?>
</div>

<?php if (!$projects): ?><p class="muted">Нет доступных проектов.</p><?php endif; ?>
<div class="project-grid">
    <?php foreach ($projects as $p): ?>
        <a class="project-card" href="index.php?page=project&id=<?= (int)$p['id'] ?>">
            <div class="project-card__head">
                <strong><?= e($p['name']) ?></strong>
                <span class="pill pill--<?= e($p['status']) ?>"><?= e($projectStatuses[$p['status']] ?? $p['status']) ?></span>
            </div>
            <div class="project-card__desc"><?= e(mb_strimwidth((string)$p['description'], 0, 120, '…')) ?></div>
            <div class="project-card__meta">Руководитель: <?= e($p['m_surname'] . ' ' . $p['m_name']) ?></div>
            <div class="project-card__meta">Открытых задач: <?= (int)$p['open_tasks'] ?> · Участников: <?= (int)$p['members'] ?><?= $p['end_date'] ? ' · Завершение: ' . human_date($p['end_date']) : '' ?></div>
        </a>
    <?php endforeach; ?>
</div>