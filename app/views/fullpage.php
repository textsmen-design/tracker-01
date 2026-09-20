<?php /* Общий каркас для страниц без бокового меню (логин, 404). */ ?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="fullpage">
<main class="fullpage__inner">
    <?php foreach ($flash as $f): ?>
        <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['text']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>