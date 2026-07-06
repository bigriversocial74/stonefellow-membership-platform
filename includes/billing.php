<?php
require_once __DIR__ . '/membership.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/payment_gateway.php';

function sf_billing_table_exists(string $table): bool {
  $pdo = sf_db();
  if (!$pdo) {
    return false;
  }
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    error_log('Stonefellow billing table check failed: ' . $e->getMessage());
    return false;
  }
}

function sf_billing_column_exists(string $table, string $column): bool {
  $pdo = sf_db();
  if (!$pdo || !sf_billing_table_exists($table)) {
    return false;
  }
  try {
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
    $stmt->execute([$column]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function sf_billing_money(int $cents, string $currency = 'USD'): string {
  return '$' . number_format($cents / 100, 2) . ($currency !== 'USD' ? ' ' . strtoupper($currency) : '');
}

function sf_billing_provider(): string {
  return function_exists('sf_payment_provider') ? sf_payment_provider() : (getenv('SF_PAYMENT_PROVIDER') ?: 'sandbox');
}

function sf_billing_is_ready(): bool {
  return sf_db() instanceof PDO
    && sf_billing_table_exists('subscription_plans')
    && sf_billing_table_exists('subscription_checkouts')
    && sf_billing_table_exists('payment_transactions')
    && sf_billing_table_exists('invoices');
}

function sf_billing_plan(int $planId): ?array {
  $pdo = sf_db();
  if (!$pdo) {
    return null;
  }
  try {
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    return $plan ?: null;
  } catch (Throwable $e) {
    error_log('Stonefellow billing plan lookup failed: ' . $e->getMessage());
    return null;
  }
}

function sf_billing_checkout_by_token(string $token): ?array {
  $pdo = sf_db();
  if (!$pdo || !sf_billing_table_exists('subscription_checkouts')) {
    return null;
  }
  try {
    $stmt = $pdo->prepare("\n      SELECT sc.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.price_cents, sp.billing_interval, sp.description, sp.allows_full_music, sp.allows_video_streaming, sp.allows_playlists, sp.allows_offline_downloads\n      FROM subscription_checkouts sc\n      INNER JOIN subscription_plans sp ON sp.id = sc.plan_id\n      WHERE sc.checkout_token = ?\n      LIMIT 1\n    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
  } catch (Throwable $e) {
    error_log('Stonefellow billing checkout lookup failed: ' . $e->getMessage());
    return null;
  }
}

function sf_billing_start_checkout(int $planId): ?string {
  $pdo = sf_auth_db_required();
  $user = sf_auth_user();
  if (!$pdo || !$user) {
    sf_auth_flash('warning', 'Sign in before starting checkout.');
    return null;
  }
  if (!sf_billing_is_ready()) {
    sf_auth_flash('warning', 'Billing tables are not installed yet. Run migration 004 before live checkout.');
    return null;
  }
  $plan = sf_billing_plan($planId);
  if (!$plan) {
    sf_auth_flash('error', 'Membership plan was not found.');
    return null;
  }
  $token = bin2hex(random_bytes(24));
  $expires = (new DateTimeImmutable('+45 minutes'))->format('Y-m-d H:i:s');
  $provider = sf_billing_provider();
  try {
    $stmt = $pdo->prepare("\n      INSERT INTO subscription_checkouts\n        (checkout_token, user_id, plan_id, provider, provider_checkout_id, status, amount_cents, currency, success_url, cancel_url, metadata_json, expires_at)\n      VALUES (?, ?, ?, ?, ?, 'pending', ?, 'USD', ?, ?, ?, ?)\n    ");
    $stmt->execute([
      $token,
      (int)$user['id'],
      (int)$plan['id'],
      $provider,
      (sf_payment_create_checkout(['checkout_token' => $token, 'amount_cents' => (int)$plan['price_cents'], 'local_checkout_url' => sf_url('billing-checkout.php?token=' . urlencode($token))])['provider_checkout_id'] ?? ($provider . '_checkout_' . substr($token, 0, 16))),
      (int)$plan['price_cents'],
      sf_url('billing-success.php?token=' . urlencode($token)),
      sf_url('billing-cancel.php?token=' . urlencode($token)),
      json_encode(['plan_slug' => $plan['slug'] ?? '', 'source' => 'subscribe_page'], JSON_UNESCAPED_SLASHES),
      $expires,
    ]);
    return $token;
  } catch (Throwable $e) {
    error_log('Stonefellow billing start checkout failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Checkout could not be started.');
    return null;
  }
}

function sf_billing_period_end(array $plan): string {
  $interval = (string)($plan['billing_interval'] ?? 'month');
  if ($interval === 'year') {
    return (new DateTimeImmutable('+1 year'))->format('Y-m-d H:i:s');
  }
  return (new DateTimeImmutable('+1 month'))->format('Y-m-d H:i:s');
}

function sf_billing_create_entitlement_grants(PDO $pdo, int $userId, array $plan, int $subscriptionId): void {
  if (!sf_billing_table_exists('content_access_grants')) {
    return;
  }
  $slug = (string)($plan['slug'] ?? '');
  $level = 'subscriber';
  if ($slug === 'founding-fan' || ($plan['plan_tier'] ?? '') === 'founding_fan') {
    $level = 'founding_fan';
  } elseif (!empty($plan['allows_offline_downloads'])) {
    $level = 'premium';
  }
  $types = [];
  if (!empty($plan['allows_full_music'])) {
    $types[] = 'song';
    $types[] = 'album';
  }
  if (!empty($plan['allows_video_streaming'])) {
    $types[] = 'video';
    $types[] = 'episode';
  }
  if (!empty($plan['allows_playlists'])) {
    $types[] = 'playlist';
  }
  $types[] = 'site_feature';
  $types = array_values(array_unique($types));
  $starts = (new DateTimeImmutable())->format('Y-m-d H:i:s');
  $expires = sf_billing_period_end($plan);
  $insert = $pdo->prepare("\n    INSERT INTO content_access_grants (user_id, content_type, content_id, grant_type, access_level, starts_at, expires_at, created_by_user_id)\n    VALUES (?, ?, NULL, 'subscription', ?, ?, ?, NULL)\n  ");
  foreach ($types as $type) {
    $insert->execute([$userId, $type, $level, $starts, $expires]);
  }
}

function sf_billing_complete_checkout(string $token, array $payment = []): bool {
  $pdo = sf_auth_db_required();
  $user = sf_auth_user();
  if (!$pdo || !$user) {
    sf_auth_flash('warning', 'Sign in before completing checkout.');
    return false;
  }
  if (!sf_billing_is_ready()) {
    sf_auth_flash('warning', 'Billing tables are not installed yet.');
    return false;
  }
  $checkout = sf_billing_checkout_by_token($token);
  if (!$checkout || (int)$checkout['user_id'] !== (int)$user['id']) {
    sf_auth_flash('error', 'Checkout session was not found.');
    return false;
  }
  if (($checkout['status'] ?? '') === 'completed') {
    sf_auth_flash('success', 'This checkout is already complete.');
    return true;
  }
  if (($checkout['status'] ?? '') !== 'pending') {
    sf_auth_flash('error', 'Checkout is no longer pending.');
    return false;
  }
  if (!empty($checkout['expires_at']) && strtotime((string)$checkout['expires_at']) < time()) {
    try {
      $pdo->prepare("UPDATE subscription_checkouts SET status='expired', updated_at=NOW() WHERE id=?")->execute([(int)$checkout['id']]);
    } catch (Throwable $e) {}
    sf_auth_flash('error', 'Checkout session expired. Start again.');
    return false;
  }

  $plan = sf_billing_plan((int)$checkout['plan_id']);
  if (!$plan) {
    sf_auth_flash('error', 'Membership plan is no longer active.');
    return false;
  }

  $provider = sf_billing_provider();
  $periodEnd = sf_billing_period_end($plan);
  $paymentId = $provider . '_pay_' . substr(hash('sha256', $token . microtime(true)), 0, 18);
  $subscriptionRef = $provider . '_sub_' . substr(hash('sha256', $token . 'sub'), 0, 18);
  $amount = (int)($checkout['amount_cents'] ?? $plan['price_cents'] ?? 0);
  $currency = (string)($checkout['currency'] ?? 'USD');

  try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE user_subscriptions SET status='canceled', updated_at=NOW() WHERE user_id=? AND status IN ('active','trialing','past_due')")->execute([(int)$user['id']]);
    $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, status, current_period_start, current_period_end, external_subscription_id) VALUES (?, ?, 'active', NOW(), ?, ?)");
    $stmt->execute([(int)$user['id'], (int)$plan['id'], $periodEnd, $subscriptionRef]);
    $subscriptionId = (int)$pdo->lastInsertId();

    $pdo->prepare("UPDATE subscription_checkouts SET status='completed', provider_payment_id=?, completed_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$paymentId, (int)$checkout['id']]);

    $invoiceNumber = 'SF-' . strtoupper(substr(hash('sha256', (string)$subscriptionId . $token), 0, 10));
    $invoice = $pdo->prepare("\n      INSERT INTO invoices (user_id, subscription_id, invoice_number, status, subtotal_cents, tax_cents, total_cents, currency, provider_invoice_id, due_at, paid_at)\n      VALUES (?, ?, ?, 'paid', ?, 0, ?, ?, ?, NOW(), NOW())\n    ");
    $invoice->execute([(int)$user['id'], $subscriptionId, $invoiceNumber, $amount, $amount, $currency, $provider . '_invoice_' . $invoiceNumber]);
    $invoiceId = (int)$pdo->lastInsertId();

    $transaction = $pdo->prepare("\n      INSERT INTO payment_transactions (user_id, subscription_id, invoice_id, checkout_id, provider, provider_payment_id, transaction_type, status, amount_cents, currency, raw_payload_json)\n      VALUES (?, ?, ?, ?, ?, ?, 'subscription', 'paid', ?, ?, ?)\n    ");
    $transaction->execute([
      (int)$user['id'],
      $subscriptionId,
      $invoiceId,
      (int)$checkout['id'],
      $provider,
      $paymentId,
      $amount,
      $currency,
      json_encode(['mode' => 'sandbox', 'payment' => $payment], JSON_UNESCAPED_SLASHES),
    ]);

    sf_billing_create_entitlement_grants($pdo, (int)$user['id'], $plan, $subscriptionId);
    $pdo->commit();
    sf_auth_flash('success', 'Membership activated: ' . ($plan['name'] ?? 'Stonefellow Access'));
    sf_notify_send_template('subscription_started', [
      'user_id' => (int)$user['id'],
      'email' => (string)$user['email'],
      'name' => (string)($user['display_name'] ?? $user['email']),
    ], [
      'plan_name' => (string)($plan['name'] ?? 'Stonefellow Access'),
      'period_end' => $periodEnd,
      'member_url' => sf_notify_absolute_url('member.php'),
    ], [
      'notification_type' => 'billing',
      'metadata' => ['event' => 'subscription_started', 'subscription_id' => $subscriptionId, 'checkout_id' => (int)$checkout['id']],
      'dispatch' => true,
    ]);
    sf_notify_send_template('payment_receipt', [
      'user_id' => (int)$user['id'],
      'email' => (string)$user['email'],
      'name' => (string)($user['display_name'] ?? $user['email']),
    ], [
      'invoice_number' => $invoiceNumber,
      'amount' => sf_billing_money($amount, $currency),
      'plan_name' => (string)($plan['name'] ?? 'Stonefellow Access'),
      'billing_url' => sf_notify_absolute_url('account-billing.php'),
    ], [
      'notification_type' => 'billing',
      'metadata' => ['event' => 'payment_receipt', 'invoice_id' => $invoiceId, 'payment_id' => $paymentId],
      'dispatch' => true,
    ]);
    return true;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Stonefellow billing complete checkout failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Payment could not be completed.');
    return false;
  }
}

function sf_billing_cancel_checkout(string $token): bool {
  $pdo = sf_db();
  $user = sf_auth_user();
  if (!$pdo || !$user || !sf_billing_table_exists('subscription_checkouts')) {
    return false;
  }
  try {
    $stmt = $pdo->prepare("UPDATE subscription_checkouts SET status='canceled', updated_at=NOW() WHERE checkout_token=? AND user_id=? AND status='pending'");
    $stmt->execute([$token, (int)$user['id']]);
    return true;
  } catch (Throwable $e) {
    return false;
  }
}

function sf_billing_active_subscription(?int $userId = null): ?array {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  if (!$pdo || !$userId || !sf_billing_table_exists('user_subscriptions')) {
    return null;
  }
  try {
    $stmt = $pdo->prepare("\n      SELECT us.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.price_cents, sp.billing_interval, sp.description\n      FROM user_subscriptions us\n      INNER JOIN subscription_plans sp ON sp.id = us.plan_id\n      WHERE us.user_id=?\n      ORDER BY FIELD(us.status,'active','trialing','past_due','canceled','expired'), us.current_period_end DESC, us.id DESC\n      LIMIT 1\n    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
  } catch (Throwable $e) {
    return null;
  }
}

function sf_billing_user_invoices(?int $userId = null): array {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  if (!$pdo || !$userId || !sf_billing_table_exists('invoices')) {
    return [];
  }
  try {
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 50');
    $stmt->execute([$userId]);
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    return [];
  }
}

function sf_billing_user_transactions(?int $userId = null): array {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  if (!$pdo || !$userId || !sf_billing_table_exists('payment_transactions')) {
    return [];
  }
  try {
    $stmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 50');
    $stmt->execute([$userId]);
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    return [];
  }
}

function sf_billing_cancel_subscription(int $subscriptionId, bool $immediate = false): bool {
  $pdo = sf_auth_db_required();
  $userId = sf_current_user_id();
  if (!$pdo || !$userId) {
    return false;
  }
  try {
    $stmt = $pdo->prepare('SELECT * FROM user_subscriptions WHERE id=? AND user_id=? LIMIT 1');
    $stmt->execute([$subscriptionId, $userId]);
    $sub = $stmt->fetch();
    if (!$sub) {
      sf_auth_flash('error', 'Subscription not found.');
      return false;
    }
    if ($immediate) {
      $pdo->prepare("UPDATE user_subscriptions SET status='canceled', updated_at=NOW() WHERE id=? AND user_id=?")->execute([$subscriptionId, $userId]);
      if (sf_billing_table_exists('content_access_grants')) {
        $pdo->prepare("UPDATE content_access_grants SET expires_at=NOW() WHERE user_id=? AND grant_type='subscription' AND (expires_at IS NULL OR expires_at > NOW())")->execute([$userId]);
      }
      sf_auth_flash('success', 'Subscription canceled immediately.');
      $recipient = sf_notify_user_recipient($userId);
      if ($recipient) {
        sf_notify_send_template('subscription_canceled', $recipient, [
          'subscription_status' => 'canceled',
          'period_end' => date('Y-m-d H:i:s'),
        ], ['notification_type' => 'billing', 'metadata' => ['event' => 'subscription_canceled', 'subscription_id' => $subscriptionId, 'immediate' => true], 'dispatch' => true]);
      }
      return true;
    }
    if (sf_billing_column_exists('user_subscriptions', 'cancel_at_period_end')) {
      $pdo->prepare("UPDATE user_subscriptions SET cancel_at_period_end=1, canceled_at=NOW(), updated_at=NOW() WHERE id=? AND user_id=?")->execute([$subscriptionId, $userId]);
      sf_auth_flash('success', 'Subscription will cancel at the end of the current period.');
      $recipient = sf_notify_user_recipient($userId);
      if ($recipient) {
        sf_notify_send_template('subscription_canceled', $recipient, [
          'subscription_status' => 'cancel_at_period_end',
          'period_end' => (string)($sub['current_period_end'] ?? ''),
        ], ['notification_type' => 'billing', 'metadata' => ['event' => 'subscription_cancel_at_period_end', 'subscription_id' => $subscriptionId], 'dispatch' => true]);
      }
    } else {
      sf_auth_flash('success', 'Cancellation request recorded. Run migration 004 to store cancel-at-period-end flags.');
    }
    return true;
  } catch (Throwable $e) {
    error_log('Stonefellow billing cancel failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Cancellation failed.');
    return false;
  }
}

function sf_billing_admin_summary(): array {
  $summary = [
    'checkouts' => 0,
    'completed_checkouts' => 0,
    'transactions' => 0,
    'revenue_cents' => 0,
    'invoices' => 0,
    'active_subscriptions' => 0,
  ];
  $pdo = sf_db();
  if (!$pdo) {
    return $summary;
  }
  try {
    if (sf_billing_table_exists('subscription_checkouts')) {
      $summary['checkouts'] = (int)$pdo->query('SELECT COUNT(*) FROM subscription_checkouts')->fetchColumn();
      $summary['completed_checkouts'] = (int)$pdo->query("SELECT COUNT(*) FROM subscription_checkouts WHERE status='completed'")->fetchColumn();
    }
    if (sf_billing_table_exists('payment_transactions')) {
      $row = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(amount_cents),0) AS revenue FROM payment_transactions WHERE status='paid'")->fetch();
      $summary['transactions'] = (int)($row['total'] ?? 0);
      $summary['revenue_cents'] = (int)($row['revenue'] ?? 0);
    }
    if (sf_billing_table_exists('invoices')) {
      $summary['invoices'] = (int)$pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
    }
    if (sf_billing_table_exists('user_subscriptions')) {
      $summary['active_subscriptions'] = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status IN ('active','trialing') AND (current_period_end IS NULL OR current_period_end >= NOW())")->fetchColumn();
    }
  } catch (Throwable $e) {
    error_log('Stonefellow billing summary failed: ' . $e->getMessage());
  }
  return $summary;
}
?>
