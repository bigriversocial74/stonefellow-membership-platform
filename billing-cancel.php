<?php
$pageTitle = 'Checkout Canceled';
$pageDescription = 'Stonefellow membership checkout was canceled.';
$pageClass = 'membership-page billing-page';
require __DIR__ . '/includes/billing.php';
$user = sf_require_login();
$token = trim((string)($_GET['token'] ?? ''));
if ($token) {
  sf_billing_cancel_checkout($token);
}
require __DIR__ . '/includes/header.php';
?>
<section class="sf-billing-hero">
  <span class="sf-kicker">Checkout Canceled</span>
  <h1>No membership changes were made.</h1>
  <p>You can choose a plan again whenever you are ready.</p>
  <div class="sf-episode-action-row">
    <a class="sf-primary-action" href="<?= sf_url('subscribe.php') ?>">Choose Plan</a>
    <a class="sf-secondary-action" href="<?= sf_url('member.php') ?>">Member Dashboard</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
