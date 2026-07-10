<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static function (string $path) use ($root): string {
    $file = $root . '/' . $path;
    return is_file($file) ? (string)file_get_contents($file) : '';
};
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$has = static fn(string $path, string $marker): bool => stripos($read($path), $marker) !== false;

$assert($has('database/migrations/022_live_commerce_stripe_connect.sql', 'merchant_payment_accounts'), 'Migration must create merchant payment accounts.');
$assert($has('database/migrations/022_live_commerce_stripe_connect.sql', 'inventory_reservations'), 'Migration must create inventory reservations.');
$assert($has('includes/commerce_provider.php', 'sf_commerce_provider_registry'), 'Provider registry must exist.');
$assert($has('includes/commerce_provider.php', "'implemented' => false"), 'Future providers must remain fail-closed.');
$assert($has('includes/commerce_provider.php', 'controller[stripe_dashboard][type]'), 'Stripe Connect Express account creation must be implemented.');
$assert($has('includes/commerce_provider.php', '/account_links'), 'Stripe-hosted onboarding links must be implemented.');
$assert($has('includes/commerce_provider.php', 'collection_options[fields]'), 'Onboarding must collect required Stripe information.');
$assert($has('includes/live_commerce_checkout_create.php', 'payment_intent_data[transfer_data][destination]'), 'Checkout must route funds to the connected merchant account.');
$assert($has('includes/payment_gateway.php', 'subscription_data[transfer_data][destination]'), 'Membership subscriptions must route through the connected merchant account.');
$assert($has('includes/payment_gateway.php', 'invoice.paid'), 'Recurring membership settlement must be processed.');
$assert($has('includes/live_commerce_checkout_settlement.php', 'Stripe amount does not match the server order total.'), 'Webhook completion must verify amount.');
$assert($has('includes/live_commerce_checkout_settlement.php', 'Stripe currency does not match the server order currency.'), 'Webhook completion must verify currency.');
$assert($has('includes/live_commerce_checkout_settlement.php', "status='consumed'"), 'Paid checkout must consume inventory reservations.');
$assert($has('includes/live_commerce_events.php', 'sf_commerce_request_full_refund'), 'Verified provider refund flow must exist.');
$assert($has('includes/live_commerce_events.php', 'charge.dispute.created'), 'Dispute events must be handled.');
$assert($has('api/payment-webhook.php', 'sf_payment_verify_webhook'), 'Payment webhook must verify provider signatures.');
$assert($has('api/payment-webhook.php', "['processed','ignored']"), 'Webhook processing must be idempotent.');
$assert($has('api/commerce-maintenance.php', 'hash_hmac'), 'Commerce maintenance endpoint must be signed.');
$assert($has('admin/payment-gateways.php', 'Connect with Stripe'), 'Admin must expose Stripe onboarding.');
$assert($has('admin/payment-reconciliation.php', 'Reconciliation issues'), 'Admin reconciliation dashboard must exist.');
$assert($has('account-orders.php', 'My Orders'), 'Customer order history must exist.');
$assert($has('order-receipt.php', 'Content-Disposition'), 'Downloadable receipts must exist.');
$assert($has('.github/workflows/code-audit.yml', 'live_commerce_stripe_connect_smoke.php'), 'Commerce smoke test must run in CI.');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
echo "Live commerce and Stripe Connect smoke tests passed.\n";
