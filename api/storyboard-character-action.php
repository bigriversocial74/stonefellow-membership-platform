<?php
if (!function_exists('mb_substr')) {
  function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length); }
}
require_once __DIR__ . '/../includes/storyboard_character_actions.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'post_required'],405);
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'add_character');
$returnUrl = trim((string)($data['return_url'] ?? ''));
$storyboardId = (int)($data['storyboard_id'] ?? 0);
$sceneId = (int)($data['scene_id'] ?? 0);
$characterId = (int)($data['character_id'] ?? 0);
$storyCharacterId = (int)($data['story_character_id'] ?? 0);
$result = ['ok'=>false,'error'=>'unknown_action'];

if ($action === 'add_character') $result = sf_sbc_add_character($storyboardId, $data);
elseif ($action === 'update_character') $result = sf_sbc_update_character($characterId, $data);
elseif ($action === 'upload_reference') $result = sf_sbc_upload_reference($characterId, 'reference_image');
elseif ($action === 'assign_scene_character') $result = sf_sbc_assign_scene_character($storyboardId, $sceneId, $characterId, $storyCharacterId);
elseif ($action === 'remove_scene_character') $result = sf_sbc_remove_scene_character($sceneId, $characterId, $storyCharacterId);
elseif ($action === 'bulk_regenerate_images') $result = sf_sbc_bulk_regenerate_images($storyboardId);

if ($returnUrl !== '') {
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok']) ? 'Character action completed.' : ('Character action failed: ' . ($result['error'] ?? 'unknown_error')));
  header('Location: ' . $returnUrl);
  exit;
}

sf_json_response($result, !empty($result['ok']) ? 200 : 400);
