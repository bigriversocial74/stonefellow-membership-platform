<?php

declare(strict_types=1);

function sf_payment_stripe_subscription_request(string $id, bool $immediate): array
{
    if (!preg_match('/^sub_[A-Za-z0-9_]+$/', $id)) return ['ok' => false, 'error' => 'invalid_subscription_reference'];
    $secret = (string)(getenv('SF_STRIPE_SECRET_KEY') ?: '');
    if ($secret === '' || !function_exists('curl_init')) return ['ok' => false, 'error' => 'stripe_runtime_unavailable'];
    $raw = '';
    $overflow = false;
    $ch = curl_init('https://api.stripe.com/v1/subscriptions/' . rawurlencode($id));
    $options = [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret, 'Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$raw, &$overflow): int {
            if (strlen($raw) + strlen($chunk) > 1048576) { $overflow = true; return 0; }
            $raw .= $chunk;
            return strlen($chunk);
        },
    ];
    if ($immediate) $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    else {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query(['cancel_at_period_end' => 'true']);
    }
    curl_setopt_array($ch, $options);
    $ran = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = json_decode($raw, true, 64);
    $ok = !$overflow && $ran !== false && $code >= 200 && $code < 300 && is_array($json) && (string)($json['id'] ?? '') === $id;
    if ($ok && $immediate && !empty($json['deleted'])) return ['ok' => true, 'state' => 'canceled'];
    if ($ok && !$immediate && !empty($json['cancel_at_period_end'])) return ['ok' => true, 'state' => 'cancel_at_period_end'];
    return ['ok' => false, 'error' => $error ?: ((string)($json['error']['message'] ?? '') ?: 'stripe_cancellation_failed')];
}

function sf_payment_cancel_provider_subscription(array $subscription, bool $immediate = false): array
{
    $provider = (string)($subscription['payment_provider'] ?? 'sandbox');
    $reference = (string)($subscription['provider_subscription_id'] ?? $subscription['external_subscription_id'] ?? '');
    if ($provider === 'sandbox') return sf_revenue_sandbox_allowed('subscription') ? ['ok' => true, 'state' => $immediate ? 'canceled' : 'cancel_at_period_end'] : ['ok' => false, 'error' => 'sandbox_disabled'];
    if ($provider !== 'stripe' || $reference === '') return ['ok' => false, 'error' => 'provider_cancellation_unavailable'];
    return sf_payment_stripe_subscription_request($reference, $immediate);
}

function sf_payment_invoice_subscription_reference(array $invoice): string
{
    return (string)($invoice['subscription'] ?? $invoice['parent']['subscription_details']['subscription'] ?? '');
}

function sf_payment_invoice_period_end(array $invoice): ?string
{
    $latest = 0;
    foreach (($invoice['lines']['data'] ?? []) as $line) $latest = max($latest, (int)($line['period']['end'] ?? 0));
    return $latest > 0 ? date('Y-m-d H:i:s', $latest) : null;
}

