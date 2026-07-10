<?php
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length); }
}
require_once __DIR__ . '/../includes/storyboard_scene_actions.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
sf_security_require_method('POST');
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

try { $data = sf_security_json_payload(65536); }
catch (LengthException $e) { sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413); }
catch (Throwable $e) { sf_json_response(['ok'=>false,'error'=>'invalid_json'],400); }
if (!$data && $_POST) $data = $_POST;

$action = (string)($data['action'] ?? 'edit_scene');
$allowed = ['edit_scene','rewrite_scene','regenerate_image','upload_image','retry_job'];
if (!in_array($action,$allowed,true)) sf_json_response(['ok'=>false,'error'=>'unknown_action'],400);
$sceneId = (int)($data['scene_id'] ?? 0);
$returnUrl = sf_agentic_safe_redirect((string)($data['return_url'] ?? ''), sf_url('admin/storyboard-builder.php'));

if (in_array($action,['rewrite_scene','regenerate_image','retry_job'],true) && empty($GLOBALS['sf_agentic_active_profile'])) {
    $type = $action === 'regenerate_image' ? 'image' : 'text';
    $targetId = $sceneId;
    if ($action === 'retry_job') {
        $job = sf_admin_fetch_one('SELECT scene_id, job_type FROM storyboard_jobs WHERE id=? LIMIT 1',[(int)($data['job_id'] ?? 0)]);
        $targetId = (int)($job['scene_id'] ?? 0);
        if (in_array((string)($job['job_type'] ?? ''),['generate_scene_image','regenerate_scene_image'],true)) $type='image';
    }
    sf_agentic_guard_live_request(['type'=>$type,'feature'=>$type==='image'?'scene_image':'scene_rewrite','target_type'=>'storyboard_scene','target_id'=>$targetId,'provider_key'=>(string)($data['provider_key'] ?? ''),'count'=>1]);
}

$result = match ($action) {
    'edit_scene' => sf_sba_update_scene($sceneId,$data),
    'rewrite_scene' => sf_sba_rewrite_scene($sceneId,(string)($data['rewrite_instruction'] ?? '')),
    'regenerate_image' => sf_sba_generate_scene_image($sceneId),
    'upload_image' => sf_sba_upload_scene_image($sceneId,'scene_image'),
    'retry_job' => sf_sba_retry_scene_job((int)($data['job_id'] ?? 0)),
};

if (!empty($data['return_url'])) {
    sf_admin_flash(!empty($result['ok'])?'success':'error',!empty($result['ok'])?'Scene action completed.':('Scene action failed: '.($result['error']??'unknown_error')));
    header('Location: '.$returnUrl,true,303);
    exit;
}
sf_json_response($result,!empty($result['ok'])?200:400);
