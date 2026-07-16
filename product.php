<?php
$pageTitle = 'Product';
$pageDescription = 'Official DesertRio product detail.';
$pageClass = 'shop-page product-detail-page desertrio-product-template';
$pageExtraStyles = ['css/desertrio-commerce.css'];
require __DIR__ . '/includes/store.php';
require __DIR__ . '/includes/desertrio_theme.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$product = sf_store_product_by_slug($slug) ?: sf_store_featured_product();
if (!$product) {
  sf_store_flash('error', 'Product was not found.');
  sf_store_redirect('merch.php');
}
$variants = $product['variants'] ?? [];
$pageTitle = (string)($product['name'] ?? 'Product');
$soldOut = sf_store_is_sold_out($product);
$locked = !sf_store_can_purchase($product);
require __DIR__ . '/includes/header.php';
?>
<section class="shop-detail shop-full-section">
  <div class="shop-detail-image">
    <img src="<?= sf_store_h(sf_store_image_url($product['image_path'] ?? '')) ?>" alt="<?= sf_store_h($product['name'] ?? '') ?>">
  </div>
  <div class="shop-detail-copy">
    <span class="shop-kicker"><?= sf_store_h($product['category_name'] ?? 'DesertRio Collection') ?> / <?= sf_store_h($product['badge_label'] ?? 'Official') ?></span>
    <h1><?= sf_store_h($product['name'] ?? '') ?></h1>
    <div class="shop-detail-price"><?= sf_store_money((int)($product['price_cents'] ?? 0)) ?></div>
    <p><?= sf_store_h($product['description'] ?? $product['short_description'] ?? '') ?></p>

    <?php foreach (sf_store_flashes() as $flash): ?>
      <div class="sf-admin-alert sf-admin-alert-<?= sf_store_h($flash['type'] ?? 'info') ?>"><?= sf_store_h($flash['message'] ?? '') ?></div>
    <?php endforeach; ?>

    <?php if ($locked): ?>
      <div class="shop-detail-note"><strong>Member release:</strong> this item requires <?= sf_store_h(sf_access_label((string)($product['access_level'] ?? 'subscriber'))) ?> access.</div>
      <div class="shop-actions"><a class="shop-btn shop-btn-primary" href="<?= sf_url('subscribe.php') ?>">Unlock Release</a><a class="shop-btn shop-btn-outline" href="<?= sf_url('signin.php') ?>">Sign In</a></div>
    <?php elseif ($soldOut): ?>
      <div class="shop-detail-note"><strong>Sold out:</strong> this release is currently unavailable. Check back for the next DesertRio collection update.</div>
    <?php else: ?>
      <form class="shop-product-form" action="<?= sf_url('cart.php') ?>" method="post">
        <?= sf_csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int)($product['id'] ?? 0) ?>">
        <?php if ($variants): ?>
          <label>Option
            <select name="variant_id">
              <?php foreach ($variants as $variant): ?>
                <?php $variantPrice = isset($variant['price_cents']) && $variant['price_cents'] !== null ? (int)$variant['price_cents'] : (int)($product['price_cents'] ?? 0); ?>
                <option value="<?= (int)$variant['id'] ?>" <?= (($variant['status'] ?? '') === 'sold_out' || (int)($variant['inventory_quantity'] ?? 0) <= 0) ? 'disabled' : '' ?>>
                  <?= sf_store_h($variant['variant_name'] ?? 'Option') ?> — <?= sf_store_money($variantPrice) ?><?= (int)($variant['inventory_quantity'] ?? 0) <= 0 ? ' (Sold out)' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php else: ?>
          <label>Option
            <select name="option">
              <?php foreach (($product['options'] ?? ['Standard']) as $option): ?>
                <option value="<?= sf_store_h($option) ?>"><?= sf_store_h($option) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>
        <label>Quantity
          <input type="number" name="quantity" value="1" min="1" max="<?= max(1, min(99, (int)($product['inventory_quantity'] ?? 99))) ?>">
        </label>
        <button class="shop-btn shop-btn-primary" type="submit">Add to Cart</button>
      </form>
    <?php endif; ?>

    <div class="shop-detail-note"><strong>Secure ordering:</strong> cart persistence, inventory, order totals, payment records, and member purchase history continue through the existing commerce runtime.</div>
  </div>
</section>
<section class="shop-section shop-full-section">
  <div class="shop-section-head"><div><span class="shop-kicker">Complete the Look</span><h2>More From DesertRio</h2></div><a class="shop-pill-link" href="<?= sf_url('merch.php') ?>">Back to Store</a></div>
  <div class="shop-related-grid">
    <?php foreach (array_slice(sf_store_products(), 0, 4) as $item): if (($item['slug'] ?? '') === ($product['slug'] ?? '')) continue; ?>
      <a class="shop-mini-card" href="<?= sf_store_h(sf_store_product_url($item)) ?>">
        <img src="<?= sf_store_h(sf_store_image_url($item['image_path'] ?? '')) ?>" alt="<?= sf_store_h($item['name'] ?? '') ?>">
        <strong><?= sf_store_h($item['name'] ?? '') ?></strong>
        <span><?= sf_store_money((int)($item['price_cents'] ?? 0)) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>