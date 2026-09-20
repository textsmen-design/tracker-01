<?php
/** Карточка задачи для канбан-доски (tz.md 12-13). */
$t = $t ?? []; /** @var array $t */
$editAllowed = (($user['role'] === 'manager' && $myId === $t['creator_id']) || $user['role'] === 'admin');
?>
<div class="task-card <?= is_overdue($t) ? 'task-card--overdue' : '' ?>" draggable="true" data-id="<?= (int)$t['id'] ?>"
     onclick="window.location='index.php?page=task&id=<?= (int)$t['id'] ?>'">
    <div class="task-card__top">
        <span class="prio prio--<?= e($t['priority']) ?>"><?= e(priority_label($t['priority'])) ?></span>
        <?php if ($t['deadline']): ?><span class="task-card__dl <?= is_overdue($t) ? 'text-danger' : '' ?>">до <?= human_date($t['deadline']) ?></span><?php endif; ?>
    </div>
    <div class="task-card__title"><?= e($t['title']) ?></div>
    <div class="task-card__meta">
        <?php
        $assign = db()->prepare('SELECT name, surname FROM users WHERE id = ?');
        $assign->execute([(int)$t['assignee_id']]);
        $a = $assign->fetch();
        echo $a ? 'Исп.: ' . e($a['surname'] . ' ' . mb_substr($a['name'], 0, 1) . '.') : '';
        $com = db()->prepare('SELECT COUNT(*) AS c FROM comments WHERE task_id = ?');
        $com->execute([(int)$t['id']]);
        $cc = (int)$com->fetchColumn();
        if ($cc > 0) { echo ' · 💬 ' . $cc; }
        $att = db()->prepare('SELECT COUNT(*) AS c FROM attachments WHERE task_id = ?');
        $att->execute([(int)$t['id']]);
        $attc = (int)$att->fetchColumn();
        if ($attc > 0) { echo ' · 📎 ' . $attc; }
        ?>
    </div>
</div>