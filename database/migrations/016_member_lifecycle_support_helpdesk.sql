-- Stonefellow migration 016: member lifecycle / retention v1 and customer support help desk v1.
-- Run after migration 015.

CREATE TABLE IF NOT EXISTS member_lifecycle_notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  admin_user_id INT DEFAULT NULL,
  note_type ENUM('retention','billing','support','access','churn_risk','general') NOT NULL DEFAULT 'general',
  note TEXT NOT NULL,
  visibility ENUM('admin','member') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lifecycle_notes_user (user_id, created_at),
  INDEX idx_lifecycle_notes_type (note_type, created_at),
  CONSTRAINT fk_lifecycle_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_lifecycle_notes_admin FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_retention_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  assigned_admin_user_id INT DEFAULT NULL,
  task_type ENUM('welcome','renewal','past_due','cancel_save','upgrade','support_followup','manual') NOT NULL DEFAULT 'manual',
  priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  title VARCHAR(190) NOT NULL,
  detail TEXT DEFAULT NULL,
  status ENUM('open','in_progress','done','dismissed') NOT NULL DEFAULT 'open',
  due_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_retention_tasks_user (user_id, status, due_at),
  INDEX idx_retention_tasks_status (status, priority, due_at),
  CONSTRAINT fk_retention_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_retention_tasks_admin FOREIGN KEY (assigned_admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(32) NOT NULL UNIQUE,
  user_id INT DEFAULT NULL,
  assigned_admin_user_id INT DEFAULT NULL,
  subject VARCHAR(190) NOT NULL,
  body TEXT DEFAULT NULL,
  category ENUM('account','billing','technical','content','merch','feedback','other') NOT NULL DEFAULT 'other',
  priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status ENUM('new','open','pending_member','pending_admin','resolved','closed') NOT NULL DEFAULT 'new',
  source ENUM('member','admin','system') NOT NULL DEFAULT 'member',
  subscription_id INT DEFAULT NULL,
  order_id BIGINT UNSIGNED DEFAULT NULL,
  invoice_id INT DEFAULT NULL,
  content_type VARCHAR(60) DEFAULT NULL,
  content_id BIGINT UNSIGNED DEFAULT NULL,
  content_slug VARCHAR(190) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  first_response_at DATETIME DEFAULT NULL,
  resolved_at DATETIME DEFAULT NULL,
  closed_at DATETIME DEFAULT NULL,
  last_message_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_support_tickets_user (user_id, status, created_at),
  INDEX idx_support_tickets_status (status, priority, last_message_at),
  INDEX idx_support_tickets_category (category, status, created_at),
  CONSTRAINT fk_support_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_support_tickets_admin FOREIGN KEY (assigned_admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  user_id INT DEFAULT NULL,
  sender_type ENUM('member','admin','system') NOT NULL DEFAULT 'member',
  message TEXT NOT NULL,
  is_internal TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_support_ticket_messages_ticket (ticket_id, created_at),
  CONSTRAINT fk_support_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_support_ticket_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  actor_user_id INT DEFAULT NULL,
  event_type VARCHAR(80) NOT NULL,
  before_json JSON DEFAULT NULL,
  after_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_support_ticket_events_ticket (ticket_id, created_at),
  CONSTRAINT fk_support_ticket_events_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_support_ticket_events_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
