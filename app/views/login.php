<section class="login-card">
    <div class="login-brand">
        <img class="login-brand__mark" src="assets/img/logo-mark.svg" alt="" width="52" height="52">
        <span class="login-brand__name"><?= e(APP_NAME) ?><small>pet team · cтопа</small></span>
    </div>
    <p class="login-card__sub">Трекер задач для команды по выгулу и уходу за питомцами</p>
    <form method="post" action="index.php?page=login">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field">
            <span>Электронная почта</span>
            <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </label>
        <label class="field">
            <span>Пароль</span>
            <input type="password" name="password" required>
        </label>
        <button class="btn btn--primary btn--block" type="submit">Войти</button>
    </form>
    <p class="login-card__hint">Демо-доступы: anna@demo.ru (админ), maxim@demo.ru (руководитель), elena@demo.ru (сотрудник). Пароль у всех: <code>demo123</code></p>
</section>