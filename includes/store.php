<?php
require_once __DIR__ . '/membership.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/notifications.php';

const SF_STORE_SESSION_CART = 'sf_store_cart_items';
const SF_STORE_SESSION_LAST_ORDER = 'sf_store_last_order';

function sf_store_h($value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sf_store_table_exists(string $table): bool {
  static $cache = [];
  $pdo = sf_db();
  if (!$pdo) {
    return false;
  }
  if (array_key_exists($table, $cache)) {
    return $cache[$table];
  }
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    $cache[$table] = (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    error_log('Stonefellow store table check failed for ' . $table . ': ' . $e->getMessage());
    $cache[$table] = false;
  }
  return $cache[$table];
}

function sf_store_column_exists(string $table, string $column): bool {
  static $cache = [];
  $key = $table . '.' . $column;
  if (array_key_exists($key, $cache)) {
    return $cache[$key];
  }
  $pdo = sf_db();
  if (!$pdo || !sf_store_table_exists($table)) {
    $cache[$key] = false;
    return false;
  }
  try {
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
    $stmt->execute([$column]);
    $cache[$key] = (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    error_log('Stonefellow store column check failed for ' . $key . ': ' . $e->getMessage());
    $cache[$key] = false;
  }
  return $cache[$key];
}

function sf_store_db_ready(): bool {
  return sf_db() instanceof PDO
    && sf_store_table_exists('products')
    && sf_store_table_exists('carts')
    && sf_store_table_exists('cart_items')
    && sf_store_table_exists('orders')
    && sf_store_table_exists('order_items');
}

function sf_store_money(int $cents): string {
  return '$' . number_format($cents / 100, 2);
}

function sf_store_int($value, int $default = 0): int {
  return is_numeric($value) ? (int)$value : $default;
}

function sf_store_clean_quantity($quantity): int {
  $quantity = sf_store_int($quantity, 1);
  if ($quantity < 1) {
    return 1;
  }
  if ($quantity > 99) {
    return 99;
  }
  return $quantity;
}

function sf_store_flash(string $type, string $message): void {
  $_SESSION['sf_store_flash'][] = ['type' => $type, 'message' => $message];
}

function sf_store_flashes(): array {
  $items = $_SESSION['sf_store_flash'] ?? [];
  unset($_SESSION['sf_store_flash']);
  return is_array($items) ? $items : [];
}

function sf_store_redirect(string $path): void {
  header('Location: ' . sf_url($path));
  exit;
}

function sf_store_static_products(): array {
  global $products;
  $rows = [];
  foreach (($products ?? []) as $index => $product) {
    $priceCents = (int)($product['price_cents'] ?? 0);
    $rows[] = [
      'id' => (int)($product['id'] ?? ($index + 1)),
      'category_id' => null,
      'category_name' => (string)($product['category'] ?? 'Merch'),
      'category_slug' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string)($product['category'] ?? 'merch'))),
      'name' => (string)($product['name'] ?? ''),
      'slug' => (string)($product['slug'] ?? ''),
      'short_description' => (string)($product['description'] ?? ''),
      'description' => (string)($product['description'] ?? ''),
      'price_cents' => $priceCents,
      'compare_at_price_cents' => null,
      'sku' => 'STATIC-' . (int)($product['id'] ?? ($index + 1)),
      'inventory_quantity' => 100,
      'product_type' => (($product['category'] ?? '') === 'Bundles') ? 'bundle' : 'physical',
      'access_level' => (($product['slug'] ?? '') === 'pilot-launch-bundle') ? 'subscriber' : 'public',
      'badge_label' => (string)($product['badge'] ?? ''),
      'is_featured' => !empty($product['badge']) && in_array($product['badge'], ['Featured', 'Bundle', 'Limited'], true) ? 1 : 0,
      'is_limited_drop' => (($product['badge'] ?? '') === 'Limited' || ($product['badge'] ?? '') === 'Bundle') ? 1 : 0,
      'status' => 'active',
      'image_path' => (string)($product['image'] ?? ''),
      'options' => $product['options'] ?? ['Standard'],
      'variants' => [],
    ];
  }
  return $rows;
}

function sf_store_categories(): array {
  if (!sf_store_table_exists('product_categories')) {
    $seen = [];
    foreach (sf_store_static_products() as $product) {
      $slug = $product['category_slug'] ?: 'merch';
      $seen[$slug] = ['id' => null, 'name' => $product['category_name'], 'slug' => $slug, 'description' => '', 'sort_order' => 0];
    }
    return array_values($seen);
  }
  try {
    $stmt = sf_db()->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    error_log('Stonefellow store categories failed: ' . $e->getMessage());
    return [];
  }
}

function sf_store_product_select_sql(): string {
  return "
    SELECT p.*, pc.name AS category_name, pc.slug AS category_slug,
      COALESCE(primary_asset.file_path, gallery_asset.file_path) AS image_path
    FROM products p
    LEFT JOIN product_categories pc ON pc.id = p.category_id
    LEFT JOIN media_assets primary_asset ON primary_asset.id = p.primary_image_asset_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    LEFT JOIN media_assets gallery_asset ON gallery_asset.id = pi.media_asset_id
  ";
}

