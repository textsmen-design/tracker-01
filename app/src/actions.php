<?php
/**
 * Tracker_01 — обработка POST/AJAX-действий: вход, задачи, статусы,
 * комментарии, уведомления, пользователи, профиль.
 */
declare(strict_types=1);

function handle_action(string $action): void
{
    $user = current_user();

    switch ($action) {
        case 'login':
            $ok = try_login(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''));
            if ($ok) {
                redirect('index.php?page=dashboard');
            }
            set_flash('error', 'Неверный логин или пароль.');
            redirect('index.php?page=login');
            // no break

        case 'logout':
            logout();
            redirect('index.php?page=login');
            // no break

        case 'save_task':
            require_login();
            csrf_verify();
            save_task($user);
            // no break

        case 'change_status':
            require_login();
            csrf_verify();
            change_task_status_ajax($user);
            // no break

        case 'add_comment':
            require_login();
            csrf_verify();
            add_comment($user);
            // no break

        case 'delete_comment':
            require_login();
            csrf_verify();
            delete_comment($user);
            // no break

        case 'edit_comment':
            require_login();
            csrf_verify();
            edit_comment($user);
            // no break

        case 'read_notifications':
            require_login();
            csrf_verify();
            db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')
                ->execute([(int)$user['id']]);
            redirect('index.php?page=notifications');
            // no break

        case 'save_user':
            require_login();
            csrf_verify();
            save_user($user);
            // no break

        case 'save_profile':
            require_login();
            csrf_verify();
            save_profile($user);
            // no break

        case 'save_project':
            require_login();
            csrf_verify();
            save_project($user);
            // no break

        case 'add_member':
            require_login();
            csrf_verify();
            add_member($user);
            // no break
    }
    redirect('index.php');
}

