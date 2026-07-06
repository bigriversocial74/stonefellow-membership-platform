<?php
$pageTitle = 'Merch Products';
$pageDescription = 'Manage Stonefellow merch products, inventory, access gates, and variants.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/store.php';

function sf_product_admin_price_to_cents($value): int {
  $value = trim((string)$value);
  if ($value === '') {
    return 0;
  }
  $value = preg_replace('/[^0-9.]/', '', $value);
  return (int)round(((float)$value) * 100);
}

function sf_product_admin_cents_to_price($value): string {
  return number_format(((int)$value) / 100, 2, '.', '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect(sf_url('admin/products.php'));
  }
  $action = (string)($_POST['action'] ?? '');

  if (!sf_admin_db_ready()) {
    sf_admin_flash('warning', 'Database is not configured. Product forms are disabled in static preview mode.');
    sf_admin_redirect(sf_url('admin/products.php'));
  }

  if ($action === 'save_product' && sf_admin_table_exists('products')) {
    $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($name);
    if ($name === '') {
      sf_admin_flash('error', 'Product name is required.');
      sf_admin_redirect(sf_url('admin/products.php'));
    }
    $payload = [
      'category_id' => sf_admin_int($_POST['category_id'] ?? null),
      'name' => $name,
      'slug' => $slug,
      'short_description' => sf_admin_nullable_string($_POST['short_description'] ?? ''),
      'description' => sf_admin_nullable_string($_POST['description'] ?? ''),
      'price_cents' => sf_product_admin_price_to_cents($_POST['price'] ?? '0'),
      'compare_at_price_cents' => ($_POST['compare_at_price'] ?? '') !== '' ? sf_product_admin_price_to_cents($_POST['compare_at_price']) : null,
      'sku' => sf_admin_nullable_string($_POST['sku'] ?? ''),
      'inventory_quantity' => max(0, sf_admin_int($_POST['inventory_quantity'] ?? 0, 0) ?? 0),
      'product_type' => in_array($_POST['product_type'] ?? 'physical', ['physical','digital','bundle'], true) ? $_POST['product_type'] : 'physical',
      'access_level' => in_array($_POST['access_level'] ?? 'public', ['public','subscriber','founding_fan'], true) ? $_POST['access_level'] : 'public',
      'badge_label' => sf_admin_nullable_string($_POST['badge_label'] ?? ''),
      'primary_image_asset_id' => sf_admin_int($_POST['primary_image_asset_id'] ?? null),
      'is_featured' => sf_admin_checkbox('is_featured'),
      'is_limited_drop' => sf_admin_checkbox('is_limited_drop'),
      'status' => in_array($_POST['status'] ?? 'draft', ['draft','active','sold_out','archived'], true) ? $_POST['status'] : 'draft',
    ];
    try {
      if ($id > 0) {
        $before = sf_admin_fetch_one('SELECT * FROM products WHERE id = ? LIMIT 1', [$id]);
        sf_admin_execute(
          'UPDATE products SET category_id=?, name=?, slug=?, short_description=?, description=?, price_cents=?, compare_at_price_cents=?, sku=?, inventory_quantity=?, product_type=?, access_level=?, badge_label=?, primary_image_asset_id=?, is_featured=?, is_limited_drop=?, status=?, updated_at=NOW() WHERE id=?',
          [$payload['category_id'], $payload['name'], $payload['slug'], $payload['short_description'], $payload['description'], $payload['price_cents'], $payload['compare_at_price_cents'], $payload['sku'], $payload['inventory_quantity'], $payload['product_type'], $payload['access_level'], $payload['badge_label'], $payload['primary_image_asset_id'], $payload['is_featured'], $payload['is_limited_drop'], $payload['status'], $id]
        );
        sf_admin_audit('update', 'product', $id, $before, $payload);
        sf_admin_flash('success', 'Product updated.');
      } else {
        sf_admin_execute(
          'INSERT INTO products (category_id, name, slug, short_description, description, price_cents, compare_at_price_cents, sku, inventory_quantity, product_type, access_level, badge_label, primary_image_asset_id, is_featured, is_limited_drop, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
          [$payload['category_id'], $payload['name'], $payload['slug'], $payload['short_description'], $payload['description'], $payload['price_cents'], $payload['compare_at_price_cents'], $payload['sku'], $payload['inventory_quantity'], $payload['product_type'], $payload['access_level'], $payload['badge_label'], $payload['primary_image_asset_id'], $payload['is_featured'], $payload['is_limited_drop'], $payload['status']]
        );
        $id = (int)sf_admin_db()->lastInsertId();
        sf_admin_audit('create', 'product', $id, null, $payload);
        sf_admin_flash('success', 'Product created.');
      }
    } catch (Throwable $e) {
      sf_admin_flash('error', 'Product save failed: ' . $e->getMessage());
    }
    sf_admin_redirect(sf_url('admin/products.php?edit=' . $id));
  }

  if ($action === 'delete_product' && sf_admin_table_exists('products')) {
    $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
    if ($id > 0) {
      $before = sf_admin_fetch_one('SELECT * FROM products WHERE id = ? LIMIT 1', [$id]);
      sf_admin_execute('DELETE FROM products WHERE id = ?', [$id]);
      sf_admin_audit('delete', 'product', $id, $before, null);
      sf_admin_flash('success', 'Product deleted.');
    }
    sf_admin_redirect(sf_url('admin/products.php'));
  }

  if ($action === 'save_variant' && sf_admin_table_exists('product_variants')) {
    $productId = sf_admin_int($_POST['product_id'] ?? null, 0) ?? 0;
    $variantId = sf_admin_int($_POST['variant_id'] ?? null, 0) ?? 0;
    $variantName = trim((string)($_POST['variant_name'] ?? ''));
    if ($productId <= 0 || $variantName === '') {
      sf_admin_flash('error', 'Variant requires a product and name.');
      sf_admin_redirect(sf_url('admin/products.php?edit=' . $productId));
    }
    $payload = [
      'product_id' => $productId,
      'variant_name' => $variantName,
      'sku' => sf_admin_nullable_string($_POST['variant_sku'] ?? ''),
      'size' => sf_admin_nullable_string($_POST['size'] ?? ''),
      'color' => sf_admin_nullable_string($_POST['color'] ?? ''),
      'price_cents' => ($_POST['variant_price'] ?? '') !== '' ? sf_product_admin_price_to_cents($_POST['variant_price']) : null,
      'inventory_quantity' => max(0, sf_admin_int($_POST['variant_inventory_quantity'] ?? 0, 0) ?? 0),
      'status' => in_array($_POST['variant_status'] ?? 'active', ['active','sold_out','inactive'], true) ? $_POST['variant_status'] : 'active',
    ];
    if ($variantId > 0) {
      $before = sf_admin_fetch_one('SELECT * FROM product_variants WHERE id = ? LIMIT 1', [$variantId]);
      sf_admin_execute('UPDATE product_variants SET variant_name=?, sku=?, size=?, color=?, price_cents=?, inventory_quantity=?, status=?, updated_at=NOW() WHERE id=? AND product_id=?', [$payload['variant_name'], $payload['sku'], $payload['size'], $payload['color'], $payload['price_cents'], $payload['inventory_quantity'], $payload['status'], $variantId, $productId]);
      sf_admin_audit('update', 'product_variant', $variantId, $before, $payload);
      sf_admin_flash('success', 'Variant updated.');
    } else {
      sf_admin_execute('INSERT INTO product_variants (product_id, variant_name, sku, size, color, price_cents, inventory_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$payload['product_id'], $payload['variant_name'], $payload['sku'], $payload['size'], $payload['color'], $payload['price_cents'], $payload['inventory_quantity'], $payload['status']]);
      $variantId = (int)sf_admin_db()->lastInsertId();
      sf_admin_audit('create', 'product_variant', $variantId, null, $payload);
      sf_admin_flash('success', 'Variant created.');
    }
    sf_admin_redirect(sf_url('admin/products.php?edit=' . $productId));
  }

  if ($action === 'delete_variant' && sf_admin_table_exists('product_variants')) {
    $productId = sf_admin_int($_POST['product_id'] ?? null, 0) ?? 0;
    $variantId = sf_admin_int($_POST['variant_id'] ?? null, 0) ?? 0;
    if ($variantId > 0) {
      $before = sf_admin_fetch_one('SELECT * FROM product_variants WHERE id = ? LIMIT 1', [$variantId]);
      sf_admin_execute('DELETE FROM product_variants WHERE id = ?', [$variantId]);
      sf_admin_audit('delete', 'product_variant', $variantId, $before, null);
      sf_admin_flash('success', 'Variant deleted.');
    }
    sf_admin_redirect(sf_url('admin/products.php?edit=' . $productId));
  }
}

$products = sf_store_products();
$categories = sf_store_categories();
$imageAssets = sf_admin_assets('image');
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$selected = $editId > 0 ? sf_store_product_by_id($editId) : null;
$variants = $selected ? sf_store_product_variants((int)$selected['id']) : [];
$categoryOptions = ['' => 'No category'];
foreach ($categories as $category) {
  $categoryOptions[(string)($category['id'] ?? '')] = (string)($category['name'] ?? 'Category');
}
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Merch Runtime', 'Products + Inventory', 'Manage merch products that feed the public store, cart, checkout, inventory holds, and subscriber-only drops.', 'products');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/orders.php') ?>"><span>Orders</span><strong>Order Queue</strong><small>Review paid, fulfilled, canceled, and refunded orders.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('merch.php') ?>"><span>Storefront</span><strong>Open Merch</strong><small>Preview public product cards and access gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('cart.php') ?>"><span>Runtime</span><strong>Cart Flow</strong><small>Test add, update, checkout, and confirmation.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $selected ? 'Edit Product' : 'Create Product' ?></span><h2><?= $selected ? sf_admin_h($selected['name'] ?? '') : 'New merch product' ?></h2></div></div>
  <form class="sf-admin-form" action="<?= sf_url('admin/products.php') ?>" method="post">
    <?= sf_csrf_field() ?>
    <input type="hidden" name="action" value="save_product">
    <input type="hidden" name="id" value="<?= (int)($selected['id'] ?? 0) ?>">
    <div class="sf-admin-form-grid">
      <label>Name <input name="name" value="<?= sf_admin_h($selected['name'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Slug <input name="slug" value="<?= sf_admin_h($selected['slug'] ?? '') ?>" placeholder="auto-generated"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Category <?= sf_admin_select('category_id', $categoryOptions, $selected['category_id'] ?? '') ?></label>
      <label>Price <input name="price" value="<?= sf_admin_h(sf_product_admin_cents_to_price($selected['price_cents'] ?? 0)) ?>" inputmode="decimal" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Compare At <input name="compare_at_price" value="<?= !empty($selected['compare_at_price_cents']) ? sf_admin_h(sf_product_admin_cents_to_price($selected['compare_at_price_cents'])) : '' ?>" inputmode="decimal"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>SKU <input name="sku" value="<?= sf_admin_h($selected['sku'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Inventory <input name="inventory_quantity" type="number" min="0" value="<?= (int)($selected['inventory_quantity'] ?? 0) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Type <?= sf_admin_select('product_type', ['physical' => 'Physical', 'digital' => 'Digital', 'bundle' => 'Bundle'], $selected['product_type'] ?? 'physical') ?></label>
      <label>Access <?= sf_admin_select('access_level', ['public' => 'Public', 'subscriber' => 'Subscriber', 'founding_fan' => 'Founding Fan'], $selected['access_level'] ?? 'public') ?></label>
      <label>Badge <input name="badge_label" value="<?= sf_admin_h($selected['badge_label'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Status <?= sf_admin_select('status', ['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out', 'archived' => 'Archived'], $selected['status'] ?? 'draft') ?></label>
      <label>Primary Image <?= sf_admin_asset_select('primary_image_asset_id', $imageAssets, $selected['primary_image_asset_id'] ?? '', 'image') ?></label>
    </div>
    <label>Short Description <input name="short_description" value="<?= sf_admin_h($selected['short_description'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
    <label>Description <textarea name="description" rows="4"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($selected['description'] ?? '') ?></textarea></label>
    <div class="sf-admin-check-row"><label><input type="checkbox" name="is_featured" <?= !empty($selected['is_featured']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Featured</label><label><input type="checkbox" name="is_limited_drop" <?= !empty($selected['is_limited_drop']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Limited Drop</label></div>
    <?= sf_admin_asset_preview_by_id($selected['primary_image_asset_id'] ?? null, 'image') ?>
    <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $selected ? 'Save Product' : 'Create Product' ?></button><a href="<?= sf_url('admin/products.php') ?>">New Product</a></div>
  </form>
</section>

<?php if ($selected): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Variants</span><h2>Options, sizes, inventory</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Variant</th><th>SKU</th><th>Size</th><th>Color</th><th>Price</th><th>Inventory</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if (!$variants): ?><tr><td colspan="8">No variants yet. The product-level price and inventory will be used.</td></tr><?php endif; ?>
    <?php foreach ($variants as $variant): ?>
      <tr><td><?= sf_admin_h($variant['variant_name'] ?? '') ?></td><td><?= sf_admin_h($variant['sku'] ?? '') ?></td><td><?= sf_admin_h($variant['size'] ?? '') ?></td><td><?= sf_admin_h($variant['color'] ?? '') ?></td><td><?= isset($variant['price_cents']) && $variant['price_cents'] !== null ? sf_store_money((int)$variant['price_cents']) : 'Product price' ?></td><td><?= (int)($variant['inventory_quantity'] ?? 0) ?></td><td><?= sf_admin_status_badge($variant['status'] ?? 'active') ?></td><td><form action="<?= sf_url('admin/products.php') ?>" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="delete_variant"><input type="hidden" name="product_id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>"><?= sf_admin_confirm_delete_button('Delete') ?></form></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <form class="sf-admin-form" action="<?= sf_url('admin/products.php') ?>" method="post">
    <?= sf_csrf_field() ?>
    <input type="hidden" name="action" value="save_variant"><input type="hidden" name="product_id" value="<?= (int)$selected['id'] ?>">
    <div class="sf-admin-form-grid"><label>Variant Name <input name="variant_name" placeholder="Large / Gold Vinyl / Bundle" required<?= sf_admin_form_disabled_attr() ?>></label><label>SKU <input name="variant_sku"<?= sf_admin_form_disabled_attr() ?>></label><label>Size <input name="size"<?= sf_admin_form_disabled_attr() ?>></label><label>Color <input name="color"<?= sf_admin_form_disabled_attr() ?>></label><label>Override Price <input name="variant_price" inputmode="decimal" placeholder="optional"<?= sf_admin_form_disabled_attr() ?>></label><label>Inventory <input name="variant_inventory_quantity" type="number" min="0" value="0"<?= sf_admin_form_disabled_attr() ?>></label><label>Status <?= sf_admin_select('variant_status', ['active'=>'Active','sold_out'=>'Sold Out','inactive'=>'Inactive'], 'active') ?></label></div>
    <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Add Variant</button></div>
  </form>
</section>
<?php endif; ?>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Catalog</span><h2>Merch products</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Product</th><th>Category</th><th>Access</th><th>Price</th><th>Inventory</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php if (!$products): ?><tr><td colspan="7">No products found.</td></tr><?php endif; ?>
    <?php foreach ($products as $product): ?>
      <tr><td><strong><?= sf_admin_h($product['name'] ?? '') ?></strong><br><small><?= sf_admin_h($product['slug'] ?? '') ?></small></td><td><?= sf_admin_h($product['category_name'] ?? '') ?></td><td><?= sf_admin_h(sf_access_label((string)($product['access_level'] ?? 'public'))) ?></td><td><?= sf_store_money((int)($product['price_cents'] ?? 0)) ?></td><td><?= (int)($product['inventory_quantity'] ?? 0) ?></td><td><?= sf_admin_status_badge($product['status'] ?? 'draft') ?></td><td><a href="<?= sf_url('admin/products.php?edit=' . (int)($product['id'] ?? 0)) ?>">Edit</a> · <a href="<?= sf_url('product.php?slug=' . urlencode((string)($product['slug'] ?? ''))) ?>">View</a><?php if (sf_admin_db_ready()): ?> <form action="<?= sf_url('admin/products.php') ?>" method="post" class="sf-inline-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= (int)($product['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete') ?></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
