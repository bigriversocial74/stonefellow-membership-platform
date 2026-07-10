<?php

declare(strict_types=1);

require __DIR__ . '/includes/live_commerce.php';
$number = trim((string)($_GET['order'] ?? ''));
$key = trim((string)($_GET['key'] ?? ''));
$order = sf_store_order_lookup_authorized($number, $key);
if (!$order) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Receipt not found.\n";
    exit;
}
$download = (string)($_GET['download'] ?? '') === '1';
if ($download) header('Content-Disposition: attachment; filename="stonefellow-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string)$order['order_number']) . '-receipt.html"');
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Receipt <?= sf_store_h($order['order_number'] ?? '') ?></title><style>body{font-family:Arial,sans-serif;max-width:760px;margin:40px auto;padding:20px;color:#111}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}.right{text-align:right}.total{font-size:1.2rem;font-weight:700}</style></head><body>
<h1>Stonefellow Receipt</h1><p><strong>Order:</strong> <?= sf_store_h($order['order_number'] ?? '') ?><br><strong>Payment:</strong> <?= sf_store_h(ucwords(str_replace('_', ' ', (string)($order['payment_status'] ?? 'unpaid')))) ?><br><strong>Date:</strong> <?= sf_store_h($order['created_at'] ?? '') ?></p>
<table><thead><tr><th>Item</th><th>Option</th><th>Qty</th><th class="right">Total</th></tr></thead><tbody><?php foreach (($order['items'] ?? []) as $item): ?><tr><td><?= sf_store_h($item['product_name'] ?? '') ?></td><td><?= sf_store_h($item['variant_name'] ?? '') ?></td><td><?= (int)($item['quantity'] ?? 0) ?></td><td class="right"><?= sf_store_money((int)($item['total_price_cents'] ?? 0)) ?></td></tr><?php endforeach; ?></tbody></table>
<p>Subtotal: <?= sf_store_money((int)($order['subtotal_cents'] ?? 0)) ?><br>Shipping: <?= sf_store_money((int)($order['shipping_cents'] ?? 0)) ?><br>Tax: <?= sf_store_money((int)($order['tax_cents'] ?? 0)) ?></p><p class="total">Total: <?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></p>
<p>Ship to:<br><?= sf_store_h($order['shipping_name'] ?? '') ?><br><?= sf_store_h($order['shipping_address_1'] ?? '') ?><br><?= sf_store_h($order['shipping_city'] ?? '') ?>, <?= sf_store_h($order['shipping_state'] ?? '') ?> <?= sf_store_h($order['shipping_postal_code'] ?? '') ?></p>
</body></html>
