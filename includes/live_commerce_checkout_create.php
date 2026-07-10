<?php

declare(strict_types=1);

function sf_commerce_create_pending_checkout(array $customer, string $discountCode = ''): array
{
    if (!sf_commerce_checkout_tables_ready()) return ['ok' => false, 'error' => 'Commerce migration 022 and the store schema are required.'];
    if (!sf_commerce_checkout_ready()) return ['ok' => false, 'error' => 'Stripe Connect onboarding must be complete before checkout can accept payments.'];
    sf_commerce_release_expired_reservations();
    $merchant = sf_commerce_default_merchant();
    $account = $merchant ? sf_commerce_payment_account((int)$merchant['id'], 'stripe') : null;
    if (!$merchant || !sf_commerce_payment_account_ready($account)) return ['ok' => false, 'error' => 'No verified merchant payment account is available.'];
    $pdo = sf_db();
    $cartId = sf_store_current_cart_id(false);
    if (!$pdo || !$cartId) return ['ok' => false, 'error' => 'Cart could not be found.'];
    $checkoutToken = bin2hex(random_bytes(24));
    $receiptToken = bin2hex(random_bytes(32));
    $orderNumber = sf_store_next_order_number();
    $expiresAt = date('Y-m-d H:i:s', time() + 2100);
    try {
        $pdo->beginTransaction();
        $cartLock = $pdo->prepare("SELECT id FROM carts WHERE id=? AND status='active' FOR UPDATE");
        $cartLock->execute([$cartId]);
        if (!$cartLock->fetch()) throw new RuntimeException('Cart is no longer active.');
        $existing = $pdo->prepare("SELECT id FROM merch_checkouts WHERE cart_id=? AND status IN ('pending','created') AND expires_at>NOW() LIMIT 1 FOR UPDATE");
        $existing->execute([$cartId]);
        if ($existing->fetch()) throw new RuntimeException('A checkout is already in progress for this cart. Return to it or wait for it to expire.');
        $items = sf_commerce_live_cart_items_for_update($pdo, $cartId);
        $totals = sf_commerce_totals($items, $discountCode);
        $errors = sf_store_validate_checkout($customer, $items, $totals);
        if (empty($totals['discount']['valid'])) $errors[] = (string)$totals['discount']['message'];
        if ($errors) throw new RuntimeException(implode(' ', $errors));
        if ((int)$totals['total_cents'] <= 0) throw new RuntimeException('Order total must be greater than zero.');
        $orderSql = "INSERT INTO orders (user_id,merchant_id,payment_account_id,order_number,receipt_token,status,payment_status,payment_provider,payment_currency,subtotal_cents,shipping_cents,tax_cents,total_cents,customer_email,customer_phone,shipping_name,shipping_address_1,shipping_address_2,shipping_city,shipping_state,shipping_postal_code,shipping_country,shipping_method,notes) VALUES (?,?,?,?,?,'pending','unpaid','stripe',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute([
            sf_current_user_id() ?: null,
            (int)$merchant['id'],
            (int)$account['id'],
            $orderNumber,
            $receiptToken,
            sf_commerce_currency($merchant),
            (int)$totals['subtotal_cents'],
            (int)$totals['shipping_cents'],
            (int)$totals['tax_cents'],
            (int)$totals['total_cents'],
            $customer['email'],
            $customer['phone'] ?: null,
            $customer['name'],
            $customer['address_1'],
            $customer['address_2'] ?: null,
            $customer['city'],
            $customer['state'],
            $customer['postal_code'],
            $customer['country'],
            'standard',
            $customer['notes'] ?: null,
        ]);
        $orderId = (int)$pdo->lastInsertId();
        $checkoutStmt = $pdo->prepare("INSERT INTO merch_checkouts (checkout_token,order_id,cart_id,merchant_id,payment_account_id,provider,mode,status,subtotal_cents,discount_cents,shipping_cents,tax_cents,total_cents,currency,discount_code,expires_at,metadata_json) VALUES (?,?,?,?,?,'stripe',?,'pending',?,?,?,?,?,?,?, ?,?)");
        $checkoutStmt->execute([
            $checkoutToken,
            $orderId,
            $cartId,
            (int)$merchant['id'],
            (int)$account['id'],
            sf_commerce_mode(),
            (int)$totals['subtotal_cents'],
            (int)$totals['discount_cents'],
            (int)$totals['shipping_cents'],
            (int)$totals['tax_cents'],
            (int)$totals['total_cents'],
            sf_commerce_currency($merchant),
            $totals['discount']['code'] ?: null,
            $expiresAt,
            json_encode(['customer_session_hash' => hash('sha256', sf_session_key())], JSON_UNESCAPED_SLASHES),
        ]);
        $checkoutId = (int)$pdo->lastInsertId();
        $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id,product_id,variant_id,product_name,variant_name,quantity,unit_price_cents,total_price_cents) VALUES (?,?,?,?,?,?,?,?)');
        $reservationInsert = $pdo->prepare("INSERT INTO inventory_reservations (checkout_id,order_id,product_id,variant_id,quantity,status,expires_at) VALUES (?,?,?,?,?,'active',?)");
        foreach ($items as $item) {
            $qty = (int)$item['quantity'];
            $itemInsert->execute([$orderId, $item['product_id'], $item['variant_id'], $item['product_name'], $item['variant_name'], $qty, $item['unit_price_cents'], $qty * $item['unit_price_cents']]);
            $reservationInsert->execute([$checkoutId, $orderId, $item['product_id'], $item['variant_id'], $qty, $expiresAt]);
        }
        sf_commerce_order_history($pdo, $orderId, '', 'pending', 'Stripe payment session created; inventory reserved for 35 minutes.');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow pending checkout creation failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    $fee = sf_commerce_platform_fee_cents($merchant, (int)$totals['total_cents']);
    $successUrl = sf_commerce_absolute_url('checkout-success.php?token=' . urlencode($checkoutToken) . '&key=' . urlencode($receiptToken) . '&session_id={CHECKOUT_SESSION_ID}');
    $cancelUrl = sf_commerce_absolute_url('checkout-cancel.php?token=' . urlencode($checkoutToken) . '&key=' . urlencode($receiptToken));
    $fields = [
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $checkoutToken,
        'customer_email' => $customer['email'],
        'customer_creation' => 'always',
        'billing_address_collection' => 'auto',
        'payment_method_types[0]' => 'card',
        'expires_at' => time() + 2100,
        'line_items[0][quantity]' => 1,
        'line_items[0][price_data][currency]' => strtolower(sf_commerce_currency($merchant)),
        'line_items[0][price_data][unit_amount]' => (int)$totals['total_cents'],
        'line_items[0][price_data][product_data][name]' => 'Stonefellow Order ' . $orderNumber,
        'line_items[0][price_data][product_data][description]' => count($items) . ' item(s), shipping and tax included',
        'metadata[checkout_kind]' => 'merch',
        'metadata[checkout_token]' => $checkoutToken,
        'metadata[order_id]' => $orderId,
        'metadata[order_number]' => $orderNumber,
        'metadata[merchant_id]' => (int)$merchant['id'],
        'metadata[payment_account_id]' => (int)$account['id'],
        'payment_intent_data[metadata][checkout_kind]' => 'merch',
        'payment_intent_data[metadata][checkout_token]' => $checkoutToken,
        'payment_intent_data[metadata][order_id]' => $orderId,
        'payment_intent_data[metadata][merchant_id]' => (int)$merchant['id'],
        'payment_intent_data[transfer_data][destination]' => (string)$account['provider_account_id'],
    ];
    if ($fee > 0) $fields['payment_intent_data[application_fee_amount]'] = $fee;
    $stripe = sf_stripe_api_request('POST', '/checkout/sessions', $fields, 'stonefellow-merch-' . $checkoutToken);
    if (empty($stripe['ok'])) {
        sf_commerce_fail_checkout($checkoutToken, 'provider_session_failed', $stripe['error'] ?: 'Stripe checkout could not be created.', 'failed');
        return ['ok' => false, 'error' => $stripe['error'] ?: 'Stripe checkout could not be created.'];
    }
    $session = $stripe['body'];
    $providerCheckoutId = (string)($session['id'] ?? '');
    $checkoutUrl = (string)($session['url'] ?? '');
    if (!preg_match('/^cs_(?:test_|live_)?[A-Za-z0-9_]+$/', $providerCheckoutId) || !preg_match('~^https://checkout\.stripe\.com/~', $checkoutUrl)) {
        sf_commerce_fail_checkout($checkoutToken, 'invalid_provider_session', 'Stripe returned an invalid checkout session.', 'failed');
        return ['ok' => false, 'error' => 'Stripe returned an invalid checkout session.'];
    }
    try {
        $stmt = $pdo->prepare("UPDATE merch_checkouts SET provider_checkout_id=?,status='created',metadata_json=?,updated_at=NOW() WHERE checkout_token=? AND status='pending'");
        $stmt->execute([$providerCheckoutId, json_encode(sf_revenue_redact_payload($session), JSON_UNESCAPED_SLASHES), $checkoutToken]);
        $pdo->prepare('UPDATE orders SET provider_checkout_id=?,updated_at=NOW() WHERE id=?')->execute([$providerCheckoutId, $orderId]);
    } catch (Throwable $e) {
        sf_commerce_fail_checkout($checkoutToken, 'provider_session_persist_failed', 'Stripe session could not be saved.', 'failed');
        return ['ok' => false, 'error' => 'Stripe session could not be saved.'];
    }
    $_SESSION['sf_live_commerce_checkout'] = ['checkout_token' => $checkoutToken, 'receipt_token' => $receiptToken, 'order_number' => $orderNumber];
    return ['ok' => true, 'checkout_url' => $checkoutUrl, 'checkout_token' => $checkoutToken, 'order_number' => $orderNumber, 'receipt_token' => $receiptToken];
}