function sf_store_products(array $filters = []): array {
  if (!sf_store_table_exists('products')) {
    $rows = sf_store_static_products();
  } else {
    $where = ["p.status IN ('active','sold_out')"];
    $params = [];
    if (!empty($filters['category'])) {
      $where[] = 'pc.slug = ?';
      $params[] = $filters['category'];
    }
    if (!empty($filters['featured'])) {
      $where[] = 'p.is_featured = 1';
    }
    $sql = sf_store_product_select_sql() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.is_featured DESC, p.is_limited_drop DESC, p.created_at DESC, p.id DESC';
    try {
      $stmt = sf_db()->prepare($sql);
      $stmt->execute($params);
      $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
      error_log('Stonefellow store products failed: ' . $e->getMessage());
      $rows = sf_store_static_products();
    }
  }

  foreach ($rows as &$row) {
    $row['badge_label'] = $row['badge_label'] ?? ($row['badge'] ?? 'Official');
    $row['category_name'] = $row['category_name'] ?? ($row['category'] ?? 'Merch');
    $row['image_path'] = $row['image_path'] ?? ($row['image'] ?? 'images/merch/merch-hero.png');
    $row['variants'] = sf_store_product_variants((int)($row['id'] ?? 0));
    if (empty($row['options'])) {
      $row['options'] = array_map(static fn($variant) => $variant['variant_name'] ?? 'Standard', $row['variants']);
      if (!$row['options']) {
        $row['options'] = ['Standard'];
      }
    }
  }
  unset($row);
  return $rows;
}

function sf_store_featured_product(): ?array {
  $products = sf_store_products(['featured' => true]);
  if ($products) {
    return $products[0];
  }
  $products = sf_store_products();
  return $products[0] ?? null;
}

function sf_store_product_by_slug(string $slug): ?array {
  $slug = trim($slug);
  if ($slug === '') {
    return null;
  }
  if (sf_store_table_exists('products')) {
    try {
      $stmt = sf_db()->prepare(sf_store_product_select_sql() . " WHERE p.slug = ? AND p.status IN ('active','sold_out','draft') LIMIT 1");
      $stmt->execute([$slug]);
      $product = $stmt->fetch();
      if ($product) {
        $product['variants'] = sf_store_product_variants((int)$product['id']);
        $product['options'] = array_map(static fn($variant) => $variant['variant_name'] ?? 'Standard', $product['variants']);
        if (!$product['options']) {
          $product['options'] = ['Standard'];
        }
        return $product;
      }
    } catch (Throwable $e) {
      error_log('Stonefellow store product lookup failed: ' . $e->getMessage());
    }
  }
  foreach (sf_store_static_products() as $product) {
    if (($product['slug'] ?? '') === $slug) {
      return $product;
    }
  }
  return null;
}

function sf_store_product_by_id(int $id): ?array {
  if ($id <= 0) {
    return null;
  }
  if (sf_store_table_exists('products')) {
    try {
      $stmt = sf_db()->prepare(sf_store_product_select_sql() . " WHERE p.id = ? AND p.status IN ('active','sold_out','draft') LIMIT 1");
      $stmt->execute([$id]);
      $product = $stmt->fetch();
      if ($product) {
        $product['variants'] = sf_store_product_variants($id);
        return $product;
      }
    } catch (Throwable $e) {
      error_log('Stonefellow store product by id failed: ' . $e->getMessage());
    }
  }
  foreach (sf_store_static_products() as $product) {
    if ((int)$product['id'] === $id) {
      return $product;
    }
  }
  return null;
}

