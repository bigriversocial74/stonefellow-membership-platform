<?php
$pageTitle = 'Cart';
$pageDescription = 'Review your DesertRio shop cart.';
$pageClass = 'shop-page cart-page desertrio-cart-template';
$pageExtraStyles = ['css/desertrio-commerce.css'];
require __DIR__ . '/includes/store.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['add'])) {
  $product = sf_store_product_by_slug((string)$_GET['add']);
  if ($product) {
    sf_store_cart_add((int)$product['id'], null, sf_store_clean_quantity($_GET['qty'] ?? 1), (string)($_GET['option'] ?? 'Standard'));
  } else {
    sf_store_flash('error', 'Product was not found.');
  }
  sf_store_redirect('cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_store_flash('error', 'Security check failed. Refresh and try again.');
    sf_store_redirect('cart.php');
  }
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'add') {
    sf_store_cart_add(
      sf_store_int($_POST['product_id'] ?? 0),
      !empty($_POST['variant_id']) ? sf_store_int($_POST['variant_id']) : null,
      sf_store_clean_quantity($_POST['quantity'] ?? 1),
      (string)($_POST['option'] ?? '')
    );
    sf_store_redirect('cart.php');
  }
  if ($action === 'update') {
    sf_store_cart_update($_POST['quantities'] ?? []);
    sf_store_redirect('cart.php');
  }
  if ($action === 'remove') {
    sf_store_cart_remove((string)($_POST['item_key'] ?? ''));
    sf_store_redirect('cart.php');
  }
  if ($action === 'clear') {
    sf_store_cart_clear();
    sf_store_flash('success', 'Cart cleared.');
    sf_store_redirect('cart.php');
  }
}

$cartItems = sf_store_cart_items();
$totals = sf_store_cart_totals($cartItems);
require __DIR__ . '/includes/header.php';
?>
<section class="shop-page-head shop-full-section">
  <span class="shop-kicker">DesertRio Shop</span>
  <h1>Your Cart</h1>
  <p>Review your collection, update quantities, or continue to secure checkout. The existing cart, inventory, member-session, and order logic remains unchanged.</p>
</section>
<section class="cart-layout shop-full-section">
  <div class="cart-items-panel">
    <?php foreach (sf_store_flashes() as $flash): ?>
      <div class="sf-admin-alert sf-admin-alert-<?= sf_store_h($flash['type'] ?? 'info') ?>"><?= sf_store_h($flash['message'] ?? '') ?></div>
    <?php endforeach; ?>

    <?php if (!$cartItems): ?>
      <article class="cart-empty-state">
        <span class="shop-kicker">Your Cart Is Waiting</span>
        <h2>Find your DesertRio favorite.</h2>
        <p>Browse official apparel, accessories, premiere pieces, and member-only releases.</p>
        <a class="shop-btn shop-btn-primary" href="<?= sf_url('merch.php') ?>">Explore the Shop</a>
      </article>
    <?php else: ?>
      <form action="<?= sf_url('cart.php') ?>" method="post" class="cart-update-form">
        <?= sf_csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <?php foreach ($cartItems as $index => $row): ?>
          <?php
            $itemKey = (string)($row['cart_item_id'] ?? $row['key'] ?? $index);
            $productSlug = (string)($row['product_slug'] ?? '');
            $lineTotal = (int)($row['unit_price_cents'] ?? 0) * (int)($row['quantity'] ?? 0);
          ?>
          <article class="cart-item">
            <img src="<?= sf_store_h(sf_store_image_url($row['image_path'] ?? '')) ?>" alt="<?= sf_store_h($row['product_name'] ?? '') ?>">
            <div>
              <span class="shop-badge"><?= sf_store_h($row['variant_name'] ?? 'Official') ?></span>
              <h3><?= sf_store_h($row['product_name'] ?? '') ?></h3>
              <p><?= sf_store_money((int)($row['unit_price_cents'] ?? 0)) ?> each</p>
              <div class="cart-qty-row">
                <label>Qty
                  <input type="number" name="quantities[<?= sf_store_h($itemKey) ?>]" value="<?= (int)($row['quantity'] ?? 1) ?>" min="0" max="99">
                </label>
              </div>
              <div class="cart-item-actions">
                <?php if ($productSlug !== ''): ?><a href="<?= sf_url('product.php?slug=' . urlencode($productSlug)) ?>">Edit</a><?php endif; ?>
                <button type="submit" form="remove-<?= preg_replace('/[^a-zA-Z0-9_-]/', '-', $itemKey) ?>">Remove</button>
              </div>
            </div>
            <strong><?= sf_store_money($lineTotal) ?></strong>
          </article>
        <?php endforeach; ?>
        <div class="cart-form-actions"><button class="shop-btn shop-btn-outline" type="submit">Update Cart</button></div>
      </form>
      <?php foreach ($cartItems as $index => $row): $itemKey = (string)($row['cart_item_id'] ?? $row['key'] ?? $index); ?>
        <form id="remove-<?= preg_replace('/[^a-zA-Z0-9_-]/', '-', $itemKey) ?>" action="<?= sf_url('cart.php') ?>" method="post" class="sf-hidden-form">
          <?= sf_csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="item_key" value="<?= sf_store_h($itemKey) ?>">
        </form>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <aside class="cart-summary-panel">
    <h2>Order Summary</h2>
    <div class="summary-line"><span>Items</span><strong><?= (int)$totals['item_count'] ?></strong></div>
    <div class="summary-line"><span>Subtotal</span><strong><?= sf_store_money((int)$totals['subtotal_cents']) ?></strong></div>
    <div class="summary-line"><span>Shipping</span><strong><?= $totals['shipping_cents'] > 0 ? sf_store_money((int)$totals['shipping_cents']) : 'Free' ?></strong></div>
    <div class="summary-line"><span>Estimated Tax</span><strong><?= sf_store_money((int)$totals['tax_cents']) ?></strong></div>
    <div class="summary-line summary-total"><span>Total</span><strong><?= sf_store_money((int)$totals['total_cents']) ?></strong></div>
    <?php if ($cartItems): ?>
      <a class="shop-btn shop-btn-primary" href="<?= sf_url('checkout.php') ?>">Continue to Checkout</a>
      <form action="<?= sf_url('cart.php') ?>" method="post" class="cart-clear-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="clear"><button class="shop-text-link shop-link-button" type="submit">Clear Cart</button></form>
    <?php endif; ?>
    <a class="shop-text-link" href="<?= sf_url('merch.php') ?>">← Keep Shopping</a>
  </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>