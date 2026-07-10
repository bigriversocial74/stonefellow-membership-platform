-- Stonefellow migration 022 manual-import compatibility edition.
-- Use this file when the database rejects ADD COLUMN IF NOT EXISTS.
-- It is safe to run after a partially completed import because every table,
-- column, index, and foreign key is checked before it is created.
-- Apply after migrations 004, 005, 008, and 021.

CREATE TABLE IF NOT EXISTS commerce_merchants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_user_id INT DEFAULT NULL,
  merchant_key VARCHAR(80) NOT NULL,
  display_name VARCHAR(190) NOT NULL,
  legal_name VARCHAR(190) DEFAULT NULL,
  support_email VARCHAR(190) DEFAULT NULL,
  country CHAR(2) NOT NULL DEFAULT 'US',
  default_currency CHAR(3) NOT NULL DEFAULT 'USD',
  platform_fee_bps INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_commerce_merchant_key (merchant_key),
  INDEX idx_commerce_merchants_owner (owner_user_id, status),
  FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS merchant_payment_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_id INT NOT NULL,
  provider VARCHAR(80) NOT NULL,
  mode ENUM('test','live') NOT NULL DEFAULT 'test',
  provider_account_id VARCHAR(190) NOT NULL,
  account_type VARCHAR(40) NOT NULL DEFAULT 'express',
  onboarding_status ENUM('not_started','pending','restricted','complete','disabled') NOT NULL DEFAULT 'not_started',
  charges_enabled TINYINT(1) NOT NULL DEFAULT 0,
  payouts_enabled TINYINT(1) NOT NULL DEFAULT 0,
  details_submitted TINYINT(1) NOT NULL DEFAULT 0,
  requirements_json JSON DEFAULT NULL,
  future_requirements_json JSON DEFAULT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive','disconnected') NOT NULL DEFAULT 'active',
  last_synced_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_merchant_provider_mode (merchant_id, provider, mode),
  UNIQUE KEY unique_provider_account_id (provider, provider_account_id),
  INDEX idx_payment_accounts_ready (merchant_id, provider, mode, status, onboarding_status),
  FOREIGN KEY (merchant_id) REFERENCES commerce_merchants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS merchant_payment_onboarding_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_account_id INT NOT NULL,
  session_token CHAR(64) NOT NULL,
  status ENUM('created','redirected','returned','expired','failed') NOT NULL DEFAULT 'created',
  return_path VARCHAR(255) NOT NULL,
  refresh_path VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  returned_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_payment_onboarding_session (session_token),
  INDEX idx_payment_onboarding_account (payment_account_id, status, expires_at),
  FOREIGN KEY (payment_account_id) REFERENCES merchant_payment_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commerce_discount_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL,
  discount_type ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
  amount INT NOT NULL DEFAULT 0,
  minimum_subtotal_cents INT NOT NULL DEFAULT 0,
  maximum_discount_cents INT DEFAULT NULL,
  usage_limit INT DEFAULT NULL,
  usage_count INT NOT NULL DEFAULT 0,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_commerce_discount_code (code),
  INDEX idx_commerce_discount_active (status, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS merch_checkouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  checkout_token CHAR(48) NOT NULL,
  order_id INT NOT NULL,
  cart_id INT DEFAULT NULL,
  merchant_id INT NOT NULL,
  payment_account_id INT NOT NULL,
  provider VARCHAR(80) NOT NULL,
  mode ENUM('test','live') NOT NULL DEFAULT 'test',
  provider_checkout_id VARCHAR(190) DEFAULT NULL,
  provider_payment_id VARCHAR(190) DEFAULT NULL,
  provider_customer_id VARCHAR(190) DEFAULT NULL,
  status ENUM('pending','created','completed','canceled','expired','failed','refunded','disputed') NOT NULL DEFAULT 'pending',
  subtotal_cents INT NOT NULL DEFAULT 0,
  discount_cents INT NOT NULL DEFAULT 0,
  shipping_cents INT NOT NULL DEFAULT 0,
  tax_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  discount_code VARCHAR(80) DEFAULT NULL,
  expires_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_merch_checkout_token (checkout_token),
  UNIQUE KEY unique_merch_checkout_order (order_id),
  UNIQUE KEY unique_merch_provider_checkout (provider, provider_checkout_id),
  INDEX idx_merch_checkout_status (status, expires_at),
  INDEX idx_merch_checkout_merchant (merchant_id, created_at),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE SET NULL,
  FOREIGN KEY (merchant_id) REFERENCES commerce_merchants(id),
  FOREIGN KEY (payment_account_id) REFERENCES merchant_payment_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  checkout_id INT NOT NULL,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT DEFAULT NULL,
  quantity INT NOT NULL,
  status ENUM('active','consumed','released','expired') NOT NULL DEFAULT 'active',
  expires_at DATETIME NOT NULL,
  released_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_inventory_reservations_stock (product_id, variant_id, status, expires_at),
  INDEX idx_inventory_reservations_checkout (checkout_id, status),
  FOREIGN KEY (checkout_id) REFERENCES merch_checkouts(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commerce_discount_redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  discount_id INT NOT NULL,
  checkout_id INT NOT NULL,
  order_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  discount_cents INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_discount_checkout (discount_id, checkout_id),
  INDEX idx_discount_redemptions_user (user_id, created_at),
  FOREIGN KEY (discount_id) REFERENCES commerce_discount_codes(id),
  FOREIGN KEY (checkout_id) REFERENCES merch_checkouts(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add order columns individually for MySQL/MariaDB versions that do not
-- support ADD COLUMN IF NOT EXISTS.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='merchant_id')=0,
  'ALTER TABLE `orders` ADD COLUMN `merchant_id` INT DEFAULT NULL AFTER `user_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_account_id')=0,
  'ALTER TABLE `orders` ADD COLUMN `payment_account_id` INT DEFAULT NULL AFTER `merchant_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_provider')=0,
  'ALTER TABLE `orders` ADD COLUMN `payment_provider` VARCHAR(80) DEFAULT NULL AFTER `payment_status`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_currency')=0,
  'ALTER TABLE `orders` ADD COLUMN `payment_currency` CHAR(3) NOT NULL DEFAULT ''USD'' AFTER `payment_provider`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='provider_checkout_id')=0,
  'ALTER TABLE `orders` ADD COLUMN `provider_checkout_id` VARCHAR(190) DEFAULT NULL AFTER `external_payment_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='provider_customer_id')=0,
  'ALTER TABLE `orders` ADD COLUMN `provider_customer_id` VARCHAR(190) DEFAULT NULL AFTER `provider_checkout_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='provider_charge_id')=0,
  'ALTER TABLE `orders` ADD COLUMN `provider_charge_id` VARCHAR(190) DEFAULT NULL AFTER `provider_customer_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_failure_code')=0,
  'ALTER TABLE `orders` ADD COLUMN `payment_failure_code` VARCHAR(120) DEFAULT NULL AFTER `provider_charge_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_failure_message')=0,
  'ALTER TABLE `orders` ADD COLUMN `payment_failure_message` VARCHAR(500) DEFAULT NULL AFTER `payment_failure_code`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='paid_at')=0,
  'ALTER TABLE `orders` ADD COLUMN `paid_at` DATETIME DEFAULT NULL AFTER `payment_failure_message`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='refunded_at')=0,
  'ALTER TABLE `orders` ADD COLUMN `refunded_at` DATETIME DEFAULT NULL AFTER `paid_at`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='disputed_at')=0,
  'ALTER TABLE `orders` ADD COLUMN `disputed_at` DATETIME DEFAULT NULL AFTER `refunded_at`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

