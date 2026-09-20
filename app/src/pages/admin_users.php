<?php
/**
 * Кейс №1 — Админ: пользователи (tz.md 10.7). Только для admin.
 */
declare(strict_types=1);

if (!can_manage_users($user)) {
    set_flash('error', 'Нет доступа.');
    redirect('index.php?page=dashboard');
}
$pageTitle = 'Пользователи';

$users = db()->query(
    "SELECT * FROM users ORDER BY role, surname, name"
)->fetchAll();

$roleNames = ['admin' => 'Администратор', 'manager' => 'Руководитель', 'employee' => 'Сотрудник'];
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
foreach ($users as $u) { if ((int)$u['id'] === $editId) { $editing = $u; break; } }
?>
<div class="toolbar">
    <details class="create-form">
        <summary class="btn btn--primary">Добавить пользователя</summary>
        <?php include __DIR__ . '/../partials/user_form.php'; ?>
    </details>
</div>

<table class="table">
    <tr><th>Сотрудник</th><th>Email</th><th>Должность</th><th>Роль</th><th>Статус</th><th></th></tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['surname'] . ' ' . $u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['position']) ?></td>
            <td><?= e($roleNames[$u['role']] ?? $u['role']) ?></td>
            <td>
                <?php if ($u['status'] === 'active'): ?><span class="pill pill--done">активен</span>
                <?php else: ?><span class="pill"><?= e($u['status']) ?></span><?php endif; ?>
            </td>
            <td><a class="linklike" href="index.php?page=admin_users&edit=<?= (int)$u['id'] ?>">изменить</a></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if ($editing): ?>
    <h2>Редактировать: <?= e($editing['surname'] . ' ' . $editing['name']) ?></h2>
    <?php include __DIR__ . '/../partials/user_form.php'; ?>
<?php endif; ?>