function sf_store_product_variants(int $productId): array {
  if ($productId <= 0 || !sf_store_table_exists('product_variants')) {
    return [];
  }
  try {
    $stmt = sf_db()->prepare("SELECT * FROM product_variants WHERE product_id = ? AND status IN ('active','sold_out') ORDER BY id ASC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    error_log('Stonefellow store variants failed: ' . $e->getMessage());
    return [];
  }
}

function sf_store_variant_by_id(int $variantId, int $productId = 0): ?array {
  if ($variantId <= 0 || !sf_store_table_exists('product_variants')) {
    return null;
  }
  try {
    $sql = 'SELECT * FROM product_variants WHERE id = ?';
    $params = [$variantId];
    if ($productId > 0) {
      $sql .= ' AND product_id = ?';
      $params[] = $productId;
    }
    $sql .= ' LIMIT 1';
    $stmt = sf_db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
  } catch (Throwable $e) {
    return null;
  }
}

function sf_store_product_url(array $product): string {
  return sf_url('product.php?slug=' . urlencode((string)($product['slug'] ?? '')));
}

function sf_store_image_url(?string $path): string {
  $path = trim((string)$path);
  if ($path === '') {
    $path = 'images/merch/merch-hero.png';
  }
  if (preg_match('~^(https?:)?//|^data:~i', $path) || str_starts_with($path, '/')) {
    return $path;
  }
  return sf_asset($path);
}

function sf_store_can_purchase(array $product): bool {
  $required = (string)($product['access_level'] ?? 'public');
  if ($required === 'public' || $required === '') {
    return true;
  }
  return sf_access_allows($required) || sf_user_has_direct_grant('product', (int)($product['id'] ?? 0));
}

function sf_store_product_stock(array $product, ?array $variant = null): int {
  if ($variant) {
    return (int)($variant['inventory_quantity'] ?? 0);
  }
  return (int)($product['inventory_quantity'] ?? 0);
}

function sf_store_is_sold_out(array $product, ?array $variant = null): bool {
  if (($product['status'] ?? '') === 'sold_out') {
    return true;
  }
  if ($variant && ($variant['status'] ?? '') === 'sold_out') {
    return true;
  }
  return sf_store_product_stock($product, $variant) <= 0;
}

function sf_store_cart_session_items(): array {
  $items = $_SESSION[SF_STORE_SESSION_CART] ?? [];
  return is_array($items) ? $items : [];
}

function sf_store_cart_session_save(array $items): void {
  $_SESSION[SF_STORE_SESSION_CART] = array_values($items);
}

function sf_store_cart_key(int $productId, ?int $variantId, string $option): string {
  return $productId . ':' . ($variantId ?: 0) . ':' . strtolower(trim($option));
}

function sf_store_current_cart_id(bool $create = true): ?int {
  if (!sf_store_db_ready()) {
    return null;
  }
  $pdo = sf_db();
  $userId = sf_current_user_id();
  $sessionId = sf_session_key();
  try {
    if ($userId) {
      $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' ORDER BY updated_at DESC, id DESC LIMIT 1");
      $stmt->execute([$userId]);
    } else {
      $stmt = $pdo->prepare("SELECT id FROM carts WHERE session_id = ? AND status = 'active' ORDER BY updated_at DESC, id DESC LIMIT 1");
      $stmt->execute([$sessionId]);
    }
    $cartId = $stmt->fetchColumn();
    if ($cartId) {
      return (int)$cartId;
    }
    if (!$create) {
      return null;
    }
    $insert = $pdo->prepare('INSERT INTO carts (user_id, session_id, status) VALUES (?, ?, \'active\')');
    $insert->execute([$userId, $sessionId]);
    return (int)$pdo->lastInsertId();
  } catch (Throwable $e) {
    error_log('Stonefellow cart lookup failed: ' . $e->getMessage());
    return null;
  }
}

function sf_store_cart_add(int $productId, ?int $variantId, int $quantity, string $option = ''): bool {
  $product = sf_store_product_by_id($productId);
  if (!$product) {
    sf_store_flash('error', 'Product was not found.');
    return false;
  }
  $variant = $variantId ? sf_store_variant_by_id($variantId, $productId) : null;
  if ($variantId && !$variant) {
    sf_store_flash('error', 'Selected product option was not found.');
    return false;
  }
  if (!sf_store_can_purchase($product)) {
    sf_store_flash('warning', 'That item requires an active membership.');
    return false;
  }
  if (sf_store_is_sold_out($product, $variant)) {
    sf_store_flash('warning', 'That item is currently sold out.');
    return false;
  }
  $quantity = sf_store_clean_quantity($quantity);
  $maxStock = sf_store_product_stock($product, $variant);
  if ($maxStock > 0 && $quantity > $maxStock) {
    $quantity = $maxStock;
  }
  $unitPrice = $variant && isset($variant['price_cents']) && $variant['price_cents'] !== null ? (int)$variant['price_cents'] : (int)$product['price_cents'];
  $option = trim($option !== '' ? $option : (string)($variant['variant_name'] ?? 'Standard'));

  if (sf_store_db_ready()) {
    $cartId = sf_store_current_cart_id(true);
    if (!$cartId) {
      sf_store_flash('error', 'Cart could not be created.');
      return false;
    }
    try {
      $pdo = sf_db();
      $existingSql = 'SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND ' . ($variant ? 'variant_id = ?' : 'variant_id IS NULL') . ' LIMIT 1';
      $existingParams = $variant ? [$cartId, $productId, (int)$variant['id']] : [$cartId, $productId];
      $stmt = $pdo->prepare($existingSql);
      $stmt->execute($existingParams);
      $existing = $stmt->fetch();
      if ($existing) {
        $newQty = min((int)$existing['quantity'] + $quantity, max($maxStock, 1));
        $pdo->prepare('UPDATE cart_items SET quantity = ?, unit_price_cents = ?, updated_at = NOW() WHERE id = ?')->execute([$newQty, $unitPrice, (int)$existing['id']]);
      } else {
        $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, unit_price_cents) VALUES (?, ?, ?, ?, ?)')->execute([$cartId, $productId, $variant ? (int)$variant['id'] : null, $quantity, $unitPrice]);
      }
      $pdo->prepare('UPDATE carts SET updated_at = NOW() WHERE id = ?')->execute([$cartId]);
      sf_store_flash('success', 'Added to cart.');
      return true;
    } catch (Throwable $e) {
      error_log('Stonefellow cart add failed: ' . $e->getMessage());
      sf_store_flash('error', 'Cart could not be updated.');
      return false;
    }
  }

  $items = sf_store_cart_session_items();
  $key = sf_store_cart_key($productId, $variant ? (int)$variant['id'] : null, $option);
  $found = false;
  foreach ($items as &$item) {
    if (($item['key'] ?? '') === $key) {
      $item['quantity'] = min((int)$item['quantity'] + $quantity, max($maxStock, 1));
      $found = true;
      break;
    }
  }
  unset($item);
  if (!$found) {
    $items[] = [
      'key' => $key,
      'product_id' => $productId,
      'variant_id' => $variant ? (int)$variant['id'] : null,
      'product_name' => (string)$product['name'],
      'product_slug' => (string)$product['slug'],
      'variant_name' => $option,
      'quantity' => $quantity,
      'unit_price_cents' => $unitPrice,
      'image_path' => (string)($product['image_path'] ?? ''),
      'product_type' => (string)($product['product_type'] ?? 'physical'),
    ];
  }
  sf_store_cart_session_save($items);
  sf_store_flash('success', 'Added to cart.');
  return true;
}

