<?php
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length); }
}
require_once __DIR__ . '/../includes/storyboard_generation.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
sf_security_require_method('POST');
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

try { $data = sf_security_json_payload(65536); }
catch (LengthException $e) { sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413); }
catch (Throwable $e) { sf_json_response(['ok'=>false,'error'=>'invalid_json'],400); }
if (!$data && $_POST) $data = $_POST;

$storyboardId = (int)($data['storyboard_id'] ?? $data['project_id'] ?? 0);
$prompt = sf_agentic_text($data['story_prompt'] ?? $data['prompt'] ?? '', 8000);
$sceneCount = max(0, min(30, (int)($data['scene_count'] ?? $data['scene_card_total'] ?? 0)));
$returnUrl = sf_agentic_safe_redirect((string)($data['return_url'] ?? ''), sf_url('admin/storyboard-builder.php?id=' . $storyboardId));

if (empty($GLOBALS['sf_agentic_active_profile'])) {
    sf_agentic_guard_live_request(['type'=>'text','feature'=>'storyboarding','target_type'=>'storyboard','target_id'=>$storyboardId,'provider_key'=>(string)($data['provider_key'] ?? ''),'count'=>1]);
}
$result = sf_sbgen_generate_storyboard($storyboardId, $prompt, $sceneCount > 0 ? $sceneCount : null);

if (!empty($data['return_url'])) {
    $sceneLabel = !empty($result['scenes']) ? ((int)$result['scenes'] . '-scene storyboard generated.') : 'Storyboard generated.';
    sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok']) ? $sceneLabel : ('Storyboard generation failed: ' . ($result['error'] ?? 'unknown_error')));
    header('Location: ' . $returnUrl, true, 303);
    exit;
}
sf_json_response($result, !empty($result['ok']) ? 200 : 400);
