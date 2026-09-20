<?php
/**
 * Tracker_01 — вспомогательные функции.
 */
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $text): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

function get_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$sent)) {
        http_response_code(419);
        exit('Недействительный CSRF-токен.');
    }
}

/** Словари статусов и приоритетов (совпадают с ENUM в БД). */
function statuses(): array
{
    return ['backlog' => 'Бэклог', 'planned' => 'Запланировано', 'in_progress' => 'В работе', 'review' => 'На проверке', 'done' => 'Выполнено'];
}

function priorities(): array
{
    return ['low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий', 'critical' => 'Критический'];
}

function status_label(string $status): string
{
    return statuses()[$status] ?? $status;
}

function priority_label(string $priority): string
{
    return priorities()[$priority] ?? $priority;
}

function human_date(?string $date, bool $withToday = true): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$d) {
        return e($date);
    }
    if ($withToday && $d->format('Y-m-d') === today()->format('Y-m-d')) {
        return 'сегодня';
    }
    return $d->format('d.m.Y');
}

/** Просрочена ли задача (дедлайн < сегодня и статус не 'done'). */
function is_overdue(array $task): bool
{
    if ($task['status'] === 'done' || empty($task['deadline'])) {
        return false;
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $task['deadline']);
    return $d && $d < today();
}

/** Дата «сегодня» для сравнений с дедлайнами. */
function today(): DateTimeImmutable
{
    return new DateTimeImmutable('today');
}

/** Валидна ли дата формата Y-m-d (реальная календарная дата). */
function valid_date(?string $date): bool
{
    if ($date === null || $date === '') {
        return true;
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return false;
    }
    return true;
}