function sf_store_cart_update(array $quantities): void {
  if (sf_store_db_ready()) {
    $cartId = sf_store_current_cart_id(false);
    if (!$cartId) {
      return;
    }
    $pdo = sf_db();
    foreach ($quantities as $itemId => $qty) {
      $qty = sf_store_int($qty, 0);
      $itemId = sf_store_int($itemId, 0);
      if ($itemId <= 0) {
        continue;
      }
      if ($qty <= 0) {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?');
        $stmt->execute([$itemId, $cartId]);
      } else {
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ? AND cart_id = ?');
        $stmt->execute([min($qty, 99), $itemId, $cartId]);
      }
    }
    $pdo->prepare('UPDATE carts SET updated_at = NOW() WHERE id = ?')->execute([$cartId]);
    sf_store_flash('success', 'Cart updated.');
    return;
  }
  $items = sf_store_cart_session_items();
  $next = [];
  foreach ($items as $index => $item) {
    $key = (string)($item['key'] ?? (string)$index);
    $qty = $quantities[$key] ?? $quantities[$index] ?? $item['quantity'] ?? 1;
    $qty = sf_store_int($qty, 0);
    if ($qty > 0) {
      $item['quantity'] = min($qty, 99);
      $next[] = $item;
    }
  }
  sf_store_cart_session_save($next);
  sf_store_flash('success', 'Cart updated.');
}

function sf_store_cart_remove(string $itemKey): void {
  if (sf_store_db_ready()) {
    $cartId = sf_store_current_cart_id(false);
    $itemId = sf_store_int($itemKey, 0);
    if ($cartId && $itemId > 0) {
      sf_db()->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')->execute([$itemId, $cartId]);
      sf_store_flash('success', 'Item removed.');
    }
    return;
  }
  $items = array_values(array_filter(sf_store_cart_session_items(), static fn($item) => (string)($item['key'] ?? '') !== $itemKey));
  sf_store_cart_session_save($items);
  sf_store_flash('success', 'Item removed.');
}

function sf_store_cart_clear(): void {
  if (sf_store_db_ready()) {
    $cartId = sf_store_current_cart_id(false);
    if ($cartId) {
      sf_db()->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
    }
  } else {
    unset($_SESSION[SF_STORE_SESSION_CART]);
  }
}

function sf_store_cart_items(): array {
  if (sf_store_db_ready()) {
    $cartId = sf_store_current_cart_id(false);
    if (!$cartId) {
      return [];
    }
    try {
      $stmt = sf_db()->prepare("\n        SELECT ci.id AS cart_item_id, ci.quantity, ci.unit_price_cents, p.id AS product_id, p.name AS product_name, p.slug AS product_slug, p.product_type, p.access_level, p.inventory_quantity, p.status AS product_status,\n          pv.id AS variant_id, pv.variant_name, pv.inventory_quantity AS variant_inventory, pv.status AS variant_status,\n          COALESCE(primary_asset.file_path, gallery_asset.file_path) AS image_path\n        FROM cart_items ci\n        INNER JOIN products p ON p.id = ci.product_id\n        LEFT JOIN product_variants pv ON pv.id = ci.variant_id\n        LEFT JOIN media_assets primary_asset ON primary_asset.id = p.primary_image_asset_id\n        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1\n        LEFT JOIN media_assets gallery_asset ON gallery_asset.id = pi.media_asset_id\n        WHERE ci.cart_id = ?\n        ORDER BY ci.created_at ASC, ci.id ASC\n      ");
      $stmt->execute([$cartId]);
      return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
      error_log('Stonefellow cart items failed: ' . $e->getMessage());
      return [];
    }
  }
  return sf_store_cart_session_items();
}

