<?php
/**
 * Кейс №1 — Уведомления (tz.md 10.6).
 */
declare(strict_types=1);

$pageTitle = 'Уведомления';
$myId = (int)$user['id'];

$stmt = db()->prepare(
    "SELECT n.*, t.title AS task_title FROM notifications n
     LEFT JOIN tasks t ON t.id = n.task_id
     WHERE n.user_id = ? ORDER BY n.is_read ASC, n.created_at DESC LIMIT 100"
);
$stmt->execute([$myId]);
$notifs = $stmt->fetchAll();

$postUrl = 'index.php';
?>
<div class="toolbar">
    <?php if ($notifs): ?>
        <form method="post" action="<?= e($postUrl) ?>">
            <input type="hidden" name="action" value="read_notifications">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button class="btn" type="submit">Отметить всё прочитанным</button>
        </form>
    <?php endif; ?>
</div>

<ul class="plain-list">
    <?php foreach ($notifs as $n): ?>
        <li class="<?= $n['is_read'] ? 'muted' : '' ?>">
            <?php if ($n['task_id']): ?>
                <a class="task-link" href="index.php?page=task&id=<?= (int)$n['task_id'] ?>"><?= e($n['text']) ?></a>
            <?php else: ?>
                <?= e($n['text']) ?>
            <?php endif; ?>
            <span class="muted"> · <?= e((new DateTimeImmutable($n['created_at']))->format('d.m.Y H:i')) ?></span>
        </li>
    <?php endforeach; ?>
    <?php if (!$notifs): ?><li class="muted">Уведомлений нет.</li><?php endif; ?>
</ul>