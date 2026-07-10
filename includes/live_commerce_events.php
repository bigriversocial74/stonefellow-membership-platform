<?php

declare(strict_types=1);

function sf_commerce_find_order_by_payment(string $paymentIntent, string $chargeId = ''): ?array
{
    if ($paymentIntent === '' && $chargeId === '') return null;
    try {
        $stmt = sf_db()->prepare('SELECT * FROM orders WHERE (external_payment_id<>\'\' AND external_payment_id=?) OR (provider_charge_id<>\'\' AND provider_charge_id=?) LIMIT 1');
        $stmt->execute([$paymentIntent, $chargeId]);
        $order = $stmt->fetch();
        if ($order) $order['items'] = sf_store_order_items((int)$order['id']);
        return $order ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_commerce_apply_refund_event(array $charge, array $event): array
{
    $paymentIntent = (string)($charge['payment_intent'] ?? '');
    $chargeId = (string)($charge['id'] ?? '');
    $order = sf_commerce_find_order_by_payment($paymentIntent, $chargeId);
    if (!$order) return ['ok' => true, 'message' => 'Refund event does not belong to a merch order.', 'ignored' => true];
    $amount = max(0, (int)($charge['amount'] ?? $order['total_cents'] ?? 0));
    $refunded = max(0, (int)($charge['amount_refunded'] ?? 0));
    $full = $amount > 0 && $refunded >= $amount;
    $pdo = sf_db();
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
        $lock->execute([(int)$order['id']]);
        $current = $lock->fetch();
        if (!$current) throw new RuntimeException('Order disappeared during refund processing.');
        if ($full && ($current['payment_status'] ?? '') !== 'refunded') {
            foreach ($order['items'] as $item) {
                $qty = max(0, (int)($item['quantity'] ?? 0));
                $productId = (int)($item['product_id'] ?? 0);
                $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
                if ($qty <= 0 || $productId <= 0) continue;
                if ($variantId) $pdo->prepare('UPDATE product_variants SET inventory_quantity=inventory_quantity+?,updated_at=NOW() WHERE id=?')->execute([$qty, $variantId]);
                else $pdo->prepare('UPDATE products SET inventory_quantity=inventory_quantity+?,updated_at=NOW() WHERE id=?')->execute([$qty, $productId]);
                sf_commerce_inventory_movement($pdo, $productId, $variantId, (int)$order['id'], $qty, 'refund', 'Full Stripe refund confirmed.');
            }
            $pdo->prepare("UPDATE orders SET status='refunded',payment_status='refunded',provider_charge_id=?,refunded_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$chargeId ?: null, (int)$order['id']]);
            $pdo->prepare("UPDATE merch_checkouts SET status='refunded',updated_at=NOW() WHERE order_id=?")->execute([(int)$order['id']]);
            sf_commerce_order_history($pdo, (int)$order['id'], (string)$current['status'], 'refunded', 'Full Stripe refund confirmed by signed webhook.');
        } elseif (!$full) {
            $pdo->prepare("UPDATE orders SET payment_status='partially_refunded',provider_charge_id=?,updated_at=NOW() WHERE id=?")->execute([$chargeId ?: null, (int)$order['id']]);
            sf_commerce_order_history($pdo, (int)$order['id'], (string)$current['status'], (string)$current['status'], 'Partial Stripe refund confirmed. Inventory was not automatically restocked.');
        }
        $idempotency = 'stripe-refund-' . ($chargeId ?: $paymentIntent) . '-' . $refunded;
        $tx = $pdo->prepare("INSERT INTO payment_transactions (user_id,merchant_id,payment_account_id,order_id,provider,provider_payment_id,provider_charge_id,idempotency_key,transaction_type,status,amount_cents,refunded_amount_cents,currency,raw_payload_json) VALUES (?,?,?,?, 'stripe',?,?,?,'refund','refunded',?,?,?,?) ON DUPLICATE KEY UPDATE status='refunded',refunded_amount_cents=VALUES(refunded_amount_cents),raw_payload_json=VALUES(raw_payload_json),updated_at=NOW()");
        $tx->execute([(int)$order['user_id'] ?: null, (int)$order['merchant_id'] ?: null, (int)$order['payment_account_id'] ?: null, (int)$order['id'], $paymentIntent ?: null, $chargeId ?: null, $idempotency, $refunded, $refunded, (string)($order['payment_currency'] ?? 'USD'), json_encode(sf_revenue_redact_payload($event), JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        return ['ok' => true, 'message' => $full ? 'Full refund applied.' : 'Partial refund recorded.', 'order_id' => (int)$order['id']];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow refund event processing failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Refund event could not be applied.'];
    }
}

function sf_commerce_apply_dispute_event(array $dispute, array $event): array
{
    $paymentIntent = (string)($dispute['payment_intent'] ?? '');
    $chargeId = (string)($dispute['charge'] ?? '');
    $order = sf_commerce_find_order_by_payment($paymentIntent, $chargeId);
    if (!$order) return ['ok' => true, 'message' => 'Dispute does not belong to a merch order.', 'ignored' => true];
    try {
        sf_db()->beginTransaction();
        sf_db()->prepare("UPDATE orders SET payment_status='disputed',provider_charge_id=?,disputed_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$chargeId ?: null, (int)$order['id']]);
        sf_db()->prepare("UPDATE merch_checkouts SET status='disputed',updated_at=NOW() WHERE order_id=?")->execute([(int)$order['id']]);
        $idempotency = 'stripe-dispute-' . substr((string)($dispute['id'] ?? hash('sha256', json_encode($dispute))), 0, 150);
        sf_db()->prepare("INSERT INTO payment_transactions (user_id,merchant_id,payment_account_id,order_id,provider,provider_payment_id,provider_charge_id,idempotency_key,transaction_type,status,amount_cents,currency,raw_payload_json) VALUES (?,?,?,?, 'stripe',?,?,?,'dispute','failed',?,?,?) ON DUPLICATE KEY UPDATE raw_payload_json=VALUES(raw_payload_json),updated_at=NOW()")->execute([(int)$order['user_id'] ?: null, (int)$order['merchant_id'] ?: null, (int)$order['payment_account_id'] ?: null, (int)$order['id'], $paymentIntent ?: null, $chargeId ?: null, $idempotency, (int)($dispute['amount'] ?? 0), strtoupper((string)($dispute['currency'] ?? $order['payment_currency'] ?? 'USD')), json_encode(sf_revenue_redact_payload($event), JSON_UNESCAPED_SLASHES)]);
        sf_commerce_order_history(sf_db(), (int)$order['id'], (string)$order['status'], (string)$order['status'], 'Stripe dispute opened; fulfillment should be reviewed immediately.');
        sf_db()->commit();
        return ['ok' => true, 'message' => 'Dispute recorded.', 'order_id' => (int)$order['id']];
    } catch (Throwable $e) {
        if (sf_db()?->inTransaction()) sf_db()->rollBack();
        return ['ok' => false, 'message' => 'Dispute could not be recorded.'];
    }
}

function sf_commerce_process_gateway_event(string $provider, string $type, array $payload): array
{
    if ($provider !== 'stripe') return ['handled' => false, 'ok' => true, 'message' => 'Commerce provider event not handled.'];
    $object = $payload['data']['object'] ?? [];
    if (!is_array($object)) $object = [];
    if ($type === 'account.updated') {
        $accountId = (string)($object['id'] ?? '');
        if ($accountId === '' || !sf_commerce_table_exists('merchant_payment_accounts')) return ['handled' => true, 'ok' => false, 'message' => 'Connected account event is invalid.'];
        try {
            $stmt = sf_db()->prepare("SELECT * FROM merchant_payment_accounts WHERE provider='stripe' AND provider_account_id=? LIMIT 1");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch();
            if (!$account) return ['handled' => true, 'ok' => true, 'message' => 'Connected account is not registered locally.', 'ignored' => true];
            $sync = sf_commerce_sync_stripe_account($account, $object);
            return ['handled' => true, 'ok' => !empty($sync['ok']), 'message' => $sync['ok'] ? 'Connected account synchronized.' : (string)$sync['error']];
        } catch (Throwable $e) {
            return ['handled' => true, 'ok' => false, 'message' => 'Connected account event failed.'];
        }
    }
    $kind = (string)($object['metadata']['checkout_kind'] ?? $object['payment_intent_data']['metadata']['checkout_kind'] ?? '');
    $token = (string)($object['client_reference_id'] ?? $object['metadata']['checkout_token'] ?? '');
    if (in_array($type, ['checkout.session.completed','checkout.session.async_payment_succeeded'], true) && ($kind === 'merch' || preg_match('/^[a-f0-9]{48}$/', $token))) {
        if ($type === 'checkout.session.completed' && !in_array((string)($object['payment_status'] ?? ''), ['paid','no_payment_required'], true)) {
            return ['handled' => true, 'ok' => true, 'message' => 'Merch checkout is awaiting asynchronous settlement.', 'pending' => true];
        }
        $result = sf_commerce_complete_checkout($token, $object, $payload);
        return ['handled' => true] + $result;
    }
    if (in_array($type, ['checkout.session.expired','checkout.session.async_payment_failed'], true) && ($kind === 'merch' || preg_match('/^[a-f0-9]{48}$/', $token))) {
        $result = sf_commerce_fail_checkout($token, $type === 'checkout.session.expired' ? 'checkout_expired' : 'async_payment_failed', 'Stripe checkout did not complete.', $type === 'checkout.session.expired' ? 'expired' : 'failed');
        return ['handled' => true] + $result;
    }
    if ($type === 'payment_intent.payment_failed' && ($kind === 'merch' || preg_match('/^[a-f0-9]{48}$/', $token))) {
        $error = (string)($object['last_payment_error']['code'] ?? $type);
        $message = (string)($object['last_payment_error']['message'] ?? 'Stripe payment attempt failed.');
        return ['handled' => true] + sf_commerce_record_payment_attempt_failure($token, $error, $message);
    }
    if ($type === 'payment_intent.canceled' && ($kind === 'merch' || preg_match('/^[a-f0-9]{48}$/', $token))) {
        return ['handled' => true] + sf_commerce_fail_checkout($token, 'payment_intent_canceled', 'Stripe canceled the payment intent.', 'failed');
    }
    if ($type === 'charge.refunded') return ['handled' => true] + sf_commerce_apply_refund_event($object, $payload);
    if ($type === 'charge.dispute.created') return ['handled' => true] + sf_commerce_apply_dispute_event($object, $payload);
    if ($type === 'charge.dispute.closed') {
        $paymentIntent = (string)($object['payment_intent'] ?? '');
        $chargeId = (string)($object['charge'] ?? '');
        $order = sf_commerce_find_order_by_payment($paymentIntent, $chargeId);
        if (!$order) return ['handled' => true, 'ok' => true, 'message' => 'Closed dispute does not belong to a merch order.', 'ignored' => true];
        $won = (string)($object['status'] ?? '') === 'won';
        try {
            sf_db()->prepare("UPDATE orders SET payment_status=?,disputed_at=NULL,updated_at=NOW() WHERE id=?")->execute([$won ? 'paid' : 'disputed', (int)$order['id']]);
            if ($won) sf_db()->prepare("UPDATE merch_checkouts SET status='completed',updated_at=NOW() WHERE order_id=?")->execute([(int)$order['id']]);
            sf_commerce_order_history(sf_db(), (int)$order['id'], (string)$order['status'], (string)$order['status'], $won ? 'Stripe dispute closed in the merchant’s favor.' : 'Stripe dispute closed against the merchant; review loss and refund evidence.');
            return ['handled' => true, 'ok' => true, 'message' => $won ? 'Dispute won and payment restored.' : 'Dispute closed against merchant.', 'order_id' => (int)$order['id']];
        } catch (Throwable $e) {
            return ['handled' => true, 'ok' => false, 'message' => 'Closed dispute could not be applied.'];
        }
    }
    return ['handled' => false, 'ok' => true, 'message' => 'Commerce event not applicable.'];
}

function sf_commerce_request_full_refund(int $orderId): array
{
    if ($orderId <= 0) return ['ok' => false, 'error' => 'Order is invalid.'];
    $order = sf_store_order_by_id($orderId);
    if (!$order || ($order['payment_provider'] ?? '') !== 'stripe' || ($order['payment_status'] ?? '') !== 'paid') return ['ok' => false, 'error' => 'Only settled Stripe orders can be refunded.'];
    $paymentIntent = (string)($order['external_payment_id'] ?? '');
    if (!preg_match('/^pi_[A-Za-z0-9_]+$/', $paymentIntent)) return ['ok' => false, 'error' => 'Stripe payment reference is missing.'];
    $idempotency = 'stonefellow-refund-order-' . $orderId . '-full';
    $result = sf_stripe_api_request('POST', '/refunds', [
        'payment_intent' => $paymentIntent,
        'metadata[stonefellow_order_id]' => $orderId,
        'metadata[stonefellow_order_number]' => (string)$order['order_number'],
    ], $idempotency);
    if (empty($result['ok'])) return ['ok' => false, 'error' => $result['error'] ?: 'Stripe refund request failed.'];
    $refund = $result['body'];
    try {
        sf_db()->prepare("INSERT INTO payment_transactions (user_id,merchant_id,payment_account_id,order_id,provider,provider_payment_id,idempotency_key,transaction_type,status,amount_cents,refunded_amount_cents,currency,raw_payload_json) VALUES (?,?,?,?, 'stripe',?,?,'refund','pending',?,?,?,?) ON DUPLICATE KEY UPDATE raw_payload_json=VALUES(raw_payload_json),updated_at=NOW()")->execute([(int)$order['user_id'] ?: null, (int)$order['merchant_id'] ?: null, (int)$order['payment_account_id'] ?: null, $orderId, $paymentIntent, $idempotency, (int)$order['total_cents'], 0, (string)($order['payment_currency'] ?? 'USD'), json_encode(sf_revenue_redact_payload($refund), JSON_UNESCAPED_SLASHES)]);
        sf_commerce_order_history(sf_db(), $orderId, (string)$order['status'], (string)$order['status'], 'Full Stripe refund requested; awaiting signed webhook confirmation.');
    } catch (Throwable $e) {
        error_log('Stonefellow refund request log failed: ' . $e->getMessage());
    }
    return ['ok' => true, 'message' => 'Stripe refund requested. Final order state will update after the signed webhook.', 'refund_id' => (string)($refund['id'] ?? '')];
}
