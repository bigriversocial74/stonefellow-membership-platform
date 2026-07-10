<?php

declare(strict_types=1);

function sf_commerce_order_history(PDO $pdo, int $orderId, string $fromStatus, string $toStatus, string $note = ''): void
{
    if (!sf_store_table_exists('order_status_history')) return;
    $actor = sf_current_user_id();
    $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id,from_status,to_status,note,changed_by_user_id) VALUES (?,?,?,?,?)');
    $stmt->execute([$orderId, $fromStatus !== '' ? $fromStatus : null, $toStatus, $note !== '' ? $note : null, $actor > 0 ? $actor : null]);
}

function sf_commerce_inventory_movement(PDO $pdo, int $productId, ?int $variantId, int $orderId, int $delta, string $reason, string $note = ''): void
{
    if (!sf_store_table_exists('product_inventory_movements')) return;
    $actor = sf_current_user_id();
    $stmt = $pdo->prepare('INSERT INTO product_inventory_movements (product_id,variant_id,order_id,delta_quantity,reason,note,created_by_user_id) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$productId, $variantId, $orderId, $delta, $reason, $note !== '' ? $note : null, $actor > 0 ? $actor : null]);
}

function sf_commerce_setting_int(string $key, int $default, int $min = 0, int $max = 100000000): int
{
    $envKey = 'SF_' . strtoupper($key);
    $raw = getenv($envKey);
    if ($raw === false || $raw === '') $raw = sf_get_setting(strtolower($key), (string)$default);
    $value = is_numeric($raw) ? (int)$raw : $default;
    return max($min, min($max, $value));
}

function sf_commerce_currency(array $merchant): string
{
    $currency = strtoupper(trim((string)($merchant['default_currency'] ?? 'USD')));
    return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'USD';
}

function sf_commerce_discount(string $code, int $subtotalCents): array
{
    $code = strtoupper(trim($code));
    $empty = ['id' => 0, 'code' => '', 'discount_cents' => 0, 'valid' => true, 'message' => ''];
    if ($code === '') return $empty;
    if (!sf_commerce_table_exists('commerce_discount_codes')) return ['id' => 0, 'code' => $code, 'discount_cents' => 0, 'valid' => false, 'message' => 'Discount codes are unavailable.'];
    try {
        $lock = sf_db()->inTransaction() ? ' FOR UPDATE' : '';
        $stmt = sf_db()->prepare("SELECT * FROM commerce_discount_codes WHERE UPPER(code)=? AND status='active' AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) LIMIT 1" . $lock);
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        if (!$row) return ['id' => 0, 'code' => $code, 'discount_cents' => 0, 'valid' => false, 'message' => 'Discount code is invalid or expired.'];
        if ((int)$row['minimum_subtotal_cents'] > $subtotalCents) return ['id' => (int)$row['id'], 'code' => $code, 'discount_cents' => 0, 'valid' => false, 'message' => 'Order subtotal does not meet the discount minimum.'];
        if ($row['usage_limit'] !== null) {
            $pending = 0;
            if (sf_commerce_table_exists('merch_checkouts')) {
                $pendingStmt = sf_db()->prepare("SELECT COUNT(*) FROM merch_checkouts WHERE UPPER(discount_code)=? AND status IN ('pending','created') AND expires_at>NOW()");
                $pendingStmt->execute([$code]);
                $pending = (int)$pendingStmt->fetchColumn();
            }
            if ((int)$row['usage_count'] + $pending >= (int)$row['usage_limit']) return ['id' => (int)$row['id'], 'code' => $code, 'discount_cents' => 0, 'valid' => false, 'message' => 'Discount code has reached its usage limit.'];
        }
        $discount = ($row['discount_type'] ?? 'fixed') === 'percent'
            ? (int)floor($subtotalCents * max(0, min(10000, (int)$row['amount'])) / 10000)
            : max(0, (int)$row['amount']);
        if ($row['maximum_discount_cents'] !== null) $discount = min($discount, max(0, (int)$row['maximum_discount_cents']));
        $discount = min($discount, $subtotalCents);
        return ['id' => (int)$row['id'], 'code' => $code, 'discount_cents' => $discount, 'valid' => true, 'message' => ''];
    } catch (Throwable $e) {
        error_log('Stonefellow discount validation failed: ' . $e->getMessage());
        return ['id' => 0, 'code' => $code, 'discount_cents' => 0, 'valid' => false, 'message' => 'Discount code could not be validated.'];
    }
}

function sf_commerce_totals(array $items, string $discountCode = ''): array
{
    $subtotal = 0;
    $count = 0;
    $physical = false;
    foreach ($items as $item) {
        $qty = max(0, (int)($item['quantity'] ?? 0));
        $unit = max(0, (int)($item['unit_price_cents'] ?? 0));
        $subtotal += $qty * $unit;
        $count += $qty;
        if (($item['product_type'] ?? 'physical') !== 'digital') $physical = true;
    }
    $discount = sf_commerce_discount($discountCode, $subtotal);
    $discountCents = !empty($discount['valid']) ? (int)$discount['discount_cents'] : 0;
    $taxable = max(0, $subtotal - $discountCents);
    $flatShipping = sf_commerce_setting_int('COMMERCE_SHIPPING_FLAT_CENTS', 800, 0, 100000);
    $freeThreshold = sf_commerce_setting_int('COMMERCE_FREE_SHIPPING_THRESHOLD_CENTS', 10000, 0, 100000000);
    $shipping = ($physical && $taxable > 0 && ($freeThreshold <= 0 || $taxable < $freeThreshold)) ? $flatShipping : 0;
    $taxBps = sf_commerce_setting_int('COMMERCE_TAX_RATE_BPS', 0, 0, 10000);
    $tax = (int)round(($taxable + $shipping) * $taxBps / 10000);
    return [
        'item_count' => $count,
        'subtotal_cents' => $subtotal,
        'discount_cents' => $discountCents,
        'shipping_cents' => $shipping,
        'tax_cents' => $tax,
        'total_cents' => max(0, $taxable + $shipping + $tax),
        'has_physical' => $physical,
        'discount' => $discount,
    ];
}

function sf_commerce_checkout_tables_ready(): bool
{
    foreach (['commerce_merchants','merchant_payment_accounts','merch_checkouts','inventory_reservations'] as $table) {
        if (!sf_commerce_table_exists($table)) return false;
    }
    return sf_store_db_ready();
}

function sf_commerce_release_expired_reservations(int $limit = 500): int
{
    if (!sf_commerce_checkout_tables_ready()) return 0;
    $limit = max(1, min(2000, $limit));
    $pdo = sf_db();
    try {
        $pdo->beginTransaction();
        $rows = $pdo->query("SELECT id,checkout_id FROM inventory_reservations WHERE status='active' AND expires_at<NOW() ORDER BY id ASC LIMIT {$limit} FOR UPDATE")->fetchAll() ?: [];
        if (!$rows) {
            $pdo->commit();
            return 0;
        }
        $ids = array_map(static fn(array $row): int => (int)$row['id'], $rows);
        $checkouts = array_values(array_unique(array_map(static fn(array $row): int => (int)$row['checkout_id'], $rows)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE inventory_reservations SET status='expired',released_at=NOW(),updated_at=NOW() WHERE id IN ({$placeholders}) AND status='active'")->execute($ids);
        if ($checkouts) {
            $cp = implode(',', array_fill(0, count($checkouts), '?'));
            $pdo->prepare("UPDATE merch_checkouts SET status='expired',updated_at=NOW() WHERE id IN ({$cp}) AND status IN ('pending','created')")->execute($checkouts);
            $pdo->prepare("UPDATE orders o INNER JOIN merch_checkouts c ON c.order_id=o.id SET o.status='canceled',o.payment_status='failed',o.payment_failure_code='checkout_expired',o.payment_failure_message='Payment session expired before completion.',o.updated_at=NOW() WHERE c.id IN ({$cp}) AND o.status='pending'")->execute($checkouts);
        }
        $pdo->commit();
        return count($ids);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow expired reservation cleanup failed: ' . $e->getMessage());
        return 0;
    }
}

function sf_commerce_live_cart_items_for_update(PDO $pdo, int $cartId): array
{
    $stmt = $pdo->prepare("SELECT ci.product_id,ci.variant_id,ci.quantity,p.name product_name,p.slug product_slug,p.product_type,p.access_level,p.price_cents product_price,p.inventory_quantity product_inventory,p.status product_status,pv.variant_name,pv.price_cents variant_price,pv.inventory_quantity variant_inventory,pv.status variant_status FROM cart_items ci INNER JOIN products p ON p.id=ci.product_id LEFT JOIN product_variants pv ON pv.id=ci.variant_id WHERE ci.cart_id=? ORDER BY ci.id FOR UPDATE");
    $stmt->execute([$cartId]);
    $items = [];
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $qty = max(1, min(99, (int)$row['quantity']));
        $product = ['id' => (int)$row['product_id'], 'access_level' => (string)$row['access_level'], 'status' => (string)$row['product_status']];
        if (($row['product_status'] ?? '') !== 'active' || !sf_store_can_purchase($product)) throw new RuntimeException('One item is no longer available.');
        $variantId = !empty($row['variant_id']) ? (int)$row['variant_id'] : null;
        if ($variantId && ($row['variant_status'] ?? '') !== 'active') throw new RuntimeException('One selected option is no longer available.');
        $inventory = $variantId ? (int)$row['variant_inventory'] : (int)$row['product_inventory'];
        $reservedStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM inventory_reservations WHERE product_id=? AND ((variant_id IS NULL AND ? IS NULL) OR variant_id=?) AND status=\'active\' AND expires_at>NOW()');
        $reservedStmt->execute([(int)$row['product_id'], $variantId, $variantId]);
        $available = max(0, $inventory - (int)$reservedStmt->fetchColumn());
        if ($available < $qty) throw new RuntimeException(($row['product_name'] ?? 'Item') . ' only has ' . $available . ' available.');
        $unit = $variantId && $row['variant_price'] !== null ? (int)$row['variant_price'] : (int)$row['product_price'];
        if ($unit <= 0) throw new RuntimeException('One item has an invalid price.');
        $items[] = [
            'product_id' => (int)$row['product_id'],
            'variant_id' => $variantId,
            'product_name' => (string)$row['product_name'],
            'product_slug' => (string)$row['product_slug'],
            'variant_name' => (string)($row['variant_name'] ?? 'Standard'),
            'quantity' => $qty,
            'unit_price_cents' => $unit,
            'product_type' => (string)($row['product_type'] ?? 'physical'),
        ];
    }
    return $items;
}
