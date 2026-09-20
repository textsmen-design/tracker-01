# База данных Tracker_01 (MySQL 8.0)

Стек кейса №1: **PHP 8.0 + MySQL 8.0** (требование реального заказа). Источник сущностей — `Tracker_01/info/tz.md`, раздел 11.

## Файлы

- `schema.sql` — создание БД `simple_tasks` и 8 таблиц:
  `users`, `projects`, `project_members`, `tasks`, `attachments`, `comments`, `notifications`, `activity_log`.
- `demo_data.sql` — демо-данные (8 пользователей, 3 проекта, 25 задач, комментарии, история, вложения, уведомления).
  В файле плейсхолдеры `{HASH_ADMIN}`, `{HASH_MANAGER}`, `{HASH_EMPLOYEE}` — заменяются на `password_hash('demo123')`,
  сгенерированные через PHP (команда ниже), перед импортом.

## Соответствие таблиц ТЗ (tz.md)

| tz.md (сущность) | Таблица MySQL | Примечание |
|---|---|---|
| Пользователь | `users` | роли `admin/manager/employee` (учебное расширение), `status active/disabled` |
| Проект | `projects` | статусы `planned/active/paused/completed/archived` (учебное расширение) |
| Участники проекта (N:M) | `project_members` | |
| Задача | `tasks` | статусы `backlog/planned/in_progress/review/done` (канбан, учебное расширение) |
| Вложение (ссылка) | `attachments` | в MVP только ссылки, без загрузки файлов |
| Комментарий | `comments` | текстовые |
| Уведомление | `notifications` | внутри приложения |
| История изменений | `activity_log` | |

## Импорт (после установки PHP и MySQL)

```bash
# 1) сгенерировать хэши и подставить в demo_data.sql
HASH_ADMIN=$(php -r 'echo password_hash("demo123", PASSWORD_DEFAULT);')
HASH_MANAGER=$(php -r 'echo password_hash("demo123", PASSWORD_DEFAULT);')
HASH_EMPLOYEE=$(php -r 'echo password_hash("demo123", PASSWORD_DEFAULT);')
# заменить плейсхолдеры в демо-файле (sed), затем импорт через mysql CLI

# 2) импорт
mysql -u root < schema.sql
mysql -u root simple_tasks < demo_data.sql

# 3) проверка
mysql -u root -e "USE simple_tasks; SHOW TABLES; SELECT COUNT(*) FROM tasks;"
```

> Пароли демо-пользователей одинаковые (`demo123`) — только для локальной демонстрации учебного MVP.