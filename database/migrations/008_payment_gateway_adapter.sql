-- Stonefellow migration 008: payment gateway adapter configuration and webhook event log.
-- Secrets remain in environment variables; this table stores non-secret display/config state.

CREATE TABLE IF NOT EXISTS payment_gateway_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(80) NOT NULL DEFAULT 'sandbox',
  mode ENUM('sandbox','test','live') NOT NULL DEFAULT 'sandbox',
  public_key VARCHAR(255) DEFAULT NULL,
  webhook_endpoint VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_payment_gateway_provider_mode (provider, mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_gateway_webhook_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(80) NOT NULL,
  provider_event_id VARCHAR(190) NOT NULL,
  event_type VARCHAR(120) NOT NULL,
  status ENUM('received','processed','ignored','failed') NOT NULL DEFAULT 'received',
  payload_json JSON DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  processed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_payment_gateway_webhook_event (provider, provider_event_id),
  INDEX idx_payment_gateway_webhook_status (provider, status, created_at),
  INDEX idx_payment_gateway_webhook_type (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_gateway_settings (provider, mode, public_key, webhook_endpoint, status)
VALUES
('sandbox', 'sandbox', NULL, 'api/payment-webhook.php?provider=sandbox', 'active'),
('stripe', 'test', NULL, 'api/payment-webhook.php?provider=stripe', 'inactive'),
('paypal', 'test', NULL, 'api/payment-webhook.php?provider=paypal', 'inactive')
ON DUPLICATE KEY UPDATE webhook_endpoint = VALUES(webhook_endpoint);
