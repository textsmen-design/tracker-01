<?php
/** Форма пользователя (создание/редактирование) — частичный шаблон. */
$editing = $editing ?? null;
$du = $editing ?? [];
?>
<form class="card create-form__body user-form" method="post" action="index.php?page=admin_users">
    <input type="hidden" name="action" value="save_user">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)($du['id'] ?? 0) ?>">
    <div class="form-grid">
        <label class="field"><span>Имя *</span><input type="text" name="name" required value="<?= e($du['name'] ?? '') ?>"></label>
        <label class="field"><span>Фамилия *</span><input type="text" name="surname" required value="<?= e($du['surname'] ?? '') ?>"></label>
        <label class="field"><span>Email *</span><input type="email" name="email" required value="<?= e($du['email'] ?? '') ?>"></label>
        <label class="field"><span>Должность</span><input type="text" name="position" value="<?= e($du['position'] ?? '') ?>"></label>
        <label class="field"><span>Роль *</span>
            <select name="role">
                <?php foreach ($roleNames as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($du['role'] ?? 'employee') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Статус *</span>
            <select name="status">
                <option value="active" <?= ($du['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>активен</option>
                <option value="blocked" <?= ($du['status'] ?? '') === 'blocked' ? 'selected' : '' ?>>заблокирован</option>
            </select>
        </label>
    </div>
    <label class="field">
        <span><?= $editing ? 'Новый пароль (пусто — не менять)' : 'Пароль (пусто — demo123)' ?></span>
        <input type="password" name="password" minlength="6" autocomplete="new-password">
    </label>
    <button class="btn btn--primary" type="submit">Сохранить</button>
    <?php if ($editing): ?>
        <a class="btn" href="index.php?page=admin_users">Отмена</a>
    <?php endif; ?>
</form>