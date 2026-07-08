<?php
require_once __DIR__ . '/../includes/storyboarding_system.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'POST required'], 405);
}
if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
  sf_json_response(['ok' => false, 'error' => 'csrf_failed'], 403);
}
if (!sf_story_v1_ready()) {
  sf_json_response(['ok' => false, 'error' => 'storyboarding_tables_missing', 'message' => 'Import database/storyboarding_system_v1.sql first.'], 503);
}

$action = (string)($_POST['action'] ?? '');
$order = array_values(array_filter(array_map('intval', explode(',', (string)($_POST['order'] ?? '')))));

try {
  if ($action === 'reorder_scene_sheets') {
    sf_story_v1_update_scene_order($order);
    sf_json_response(['ok' => true, 'message' => 'Scene order saved.', 'order' => $order]);
  }
  if ($action === 'reorder_scene_cards') {
    sf_story_v1_update_card_order($order);
    sf_json_response(['ok' => true, 'message' => 'Scene card order saved.', 'order' => $order]);
  }
  sf_json_response(['ok' => false, 'error' => 'unknown_action'], 400);
} catch (Throwable $e) {
  error_log('Storyboarding system API failed: ' . $e->getMessage());
  sf_json_response(['ok' => false, 'error' => 'storyboarding_action_failed'], 500);
}
