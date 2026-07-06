-- Stonefellow migration 018: admin roles / permissions v1 and security hardening + audit log v1.
-- Run after migration 017.

CREATE TABLE IF NOT EXISTS admin_roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_key VARCHAR(90) NOT NULL UNIQUE,
  role_label VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admin_roles_status (status, role_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  permission_key VARCHAR(120) NOT NULL UNIQUE,
  permission_label VARCHAR(190) NOT NULL,
  module_key VARCHAR(90) NOT NULL,
  description TEXT DEFAULT NULL,
  risk_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admin_permissions_module (module_key, risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_role_permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  permission_key VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_admin_role_permission (role_id, permission_key),
  INDEX idx_admin_role_permissions_key (permission_key),
  CONSTRAINT fk_admin_role_permissions_role FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_role_permissions_permission FOREIGN KEY (permission_key) REFERENCES admin_permissions(permission_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_user_roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  assigned_by_user_id INT DEFAULT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_admin_user_role (user_id, role_id),
  INDEX idx_admin_user_roles_user (user_id),
  CONSTRAINT fk_admin_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_user_roles_role FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_user_roles_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_audit_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT DEFAULT NULL,
  actor_email VARCHAR(190) DEFAULT NULL,
  event_type VARCHAR(100) NOT NULL,
  severity ENUM('info','notice','warning','critical') NOT NULL DEFAULT 'info',
  entity_type VARCHAR(90) DEFAULT NULL,
  entity_id BIGINT UNSIGNED DEFAULT NULL,
  route_path VARCHAR(190) DEFAULT NULL,
  request_method VARCHAR(12) DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_security_audit_actor (actor_user_id, created_at),
  INDEX idx_security_audit_type (event_type, severity, created_at),
  INDEX idx_security_audit_entity (entity_type, entity_id, created_at),
  CONSTRAINT fk_security_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_security_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_id_hash VARCHAR(128) NOT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_route VARCHAR(190) DEFAULT NULL,
  risk_flags_json JSON DEFAULT NULL,
  status ENUM('active','expired','revoked') NOT NULL DEFAULT 'active',
  UNIQUE KEY uniq_admin_security_session (session_id_hash),
  INDEX idx_admin_security_sessions_user (user_id, status, last_seen_at),
  CONSTRAINT fk_admin_security_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_roles (role_key, role_label, description, is_system, status) VALUES
  ('super_admin','Super Admin','Full access to all admin modules and security settings.',1,'active'),
  ('content_admin','Content Admin','Manage posts, publishing, media catalog, uploads, and content operations.',1,'active'),
  ('support_admin','Support Admin','Manage members, support tickets, lifecycle tasks, and messages.',1,'active'),
  ('finance_admin','Finance Admin','Manage billing, revenue, orders, products, payments, and subscription access.',1,'active'),
  ('analyst','Analyst','Read analytics, activity, QA, and reporting dashboards.',1,'active')
ON DUPLICATE KEY UPDATE role_label=VALUES(role_label), description=VALUES(description), is_system=VALUES(is_system), status=VALUES(status);

INSERT INTO admin_permissions (permission_key, permission_label, module_key, description, risk_level) VALUES
  ('admin.security.manage','Manage security and permissions','security','Edit roles, permissions, audit logs, and security settings.','critical'),
  ('admin.audit.view','View audit logs','security','Read admin and security audit history.','high'),
  ('admin.content.manage','Manage content','content','Manage posts, pages, episodes, videos, albums, songs, uploads, imports, and publishing.','high'),
  ('admin.members.manage','Manage members','members','Manage members, lifecycle, retention tasks, support, messages, and notifications.','high'),
  ('admin.billing.manage','Manage billing and revenue','billing','Manage subscriptions, payment gateways, tiers, revenue dashboard, merch orders, and invoices.','critical'),
  ('admin.ops.manage','Manage ops automation','ops','Manage scheduler jobs, content ops, activity, and launch checklist.','high'),
  ('admin.analytics.view','View analytics','analytics','View analytics, reports, search discovery, QA, and performance dashboards.','medium'),
  ('admin.settings.manage','Manage settings','settings','Manage site settings, installer checks, and system health configuration.','critical')
ON DUPLICATE KEY UPDATE permission_label=VALUES(permission_label), module_key=VALUES(module_key), description=VALUES(description), risk_level=VALUES(risk_level);

INSERT IGNORE INTO admin_role_permissions (role_id, permission_key)
SELECT r.id, p.permission_key FROM admin_roles r JOIN admin_permissions p WHERE r.role_key='super_admin';
INSERT IGNORE INTO admin_role_permissions (role_id, permission_key)
SELECT r.id, p.permission_key FROM admin_roles r JOIN admin_permissions p ON p.permission_key IN ('admin.content.manage','admin.analytics.view','admin.ops.manage') WHERE r.role_key='content_admin';
INSERT IGNORE INTO admin_role_permissions (role_id, permission_key)
SELECT r.id, p.permission_key FROM admin_roles r JOIN admin_permissions p ON p.permission_key IN ('admin.members.manage','admin.analytics.view') WHERE r.role_key='support_admin';
INSERT IGNORE INTO admin_role_permissions (role_id, permission_key)
SELECT r.id, p.permission_key FROM admin_roles r JOIN admin_permissions p ON p.permission_key IN ('admin.billing.manage','admin.analytics.view') WHERE r.role_key='finance_admin';
INSERT IGNORE INTO admin_role_permissions (role_id, permission_key)
SELECT r.id, p.permission_key FROM admin_roles r JOIN admin_permissions p ON p.permission_key IN ('admin.analytics.view','admin.audit.view') WHERE r.role_key='analyst';

INSERT IGNORE INTO admin_user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN admin_roles r ON r.role_key='super_admin' WHERE u.role='admin';
