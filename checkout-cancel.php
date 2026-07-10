<?php

declare(strict_types=1);

$pageTitle = 'Checkout Canceled';
$pageDescription = 'Stonefellow checkout canceled.';
$pageClass = 'shop-page order-page';
require __DIR__ . '/includes/live_commerce.php';
$token = trim((string)($_GET['token'] ?? ''));
$key = trim((string)($_GET['key'] ?? ''));
$checkout = sf_commerce_checkout_by_token($token);
if (!$checkout || !sf_commerce_checkout_authorized($checkout, $key)) $checkout = null;
require __DIR__ . '/includes/header.php';
?>
<section class="order-confirmation shop-full-section">
  <span class="shop-kicker">Stripe Checkout</span><h1>Checkout closed.</h1>
  <p><?= $checkout ? 'No local order is marked paid until Stripe confirms it by signed webhook. Your inventory reservation remains briefly available while the payment session closes.' : 'The checkout reference was not found.' ?></p>
  <a class="shop-btn shop-btn-primary" href="<?= sf_url('cart.php') ?>">Return to Cart</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
