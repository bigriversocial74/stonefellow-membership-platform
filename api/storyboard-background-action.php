<?php
require_once __DIR__ . '/../includes/story_scene_backgrounds.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'post_required'],405);
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'assign_scene_background');
$sceneId = (int)($data['scene_id'] ?? 0);
$backgroundId = (int)($data['background_id'] ?? 0);
$returnUrl = trim((string)($data['return_url'] ?? ''));
$result = ['ok'=>false,'error'=>'unknown_action'];

if ($action === 'assign_scene_background') $result = sf_scene_background_assign_scene($sceneId, $backgroundId, (string)($data['usage_notes'] ?? ''));

if ($returnUrl !== '') {
  $message = !empty($result['ok']) ? 'Scene background saved.' : ('Scene background failed: ' . ($result['error'] ?? 'unknown_error'));
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', $message);
  header('Location: ' . $returnUrl);
  exit;
}

sf_json_response($result, !empty($result['ok']) ? 200 : 400);