ALTER TABLE orders
  MODIFY COLUMN payment_status ENUM('unpaid','authorized','paid','failed','refunded','partially_refunded','disputed') NOT NULL DEFAULT 'unpaid';

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND INDEX_NAME='idx_orders_merchant_created')=0,
  'CREATE INDEX `idx_orders_merchant_created` ON `orders` (`merchant_id`,`created_at`)','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND INDEX_NAME='idx_orders_provider_checkout')=0,
  'CREATE INDEX `idx_orders_provider_checkout` ON `orders` (`payment_provider`,`provider_checkout_id`)','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND INDEX_NAME='idx_orders_provider_payment')=0,
  'CREATE INDEX `idx_orders_provider_payment` ON `orders` (`payment_provider`,`external_payment_id`)','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND CONSTRAINT_NAME='fk_orders_commerce_merchant')=0,
  'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_commerce_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `commerce_merchants`(`id`) ON DELETE SET NULL','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND CONSTRAINT_NAME='fk_orders_payment_account')=0,
  'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_payment_account` FOREIGN KEY (`payment_account_id`) REFERENCES `merchant_payment_accounts`(`id`) ON DELETE SET NULL','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

-- Add payment transaction columns individually.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND COLUMN_NAME='merchant_id')=0,
  'ALTER TABLE `payment_transactions` ADD COLUMN `merchant_id` INT DEFAULT NULL AFTER `user_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND COLUMN_NAME='payment_account_id')=0,
  'ALTER TABLE `payment_transactions` ADD COLUMN `payment_account_id` INT DEFAULT NULL AFTER `merchant_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND COLUMN_NAME='provider_charge_id')=0,
  'ALTER TABLE `payment_transactions` ADD COLUMN `provider_charge_id` VARCHAR(190) DEFAULT NULL AFTER `provider_payment_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND COLUMN_NAME='idempotency_key')=0,
  'ALTER TABLE `payment_transactions` ADD COLUMN `idempotency_key` VARCHAR(190) DEFAULT NULL AFTER `provider_charge_id`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND COLUMN_NAME='refunded_amount_cents')=0,
  'ALTER TABLE `payment_transactions` ADD COLUMN `refunded_amount_cents` INT NOT NULL DEFAULT 0 AFTER `amount_cents`','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