function sf_store_cart_totals(?array $items = null): array {
  $items = $items ?? sf_store_cart_items();
  $subtotal = 0;
  $hasPhysical = false;
  $count = 0;
  foreach ($items as $item) {
    $qty = (int)($item['quantity'] ?? 0);
    $unit = (int)($item['unit_price_cents'] ?? 0);
    $subtotal += $qty * $unit;
    $count += $qty;
    if (($item['product_type'] ?? 'physical') !== 'digital') {
      $hasPhysical = true;
    }
  }
  $shipping = ($subtotal > 0 && $hasPhysical) ? ($subtotal >= 10000 ? 0 : 800) : 0;
  $tax = $subtotal > 0 ? (int)round(($subtotal + $shipping) * 0.062) : 0;
  $total = $subtotal + $shipping + $tax;
  return [
    'item_count' => $count,
    'subtotal_cents' => $subtotal,
    'shipping_cents' => $shipping,
    'tax_cents' => $tax,
    'total_cents' => $total,
    'has_physical' => $hasPhysical,
  ];
}

function sf_store_customer_from_post(array $post): array {
  return [
    'email' => sf_normalize_email((string)($post['email'] ?? '')),
    'name' => trim((string)($post['name'] ?? '')),
    'phone' => trim((string)($post['phone'] ?? '')),
    'address_1' => trim((string)($post['address_1'] ?? $post['address'] ?? '')),
    'address_2' => trim((string)($post['address_2'] ?? '')),
    'city' => trim((string)($post['city'] ?? '')),
    'state' => trim((string)($post['state'] ?? '')),
    'postal_code' => trim((string)($post['postal_code'] ?? $post['zip'] ?? '')),
    'country' => trim((string)($post['country'] ?? 'US')) ?: 'US',
    'notes' => trim((string)($post['notes'] ?? '')),
  ];
}

function sf_store_validate_checkout(array $customer, array $items, array $totals): array {
  $errors = [];
  if (!$items || $totals['total_cents'] <= 0) {
    $errors[] = 'Your cart is empty.';
  }
  if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
  }
  if ($customer['name'] === '') {
    $errors[] = 'Full name is required.';
  }
  if (!empty($totals['has_physical'])) {
    foreach (['address_1' => 'shipping address', 'city' => 'city', 'state' => 'state', 'postal_code' => 'postal code'] as $key => $label) {
      if ($customer[$key] === '') {
        $errors[] = 'Enter your ' . $label . '.';
      }
    }
  }
  return $errors;
}

function sf_store_next_order_number(): string {
  return 'SF-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function sf_store_insert_order_history(PDO $pdo, int $orderId, string $fromStatus, string $toStatus, string $note = ''): void {
  if (!sf_store_table_exists('order_status_history')) {
    return;
  }
  $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id, from_status, to_status, note, changed_by_user_id) VALUES (?, ?, ?, ?, ?)');
  $stmt->execute([$orderId, $fromStatus ?: null, $toStatus, $note ?: null, sf_current_user_id()]);
}

function sf_store_record_inventory_movement(PDO $pdo, int $productId, ?int $variantId, int $orderId, int $delta, string $reason): void {
  if (!sf_store_table_exists('product_inventory_movements')) {
    return;
  }
  $stmt = $pdo->prepare('INSERT INTO product_inventory_movements (product_id, variant_id, order_id, delta_quantity, reason, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)');
  $stmt->execute([$productId, $variantId, $orderId, $delta, $reason, sf_current_user_id()]);
}

