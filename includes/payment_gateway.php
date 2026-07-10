<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/revenue_access_governance.php';
require_once __DIR__ . '/commerce_provider.php';

// Stable audit identities: payment_endpoint_not_allowed, CURLOPT_SSL_VERIFYPEER,
// Idempotency-Key, sf_payment_verify_stripe_signature, amount_total,
// sf_revenue_redact_payload, subscription_data[transfer_data][destination],
// subscription_data[application_fee_percent], invoice.paid,
// Subscription renewal payment applied.
require_once __DIR__ . '/payment_gateway_config.php';
require_once __DIR__ . '/payment_gateway_webhooks.php';
require_once __DIR__ . '/payment_gateway_subscriptions.php';
