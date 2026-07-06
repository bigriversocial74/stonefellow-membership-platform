-- Stonefellow migration 005: merch cart, order runtime, inventory movement, and fulfillment audit.
-- Apply after database/stonefellow_streaming_platform.sql and migrations 001, 002, 003, and 004.
-- Public cart and checkout work in session-preview mode without this migration, but database mode
-- should use these optional order columns and runtime audit tables.

DROP PROCEDURE IF EXISTS sf_add_store_column;
DROP PROCEDURE IF EXISTS sf_add_store_index;
DELIMITER //
CREATE PROCEDURE sf_add_store_column(IN table_name VARCHAR(64), IN column_name VARCHAR(64), IN column_definition TEXT)
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

CREATE PROCEDURE sf_add_store_index(IN table_name VARCHAR(64), IN index_name VARCHAR(64), IN index_definition TEXT)
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

CALL sf_add_store_column('orders', 'receipt_token', '`receipt_token` CHAR(64) DEFAULT NULL AFTER `order_number`');
CALL sf_add_store_column('orders', 'payment_status', '`payment_status` ENUM(''unpaid'',''authorized'',''paid'',''failed'',''refunded'') NOT NULL DEFAULT ''unpaid'' AFTER `status`');
CALL sf_add_store_column('orders', 'fulfillment_status', '`fulfillment_status` ENUM(''unfulfilled'',''partial'',''fulfilled'',''returned'',''canceled'') NOT NULL DEFAULT ''unfulfilled'' AFTER `payment_status`');
CALL sf_add_store_column('orders', 'customer_phone', '`customer_phone` VARCHAR(40) DEFAULT NULL AFTER `customer_email`');
CALL sf_add_store_column('orders', 'shipping_method', '`shipping_method` VARCHAR(120) NOT NULL DEFAULT ''standard'' AFTER `shipping_country`');
CALL sf_add_store_column('orders', 'notes', '`notes` TEXT DEFAULT NULL AFTER `shipping_method`');

CALL sf_add_store_index('orders', 'idx_orders_payment_fulfillment', 'idx_orders_payment_fulfillment ON orders (payment_status, fulfillment_status, created_at)');
CALL sf_add_store_index('orders', 'idx_orders_receipt_token', 'idx_orders_receipt_token ON orders (receipt_token)');
CALL sf_add_store_index('cart_items', 'idx_cart_items_cart_product_variant', 'idx_cart_items_cart_product_variant ON cart_items (cart_id, product_id, variant_id)');

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

DROP PROCEDURE IF EXISTS sf_add_store_column;
DROP PROCEDURE IF EXISTS sf_add_store_index;
