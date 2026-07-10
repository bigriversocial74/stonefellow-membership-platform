<?php

declare(strict_types=1);

function sf_commerce_orders_for_user(int $userId, int $limit = 100): array
{
    if ($userId <= 0 || !sf_store_table_exists('orders')) return [];
    try {
        $stmt = sf_db()->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, min(500, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function sf_commerce_reconciliation_summary(int $limit = 200): array
{
    $result = ['counts' => ['failed_webhooks' => 0, 'stale_checkouts' => 0, 'paid_without_transaction' => 0, 'amount_mismatches' => 0, 'expired_reservations' => 0], 'issues' => []];
    if (!sf_commerce_checkout_tables_ready()) return $result;
    $pdo = sf_db();
    $queries = [
        'failed_webhooks' => "SELECT id,provider_event_id reference,event_type detail,created_at FROM payment_gateway_webhook_events WHERE status IN ('failed','rejected') ORDER BY created_at DESC LIMIT {$limit}",
        'stale_checkouts' => "SELECT c.id,c.checkout_token reference,CONCAT('Checkout ',c.status,' for order ',o.order_number) detail,c.created_at FROM merch_checkouts c INNER JOIN orders o ON o.id=c.order_id WHERE c.status IN ('pending','created') AND c.expires_at<NOW() ORDER BY c.created_at ASC LIMIT {$limit}",
        'paid_without_transaction' => "SELECT o.id,o.order_number reference,'Paid order has no paid merchandise transaction' detail,o.created_at FROM orders o LEFT JOIN payment_transactions t ON t.order_id=o.id AND t.transaction_type='merch_order' AND t.status='paid' WHERE o.payment_status='paid' AND t.id IS NULL ORDER BY o.created_at DESC LIMIT {$limit}",
        'amount_mismatches' => "SELECT o.id,o.order_number reference,CONCAT('Order ',o.total_cents,' vs transaction ',t.amount_cents) detail,o.created_at FROM orders o INNER JOIN payment_transactions t ON t.order_id=o.id AND t.transaction_type='merch_order' AND t.status='paid' WHERE o.total_cents<>t.amount_cents OR UPPER(o.payment_currency)<>UPPER(t.currency) ORDER BY o.created_at DESC LIMIT {$limit}",
        'expired_reservations' => "SELECT r.id,CAST(r.checkout_id AS CHAR) reference,'Active inventory reservation is expired' detail,r.created_at FROM inventory_reservations r WHERE r.status='active' AND r.expires_at<NOW() ORDER BY r.expires_at ASC LIMIT {$limit}",
    ];
    foreach ($queries as $type => $sql) {
        try {
            $rows = $pdo->query($sql)->fetchAll() ?: [];
            $result['counts'][$type] = count($rows);
            foreach ($rows as $row) $result['issues'][] = ['type' => $type] + $row;
        } catch (Throwable $e) {
            $result['issues'][] = ['type' => $type, 'reference' => 'query', 'detail' => 'Reconciliation query failed.', 'created_at' => ''];
        }
    }
    return $result;
}

function sf_commerce_update_order_status(int $orderId, string $status, string $note = ''): array
{
    $order = sf_store_order_by_id($orderId);
    if (!$order) return ['ok' => false, 'error' => 'Order was not found.'];
    if (($order['payment_provider'] ?? '') !== 'stripe') {
        return ['ok' => sf_store_update_order_status($orderId, $status, $note), 'error' => ''];
    }
    $paymentStatus = (string)($order['payment_status'] ?? 'unpaid');
    if ($status === 'refunded') return ['ok' => false, 'error' => 'Stripe refunds must use the verified refund action.'];
    if ($status === 'paid' && $paymentStatus !== 'paid') return ['ok' => false, 'error' => 'Stripe orders become paid only after a signed webhook.'];
    if ($status === 'fulfilled' && $paymentStatus !== 'paid') return ['ok' => false, 'error' => 'Only settled Stripe orders can be fulfilled.'];
    if ($status === 'canceled' && $paymentStatus === 'paid') return ['ok' => false, 'error' => 'Paid Stripe orders must be refunded instead of canceled.'];
    if ($status === 'canceled' && $paymentStatus !== 'paid') {
        try {
            $stmt = sf_db()->prepare('SELECT checkout_token FROM merch_checkouts WHERE order_id=? LIMIT 1');
            $stmt->execute([$orderId]);
            $token = (string)$stmt->fetchColumn();
            if ($token !== '') {
                $result = sf_commerce_fail_checkout($token, 'admin_canceled', $note ?: 'Checkout canceled by an administrator.', 'canceled');
                return ['ok' => !empty($result['ok']), 'error' => empty($result['ok']) ? (string)($result['message'] ?? 'Cancellation failed.') : ''];
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Checkout cancellation failed.'];
        }
    }
    $ok = sf_store_update_order_status($orderId, $status, $note);
    return ['ok' => $ok, 'error' => $ok ? '' : 'Order status could not be updated.'];
}
