-- Stonefellow migration 004: billing, subscription checkout, invoices, and entitlement activation.
-- Apply after database/stonefellow_streaming_platform.sql and migrations 001, 002, and 003.
-- The PHP billing layer runs in sandbox mode by default and can be replaced by Stripe/processor webhooks later.

DROP PROCEDURE IF EXISTS sf_add_billing_column;
DROP PROCEDURE IF EXISTS sf_add_billing_index;
DELIMITER //
CREATE PROCEDURE sf_add_billing_column(IN table_name VARCHAR(64), IN column_name VARCHAR(64), IN column_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = column_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN ', column_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //

CREATE PROCEDURE sf_add_billing_index(IN table_name VARCHAR(64), IN index_name VARCHAR(64), IN index_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND INDEX_NAME = index_name
  ) THEN
    SET @ddl = CONCAT('CREATE INDEX ', index_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

CALL sf_add_billing_column('subscription_plans', 'trial_days', '`trial_days` INT NOT NULL DEFAULT 0 AFTER `billing_interval`');
CALL sf_add_billing_column('subscription_plans', 'sort_order', '`sort_order` INT NOT NULL DEFAULT 100 AFTER `is_featured`');
CALL sf_add_billing_column('subscription_plans', 'public_badge', '`public_badge` VARCHAR(80) DEFAULT NULL AFTER `description`');
CALL sf_add_billing_column('subscription_plans', 'processor_price_id', '`processor_price_id` VARCHAR(190) DEFAULT NULL AFTER `public_badge`');

CALL sf_add_billing_column('user_subscriptions', 'payment_provider', '`payment_provider` VARCHAR(80) NOT NULL DEFAULT ''sandbox'' AFTER `external_subscription_id`');
CALL sf_add_billing_column('user_subscriptions', 'provider_customer_id', '`provider_customer_id` VARCHAR(190) DEFAULT NULL AFTER `payment_provider`');
CALL sf_add_billing_column('user_subscriptions', 'provider_subscription_id', '`provider_subscription_id` VARCHAR(190) DEFAULT NULL AFTER `provider_customer_id`');
CALL sf_add_billing_column('user_subscriptions', 'cancel_at_period_end', '`cancel_at_period_end` TINYINT(1) NOT NULL DEFAULT 0 AFTER `provider_subscription_id`');
CALL sf_add_billing_column('user_subscriptions', 'trial_ends_at', '`trial_ends_at` DATETIME DEFAULT NULL AFTER `cancel_at_period_end`');
CALL sf_add_billing_column('user_subscriptions', 'canceled_at', '`canceled_at` DATETIME DEFAULT NULL AFTER `trial_ends_at`');

CALL sf_add_billing_index('subscription_plans', 'idx_subscription_plans_sort', 'idx_subscription_plans_sort ON subscription_plans (status, sort_order, price_cents)');
CALL sf_add_billing_index('user_subscriptions', 'idx_user_subscriptions_provider', 'idx_user_subscriptions_provider ON user_subscriptions (payment_provider, provider_subscription_id)');

CREATE TABLE IF NOT EXISTS billing_customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider VARCHAR(80) NOT NULL DEFAULT 'sandbox',
  provider_customer_id VARCHAR(190) NOT NULL,
  billing_email VARCHAR(190) DEFAULT NULL,
  billing_name VARCHAR(190) DEFAULT NULL,
  status ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_billing_customer_provider (provider, provider_customer_id),
  INDEX idx_billing_customer_user (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_checkouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  checkout_token CHAR(48) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  provider VARCHAR(80) NOT NULL DEFAULT 'sandbox',
  provider_checkout_id VARCHAR(190) DEFAULT NULL,
  provider_payment_id VARCHAR(190) DEFAULT NULL,
  status ENUM('pending','completed','canceled','expired','failed') NOT NULL DEFAULT 'pending',
  amount_cents INT NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  success_url VARCHAR(255) DEFAULT NULL,
  cancel_url VARCHAR(255) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_subscription_checkouts_user (user_id, status, created_at),
  INDEX idx_subscription_checkouts_plan (plan_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subscription_id INT DEFAULT NULL,
  invoice_number VARCHAR(80) NOT NULL UNIQUE,
  status ENUM('draft','open','paid','void','uncollectible','refunded') NOT NULL DEFAULT 'open',
  subtotal_cents INT NOT NULL DEFAULT 0,
  tax_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  provider_invoice_id VARCHAR(190) DEFAULT NULL,
  due_at DATETIME DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_invoices_user_status (user_id, status, created_at),
  INDEX idx_invoices_subscription (subscription_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  subscription_id INT DEFAULT NULL,
  invoice_id INT DEFAULT NULL,
  order_id INT DEFAULT NULL,
  checkout_id INT DEFAULT NULL,
  provider VARCHAR(80) NOT NULL DEFAULT 'sandbox',
  provider_payment_id VARCHAR(190) DEFAULT NULL,
  transaction_type ENUM('subscription','merch_order','refund','adjustment') NOT NULL DEFAULT 'subscription',
  status ENUM('pending','authorized','paid','failed','refunded','canceled') NOT NULL DEFAULT 'pending',
  amount_cents INT NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  raw_payload_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payment_transactions_user (user_id, status, created_at),
  INDEX idx_payment_transactions_subscription (subscription_id, status),
  INDEX idx_payment_transactions_provider (provider, provider_payment_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  FOREIGN KEY (checkout_id) REFERENCES subscription_checkouts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS billing_webhook_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(80) NOT NULL DEFAULT 'sandbox',
  provider_event_id VARCHAR(190) DEFAULT NULL,
  event_type VARCHAR(120) NOT NULL,
  status ENUM('received','processed','ignored','failed') NOT NULL DEFAULT 'received',
  payload_json JSON DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  processed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_billing_webhook_provider_event (provider, provider_event_id),
  INDEX idx_billing_webhooks_type_status (event_type, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE subscription_plans SET sort_order = CASE slug
  WHEN 'monthly-access' THEN 10
  WHEN 'annual-access' THEN 20
  WHEN 'founding-fan' THEN 30
  ELSE 100
END;

UPDATE subscription_plans SET public_badge = CASE
  WHEN slug = 'annual-access' THEN 'Most Popular'
  WHEN slug = 'founding-fan' THEN 'Founder'
  ELSE public_badge
END;

DROP PROCEDURE IF EXISTS sf_add_billing_column;
DROP PROCEDURE IF EXISTS sf_add_billing_index;