function sf_store_create_order(array $customer): ?array {
  $items = sf_store_cart_items();
  $totals = sf_store_cart_totals($items);
  $errors = sf_store_validate_checkout($customer, $items, $totals);
  if ($errors) {
    foreach ($errors as $error) {
      sf_store_flash('error', $error);
    }
    return null;
  }

  if (!sf_store_db_ready()) {
    $orderNumber = sf_store_next_order_number();
    $order = [
      'id' => 0,
      'order_number' => $orderNumber,
      'receipt_token' => hash('sha256', $orderNumber . sf_session_key()),
      'status' => 'paid',
      'payment_status' => 'paid',
      'fulfillment_status' => 'unfulfilled',
      'customer_email' => $customer['email'],
      'shipping_name' => $customer['name'],
      'shipping_address_1' => $customer['address_1'],
      'shipping_address_2' => $customer['address_2'],
      'shipping_city' => $customer['city'],
      'shipping_state' => $customer['state'],
      'shipping_postal_code' => $customer['postal_code'],
      'shipping_country' => $customer['country'],
      'subtotal_cents' => $totals['subtotal_cents'],
      'shipping_cents' => $totals['shipping_cents'],
      'tax_cents' => $totals['tax_cents'],
      'total_cents' => $totals['total_cents'],
      'items' => $items,
      'created_at' => date('Y-m-d H:i:s'),
      'mode' => 'session_preview',
    ];
    $_SESSION[SF_STORE_SESSION_LAST_ORDER] = $order;
    sf_store_send_order_notifications($order);
    sf_store_cart_clear();
    return $order;
  }

  $pdo = sf_db();
  $cartId = sf_store_current_cart_id(false);
  if (!$cartId) {
    sf_store_flash('error', 'Cart could not be found.');
    return null;
  }
  $orderNumber = sf_store_next_order_number();
  $receiptToken = bin2hex(random_bytes(32));
  $provider = getenv('SF_PAYMENT_PROVIDER') ?: 'sandbox';
  $paymentId = $provider . '_merch_' . substr(hash('sha256', $orderNumber . microtime(true)), 0, 18);

  try {
    $pdo->beginTransaction();

    foreach ($items as $item) {
      $productId = (int)($item['product_id'] ?? 0);
      $variantId = isset($item['variant_id']) ? (int)$item['variant_id'] : 0;
      $qty = (int)($item['quantity'] ?? 0);
      $product = sf_store_product_by_id($productId);
      $variant = $variantId > 0 ? sf_store_variant_by_id($variantId, $productId) : null;
      if (!$product || !sf_store_can_purchase($product) || sf_store_is_sold_out($product, $variant)) {
        throw new RuntimeException('One item in your cart is no longer available.');
      }
      $available = sf_store_product_stock($product, $variant);
      if ($available > 0 && $qty > $available) {
        throw new RuntimeException(($product['name'] ?? 'Item') . ' only has ' . $available . ' available.');
      }
    }

    $columns = ['user_id','order_number','status','subtotal_cents','shipping_cents','tax_cents','total_cents','customer_email','shipping_name','shipping_address_1','shipping_address_2','shipping_city','shipping_state','shipping_postal_code','shipping_country','external_payment_id'];
    $values = [sf_current_user_id(), $orderNumber, 'paid', $totals['subtotal_cents'], $totals['shipping_cents'], $totals['tax_cents'], $totals['total_cents'], $customer['email'], $customer['name'], $customer['address_1'], $customer['address_2'], $customer['city'], $customer['state'], $customer['postal_code'], $customer['country'], $paymentId];
    $optional = [
      'receipt_token' => $receiptToken,
      'payment_status' => 'paid',
      'fulfillment_status' => 'unfulfilled',
      'customer_phone' => $customer['phone'],
      'shipping_method' => 'standard',
      'notes' => $customer['notes'],
    ];
    foreach ($optional as $column => $value) {
      if (sf_store_column_exists('orders', $column)) {
        $columns[] = $column;
        $values[] = $value;
      }
    }
    $sql = 'INSERT INTO orders (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $orderId = (int)$pdo->lastInsertId();

    $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, quantity, unit_price_cents, total_price_cents) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($items as $item) {
      $productId = (int)($item['product_id'] ?? 0);
      $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
      $qty = (int)($item['quantity'] ?? 0);
      $unit = (int)($item['unit_price_cents'] ?? 0);
      $itemInsert->execute([$orderId, $productId, $variantId, (string)($item['product_name'] ?? ''), (string)($item['variant_name'] ?? ''), $qty, $unit, $qty * $unit]);
      if ($variantId) {
        $pdo->prepare('UPDATE product_variants SET inventory_quantity = GREATEST(inventory_quantity - ?, 0), updated_at = NOW() WHERE id = ?')->execute([$qty, $variantId]);
      } else {
        $pdo->prepare('UPDATE products SET inventory_quantity = GREATEST(inventory_quantity - ?, 0), updated_at = NOW() WHERE id = ?')->execute([$qty, $productId]);
      }
      sf_store_record_inventory_movement($pdo, $productId, $variantId, $orderId, -$qty, 'order_paid');
    }

    $pdo->prepare("UPDATE carts SET status = 'converted', updated_at = NOW() WHERE id = ?")->execute([$cartId]);

    if (sf_store_table_exists('payment_transactions')) {
      $stmt = $pdo->prepare("INSERT INTO payment_transactions (user_id, order_id, provider, provider_payment_id, transaction_type, status, amount_cents, currency, raw_payload_json) VALUES (?, ?, ?, ?, 'merch_order', 'paid', ?, 'USD', ?)");
      $stmt->execute([sf_current_user_id(), $orderId, $provider, $paymentId, $totals['total_cents'], json_encode(['mode' => 'sandbox', 'order_number' => $orderNumber], JSON_UNESCAPED_SLASHES)]);
    }

    sf_store_insert_order_history($pdo, $orderId, '', 'paid', 'Sandbox checkout completed.');
    $pdo->commit();

    $order = sf_store_order_by_number($orderNumber, $receiptToken) ?: ['order_number' => $orderNumber, 'receipt_token' => $receiptToken];
    $_SESSION[SF_STORE_SESSION_LAST_ORDER] = ['order_number' => $orderNumber, 'receipt_token' => $receiptToken];
    sf_store_send_order_notifications($order);
    return $order;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Stonefellow order create failed: ' . $e->getMessage());
    sf_store_flash('error', $e->getMessage());
    return null;
  }
}

