<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static function (string $path) use ($root): string {
    $file = $root . '/' . $path;
    return is_file($file) ? (string)file_get_contents($file) : '';
};
$contains = static function (string $path, array $markers) use ($read): bool {
    $body = $read($path);
    if ($body === '') return false;
    foreach ($markers as $marker) if (stripos($body, (string)$marker) === false) return false;
    return true;
};
$sections = [
    'Provider Architecture' => [
        ['includes/commerce_provider.php', ['sf_commerce_provider_registry', "'stripe'", "'paypal'", "'square'", "'implemented' => false"]],
        ['database/migrations/022_live_commerce_stripe_connect.sql', ['commerce_merchants', 'merchant_payment_accounts', 'provider VARCHAR(80)']],
    ],
    'Stripe Connect Onboarding' => [
        ['includes/commerce_provider.php', ['controller[stripe_dashboard][type]', 'capabilities[card_payments][requested]', '/account_links', 'eventually_due']],
        ['admin/payment-gateways.php', ['Connect with Stripe', 'continue_onboarding', 'sync_stripe']],
    ],
    'Credentials & Transport Security' => [
        ['includes/commerce_provider.php', ['sf_commerce_secret_ready', 'CURLOPT_SSL_VERIFYPEER', 'stripe_endpoint_not_allowed', 'Idempotency-Key']],
        ['api/payment-webhook.php', ['sf_security_raw_body(1048576)', 'sf_payment_verify_webhook', 'invalid_signature']],
        ['api/commerce-maintenance.php', ['SF_COMMERCE_MAINTENANCE_SECRET', 'hash_hmac', 'abs(time() - $timestamp) > 300']],
    ],
    'Server Pricing, Shipping, Tax & Discounts' => [
        ['includes/live_commerce_core.php', ['sf_commerce_totals', 'COMMERCE_SHIPPING_FLAT_CENTS', 'COMMERCE_TAX_RATE_BPS', 'sf_commerce_discount']],
        ['checkout.php', ['Final totals are recalculated on the server', 'discount_code', 'Continue to Stripe']],
    ],
    'Inventory Reservation & Concurrency' => [
        ['database/migrations/022_live_commerce_stripe_connect.sql', ['inventory_reservations', "ENUM('active','consumed','released','expired')"]],
        ['includes/live_commerce_checkout_create.php', ['FOR UPDATE', 'A checkout is already in progress']],
        ['includes/live_commerce_checkout_settlement.php', ["status='consumed'", 'inventory_quantity>=?']],
    ],
    'Checkout & Settlement Integrity' => [
        ['includes/live_commerce_checkout_create.php', ['payment_intent_data[transfer_data][destination]', 'payment_method_types[0]']],
        ['includes/live_commerce_checkout_settlement.php', ['Stripe amount does not match', 'Stripe currency does not match', 'Stripe payment is not settled']],
        ['includes/payment_gateway.php', ['subscription_data[transfer_data][destination]', 'subscription_data[application_fee_percent]', 'invoice.paid', 'Subscription renewal payment applied']],
        ['checkout-success.php', ['signed Stripe webhook is the source of truth', 'Payment Confirmed']],
    ],
    'Webhook Routing & Idempotency' => [
        ['api/payment-webhook.php', ["['processed','ignored']", 'sf_commerce_process_gateway_event', 'sf_payment_record_gateway_event']],
        ['includes/live_commerce_events.php', ['checkout.session.async_payment_succeeded', 'payment_intent.payment_failed']],
        ['includes/live_commerce_checkout_settlement.php', ['Merch checkout already completed']],
    ],
    'Refunds, Disputes & Recovery' => [
        ['includes/live_commerce_events.php', ['sf_commerce_request_full_refund', 'charge.refunded', 'charge.dispute.created', 'charge.dispute.closed']],
        ['admin/orders.php', ['sf_commerce_request_full_refund', 'refund_order', 'active Stripe dispute']],
    ],
    'Customer & Admin Operations' => [
        ['account-orders.php', ['My Orders', 'Download']],
        ['order-receipt.php', ['Content-Disposition', 'Stonefellow Receipt']],
        ['admin/payment-reconciliation.php', ['sf_commerce_reconciliation_summary', 'cleanup_expired', 'Release Expired Reservations']],
    ],
    'Migration, Configuration, Documentation & CI' => [
        ['database/migrations/022_live_commerce_stripe_connect.sql', ['merch_checkouts', 'commerce_discount_codes', 'payment_gateway_webhook_events']],
        ['.env.example', ['SF_STRIPE_PLATFORM_FEE_BPS', 'SF_COMMERCE_MAINTENANCE_SECRET', 'SF_COMMERCE_TAX_RATE_BPS']],
        ['docs/LIVE_COMMERCE_STRIPE_CONNECT_V1.md', ['Initial static score', 'Final static score', '10/10']],
        ['.github/workflows/code-audit.yml', ['live_commerce_stripe_connect_smoke.php', 'live-commerce-stripe-connect-audit.php']],
    ],
];

$failed = [];
$allPassed = true;
echo "Stonefellow Live Commerce & Stripe Connect Audit v1\n";
echo str_repeat('=', 58) . "\n";
foreach ($sections as $section => $checks) {
    $passed = 0;
    foreach ($checks as [$path, $markers]) {
        $ok = $contains($path, $markers);
        if ($ok) $passed++;
        else $failed[] = $section . ': ' . $path . ' is missing required evidence.';
    }
    $score = (int)round($passed / count($checks) * 10);
    if ($score !== 10) $allPassed = false;
    echo sprintf("%-42s %d/10 (%d/%d)\n", $section, $score, $passed, count($checks));
}
echo str_repeat('-', 58) . "\n";
echo $allPassed ? "Overall score: 10/10\n" : "Overall score: below 10/10\n";
if ($failed) {
    echo "\nBlocking findings:\n- " . implode("\n- ", $failed) . "\n";
    exit(1);
}
echo "Result: PASS — all ten sections score 10/10.\n";
