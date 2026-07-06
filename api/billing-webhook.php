<?php
require_once __DIR__ . '/../includes/billing.php';

$payload = sf_request_json();
$pdo = sf_db();
if (!$pdo || !sf_billing_table_exists('billing_webhook_events')) {
  sf_json_response(['ok' => false, 'error' => 'Billing webhook table unavailable. Run migration 004.'], 503);
}

$secret = getenv('SF_BILLING_WEBHOOK_SECRET') ?: '';
$provided = $_SERVER['HTTP_X_STONEFELLOW_SIGNATURE'] ?? ($_GET['signature'] ?? '');
if ($secret !== '') {
  $raw = file_get_contents('php://input') ?: json_encode($payload, JSON_UNESCAPED_SLASHES);
  $expected = hash_hmac('sha256', $raw, $secret);
  if (!hash_equals($expected, (string)$provided)) {
    sf_json_response(['ok' => false, 'error' => 'Invalid signature.'], 401);
  }
}

$provider = substr((string)($payload['provider'] ?? sf_billing_provider()), 0, 80);
$eventId = substr((string)($payload['id'] ?? $payload['event_id'] ?? ('evt_' . bin2hex(random_bytes(8)))), 0, 190);
$eventType = substr((string)($payload['type'] ?? $payload['event_type'] ?? 'unknown'), 0, 120);
$status = 'received';
$error = null;
$processedAt = null;

try {
  $stmt = $pdo->prepare("INSERT IGNORE INTO billing_webhook_events (provider, provider_event_id, event_type, status, payload_json) VALUES (?, ?, ?, 'received', ?)");
  $stmt->execute([$provider, $eventId, $eventType, json_encode($payload, JSON_UNESCAPED_SLASHES)]);

  if (in_array($eventType, ['checkout.completed', 'payment.succeeded', 'invoice.paid'], true)) {
    $token = (string)($payload['checkout_token'] ?? $payload['data']['checkout_token'] ?? '');
    if ($token !== '') {
      // Webhook completion for production processors should map the external event
      // back to a local checkout token. In sandbox, the visible checkout page does this.
      $checkout = sf_billing_checkout_by_token($token);
      if ($checkout && ($checkout['status'] ?? '') === 'pending') {
        $pdo->prepare("UPDATE subscription_checkouts SET provider_payment_id=?, status='completed', completed_at=NOW(), updated_at=NOW() WHERE checkout_token=?")->execute([
          substr((string)($payload['payment_id'] ?? $payload['data']['payment_id'] ?? $eventId), 0, 190),
          $token,
        ]);
      }
      $status = 'processed';
      $processedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    } else {
      $status = 'ignored';
      $error = 'No checkout_token supplied.';
    }
  } elseif (in_array($eventType, ['subscription.canceled', 'customer.subscription.deleted'], true)) {
    $subscriptionRef = (string)($payload['subscription_id'] ?? $payload['data']['subscription_id'] ?? '');
    if ($subscriptionRef !== '' && sf_billing_table_exists('user_subscriptions')) {
      $subscriptionRow = null;
      try {
        $lookup = $pdo->prepare('SELECT * FROM user_subscriptions WHERE external_subscription_id=? OR provider_subscription_id=? LIMIT 1');
        $lookup->execute([$subscriptionRef, $subscriptionRef]);
        $subscriptionRow = $lookup->fetch() ?: null;
      } catch (Throwable $ignore) {}
      $pdo->prepare("UPDATE user_subscriptions SET status='canceled', canceled_at=NOW(), updated_at=NOW() WHERE external_subscription_id=? OR provider_subscription_id=?")->execute([$subscriptionRef, $subscriptionRef]);
      if ($subscriptionRow && !empty($subscriptionRow['user_id'])) {
        $recipient = sf_notify_user_recipient((int)$subscriptionRow['user_id']);
        if ($recipient) {
          sf_notify_send_template('subscription_canceled', $recipient, [
            'subscription_status' => 'canceled',
            'period_end' => (string)($subscriptionRow['current_period_end'] ?? ''),
          ], ['notification_type' => 'billing', 'metadata' => ['event' => 'webhook_subscription_canceled', 'subscription_ref' => $subscriptionRef], 'dispatch' => true]);
        }
      }
      $status = 'processed';
      $processedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    } else {
      $status = 'ignored';
      $error = 'No subscription reference supplied.';
    }
  } elseif (in_array($eventType, ['payment.failed', 'invoice.payment_failed', 'charge.failed'], true)) {
    foreach (sf_notify_admin_recipients() as $adminRecipient) {
      sf_notify_send_template('admin_failed_payment', $adminRecipient, [
        'payment_status' => (string)($payload['status'] ?? $eventType),
        'provider_payment_id' => (string)($payload['payment_id'] ?? $payload['data']['payment_id'] ?? $eventId),
        'error_message' => (string)($payload['error_message'] ?? $payload['data']['error_message'] ?? 'Provider reported a failed payment.'),
      ], ['notification_type' => 'admin', 'metadata' => ['event' => $eventType, 'provider_event_id' => $eventId], 'dispatch' => true]);
    }
    $status = 'processed';
    $processedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');
  } else {
    $status = 'ignored';
  }

  $pdo->prepare("UPDATE billing_webhook_events SET status=?, error_message=?, processed_at=? WHERE provider=? AND provider_event_id=?")->execute([$status, $error, $processedAt, $provider, $eventId]);
  sf_json_response(['ok' => true, 'status' => $status, 'event_type' => $eventType]);
} catch (Throwable $e) {
  error_log('Stonefellow billing webhook failed: ' . $e->getMessage());
  try {
    $pdo->prepare("UPDATE billing_webhook_events SET status='failed', error_message=? WHERE provider=? AND provider_event_id=?")->execute([$e->getMessage(), $provider, $eventId]);
  } catch (Throwable $ignore) {}
  sf_json_response(['ok' => false, 'error' => 'Webhook processing failed.'], 500);
}