function sf_store_order_by_number(string $orderNumber, ?string $receiptToken = null): ?array {
  $orderNumber = trim($orderNumber);
  if ($orderNumber === '') {
    $last = $_SESSION[SF_STORE_SESSION_LAST_ORDER] ?? null;
    if (is_array($last) && !empty($last['order_number'])) {
      $orderNumber = (string)$last['order_number'];
      $receiptToken = $receiptToken ?: (string)($last['receipt_token'] ?? '');
    }
  }
  if ($orderNumber === '') {
    return null;
  }
  if (!sf_store_db_ready()) {
    $last = $_SESSION[SF_STORE_SESSION_LAST_ORDER] ?? null;
    return is_array($last) && (($last['order_number'] ?? '') === $orderNumber) ? $last : null;
  }
  try {
    $where = 'order_number = ?';
    $params = [$orderNumber];
    if (sf_store_column_exists('orders', 'receipt_token') && $receiptToken) {
      $where .= ' AND receipt_token = ?';
      $params[] = $receiptToken;
    }
    $stmt = sf_db()->prepare('SELECT * FROM orders WHERE ' . $where . ' LIMIT 1');
    $stmt->execute($params);
    $order = $stmt->fetch();
    if (!$order) {
      return null;
    }
    $items = sf_store_order_items((int)$order['id']);
    $order['items'] = $items;
    return $order;
  } catch (Throwable $e) {
    error_log('Stonefellow order lookup failed: ' . $e->getMessage());
    return null;
  }
}

