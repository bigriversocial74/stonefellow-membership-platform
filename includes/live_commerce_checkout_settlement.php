<?php

declare(strict_types=1);

function sf_commerce_checkout_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $token) || !sf_commerce_table_exists('merch_checkouts')) return null;
    try {
        $stmt = sf_db()->prepare('SELECT c.*,o.order_number,o.receipt_token,o.user_id,o.status AS order_status,o.payment_status,o.customer_email,o.total_cents AS order_total_cents FROM merch_checkouts c INNER JOIN orders o ON o.id=c.order_id WHERE c.checkout_token=? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_commerce_checkout_authorized(array $checkout, string $receiptKey = ''): bool
{
    $owner = sf_current_user_id() > 0 && (int)($checkout['user_id'] ?? 0) === sf_current_user_id();
    $keyOk = $receiptKey !== '' && hash_equals((string)($checkout['receipt_token'] ?? ''), $receiptKey);
    $session = $_SESSION['sf_live_commerce_checkout'] ?? [];
    $sessionOk = is_array($session) && hash_equals((string)($session['checkout_token'] ?? ''), (string)($checkout['checkout_token'] ?? ''));
    return $owner || $keyOk || $sessionOk;
}

function sf_commerce_record_payment_attempt_failure(string $token, string $code, string $message): array
{
    $checkout = sf_commerce_checkout_by_token($token);
    if (!$checkout) return ['ok' => false, 'message' => 'Checkout was not found.'];
    if (in_array((string)$checkout['status'], ['completed','refunded','disputed'], true)) return ['ok' => true, 'message' => 'Finalized checkout was not changed.', 'duplicate' => true];
    try {
        sf_db()->prepare("UPDATE orders SET payment_status='failed',payment_failure_code=?,payment_failure_message=?,updated_at=NOW() WHERE id=? AND status='pending'")->execute([substr($code, 0, 120), substr($message, 0, 500), (int)$checkout['order_id']]);
        sf_commerce_order_history(sf_db(), (int)$checkout['order_id'], 'pending', 'pending', 'Stripe payment attempt failed; checkout remains open for retry until expiration.');
        return ['ok' => true, 'message' => 'Payment attempt failure recorded; checkout remains open for recovery.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Payment attempt failure could not be recorded.'];
    }
}

function sf_commerce_fail_checkout(string $token, string $code, string $message, string $checkoutStatus = 'failed'): array
{
    $checkout = sf_commerce_checkout_by_token($token);
    if (!$checkout) return ['ok' => false, 'message' => 'Checkout was not found.'];
    if (in_array((string)$checkout['status'], ['completed','refunded','disputed'], true)) return ['ok' => true, 'message' => 'Completed checkout was not changed.', 'duplicate' => true];
    $pdo = sf_db();
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT * FROM merch_checkouts WHERE id=? FOR UPDATE');
        $lock->execute([(int)$checkout['id']]);
        $current = $lock->fetch();
        if (!$current || in_array((string)$current['status'], ['completed','refunded','disputed'], true)) {
            $pdo->commit();
            return ['ok' => true, 'message' => 'Checkout already finalized.', 'duplicate' => true];
        }
        $status = in_array($checkoutStatus, ['canceled','expired','failed'], true) ? $checkoutStatus : 'failed';
        $pdo->prepare('UPDATE inventory_reservations SET status=?,released_at=NOW(),updated_at=NOW() WHERE checkout_id=? AND status=\'active\'')->execute([$status === 'expired' ? 'expired' : 'released', (int)$current['id']]);
        $pdo->prepare('UPDATE merch_checkouts SET status=?,updated_at=NOW() WHERE id=?')->execute([$status, (int)$current['id']]);
        $pdo->prepare("UPDATE orders SET status='canceled',payment_status='failed',payment_failure_code=?,payment_failure_message=?,updated_at=NOW() WHERE id=? AND status='pending'")->execute([substr($code, 0, 120), substr($message, 0, 500), (int)$current['order_id']]);
        sf_commerce_order_history($pdo, (int)$current['order_id'], 'pending', 'canceled', $message);
        $pdo->commit();
        return ['ok' => true, 'message' => $message];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow checkout failure transition failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Checkout failure could not be recorded.'];
    }
}

function sf_commerce_complete_checkout(string $token, array $session, array $event): array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $token)) return ['ok' => false, 'message' => 'Merch checkout token is invalid.'];
    $pdo = sf_db();
    if (!$pdo || !sf_commerce_checkout_tables_ready()) return ['ok' => false, 'message' => 'Commerce database is not ready.'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT c.*,o.status AS order_status,o.payment_status,o.receipt_token,o.order_number,o.user_id,o.customer_email,o.shipping_name FROM merch_checkouts c INNER JOIN orders o ON o.id=c.order_id WHERE c.checkout_token=? FOR UPDATE');
        $stmt->execute([$token]);
        $checkout = $stmt->fetch();
        if (!$checkout) throw new RuntimeException('Merch checkout was not found.');
        if (($checkout['status'] ?? '') === 'completed') {
            $pdo->commit();
            return ['ok' => true, 'message' => 'Merch checkout already completed.', 'duplicate' => true];
        }
        if (in_array((string)$checkout['status'], ['refunded','disputed'], true)) throw new RuntimeException('Merch checkout is already finalized.');
        $providerCheckoutId = (string)($session['id'] ?? '');
        if ($providerCheckoutId === '' || !hash_equals((string)($checkout['provider_checkout_id'] ?? ''), $providerCheckoutId)) throw new RuntimeException('Stripe checkout session does not match the pending order.');
        if ((int)($session['amount_total'] ?? -1) !== (int)$checkout['total_cents']) throw new RuntimeException('Stripe amount does not match the server order total.');
        if (strtoupper((string)($session['currency'] ?? '')) !== strtoupper((string)$checkout['currency'])) throw new RuntimeException('Stripe currency does not match the server order currency.');
        if (!in_array((string)($session['payment_status'] ?? ''), ['paid','no_payment_required'], true)) throw new RuntimeException('Stripe payment is not settled.');
        $paymentIntent = substr((string)($session['payment_intent'] ?? ''), 0, 190);
        if ($paymentIntent === '') throw new RuntimeException('Stripe payment identifier is missing.');
        $duplicate = $pdo->prepare("SELECT id FROM payment_transactions WHERE provider='stripe' AND provider_payment_id=? AND status='paid' LIMIT 1");
        $duplicate->execute([$paymentIntent]);
        if ($duplicate->fetch()) throw new RuntimeException('Stripe payment has already been applied to another transaction.');
        $reservations = $pdo->prepare("SELECT * FROM inventory_reservations WHERE checkout_id=? ORDER BY id FOR UPDATE");
        $reservations->execute([(int)$checkout['id']]);
        foreach ($reservations->fetchAll() ?: [] as $reservation) {
            if (($reservation['status'] ?? '') === 'consumed') continue;
            if (($reservation['status'] ?? '') !== 'active') throw new RuntimeException('Inventory reservation is no longer active.');
            $qty = (int)$reservation['quantity'];
            if (!empty($reservation['variant_id'])) {
                $dec = $pdo->prepare("UPDATE product_variants SET inventory_quantity=inventory_quantity-?,updated_at=NOW() WHERE id=? AND inventory_quantity>=?");
                $dec->execute([$qty, (int)$reservation['variant_id'], $qty]);
            } else {
                $dec = $pdo->prepare("UPDATE products SET inventory_quantity=inventory_quantity-?,updated_at=NOW() WHERE id=? AND inventory_quantity>=?");
                $dec->execute([$qty, (int)$reservation['product_id'], $qty]);
            }
            if ($dec->rowCount() !== 1) throw new RuntimeException('Inventory changed before payment completion. Manual reconciliation is required.');
            sf_commerce_inventory_movement($pdo, (int)$reservation['product_id'], !empty($reservation['variant_id']) ? (int)$reservation['variant_id'] : null, (int)$checkout['order_id'], -$qty, 'order_paid');
            $pdo->prepare("UPDATE inventory_reservations SET status='consumed',updated_at=NOW() WHERE id=? AND status='active'")->execute([(int)$reservation['id']]);
        }
        $customer = substr((string)($session['customer'] ?? ''), 0, 190);
        $pdo->prepare("UPDATE merch_checkouts SET status='completed',provider_payment_id=?,provider_customer_id=?,completed_at=NOW(),metadata_json=?,updated_at=NOW() WHERE id=?")->execute([$paymentIntent, $customer ?: null, json_encode(sf_revenue_redact_payload($event), JSON_UNESCAPED_SLASHES), (int)$checkout['id']]);
        $pdo->prepare("UPDATE orders SET status='paid',payment_status='paid',external_payment_id=?,provider_customer_id=?,payment_failure_code=NULL,payment_failure_message=NULL,paid_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$paymentIntent, $customer ?: null, (int)$checkout['order_id']]);
        $tx = $pdo->prepare("INSERT INTO payment_transactions (user_id,merchant_id,payment_account_id,order_id,provider,provider_payment_id,idempotency_key,transaction_type,status,amount_cents,currency,raw_payload_json) VALUES (?,?,?,?, 'stripe',?,?,'merch_order','paid',?,?,?)");
        $tx->execute([(int)$checkout['user_id'] ?: null, (int)$checkout['merchant_id'], (int)$checkout['payment_account_id'], (int)$checkout['order_id'], $paymentIntent, 'merch-paid-' . (int)$checkout['id'], (int)$checkout['total_cents'], (string)$checkout['currency'], json_encode(sf_revenue_redact_payload($event), JSON_UNESCAPED_SLASHES)]);
        if (!empty($checkout['discount_code']) && sf_commerce_table_exists('commerce_discount_redemptions')) {
            $discountStmt = $pdo->prepare('SELECT id FROM commerce_discount_codes WHERE UPPER(code)=? LIMIT 1 FOR UPDATE');
            $discountStmt->execute([strtoupper((string)$checkout['discount_code'])]);
            $discountId = (int)$discountStmt->fetchColumn();
            if ($discountId > 0) {
                $inserted = $pdo->prepare('INSERT IGNORE INTO commerce_discount_redemptions (discount_id,checkout_id,order_id,user_id,discount_cents) VALUES (?,?,?,?,?)');
                $inserted->execute([$discountId, (int)$checkout['id'], (int)$checkout['order_id'], (int)$checkout['user_id'] ?: null, (int)$checkout['discount_cents']]);
                if ($inserted->rowCount() === 1) $pdo->prepare('UPDATE commerce_discount_codes SET usage_count=usage_count+1,updated_at=NOW() WHERE id=?')->execute([$discountId]);
            }
        }
        if (!empty($checkout['cart_id'])) $pdo->prepare("UPDATE carts SET status='converted',updated_at=NOW() WHERE id=? AND status='active'")->execute([(int)$checkout['cart_id']]);
        sf_commerce_order_history($pdo, (int)$checkout['order_id'], (string)$checkout['order_status'], 'paid', 'Stripe payment verified by signed webhook.');
        $pdo->commit();
        $order = sf_store_order_by_id((int)$checkout['order_id']);
        if ($order) sf_store_send_order_notifications($order);
        return ['ok' => true, 'message' => 'Merch payment completed.', 'order_id' => (int)$checkout['order_id']];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow merch checkout completion failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}
