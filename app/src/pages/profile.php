<?php
/**
 * Кейс №1 — Профиль и смена пароля.
 */
declare(strict_types=1);

$pageTitle = 'Настройки';
?>
<form class="card create-form__body" method="post" action="index.php?page=profile">
    <input type="hidden" name="action" value="save_profile">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="form-grid">
        <label class="field"><span>Имя *</span><input type="text" name="name" required value="<?= e($user['name']) ?>"></label>
        <label class="field"><span>Фамилия *</span><input type="text" name="surname" required value="<?= e($user['surname']) ?>"></label>
    </div>
    <label class="field"><span>Должность</span><input type="text" name="position" value="<?= e($user['position']) ?>"></label>
    <p class="muted">Электронная почта: <?= e($user['email']) ?> · Роль: <?= e($user['role']) ?></p>

    <hr>
    <p><strong>Смена пароля</strong> (заполните, чтобы изменить)</p>
    <div class="form-grid">
        <label class="field"><span>Текущий пароль</span><input type="password" name="password_current" autocomplete="current-password"></label>
        <label class="field"><span>Новый пароль</span><input type="password" name="password" minlength="6" autocomplete="new-password"></label>
    </div>
    <button class="btn btn--primary" type="submit">Сохранить</button>
</form>