function sf_store_order_items(int $orderId): array {
  if ($orderId <= 0 || !sf_store_table_exists('order_items')) {
    return [];
  }
  try {
    $stmt = sf_db()->prepare("\n      SELECT oi.*, COALESCE(primary_asset.file_path, gallery_asset.file_path) AS image_path, p.slug AS product_slug\n      FROM order_items oi\n      LEFT JOIN products p ON p.id = oi.product_id\n      LEFT JOIN media_assets primary_asset ON primary_asset.id = p.primary_image_asset_id\n      LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1\n      LEFT JOIN media_assets gallery_asset ON gallery_asset.id = pi.media_asset_id\n      WHERE oi.order_id = ?\n      ORDER BY oi.id ASC\n    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    return [];
  }
}

function sf_store_recent_orders(int $limit = 100): array {
  if (!sf_store_table_exists('orders')) {
    return [];
  }
  $columns = 'o.*';
  try {
    $stmt = sf_db()->prepare("SELECT {$columns}, u.display_name, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC, o.id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    error_log('Stonefellow orders fetch failed: ' . $e->getMessage());
    return [];
  }
}


function sf_store_apply_order_inventory_for_status(PDO $pdo, array $order, string $newStatus): void {
  $oldStatus = (string)($order['status'] ?? '');
  $orderId = (int)($order['id'] ?? 0);
  if ($orderId <= 0 || $oldStatus === $newStatus) {
    return;
  }
  $restockStatuses = ['canceled', 'refunded'];
  $paidStatuses = ['paid', 'fulfilled'];
  $items = $order['items'] ?? sf_store_order_items($orderId);

  if (in_array($newStatus, $restockStatuses, true) && !in_array($oldStatus, $restockStatuses, true)) {
    foreach ($items as $item) {
      $qty = (int)($item['quantity'] ?? 0);
      $productId = (int)($item['product_id'] ?? 0);
      $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
      if ($qty <= 0 || $productId <= 0) {
        continue;
      }
      if ($variantId) {
        $pdo->prepare('UPDATE product_variants SET inventory_quantity = inventory_quantity + ?, updated_at = NOW() WHERE id = ?')->execute([$qty, $variantId]);
      } else {
        $pdo->prepare('UPDATE products SET inventory_quantity = inventory_quantity + ?, updated_at = NOW() WHERE id = ?')->execute([$qty, $productId]);
      }
      sf_store_record_inventory_movement($pdo, $productId, $variantId, $orderId, $qty, $newStatus === 'refunded' ? 'refund' : 'order_canceled');
    }
    return;
  }

  if (in_array($oldStatus, $restockStatuses, true) && in_array($newStatus, $paidStatuses, true)) {
    foreach ($items as $item) {
      $qty = (int)($item['quantity'] ?? 0);
      $productId = (int)($item['product_id'] ?? 0);
      $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
      if ($qty <= 0 || $productId <= 0) {
        continue;
      }
      if ($variantId) {
        $pdo->prepare('UPDATE product_variants SET inventory_quantity = GREATEST(inventory_quantity - ?, 0), updated_at = NOW() WHERE id = ?')->execute([$qty, $variantId]);
      } else {
        $pdo->prepare('UPDATE products SET inventory_quantity = GREATEST(inventory_quantity - ?, 0), updated_at = NOW() WHERE id = ?')->execute([$qty, $productId]);
      }
      sf_store_record_inventory_movement($pdo, $productId, $variantId, $orderId, -$qty, 'order_paid');
    }
  }
}

function sf_store_order_status_options(): array {
  return ['pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'canceled' => 'Canceled', 'refunded' => 'Refunded'];
}

function sf_store_update_order_status(int $orderId, string $status, string $note = ''): bool {
  if ($orderId <= 0 || !sf_store_table_exists('orders') || !array_key_exists($status, sf_store_order_status_options())) {
    return false;
  }
  $pdo = sf_db();
  try {
    $order = sf_store_order_by_id($orderId);
    if (!$order) {
      return false;
    }
    $pdo->beginTransaction();
    sf_store_apply_order_inventory_for_status($pdo, $order, $status);
    $columns = ['status = ?'];
    $params = [$status];
    if (sf_store_column_exists('orders', 'fulfillment_status')) {
      $fulfillment = $status === 'fulfilled' ? 'fulfilled' : ($status === 'canceled' ? 'canceled' : ($order['fulfillment_status'] ?? 'unfulfilled'));
      $columns[] = 'fulfillment_status = ?';
      $params[] = $fulfillment;
    }
    if (sf_store_column_exists('orders', 'payment_status')) {
      $payment = in_array($status, ['paid','fulfilled'], true) ? 'paid' : ($status === 'refunded' ? 'refunded' : ($status === 'canceled' ? 'failed' : 'unpaid'));
      $columns[] = 'payment_status = ?';
      $params[] = $payment;
    }
    $params[] = $orderId;
    $stmt = $pdo->prepare('UPDATE orders SET ' . implode(', ', $columns) . ', updated_at = NOW() WHERE id = ?');
    $stmt->execute($params);
    sf_store_insert_order_history($pdo, $orderId, (string)($order['status'] ?? ''), $status, $note);
    $pdo->commit();
    $updatedOrder = sf_store_order_by_id($orderId) ?: $order;
    sf_store_send_order_status_notification($updatedOrder, $status, $note);
    return true;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Stonefellow update order status failed: ' . $e->getMessage());
    return false;
  }
}

function sf_store_order_by_id(int $orderId): ?array {
  if ($orderId <= 0 || !sf_store_table_exists('orders')) {
    return null;
  }
  try {
    $stmt = sf_db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    if ($row) {
      $row['items'] = sf_store_order_items($orderId);
    }
    return $row ?: null;
  } catch (Throwable $e) {
    return null;
  }
}

function sf_store_order_history(int $orderId): array {
  if ($orderId <= 0 || !sf_store_table_exists('order_status_history')) {
    return [];
  }
  try {
    $stmt = sf_db()->prepare('SELECT h.*, u.display_name FROM order_status_history h LEFT JOIN users u ON u.id = h.changed_by_user_id WHERE h.order_id = ? ORDER BY h.created_at DESC, h.id DESC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    return [];
  }
}

function sf_store_order_notification_vars(array $order, string $note = ''): array {
  $orderNumber = (string)($order['order_number'] ?? '');
  $receiptKey = (string)($order['receipt_token'] ?? '');
  $receiptPath = 'order-confirmation.php?order=' . urlencode($orderNumber) . ($receiptKey !== '' ? '&key=' . urlencode($receiptKey) : '');
  $orderId = (int)($order['id'] ?? 0);
  return [
    'recipient_name' => (string)($order['shipping_name'] ?? $order['customer_email'] ?? 'Stonefellow fan'),
    'order_number' => $orderNumber,
    'order_total' => sf_store_money((int)($order['total_cents'] ?? 0)),
    'receipt_url' => sf_notify_absolute_url($receiptPath),
    'admin_order_url' => sf_notify_absolute_url('admin/orders.php' . ($orderId > 0 ? '?view=' . $orderId : '')),
    'fulfillment_note' => $note,
  ];
}

function sf_store_send_order_notifications(array $order): void {
  $email = trim((string)($order['customer_email'] ?? ''));
  $vars = sf_store_order_notification_vars($order);
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sf_notify_send_template('merch_order_confirmation', [
      'user_id' => isset($order['user_id']) ? (int)$order['user_id'] : null,
      'email' => $email,
      'name' => (string)($order['shipping_name'] ?? $email),
    ], $vars, [
      'notification_type' => 'commerce',
      'metadata' => ['event' => 'merch_order_created', 'order_number' => $order['order_number'] ?? '', 'order_id' => $order['id'] ?? null],
      'dispatch' => true,
    ]);
  }
  foreach (sf_notify_admin_recipients() as $adminRecipient) {
    sf_notify_send_template('admin_new_order', $adminRecipient, $vars, [
      'notification_type' => 'admin',
      'metadata' => ['event' => 'admin_new_order', 'order_number' => $order['order_number'] ?? '', 'order_id' => $order['id'] ?? null],
      'dispatch' => true,
    ]);
  }
}

function sf_store_send_order_status_notification(array $order, string $status, string $note = ''): void {
  if ($status !== 'fulfilled') {
    return;
  }
  $email = trim((string)($order['customer_email'] ?? ''));
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return;
  }
  sf_notify_send_template('order_fulfilled', [
    'user_id' => isset($order['user_id']) ? (int)$order['user_id'] : null,
    'email' => $email,
    'name' => (string)($order['shipping_name'] ?? $email),
  ], sf_store_order_notification_vars($order, $note), [
    'notification_type' => 'commerce',
    'metadata' => ['event' => 'order_fulfilled', 'order_number' => $order['order_number'] ?? '', 'order_id' => $order['id'] ?? null],
    'dispatch' => true,
  ]);
}
?>