function sf_payment_process_subscription_invoice(PDO $pdo, string $type, array $invoice, array $payload): array
{
    $reference = sf_payment_invoice_subscription_reference($invoice);
    if ($reference === '') return ['ok' => true, 'message' => 'Invoice has no subscription reference.', 'ignored' => true];
    $stmt = $pdo->prepare('SELECT * FROM user_subscriptions WHERE provider_subscription_id=? OR external_subscription_id=? LIMIT 1');
    $stmt->execute([$reference, $reference]);
    $subscription = $stmt->fetch();
    if (!$subscription) return ['ok' => true, 'message' => 'Invoice subscription is not registered locally.', 'ignored' => true];
    $paid = $type === 'invoice.paid' && !empty($invoice['paid']) && (string)($invoice['status'] ?? '') === 'paid';
    $paymentId = substr((string)($invoice['payment_intent'] ?? $invoice['charge'] ?? $invoice['id'] ?? ''), 0, 190);
    $amount = max(0, (int)($invoice['amount_paid'] ?? $invoice['amount_due'] ?? 0));
    $currency = sf_revenue_normalize_currency($invoice['currency'] ?? 'USD') ?: 'USD';
    $idempotency = 'stripe-invoice-' . substr((string)($invoice['id'] ?? hash('sha256', json_encode($invoice))), 0, 150);
    if ($paid) {
        $periodEnd = sf_payment_invoice_period_end($invoice);
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE user_subscriptions SET status='active',current_period_end=COALESCE(?,current_period_end),updated_at=NOW() WHERE id=?")->execute([$periodEnd, (int)$subscription['id']]);
        $pdo->prepare("UPDATE content_access_grants SET expires_at=COALESCE(?,expires_at) WHERE user_id=? AND grant_type='subscription' AND source_id=?")->execute([$periodEnd, (int)$subscription['user_id'], (int)$subscription['id']]);
        $pdo->prepare("INSERT INTO payment_transactions (user_id,subscription_id,provider,provider_payment_id,idempotency_key,transaction_type,status,amount_cents,currency,raw_payload_json) VALUES (?,?,'stripe',?,?,'subscription','paid',?,?,?) ON DUPLICATE KEY UPDATE status='paid',amount_cents=VALUES(amount_cents),raw_payload_json=VALUES(raw_payload_json),updated_at=NOW()")->execute([(int)$subscription['user_id'], (int)$subscription['id'], $paymentId ?: null, $idempotency, $amount, $currency, json_encode(sf_revenue_redact_payload($payload), JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        return ['ok' => true, 'message' => 'Subscription renewal payment applied.'];
    }
    $pdo->prepare("UPDATE user_subscriptions SET status='past_due',updated_at=NOW() WHERE id=?")->execute([(int)$subscription['id']]);
    $pdo->prepare("INSERT INTO payment_transactions (user_id,subscription_id,provider,provider_payment_id,idempotency_key,transaction_type,status,amount_cents,currency,raw_payload_json) VALUES (?,?,'stripe',?,?,'subscription','failed',?,?,?) ON DUPLICATE KEY UPDATE status='failed',raw_payload_json=VALUES(raw_payload_json),updated_at=NOW()")->execute([(int)$subscription['user_id'], (int)$subscription['id'], $paymentId ?: null, $idempotency, max(0, (int)($invoice['amount_due'] ?? 0)), $currency, json_encode(sf_revenue_redact_payload($payload), JSON_UNESCAPED_SLASHES)]);
    return ['ok' => true, 'message' => 'Subscription payment failure recorded.'];
}

function sf_payment_process_gateway_event(string $provider, string $type, array $payload): array
{
    $pdo = sf_db();
    if (!$pdo) return ['ok' => false, 'message' => 'No database connection.'];
    $object = sf_payment_event_object($payload);
    try {
        if ($provider === 'stripe' && $type === 'checkout.session.completed') {
            if (($object['metadata']['checkout_kind'] ?? '') === 'merch') return ['ok' => true, 'message' => 'Merch event is handled by the commerce router.', 'ignored' => true];
            $token = sf_payment_checkout_token_from_event($payload);
            if ($token === '') return ['ok' => false, 'message' => 'Checkout token missing.'];
            return sf_billing_complete_provider_checkout($token, [
                'provider' => 'stripe',
                'verified' => true,
                'provider_payment_id' => (string)($object['payment_intent'] ?? $object['id'] ?? ''),
                'provider_subscription_id' => (string)($object['subscription'] ?? ''),
                'provider_customer_id' => (string)($object['customer'] ?? ''),
                'amount_cents' => isset($object['amount_total']) ? (int)$object['amount_total'] : null,
                'currency' => sf_revenue_normalize_currency($object['currency'] ?? ''),
                'payment_status' => (string)($object['payment_status'] ?? ''),
                'payload' => sf_revenue_redact_payload($payload),
            ]);
        }
        if ($provider === 'stripe' && in_array($type, ['invoice.paid','invoice.payment_failed'], true)) return sf_payment_process_subscription_invoice($pdo, $type, $object, $payload);
        if ($provider === 'stripe' && in_array($type, ['customer.subscription.deleted','customer.subscription.paused'], true)) {
            $reference = (string)($object['id'] ?? '');
            if ($reference !== '' && sf_settings_table_exists('user_subscriptions')) {
                $stmt = $pdo->prepare('SELECT user_id FROM user_subscriptions WHERE provider_subscription_id=? OR external_subscription_id=? LIMIT 1');
                $stmt->execute([$reference, $reference]);
                $userId = (int)$stmt->fetchColumn();
                $pdo->prepare("UPDATE user_subscriptions SET status='canceled',canceled_at=NOW(),updated_at=NOW() WHERE provider_subscription_id=? OR external_subscription_id=?")->execute([$reference, $reference]);
                if ($userId) sf_revenue_expire_subscription_grants($pdo, $userId);
                return ['ok' => true, 'message' => 'Subscription canceled.'];
            }
        }
        if ($provider === 'stripe' && $type === 'customer.subscription.updated') {
            $reference = (string)($object['id'] ?? '');
            $status = in_array((string)($object['status'] ?? ''), ['active','trialing','past_due','canceled'], true) ? (string)$object['status'] : 'past_due';
            $periodEnd = !empty($object['current_period_end']) ? date('Y-m-d H:i:s', (int)$object['current_period_end']) : null;
            $pdo->prepare('UPDATE user_subscriptions SET status=?,cancel_at_period_end=?,current_period_end=COALESCE(?,current_period_end),updated_at=NOW() WHERE provider_subscription_id=? OR external_subscription_id=?')->execute([$status, !empty($object['cancel_at_period_end']) ? 1 : 0, $periodEnd, $reference, $reference]);
            return ['ok' => true, 'message' => 'Subscription state synchronized.'];
        }
        return ['ok' => true, 'message' => 'Event recorded.', 'ignored' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow gateway processing failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Gateway event processing failed.'];
    }
}
?>
