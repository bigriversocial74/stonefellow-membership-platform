-- Stonefellow migration 005: merch cart, order runtime, inventory movement, and fulfillment audit.
-- Apply after database/stonefellow_streaming_platform.sql and migrations 001, 002, 003, and 004.
-- Installer-safe version: avoids DELIMITER/stored procedures so it can run through PDO.

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS `receipt_token` CHAR(64) DEFAULT NULL AFTER `order_number`,
  ADD COLUMN IF NOT EXISTS `payment_status` ENUM('unpaid','authorized','paid','failed','refunded') NOT NULL DEFAULT 'unpaid' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `fulfillment_status` ENUM('unfulfilled','partial','fulfilled','returned','canceled') NOT NULL DEFAULT 'unfulfilled' AFTER `payment_status`,
  ADD COLUMN IF NOT EXISTS `customer_phone` VARCHAR(40) DEFAULT NULL AFTER `customer_email`,
  ADD COLUMN IF NOT EXISTS `shipping_method` VARCHAR(120) NOT NULL DEFAULT 'standard' AFTER `shipping_country`,
  ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `shipping_method`;

CREATE INDEX idx_orders_payment_fulfillment ON orders (payment_status, fulfillment_status, created_at);
CREATE INDEX idx_orders_receipt_token ON orders (receipt_token);
CREATE INDEX idx_cart_items_cart_product_variant ON cart_items (cart_id, product_id, variant_id);

CREATE TABLE IF NOT EXISTS order_status_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  from_status VARCHAR(40) DEFAULT NULL,
  to_status VARCHAR(40) NOT NULL,
  note TEXT DEFAULT NULL,
  changed_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order_status_history_order (order_id, created_at),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  variant_id INT DEFAULT NULL,
  order_id INT DEFAULT NULL,
  delta_quantity INT NOT NULL,
  reason ENUM('manual_adjustment','order_paid','order_canceled','refund','restock') NOT NULL DEFAULT 'manual_adjustment',
  note TEXT DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inventory_movements_product (product_id, variant_id, created_at),
  INDEX idx_inventory_movements_order (order_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
