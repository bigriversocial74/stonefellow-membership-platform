<?php
require_once __DIR__ . '/../includes/store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => true, 'cart' => ['items' => sf_store_cart_items(), 'totals' => sf_store_cart_totals()]]);
}

$data = sf_request_json();
$action = (string)($data['action'] ?? '');
if (!sf_verify_csrf($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
  sf_json_response(['ok' => false, 'error' => 'Security check failed.'], 403);
}

if ($action === 'add') {
  $ok = sf_store_cart_add(
    sf_store_int($data['product_id'] ?? 0),
    !empty($data['variant_id']) ? sf_store_int($data['variant_id']) : null,
    sf_store_clean_quantity($data['quantity'] ?? 1),
    (string)($data['option'] ?? '')
  );
  sf_json_response(['ok' => $ok, 'items' => sf_store_cart_items(), 'totals' => sf_store_cart_totals()], $ok ? 200 : 422);
}

if ($action === 'update') {
  sf_store_cart_update(is_array($data['quantities'] ?? null) ? $data['quantities'] : []);
  sf_json_response(['ok' => true, 'items' => sf_store_cart_items(), 'totals' => sf_store_cart_totals()]);
}

if ($action === 'remove') {
  sf_store_cart_remove((string)($data['item_key'] ?? ''));
  sf_json_response(['ok' => true, 'items' => sf_store_cart_items(), 'totals' => sf_store_cart_totals()]);
}

if ($action === 'clear') {
  sf_store_cart_clear();
  sf_json_response(['ok' => true, 'items' => [], 'totals' => sf_store_cart_totals([])]);
}

sf_json_response(['ok' => false, 'error' => 'Unsupported cart action.'], 400);
