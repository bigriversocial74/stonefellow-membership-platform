<?php
if (!function_exists('mb_substr')) {
  function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length); }
}
require_once __DIR__ . '/../includes/storyboard_scene_actions.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'post_required'],405);
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'edit_scene');
$sceneId = (int)($data['scene_id'] ?? 0);
$returnUrl = trim((string)($data['return_url'] ?? ''));
$result = ['ok'=>false,'error'=>'unknown_action'];

if ($action === 'edit_scene') $result = sf_sba_update_scene($sceneId, $data);
elseif ($action === 'rewrite_scene') $result = sf_sba_rewrite_scene($sceneId, (string)($data['rewrite_instruction'] ?? ''));
elseif ($action === 'regenerate_image') $result = sf_sba_generate_scene_image($sceneId);
elseif ($action === 'upload_image') $result = sf_sba_upload_scene_image($sceneId, 'scene_image');
elseif ($action === 'retry_job') $result = sf_sba_retry_scene_job((int)($data['job_id'] ?? 0));

if ($returnUrl !== '') {
  $message = !empty($result['ok']) ? 'Scene action completed.' : ('Scene action failed: ' . ($result['error'] ?? 'unknown_error'));
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', $message);
  header('Location: ' . $returnUrl);
  exit;
}

sf_json_response($result, !empty($result['ok']) ? 200 : 400);
