<?php
require_once __DIR__ . '/billing.php';

function sf_billing_complete_provider_checkout(string $checkoutToken, array $providerPayment = []): array {
  $pdo = sf_db();
  if (!$pdo || !sf_billing_is_ready()) {
    return ['ok' => false, 'message' => 'Billing database is not ready.'];
  }
  $checkout = sf_billing_checkout_by_token($checkoutToken);
  if (!$checkout) {
    return ['ok' => false, 'message' => 'Checkout was not found.'];
  }
  if (($checkout['status'] ?? '') === 'completed') {
    return ['ok' => true, 'message' => 'Checkout already completed.'];
  }
  $plan = sf_billing_plan((int)$checkout['plan_id']);
  if (!$plan) {
    return ['ok' => false, 'message' => 'Plan is no longer active.'];
  }
  $periodEnd = sf_billing_period_end($plan);
  $provider = (string)($providerPayment['provider'] ?? $checkout['provider'] ?? sf_billing_provider());
  $paymentId = (string)($providerPayment['provider_payment_id'] ?? ($provider . '_pay_' . substr(hash('sha256', $checkoutToken . microtime(true)), 0, 18)));
  $subscriptionRef = (string)($providerPayment['provider_subscription_id'] ?? ($provider . '_sub_' . substr(hash('sha256', $checkoutToken . 'sub'), 0, 18)));
  $customerRef = (string)($providerPayment['provider_customer_id'] ?? '');
  $amount = (int)($checkout['amount_cents'] ?? $plan['price_cents'] ?? 0);
  $currency = (string)($checkout['currency'] ?? 'USD');
  $userId = (int)$checkout['user_id'];
  try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE user_subscriptions SET status='canceled', updated_at=NOW() WHERE user_id=? AND status IN ('active','trialing','past_due')")->execute([$userId]);
    $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, status, current_period_start, current_period_end, external_subscription_id, payment_provider, provider_customer_id, provider_subscription_id) VALUES (?, ?, 'active', NOW(), ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, (int)$plan['id'], $periodEnd, $subscriptionRef, $provider, $customerRef, $subscriptionRef]);
    $subscriptionId = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE subscription_checkouts SET status='completed', provider_payment_id=?, completed_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$paymentId, (int)$checkout['id']]);
    $invoiceNumber = 'SF-' . strtoupper(substr(hash('sha256', (string)$subscriptionId . $checkoutToken), 0, 10));
    $invoice = $pdo->prepare("INSERT INTO invoices (user_id, subscription_id, invoice_number, status, subtotal_cents, tax_cents, total_cents, currency, provider_invoice_id, due_at, paid_at, metadata_json) VALUES (?, ?, ?, 'paid', ?, 0, ?, ?, ?, NOW(), NOW(), ?)");
    $invoice->execute([$userId, $subscriptionId, $invoiceNumber, $amount, $amount, $currency, $provider . '_invoice_' . $invoiceNumber, json_encode(['provider_event' => $providerPayment], JSON_UNESCAPED_SLASHES)]);
    $invoiceId = (int)$pdo->lastInsertId();
    $transaction = $pdo->prepare("INSERT INTO payment_transactions (user_id, subscription_id, invoice_id, checkout_id, provider, provider_payment_id, transaction_type, status, amount_cents, currency, raw_payload_json) VALUES (?, ?, ?, ?, ?, ?, 'subscription', 'paid', ?, ?, ?)");
    $transaction->execute([$userId, $subscriptionId, $invoiceId, (int)$checkout['id'], $provider, $paymentId, $amount, $currency, json_encode($providerPayment, JSON_UNESCAPED_SLASHES)]);
    sf_billing_create_entitlement_grants($pdo, $userId, $plan, $subscriptionId);
    $pdo->commit();
    return ['ok' => true, 'message' => 'Provider checkout activated.', 'subscription_id' => $subscriptionId, 'invoice_id' => $invoiceId];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Stonefellow provider checkout activation failed: ' . $e->getMessage());
    return ['ok' => false, 'message' => $e->getMessage()];
  }
}
?>
