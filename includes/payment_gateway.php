<?php
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/notifications.php';

function sf_payment_provider(): string {
  $provider = sf_get_setting('payment_provider', getenv('SF_PAYMENT_PROVIDER') ?: 'sandbox') ?: 'sandbox';
  return in_array($provider, ['sandbox','stripe','paypal'], true) ? $provider : 'sandbox';
}

function sf_payment_provider_label(?string $provider = null): string {
  $provider = $provider ?: sf_payment_provider();
  return ['sandbox'=>'Sandbox','stripe'=>'Stripe','paypal'=>'PayPal'][$provider] ?? ucfirst($provider);
}

function sf_payment_gateway_ready(?string $provider = null): bool {
  $provider = $provider ?: sf_payment_provider();
  if ($provider === 'sandbox') {
    return true;
  }
  if ($provider === 'stripe') {
    return (bool)getenv('SF_STRIPE_SECRET_KEY');
  }
  if ($provider === 'paypal') {
    return (bool)(getenv('SF_PAYPAL_CLIENT_ID') && getenv('SF_PAYPAL_SECRET'));
  }
  return false;
}

function sf_payment_gateway_status(): array {
  $provider = sf_payment_provider();
  return [
    'provider' => $provider,
    'label' => sf_payment_provider_label($provider),
    'ready' => sf_payment_gateway_ready($provider),
    'mode' => getenv('SF_PAYMENT_MODE') ?: 'sandbox',
    'stripe_public' => sf_get_setting('stripe_publishable_key', getenv('SF_STRIPE_PUBLISHABLE_KEY') ?: ''),
    'paypal_client_id' => sf_get_setting('paypal_client_id', getenv('SF_PAYPAL_CLIENT_ID') ?: ''),
  ];
}

function sf_payment_create_checkout(array $payload): array {
  $provider = sf_payment_provider();
  $amount = (int)($payload['amount_cents'] ?? 0);
  $token = (string)($payload['checkout_token'] ?? bin2hex(random_bytes(16)));
  $localUrl = (string)($payload['local_checkout_url'] ?? '');
  $externalId = $provider . '_checkout_' . substr(hash('sha256', $token . json_encode($payload)), 0, 18);

  if ($provider === 'sandbox' || !sf_payment_gateway_ready($provider)) {
    return [
      'ok' => true,
      'provider' => 'sandbox',
      'provider_checkout_id' => $externalId,
      'checkout_url' => $localUrl,
      'mode' => 'sandbox',
      'message' => 'Sandbox checkout created locally.',
    ];
  }

  // Production adapters are intentionally isolated here. Replace the placeholder
  // branch with Stripe Checkout Session or PayPal Order creation without touching
  // membership, merch, or notification business logic.
  return [
    'ok' => true,
    'provider' => $provider,
    'provider_checkout_id' => $externalId,
    'checkout_url' => $localUrl,
    'mode' => 'adapter-ready',
    'message' => sf_payment_provider_label($provider) . ' adapter shell created. Wire provider SDK/API call here.',
    'amount_cents' => $amount,
  ];
}

function sf_payment_record_gateway_event(string $provider, string $eventType, array $payload, string $status = 'received', ?string $error = null): void {
  $pdo = sf_db();
  if (!$pdo) {
    return;
  }
  try {
    $eventId = (string)($payload['id'] ?? $payload['event_id'] ?? hash('sha256', $provider . $eventType . json_encode($payload)));
    if (sf_settings_table_exists('payment_gateway_webhook_events')) {
      $stmt = $pdo->prepare("INSERT INTO payment_gateway_webhook_events (provider, provider_event_id, event_type, status, payload_json, error_message, processed_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status), payload_json=VALUES(payload_json), error_message=VALUES(error_message), processed_at=NOW()");
      $stmt->execute([$provider, $eventId, $eventType, $status, json_encode($payload, JSON_UNESCAPED_SLASHES), $error]);
    }
  } catch (Throwable $e) {
    error_log('Stonefellow payment gateway event log failed: ' . $e->getMessage());
  }
}

function sf_payment_verify_webhook(string $provider, string $rawBody, array $headers = []): bool {
  if ($provider === 'sandbox') {
    return true;
  }
  if ($provider === 'stripe') {
    return getenv('SF_STRIPE_WEBHOOK_SECRET') ? !empty($headers['HTTP_STRIPE_SIGNATURE'] ?? $headers['stripe-signature'] ?? '') : false;
  }
  if ($provider === 'paypal') {
    return getenv('SF_PAYPAL_WEBHOOK_ID') ? !empty($headers['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '') : false;
  }
  return false;
}
?>
