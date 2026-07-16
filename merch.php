<?php
$pageTitle = 'Shop';
$pageDescription = 'Shop official DesertRio apparel, accessories, limited releases, and cast-inspired collections.';
$pageClass = 'shop-page merch-page desertrio-shop-template';
$pageExtraStyles = ['css/desertrio-commerce.css'];
require __DIR__ . '/includes/store.php';
require __DIR__ . '/includes/desertrio_theme.php';

$categorySlug = trim((string)($_GET['category'] ?? ''));
$products = sf_store_products($categorySlug !== '' ? ['category' => $categorySlug] : []);
$featuredProduct = sf_store_featured_product() ?: ($products[0] ?? null);
$categories = sf_store_categories();
$cartCount = sf_store_cart_totals()['item_count'];
$themeMerchHero = sf_asset($desertRioAssets['story_afterparty']);
require __DIR__ . '/includes/header.php';
?>
<section class="shop-hero shop-full-section">
  <div class="shop-hero-copy">
    <span class="shop-kicker">The DesertRio Collection</span>
    <h1>Bring the Desert<br>Home With You.</h1>
    <p>Shop cast-inspired apparel, poolside essentials, limited accessories, premiere releases, and collectible pieces from the world of DesertRio.</p>
    <div class="shop-actions">
      <a class="shop-btn shop-btn-primary" href="#featured-store">Shop the Collection</a>
      <a class="shop-btn shop-btn-outline" href="<?= sf_url('subscribe.php') ?>">Member Exclusives</a>
    </div>
  </div>
  <div class="shop-hero-art">
    <img src="<?= sf_store_h($themeMerchHero) ?>" alt="DesertRio cast at an exclusive Arizona event">
    <?php if ($featuredProduct): ?>
      <div class="shop-drop-card">
        <span><?= sf_store_h($featuredProduct['badge_label'] ?? 'Featured Release') ?></span>
        <strong><?= sf_store_h($featuredProduct['name'] ?? '') ?></strong>
        <p><?= sf_store_h($featuredProduct['short_description'] ?? $featuredProduct['description'] ?? '') ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="featured-store" class="shop-section shop-full-section">
  <div class="shop-section-head">
    <div>
      <span class="shop-kicker">Official Store</span>
      <h2>DesertRio Favorites</h2>
    </div>
    <a class="shop-pill-link" href="<?= sf_url('cart.php') ?>">View Cart<?= $cartCount ? ' (' . (int)$cartCount . ')' : '' ?></a>
  </div>

  <?php foreach (sf_store_flashes() as $flash): ?>
    <div class="sf-admin-alert sf-admin-alert-<?= sf_store_h($flash['type'] ?? 'info') ?>"><?= sf_store_h($flash['message'] ?? '') ?></div>
  <?php endforeach; ?>

  <nav class="shop-category-tabs" aria-label="Product categories">
    <a href="<?= sf_url('merch.php') ?>" class="<?= $categorySlug === '' ? 'is-active' : '' ?>">All</a>
    <?php foreach ($categories as $category): ?>
      <a href="<?= sf_url('merch.php?category=' . urlencode((string)($category['slug'] ?? ''))) ?>" class="<?= $categorySlug === ($category['slug'] ?? '') ? 'is-active' : '' ?>"><?= sf_store_h($category['name'] ?? '') ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="shop-product-grid">
    <?php if (!$products): ?>
      <article class="shop-product-card shop-empty-card"><div class="shop-product-body"><h3>No products found</h3><p>Add active products in Admin → Merch Products.</p></div></article>
    <?php endif; ?>
    <?php foreach ($products as $product): ?>
      <?php
        $soldOut = sf_store_is_sold_out($product);
        $locked = !sf_store_can_purchase($product);
      ?>
      <article class="shop-product-card">
        <a class="shop-product-image" href="<?= sf_store_h(sf_store_product_url($product)) ?>">
          <img src="<?= sf_store_h(sf_store_image_url($product['image_path'] ?? '')) ?>" alt="<?= sf_store_h($product['name'] ?? '') ?>">
        </a>
        <div class="shop-product-body">
          <div class="shop-product-meta">
            <span class="shop-badge"><?= sf_store_h($product['badge_label'] ?? ($soldOut ? 'Sold Out' : 'Official')) ?></span>
            <strong><?= sf_store_money((int)($product['price_cents'] ?? 0)) ?></strong>
          </div>
          <h3><?= sf_store_h($product['name'] ?? '') ?></h3>
          <p><?= sf_store_h($product['short_description'] ?? $product['description'] ?? '') ?></p>
          <div class="shop-card-actions">
            <?php if ($locked): ?>
              <a class="shop-btn shop-btn-primary" href="<?= sf_url('subscribe.php') ?>">Unlock Release</a>
            <?php elseif ($soldOut): ?>
              <button class="shop-btn shop-btn-primary" type="button" disabled>Sold Out</button>
            <?php else: ?>
              <form class="shop-inline-add" action="<?= sf_url('cart.php') ?>" method="post">
                <?= sf_csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)($product['id'] ?? 0) ?>">
                <input type="hidden" name="quantity" value="1">
                <button class="shop-btn shop-btn-primary" type="submit">Add to Cart</button>
              </form>
            <?php endif; ?>
            <a class="shop-text-link" href="<?= sf_store_h(sf_store_product_url($product)) ?>">View Details →</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="shop-feature-band shop-full-section">
  <div>
    <span class="shop-kicker">Members First</span>
    <h2>Premiere Drops, Cast Favorites, and Limited Desert Releases.</h2>
    <p>Member-only access rules remain connected to the existing commerce runtime, allowing selected products to open early for subscribers and founding viewers.</p>
  </div>
  <a class="shop-btn shop-btn-primary" href="<?= sf_url('subscribe.php') ?>">View Membership</a>
</section>

<section class="shop-process shop-full-section">
  <article><span>01</span><h3>Choose Your Look</h3><p>Browse official collections, sizes, limited releases, accessories, and cast-inspired products.</p></article>
  <article><span>02</span><h3>Save Your Cart</h3><p>Cart items remain connected to the existing session and member-account persistence system.</p></article>
  <article><span>03</span><h3>Secure Checkout</h3><p>Orders continue through the existing Stripe, inventory, transaction, and order-history workflows.</p></article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>