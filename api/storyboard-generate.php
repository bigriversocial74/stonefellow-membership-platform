<?php
if (!function_exists('mb_substr')) {
  function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length); }
}
require_once __DIR__ . '/../includes/storyboard_generation.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'post_required'],405);
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$storyboardId = (int)($data['storyboard_id'] ?? $data['project_id'] ?? 0);
$prompt = trim((string)($data['story_prompt'] ?? $data['prompt'] ?? ''));
$sceneCount = (int)($data['scene_count'] ?? $data['scene_card_total'] ?? 0);
$returnUrl = trim((string)($data['return_url'] ?? ''));
$result = sf_sbgen_generate_storyboard($storyboardId, $prompt, $sceneCount > 0 ? $sceneCount : null);

if ($returnUrl !== '') {
  $sceneLabel = !empty($result['scenes']) ? ((int)$result['scenes'] . '-scene storyboard generated.') : 'Storyboard generated.';
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok']) ? $sceneLabel : ('Storyboard generation failed: ' . ($result['error'] ?? 'unknown_error')));
  header('Location: ' . $returnUrl);
  exit;
}

sf_json_response($result, !empty($result['ok']) ? 200 : 400);
