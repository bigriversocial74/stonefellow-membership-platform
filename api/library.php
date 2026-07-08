<?php
require_once __DIR__ . '/../includes/library.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'POST required'], 405);
}

$userId = sf_current_user_id();
if (!$userId) {
  sf_json_response(['ok' => false, 'error' => 'login_required', 'message' => 'Sign in to save member library items.'], 401);
}

$data = sf_request_json();
$action = (string)($data['action'] ?? 'save');
$type = strtolower(trim((string)($data['content_type'] ?? '')));
$contentId = sf_int_from_request($data, 'content_id');
$status = strtolower(trim((string)($data['library_status'] ?? 'saved')));
if (!array_key_exists($status, sf_library_statuses())) {
  $status = 'saved';
}
if ($type === '' || $contentId <= 0) {
  sf_json_response(['ok' => false, 'error' => 'content_type_and_id_required'], 422);
}

if (!sf_db() || !sf_library_table_exists('member_library_items')) {
  sf_json_response(['ok' => false, 'error' => 'library_table_missing', 'message' => 'Import the member runtime tracking SQL migration before using live library saves.'], 503);
}

try {
  if ($action === 'remove') {
    $removed = sf_library_remove_item((int)$userId, $type, $contentId, $status);
    sf_json_response(['ok' => $removed, 'removed' => $removed, 'content_type' => $type, 'content_id' => $contentId, 'library_status' => $status]);
  }

  $item = sf_library_catalog_item($type, $contentId, $status, [
    'title' => $data['title'] ?? null,
    'slug' => $data['slug'] ?? null,
    'image_path' => $data['image_path'] ?? null,
    'content_url' => $data['content_url'] ?? null,
    'access_level' => $data['access_level'] ?? null,
    'progress_percent' => isset($data['progress_percent']) ? max(0, min(100, (int)$data['progress_percent'])) : null,
    'position_seconds' => isset($data['position_seconds']) ? max(0, (int)$data['position_seconds']) : null,
  ]);
  if (!$item) {
    sf_json_response(['ok' => false, 'error' => 'catalog_item_not_found'], 404);
  }

  $saved = sf_library_save_item((int)$userId, $item);
  sf_json_response(['ok' => $saved, 'saved' => $saved, 'item' => $item]);
} catch (Throwable $e) {
  error_log('Library API failed: ' . $e->getMessage());
  sf_json_response(['ok' => false, 'error' => 'library_action_failed'], 500);
}