ALTER TABLE payment_transactions
  MODIFY COLUMN transaction_type ENUM('subscription','merch_order','refund','dispute','adjustment') NOT NULL DEFAULT 'subscription';

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND INDEX_NAME='unique_payment_transaction_idempotency')=0,
  'CREATE UNIQUE INDEX `unique_payment_transaction_idempotency` ON `payment_transactions` (`provider`,`idempotency_key`)','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND INDEX_NAME='idx_payment_transactions_order_status')=0,
  'CREATE INDEX `idx_payment_transactions_order_status` ON `payment_transactions` (`order_id`,`status`,`created_at`)','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND CONSTRAINT_NAME='fk_payment_transactions_merchant')=0,
  'ALTER TABLE `payment_transactions` ADD CONSTRAINT `fk_payment_transactions_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `commerce_merchants`(`id`) ON DELETE SET NULL','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='payment_transactions' AND CONSTRAINT_NAME='fk_payment_transactions_account')=0,
  'ALTER TABLE `payment_transactions` ADD CONSTRAINT `fk_payment_transactions_account` FOREIGN KEY (`payment_account_id`) REFERENCES `merchant_payment_accounts`(`id`) ON DELETE SET NULL','SELECT 1');
PREPARE sf_stmt FROM @sql; EXECUTE sf_stmt; DEALLOCATE PREPARE sf_stmt;

ALTER TABLE payment_gateway_webhook_events
  MODIFY COLUMN status ENUM('received','processed','ignored','failed','rejected') NOT NULL DEFAULT 'received';

INSERT INTO commerce_merchants (owner_user_id, merchant_key, display_name, support_email, country, default_currency, platform_fee_bps, status)
SELECT (SELECT id FROM users WHERE role='admin' AND status='active' ORDER BY id ASC LIMIT 1),
       'stonefellow',
       'Stonefellow',
       (SELECT email FROM users WHERE role='admin' AND status='active' ORDER BY id ASC LIMIT 1),
       'US',
       'USD',
       0,
       'active'
WHERE NOT EXISTS (SELECT 1 FROM commerce_merchants WHERE merchant_key='stonefellow');

SELECT 'Stonefellow commerce migration 022 compatibility import completed.' AS result;