function save_task(array $user): void
{
    $id        = (int)($_POST['id'] ?? 0);
    $projectId = (int)($_POST['project_id'] ?? 0);

    if ($projectId <= 0 || !can_manage_tasks_in($user, $projectId)) {
        set_flash('error', 'Нет прав на работу с задачами этого проекта.');
        redirect('index.php?page=projects');
    }

    $title    = trim((string)($_POST['title'] ?? ''));
    $assignee = (int)($_POST['assignee_id'] ?? 0);
    $status   = (string)($_POST['status'] ?? 'backlog');
    $priority = (string)($_POST['priority'] ?? 'medium');
    $deadline = $_POST['deadline'] ?? null ?: null;
    $tags     = trim((string)($_POST['tags'] ?? ''));

    $allowedStatus = ['backlog','planned','in_progress','review','done'];
    $allowedPriority = ['low','medium','high','critical'];
    if ($title === '' || !in_array($status, $allowedStatus, true) || !in_array($priority, $allowedPriority, true)) {
        set_flash('error', 'Заполните обязательные поля корректно.');
        redirect('index.php?page=task_form' . ($id ? '&id=' . $id : '') . '&project_id=' . $projectId);
    }
    if (!valid_date($deadline)) {
        set_flash('error', 'Некорректная дата дедлайна.');
        redirect('index.php?page=task_form' . ($id ? '&id=' . $id : '') . '&project_id=' . $projectId);
    }

    $pdo = db();
    if ($id > 0) {
        $task = find_task($id);
        if (!$task || !can_manage_tasks_in($user, (int)$task['project_id'])) {
            set_flash('error', 'Нет прав на редактирование этой задачи.');
            redirect('index.php?page=projects');
        }
        $old = $task;
        $pdo->prepare(
            'UPDATE tasks SET title=?, description=?, project_id=?, assignee_id=?, status=?, priority=?, deadline=?, tags=?
             WHERE id=?'
        )->execute([$title, (string)($_POST['description'] ?? ''), $projectId, $assignee, $status, $priority, $deadline, $tags, $id]);

        log_changes($id, $user, $old, [
            'title' => $title, 'project_id' => $projectId, 'assignee_id' => $assignee,
            'status' => $status, 'priority' => $priority, 'deadline' => $deadline, 'tags' => $tags,
        ]);
        notify_assignee($id, $assignee, (int)$old['assignee_id'], "Изменена задача: {$title}");
        if ($deadline !== (string)($old['deadline'] ?? '') && $assignee > 0 && $assignee !== (int)$user['id']) {
            db()->prepare('INSERT INTO notifications (user_id, task_id, type, text) VALUES (?,?,?,?)')
                ->execute([$assignee, $id, 'deadline', "Изменён срок задачи: {$title}"]);
        }
        set_flash('success', 'Задача обновлена.');
    } else {
        $pdo->prepare(
            'INSERT INTO tasks (title, description, project_id, creator_id, assignee_id, status, priority, deadline, tags)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$title, (string)($_POST['description'] ?? ''), $projectId, (int)$user['id'], $assignee, $status, $priority, $deadline, $tags]);
        $taskId = (int)$pdo->lastInsertId();
        log_simple($taskId, $user, 'Создана задача');
        notify_assignee($taskId, $assignee, 0, "Назначена новая задача: {$title}");
        set_flash('success', 'Задача создана.');
    }
    redirect('index.php?page=project&id=' . $projectId . '&tab=kanban');
}

function change_task_status_ajax(array $user): void
{
    $id  = (int)($_POST['task_id'] ?? 0);
    $new = (string)($_POST['status'] ?? '');
    $allowed = ['backlog','planned','in_progress','review','done'];
    $task = find_task($id);
    if (!$task || !in_array($new, $allowed, true) || !can_change_task_status($user, $task)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Нет прав']);
        exit;
    }
    db()->prepare('UPDATE tasks SET status = ? WHERE id = ?')->execute([$new, $id]);
    log_changes($id, $user, $task, ['status' => $new]);
    if ((int)$task['assignee_id'] > 0 && (int)$task['assignee_id'] !== (int)$user['id']) {
        db()->prepare('INSERT INTO notifications (user_id, task_id, type, text) VALUES (?,?,?,?)')
            ->execute([(int)$task['assignee_id'], $id, 'status', 'Изменён статус задачи: ' . $task['title']]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

function add_comment(array $user): void
{
    $id  = (int)($_POST['task_id'] ?? 0);
    $text = trim((string)($_POST['text'] ?? ''));
    $task = find_task($id);
    if (!$task || !can_view_task($user, $task)) {
        set_flash('error', 'Нет доступа к задаче.');
        redirect('index.php?page=projects');
    }
    if ($text === '') {
        set_flash('error', 'Комментарий не может быть пустым.');
        redirect('index.php?page=task&id=' . $id);
    }
    db()->prepare('INSERT INTO comments (task_id, author_id, text) VALUES (?, ?, ?)')
        ->execute([$id, (int)$user['id'], $text]);
    $pdo = db();
    $pdo->prepare('INSERT INTO notifications (user_id, task_id, type, text) SELECT DISTINCT assignee_id, ?, \'comment\', ? FROM tasks WHERE id = ? AND assignee_id <> ?')
        ->execute([$id, 'Новый комментарий в задаче: ' . $task['title'], $id, (int)$user['id']]);
    set_flash('success', 'Комментарий добавлен.');
    redirect('index.php?page=task&id=' . $id);
}

function delete_comment(array $user): void
{
    $commentId = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM comments WHERE id = ?');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    if ($comment && can_edit_comment($user, $comment)) {
        db()->prepare('DELETE FROM comments WHERE id = ?')->execute([$commentId]);
        set_flash('success', 'Комментарий удалён.');
        redirect('index.php?page=task&id=' . (int)$comment['task_id']);
    }
    set_flash('error', 'Нет прав на удаление комментария.');
    redirect('index.php?page=dashboard');
}

function edit_comment(array $user): void
{
    $commentId = (int)($_POST['id'] ?? 0);
    $text      = trim((string)($_POST['text'] ?? ''));
    $stmt = db()->prepare('SELECT * FROM comments WHERE id = ?');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    if (!$comment || !can_edit_comment($user, $comment)) {
        set_flash('error', 'Нет прав на редактирование комментария.');
        redirect('index.php?page=dashboard');
    }
    if ($text === '') {
        set_flash('error', 'Комментарий не может быть пустым.');
        redirect('index.php?page=task&id=' . (int)$comment['task_id']);
    }
    db()->prepare('UPDATE comments SET text = ? WHERE id = ?')->execute([$text, $commentId]);
    log_simple((int)$comment['task_id'], $user, 'Обновлён комментарий');
    set_flash('success', 'Комментарий обновлён.');
    redirect('index.php?page=task&id=' . (int)$comment['task_id']);
}

function save_user(array $user): void
{
    if (!can_manage_users($user)) {
        set_flash('error', 'Нет доступа.');
        redirect('index.php?page=dashboard');
    }
    $pdo    = db();
    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim((string)($_POST['name'] ?? ''));
    $surname = trim((string)($_POST['surname'] ?? ''));
    $pos    = trim((string)($_POST['position'] ?? ''));
    $email  = trim((string)($_POST['email'] ?? ''));
    $role   = (string)($_POST['role'] ?? 'employee');
    $status = (string)($_POST['status'] ?? 'active');
    $pass   = (string)($_POST['password'] ?? '');

    if ($name === '' || $surname === '' || $email === '' || !in_array($role, ['admin','manager','employee'], true)) {
        set_flash('error', 'Некорректные данные пользователя.');
        redirect('index.php?page=admin_users');
    }

    try {
        if ($id > 0) {
            $sql = 'UPDATE users SET name=?, surname=?, position=?, email=?, role=?, status=?';
            $args = [$name, $surname, $pos, $email, $role, $status];
            if ($pass !== '') {
                $sql .= ', password_hash=?';
                $args[] = password_hash($pass, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=?';
            $args[] = $id;
            $pdo->prepare($sql)->execute($args);
        } else {
            if ($pass === '') {
                $pass = 'demo123';
            }
            $pdo->prepare('INSERT INTO users (name, surname, position, email, password_hash, role, status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$name, $surname, $pos, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $status]);
        }
    } catch (PDOException $ex) {
        if ((string)$ex->getCode() === '23000') {
            set_flash('error', 'Такой email уже используется другим пользователем.');
        } else {
            set_flash('error', 'Не удалось сохранить пользователя.');
        }
        redirect('index.php?page=admin_users' . ($id > 0 ? '&edit=' . $id : ''));
    }
    set_flash('success', 'Сохранено.');
    redirect('index.php?page=admin_users');
}

function save_profile(array $user): void
{
    $name    = trim((string)($_POST['name'] ?? ''));
    $surname = trim((string)($_POST['surname'] ?? ''));
    $pos     = trim((string)($_POST['position'] ?? ''));
    $passNew = (string)($_POST['password'] ?? '');
    $passCur = (string)($_POST['password_current'] ?? '');

    if ($name === '' || $surname === '') {
        set_flash('error', 'Имя и фамилия обязательны.');
        redirect('index.php?page=profile');
    }
    $pdo = db();
    $pdo->prepare('UPDATE users SET name=?, surname=?, position=? WHERE id=?')
        ->execute([$name, $surname, $pos, (int)$user['id']]);
    if ($passNew !== '') {
        if (!password_verify($passCur, $user['password_hash'])) {
            set_flash('error', 'Текущий пароль указан неверно.');
            redirect('index.php?page=profile');
        }
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
            ->execute([password_hash($passNew, PASSWORD_DEFAULT), (int)$user['id']]);
    }
    set_flash('success', 'Профиль обновлён.');
    redirect('index.php?page=profile');
}

function log_simple(int $taskId, array $user, string $action): void
{
    db()->prepare('INSERT INTO activity_log (task_id, user_id, action) VALUES (?,?,?)')
        ->execute([$taskId, (int)$user['id'], $action]);
}

function log_changes(int $taskId, array $user, array $old, array $new): void
{
    $map = [
        'title' => 'Название', 'project_id' => 'Проект', 'assignee_id' => 'Исполнитель',
        'status' => 'Статус', 'priority' => 'Приоритет', 'deadline' => 'Дедлайн', 'tags' => 'Теги',
    ];
    foreach ($map as $key => $label) {
        if ((string)($old[$key] ?? '') !== (string)($new[$key] ?? '')) {
            $val = $new[$key] ?? '';
            log_simple($taskId, $user, "{$label}: {$val}");
        }
    }
}

function notify_assignee(int $taskId, int $assignee, int $oldAssignee, string $text): void
{
    if ($assignee <= 0 || $assignee === $oldAssignee) {
        return;
    }
    db()->prepare('INSERT INTO notifications (user_id, task_id, type, text) VALUES (?,?,?,?)')
        ->execute([$assignee, $taskId, 'assigned', $text]);
}

function save_project(array $user): void
{
    if (!can_manage_projects($user)) {
        set_flash('error', 'Нет доступа.');
        redirect('index.php?page=projects');
    }
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim((string)($_POST['name'] ?? ''));
    $descr = trim((string)($_POST['description'] ?? ''));
    $mgr   = (int)($_POST['manager_id'] ?? 0);
    $start = $_POST['start_date'] ?? null ?: null;
    $end   = $_POST['end_date'] ?? null ?: null;
    $st    = (string)($_POST['status'] ?? 'planned');
    $allowedProjectStatuses = ['planned', 'active', 'paused', 'completed', 'archived'];

    if ($name === '' || $mgr <= 0 || !in_array($st, $allowedProjectStatuses, true)) {
        set_flash('error', 'Заполните название и руководителя проекта.');
        redirect('index.php?page=projects');
    }
    if (!valid_date($start) || !valid_date($end)) {
        set_flash('error', 'Некорректная дата проекта.');
        redirect('index.php?page=projects');
    }

    $pdo = db();
    if ($id > 0) {
        $pdo->prepare('UPDATE projects SET name=?, description=?, manager_id=?, start_date=?, end_date=?, status=? WHERE id=?')
            ->execute([$name, $descr, $mgr, $start, $end, $st, $id]);
        set_flash('success', 'Проект обновлён.');
    } else {
        $pdo->prepare('INSERT INTO projects (name, description, manager_id, start_date, end_date, status) VALUES (?,?,?,?,?,?)')
            ->execute([$name, $descr, $mgr, $start, $end, $st]);
        $projectId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO project_members (project_id, user_id) VALUES (?,?)')
            ->execute([$projectId, (int)$user['id']]);
        if ($mgr !== (int)$user['id']) {
            $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?,?)')
                ->execute([$projectId, $mgr]);
        }
        set_flash('success', 'Проект создан.');
    }
    redirect('index.php?page=projects');
}

function add_member(array $user): void
{
    $projectId = (int)($_POST['project_id'] ?? 0);
    $userId    = (int)($_POST['user_id'] ?? 0);
    if ($projectId <= 0 || $userId <= 0 || !can_manage_tasks_in($user, $projectId)) {
        set_flash('error', 'Нет доступа.');
        redirect('index.php?page=projects');
    }
    db()->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?,?)')
        ->execute([$projectId, $userId]);
    set_flash('success', 'Участник добавлен.');
    redirect('index.php?page=project&id=' . $projectId . '&tab=members');
}