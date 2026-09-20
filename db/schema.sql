-- ============================================================
-- Tracker_01 — Схема базы данных MySQL 8.0
-- Кейс №1 «Простая система учёта задач» (freelance.ru #1645675)
-- Основание: Tracker_01/info/tz.md (сущности — раздел 11)
-- Обозначения: [ЗАКАЗ]=клиент, [УРОК]=учебное расширение FGIp07
-- ============================================================

CREATE DATABASE IF NOT EXISTS simple_tasks
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE simple_tasks;

-- ------------------------------------------------------------
-- 1. Пользователи [ПРЕДПОЛОЖЕНИЕ по составу, роли: УРОК]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,          -- имя
  surname       VARCHAR(100) NOT NULL,          -- фамилия
  position      VARCHAR(100) DEFAULT NULL,      -- должность
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash() PHP [ЗАКАЗ: PHP]
  role          ENUM('admin','manager','employee') NOT NULL DEFAULT 'employee',
  status        ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Проекты [состав полей: ПРЕДПОЛОЖЕНИЕ; статусы: УРОК]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(190) NOT NULL,
  description   TEXT DEFAULT NULL,
  manager_id    INT UNSIGNED NOT NULL,          -- руководитель проекта
  status        ENUM('planned','active','paused','completed','archived') NOT NULL DEFAULT 'planned',
  start_date    DATE DEFAULT NULL,
  end_date      DATE DEFAULT NULL,              -- плановая дата завершения
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_projects_manager (manager_id),
  CONSTRAINT fk_projects_manager FOREIGN KEY (manager_id) REFERENCES users (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Участники проектов (N:M) [ПРЕДПОЛОЖЕНИЕ]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_members (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member (project_id, user_id),
  KEY idx_members_user (user_id),
  CONSTRAINT fk_members_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Задачи [поля: УРОК; хранимые ссылки вместо файлов: ПРЕДПОЛОЖЕНИЕ]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title         VARCHAR(190) NOT NULL,
  description   TEXT DEFAULT NULL,
  project_id    INT UNSIGNED NOT NULL,
  creator_id    INT UNSIGNED NOT NULL,          -- постановщик (автор)
  assignee_id   INT UNSIGNED NOT NULL,          -- исполнитель
  status        ENUM('backlog','planned','in_progress','review','done') NOT NULL DEFAULT 'backlog',
  priority      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  deadline      DATE DEFAULT NULL,              -- дедлайн (может отсутствовать)
  tags          VARCHAR(255) DEFAULT NULL,      -- теги (строка, разделитель ,) — MVP
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tasks_project (project_id),
  KEY idx_tasks_assignee (assignee_id),
  KEY idx_tasks_status (status),
  CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_creator FOREIGN KEY (creator_id) REFERENCES users (id),
  CONSTRAINT fk_tasks_assignee FOREIGN KEY (assignee_id) REFERENCES users (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. Вложения (только ссылки) [ПРЕДПОЛОЖЕНИЕ]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attachments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id    INT UNSIGNED NOT NULL,
  url        VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attachments_task (task_id),
  CONSTRAINT fk_attachments_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. Комментарии [УРОК]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id    INT UNSIGNED NOT NULL,
  author_id  INT UNSIGNED NOT NULL,
  text       TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_task (task_id),
  CONSTRAINT fk_comments_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_author FOREIGN KEY (author_id) REFERENCES users (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. Уведомления (внутри приложения) [УРОК]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  task_id    INT UNSIGNED DEFAULT NULL,
  type       VARCHAR(50) NOT NULL,   -- assigned / deadline_changed / status_changed / comment / deadline_coming / overdue
  text       VARCHAR(500) NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, is_read),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. История изменений задач [ПРЕДПОЛОЖЕНИЕ]
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  action     VARCHAR(255) NOT NULL,  -- например: 'Статус: В работе', 'Назначен исполнитель'
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_task (task_id),
  CONSTRAINT fk_activity_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;