<?php
/**
 * Cross-cutting governance for Stonefellow's live AI provider calls and
 * supervised action execution. This file has no side effects until
 * sf_agentic_guard_request() is called by ai_settings.php.
 */

if (defined('SF_AGENTIC_GOVERNANCE_LOADED')) {
    return;
}
define('SF_AGENTIC_GOVERNANCE_LOADED', true);

function sf_agentic_path(): string
{
    return trim(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')), '/');
}

function sf_agentic_is_api(): bool
{
    return str_starts_with(sf_agentic_path(), 'api/');
}

function sf_agentic_error_message(string $error): string
{
    $messages = [
        'permission_denied' => 'Your admin role does not allow this AI operation.',
        'provider_not_ready' => 'The selected AI provider is not active and securely configured.',
        'provider_not_supported' => 'The selected provider is not supported for this operation.',
        'ai_secret_not_configured' => 'SF_AI_SETTINGS_SECRET must be configured with a long random value before production AI calls.',
        'ai_limits_not_configured' => 'Configure a monthly provider budget and the relevant token or image limit before production AI calls.',
        'ai_budget_exceeded' => 'The provider monthly AI budget has been reached.',
        'ai_token_limit_exceeded' => 'The provider monthly token limit has been reached.',
        'ai_image_limit_exceeded' => 'The provider monthly image limit has been reached.',
        'ai_rate_limited' => 'Too many AI requests were submitted. Try again later.',
        'ai_request_in_progress' => 'A matching AI operation is already running.',
        'prompt_too_large' => 'The AI prompt or instruction is too large.',
        'bulk_generation_limit' => 'The bulk image request exceeds the configured maximum.',
        'approval_required' => 'Generated media must be approved before it can become current.',
        'action_changed_after_approval' => 'The action changed after approval and must be reviewed again.',
        'distinct_executor_required' => 'High-risk actions must be executed by a different admin than the approver.',
        'invalid_action_payload' => 'The approved action payload is invalid or too large.',
    ];
    return $messages[$error] ?? 'The AI operation was blocked by the governance layer.';
}

function sf_agentic_abort(string $error, int $status = 403): never
{
    $message = sf_agentic_error_message($error);
    if (sf_agentic_is_api() && function_exists('sf_json_response')) {
        sf_json_response(['ok' => false, 'error' => $error, 'message' => $message], $status);
    }

    if (function_exists('sf_admin_flash')) {
        sf_admin_flash('error', $message);
    }
    $fallback = function_exists('sf_url') ? sf_url('admin/index.php') : '/';
    $current = (string)($_SERVER['REQUEST_URI'] ?? $fallback);
    $target = function_exists('sf_security_safe_redirect')
        ? sf_security_safe_redirect($current, $fallback)
        : $fallback;
    header('Location: ' . $target, true, 303);
    exit;
}

function sf_agentic_safe_redirect(?string $target, string $fallback): string
{
    if (function_exists('sf_security_safe_redirect')) {
        return sf_security_safe_redirect($target, $fallback);
    }
    $target = trim((string)$target);
    if ($target === '' || str_starts_with($target, '//') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
        return $fallback;
    }
    return $target;
}

function sf_agentic_text($value, int $maxBytes, string $fallback = ''): string
{
    $value = trim((string)$value);
    if ($value === '') return $fallback;
    if (strlen($value) > $maxBytes) return substr($value, 0, $maxBytes);
    return $value;
}

function sf_agentic_model_name($value): string
{
    $value = trim((string)$value);
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{0,119}$/', $value) ? $value : '';
}

function sf_agentic_table_exists(string $table): bool
{
    if (function_exists('sf_admin_table_exists')) return sf_admin_table_exists($table);
    if (!function_exists('sf_db')) return false;
    $pdo = sf_db();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sf_agentic_db(): ?PDO
{
    if (function_exists('sf_admin_db')) return sf_admin_db();
    if (function_exists('sf_db')) return sf_db();
    return null;
}

function sf_agentic_permission_for_path(string $path): string
{
    $base = basename($path);
    if ($base === 'ai-settings.php') return 'admin.settings.manage';
    if ($base === 'ai-autonomy-policies.php') return 'admin.security.manage';
    if (preg_match('/^ai-(platform|execution|publishing|operations|mission)/', $base)) return 'admin.ops.manage';
    if (in_array($base, ['theme-images.php','storyboard-builder.php','storyboards.php','storyboard-generate.php','storyboard-scene-action.php','story-episode-outline.php'], true)) {
        return 'admin.content.manage';
    }
    return '';
}

function sf_agentic_user_can(string $permission, ?int $userId = null): bool
{
    if ($permission === '') return true;
    $userId = $userId ?: (function_exists('sf_current_user_id') ? (int)sf_current_user_id() : (int)($_SESSION['sf_user_id'] ?? 0));
    if ($userId <= 0) return false;
    $pdo = sf_agentic_db();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare('SELECT role, status FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || ($user['status'] ?? '') !== 'active' || ($user['role'] ?? '') !== 'admin') return false;
        if (!sf_agentic_table_exists('admin_user_roles') || !sf_agentic_table_exists('admin_role_permissions') || !sf_agentic_table_exists('admin_roles')) return true;
        $assigned = $pdo->prepare('SELECT COUNT(*) FROM admin_user_roles WHERE user_id=?');
        $assigned->execute([$userId]);
        if ((int)$assigned->fetchColumn() === 0) return true;
        $check = $pdo->prepare("SELECT COUNT(*) FROM admin_user_roles ur INNER JOIN admin_role_permissions rp ON rp.role_id=ur.role_id INNER JOIN admin_roles r ON r.id=ur.role_id WHERE ur.user_id=? AND rp.permission_key=? AND r.status='active'");
        $check->execute([$userId, $permission]);
        return (int)$check->fetchColumn() > 0;
    } catch (Throwable $e) {
        error_log('Stonefellow AI permission check failed: ' . $e->getMessage());
        return false;
    }
}

function sf_agentic_require_permission(string $permission = ''): void
{
    $permission = $permission !== '' ? $permission : sf_agentic_permission_for_path(sf_agentic_path());
    if ($permission !== '' && !sf_agentic_user_can($permission)) sf_agentic_abort('permission_denied', 403);
}

function sf_agentic_lock_name(string $key): string
{
    return 'sf_ai_' . substr(hash('sha256', $key), 0, 48);
}

function sf_agentic_acquire_lock(string $key, int $timeoutSeconds = 0): bool
{
    $pdo = sf_agentic_db();
    if (!$pdo) return !function_exists('sf_is_production') || !sf_is_production();
    $name = sf_agentic_lock_name($key);
    try {
        $stmt = $pdo->prepare('SELECT GET_LOCK(?, ?)');
        $stmt->execute([$name, max(0, min(5, $timeoutSeconds))]);
        $ok = (int)$stmt->fetchColumn() === 1;
        if ($ok) $GLOBALS['sf_agentic_locks'][$name] = true;
        return $ok;
    } catch (Throwable $e) {
        error_log('Stonefellow AI lock failed: ' . $e->getMessage());
        return false;
    }
}

function sf_agentic_release_locks(): void
{
    $pdo = sf_agentic_db();
    if (!$pdo) return;
    foreach (array_keys((array)($GLOBALS['sf_agentic_locks'] ?? [])) as $name) {
        try {
            $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $stmt->execute([$name]);
        } catch (Throwable $e) {
            error_log('Stonefellow AI lock release failed: ' . $e->getMessage());
        }
    }
    $GLOBALS['sf_agentic_locks'] = [];
}
register_shutdown_function('sf_agentic_release_locks');

function sf_agentic_request_profile(): ?array
{
    if (PHP_SAPI === 'cli' || strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') return null;
    $path = sf_agentic_path();
    $base = basename($path);
    $action = (string)($_POST['action'] ?? '');

    if ($base === 'storyboard-generate.php') {
        return ['type'=>'text','feature'=>'storyboarding','target_type'=>'storyboard','target_id'=>(int)($_POST['storyboard_id'] ?? $_POST['project_id'] ?? 0),'provider_key'=>(string)($_POST['provider_key'] ?? ''),'count'=>1];
    }
    if ($base === 'story-episode-outline.php') {
        return ['type'=>'text','feature'=>'story_episode_outline','target_type'=>'story_episode','target_id'=>(int)($_POST['episode_id'] ?? 0),'provider_key'=>(string)($_POST['provider_key'] ?? ''),'count'=>1];
    }
    if ($base === 'storyboard-scene-action.php') {
        if (in_array($action, ['rewrite_scene','retry_job'], true)) {
            return ['type'=>'text','feature'=>'scene_rewrite','target_type'=>'storyboard_scene','target_id'=>(int)($_POST['scene_id'] ?? $_POST['job_id'] ?? 0),'provider_key'=>(string)($_POST['provider_key'] ?? ''),'count'=>1];
        }
        if ($action === 'regenerate_image') {
            return ['type'=>'image','feature'=>'scene_image','target_type'=>'storyboard_scene','target_id'=>(int)($_POST['scene_id'] ?? 0),'provider_key'=>(string)($_POST['provider_key'] ?? ''),'count'=>1];
        }
    }
    if ($base === 'theme-images.php') {
        if ($action === 'use_generated_current') sf_agentic_abort('approval_required', 409);
        if ($action === 'generate_image') {
            return ['type'=>'image','feature'=>'theme_image','target_type'=>'theme_image','target_id'=>(int)($_POST['image_id'] ?? 0),'provider_key'=>'','count'=>1];
        }
        if ($action === 'generate_all') {
            $themeId = (int)($_POST['theme_id'] ?? 0);
            $count = 0;
            $pdo = sf_agentic_db();
            if ($pdo && $themeId > 0 && sf_agentic_table_exists('show_theme_images')) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM show_theme_images WHERE theme_id=?');
                $stmt->execute([$themeId]);
                $count = (int)$stmt->fetchColumn();
            }
            return ['type'=>'image','feature'=>'theme_image_bulk','target_type'=>'show_theme','target_id'=>$themeId,'provider_key'=>'','count'=>max(1,$count)];
        }
    }
    return null;
}

function sf_agentic_provider_for_profile(array $profile): ?array
{
    if (!function_exists('sf_ai_providers') || !function_exists('sf_ai_provider')) return null;
    $key = trim((string)($profile['provider_key'] ?? ''));
    $targetType = (string)($profile['target_type'] ?? '');
    $targetId = (int)($profile['target_id'] ?? 0);
    $providerColumn = ($profile['type'] ?? 'text') === 'image' ? 'default_image_provider' : 'default_text_provider';
    $pdo = sf_agentic_db();
    if ($key === '' && $pdo && $targetId > 0) {
        try {
            if ($targetType === 'storyboard' && sf_agentic_table_exists('storyboards')) {
                $stmt = $pdo->prepare('SELECT `' . $providerColumn . '` FROM storyboards WHERE id=? LIMIT 1');
                $stmt->execute([$targetId]);
                $key = trim((string)$stmt->fetchColumn());
            } elseif ($targetType === 'storyboard_scene' && sf_agentic_table_exists('storyboard_scenes') && sf_agentic_table_exists('storyboards')) {
                $stmt = $pdo->prepare('SELECT b.`' . $providerColumn . '` FROM storyboard_scenes s INNER JOIN storyboards b ON b.id=s.storyboard_id WHERE s.id=? LIMIT 1');
                $stmt->execute([$targetId]);
                $key = trim((string)$stmt->fetchColumn());
            }
        } catch (Throwable $e) {
            error_log('Stonefellow AI target provider lookup failed: ' . $e->getMessage());
            $key = '';
        }
    }
    if ($key !== '') return sf_ai_provider($key);
    $defaultField = ($profile['type'] ?? 'text') === 'image' ? 'is_default_image' : 'is_default_text';
    foreach (sf_ai_providers() as $provider) if (!empty($provider[$defaultField])) return $provider;
    return null;
}

function sf_agentic_usage_snapshot(string $providerKey): array
{
    $empty = ['tokens'=>0,'images'=>0,'cost_cents'=>0,'events'=>0];
    if (!sf_agentic_table_exists('ai_usage_events')) return $empty;
    $pdo = sf_agentic_db();
    if (!$pdo) return $empty;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) events, COALESCE(SUM(prompt_tokens + completion_tokens),0) tokens, COALESCE(SUM(image_count),0) images, COALESCE(SUM(estimated_cost_cents),0) cost_cents FROM ai_usage_events WHERE provider_key=? AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01') AND request_status IN ('queued','success','failed')");
        $stmt->execute([$providerKey]);
        $row = $stmt->fetch() ?: [];
        return ['tokens'=>(int)($row['tokens'] ?? 0),'images'=>(int)($row['images'] ?? 0),'cost_cents'=>(int)($row['cost_cents'] ?? 0),'events'=>(int)($row['events'] ?? 0)];
    } catch (Throwable $e) {
        error_log('Stonefellow AI usage snapshot failed: ' . $e->getMessage());
        return $empty;
    }
}

function sf_agentic_estimated_tokens(array $profile): int
{
    $configured = (int)(getenv('SF_AI_TEXT_REQUEST_ESTIMATED_TOKENS') ?: 8000);
    $text = '';
    foreach (['prompt','story_prompt','rewrite_instruction'] as $key) $text .= ' ' . (string)($_POST[$key] ?? '');
    return max(500, min(50000, max((int)ceil(strlen($text) / 4), $configured)));
}

function sf_agentic_reserve_usage(array $provider, array $profile): int
{
    if (!sf_agentic_table_exists('ai_usage_events')) return 0;
    $pdo = sf_agentic_db();
    if (!$pdo) return 0;
    $type = ($profile['type'] ?? 'text') === 'image' ? 'image' : 'text';
    $reserve = $type === 'image'
        ? (int)(getenv('SF_AI_IMAGE_REQUEST_RESERVE_CENTS') ?: 40) * max(1, (int)($profile['count'] ?? 1))
        : (int)(getenv('SF_AI_TEXT_REQUEST_RESERVE_CENTS') ?: 25);
    $images = $type === 'image' ? max(1, (int)($profile['count'] ?? 1)) : 0;
    $feature = substr('governance_' . (string)($profile['feature'] ?? 'ai'), 0, 80);
    try {
        $stmt = $pdo->prepare("INSERT INTO ai_usage_events (provider_key, feature_key, related_type, related_id, model_key, request_type, prompt_tokens, completion_tokens, image_count, estimated_cost_cents, request_status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 'queued', ?)");
        $stmt->execute([
            (string)$provider['provider_key'], $feature, (string)($profile['target_type'] ?? 'ai_request'),
            (int)($profile['target_id'] ?? 0) ?: null,
            (string)(($type === 'image' ? $provider['image_model'] : $provider['default_model']) ?? ''),
            $type, $images, max(0,$reserve), function_exists('sf_current_user_id') ? sf_current_user_id() : null,
        ]);
        $id = (int)$pdo->lastInsertId();
        $GLOBALS['sf_agentic_reservation_id'] = $id;
        return $id;
    } catch (Throwable $e) {
        error_log('Stonefellow AI usage reservation failed: ' . $e->getMessage());
        return 0;
    }
}

function sf_agentic_finalize_reservation(string $status, array $usage = [], string $error = ''): bool
{
    $id = (int)($GLOBALS['sf_agentic_reservation_id'] ?? 0);
    if ($id <= 0 || !sf_agentic_table_exists('ai_usage_events')) return false;
    $pdo = sf_agentic_db();
    if (!$pdo) return false;
    $status = in_array($status, ['success','failed','canceled'], true) ? $status : 'failed';
    $prompt = max(0, (int)($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0));
    $completion = max(0, (int)($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0));
    try {
        $stmt = $pdo->prepare('UPDATE ai_usage_events SET prompt_tokens=?, completion_tokens=?, request_status=?, error_message=? WHERE id=?');
        $ok = $stmt->execute([$prompt,$completion,$status,$error !== '' ? substr($error,0,2000) : null,$id]);
        if ($ok) unset($GLOBALS['sf_agentic_reservation_id']);
        return $ok;
    } catch (Throwable $e) {
        error_log('Stonefellow AI usage reservation finalize failed: ' . $e->getMessage());
        return false;
    }
}

function sf_agentic_guard_live_request(array $profile): void
{
    sf_agentic_require_permission('admin.content.manage');
    $count = max(1, (int)($profile['count'] ?? 1));
    $maxBulk = max(1, (int)(getenv('SF_AI_MAX_BULK_IMAGES') ?: 8));
    if (($profile['type'] ?? '') === 'image' && $count > $maxBulk) sf_agentic_abort('bulk_generation_limit', 422);

    if (function_exists('sf_is_production') && sf_is_production()) {
        $secret = trim((string)(getenv('SF_AI_SETTINGS_SECRET') ?: ''));
        if (strlen($secret) < 32) sf_agentic_abort('ai_secret_not_configured', 503);
    }

    $provider = sf_agentic_provider_for_profile($profile);
    if (!$provider || !in_array((string)($provider['provider_key'] ?? ''), ['chatgpt','claude'], true)) sf_agentic_abort('provider_not_supported', 422);
    if (($profile['type'] ?? 'text') === 'image' && ($provider['provider_key'] ?? '') !== 'chatgpt') sf_agentic_abort('provider_not_supported', 422);
    if (($provider['status'] ?? '') !== 'active' || !in_array((string)($provider['key_status'] ?? ''), ['configured','connected'], true) || !function_exists('sf_ai_decrypt_secret') || sf_ai_decrypt_secret($provider['encrypted_api_key'] ?? '') === '') {
        sf_agentic_abort('provider_not_ready', 503);
    }

    $budget = max(0, (int)($provider['monthly_budget_cents'] ?? 0));
    $tokenLimit = max(0, (int)($provider['monthly_token_limit'] ?? 0));
    $imageLimit = max(0, (int)($provider['monthly_image_limit'] ?? 0));
    if (function_exists('sf_is_production') && sf_is_production()) {
        if ($budget <= 0 || (($profile['type'] ?? 'text') === 'image' ? $imageLimit <= 0 : $tokenLimit <= 0)) sf_agentic_abort('ai_limits_not_configured', 503);
    }

    $snapshot = sf_agentic_usage_snapshot((string)$provider['provider_key']);
    $reserve = ($profile['type'] ?? 'text') === 'image'
        ? (int)(getenv('SF_AI_IMAGE_REQUEST_RESERVE_CENTS') ?: 40) * $count
        : (int)(getenv('SF_AI_TEXT_REQUEST_RESERVE_CENTS') ?: 25);
    if ($budget > 0 && ($snapshot['cost_cents'] + max(0,$reserve)) > $budget) sf_agentic_abort('ai_budget_exceeded', 429);
    if (($profile['type'] ?? 'text') === 'image') {
        if ($imageLimit > 0 && ($snapshot['images'] + $count) > $imageLimit) sf_agentic_abort('ai_image_limit_exceeded', 429);
    } else {
        $estimate = sf_agentic_estimated_tokens($profile);
        if ($tokenLimit > 0 && ($snapshot['tokens'] + $estimate) > $tokenLimit) sf_agentic_abort('ai_token_limit_exceeded', 429);
    }

    $userId = function_exists('sf_current_user_id') ? (int)sf_current_user_id() : 0;
    $limitCount = ($profile['type'] ?? 'text') === 'image'
        ? max(1, (int)(getenv('SF_AI_IMAGE_REQUESTS_PER_15_MIN') ?: 8))
        : max(1, (int)(getenv('SF_AI_TEXT_REQUESTS_PER_15_MIN') ?: 20));
    if (function_exists('sf_security_session_rate_limit')) {
        $limit = sf_security_session_rate_limit('ai|' . $userId . '|' . $provider['provider_key'] . '|' . $profile['type'], $limitCount, 900);
        if (empty($limit['allowed'])) {
            header('Retry-After: ' . (int)($limit['retry_after'] ?? 60));
            sf_agentic_abort('ai_rate_limited', 429);
        }
    }

    $lockKey = 'provider|' . $provider['provider_key'] . '|' . $profile['type'] . '|' . ($profile['target_type'] ?? '') . '|' . (int)($profile['target_id'] ?? 0);
    if (!sf_agentic_acquire_lock($lockKey, 0)) sf_agentic_abort('ai_request_in_progress', 409);
    sf_agentic_reserve_usage($provider, $profile);
    $GLOBALS['sf_agentic_active_profile'] = $profile;
    $GLOBALS['sf_agentic_active_provider'] = $provider;
}

function sf_agentic_guard_request(): void
{
    if (PHP_SAPI === 'cli') return;
    sf_agentic_require_permission();
    if (isset($_POST['return_url'])) {
        $_POST['return_url'] = sf_agentic_safe_redirect((string)$_POST['return_url'], function_exists('sf_url') ? sf_url('admin/index.php') : '/');
    }
    foreach (['prompt','story_prompt','rewrite_instruction'] as $key) {
        if (isset($_POST[$key]) && strlen((string)$_POST[$key]) > 16000) sf_agentic_abort('prompt_too_large', 422);
    }
    $profile = sf_agentic_request_profile();
    if ($profile) sf_agentic_guard_live_request($profile);
}

function sf_agentic_snapshot_storyboard(int $storyboardId, string $reason): void
{
    if ($storyboardId <= 0 || !function_exists('sf_admin_fetch_one') || !function_exists('sf_admin_audit')) return;
    $storyboard = sf_admin_fetch_one('SELECT * FROM storyboards WHERE id=? LIMIT 1', [$storyboardId]);
    if (!$storyboard) return;
    $scenes = sf_agentic_table_exists('storyboard_scenes') ? sf_admin_fetch_all('SELECT * FROM storyboard_scenes WHERE storyboard_id=? ORDER BY scene_number,id', [$storyboardId]) : [];
    $characters = sf_agentic_table_exists('storyboard_characters') ? sf_admin_fetch_all('SELECT * FROM storyboard_characters WHERE storyboard_id=? ORDER BY character_order,id', [$storyboardId]) : [];
    $links = sf_agentic_table_exists('storyboard_scene_characters') ? sf_admin_fetch_all('SELECT * FROM storyboard_scene_characters WHERE storyboard_id=? ORDER BY scene_id,character_id', [$storyboardId]) : [];
    sf_admin_audit('ai_pre_mutation_snapshot', 'storyboard', $storyboardId, ['reason'=>$reason,'storyboard'=>$storyboard,'scenes'=>$scenes,'characters'=>$characters,'scene_characters'=>$links], null);
}

function sf_agentic_snapshot_scene(int $sceneId, string $reason): void
{
    if ($sceneId <= 0 || !function_exists('sf_admin_fetch_one') || !function_exists('sf_admin_audit')) return;
    $scene = sf_admin_fetch_one('SELECT * FROM storyboard_scenes WHERE id=? LIMIT 1', [$sceneId]);
    if (!$scene) return;
    $links = sf_agentic_table_exists('storyboard_scene_characters') ? sf_admin_fetch_all('SELECT * FROM storyboard_scene_characters WHERE scene_id=? ORDER BY character_id', [$sceneId]) : [];
    sf_admin_audit('ai_pre_mutation_snapshot', 'storyboard_scene', $sceneId, ['reason'=>$reason,'scene'=>$scene,'scene_characters'=>$links], null);
}

function sf_agentic_snapshot_episode(int $episodeId, string $reason): void
{
    if ($episodeId <= 0 || !function_exists('sf_admin_fetch_one') || !function_exists('sf_admin_audit')) return;
    $episode = sf_admin_fetch_one('SELECT * FROM story_episodes WHERE id=? LIMIT 1', [$episodeId]);
    if ($episode) sf_admin_audit('ai_pre_mutation_snapshot', 'story_episode', $episodeId, ['reason'=>$reason,'episode'=>$episode], null);
}

function sf_agentic_action_execution_allowed(array $action): array
{
    $payload = (string)($action['payload_json'] ?? '');
    if (strlen($payload) > 65535) return ['ok'=>false,'error'=>'invalid_action_payload'];
    if ($payload !== '' && !is_array(json_decode($payload, true))) return ['ok'=>false,'error'=>'invalid_action_payload'];
    $approvedAt = strtotime((string)($action['approved_at'] ?? '')) ?: 0;
    $updatedAt = strtotime((string)($action['updated_at'] ?? '')) ?: 0;
    if ($approvedAt > 0 && $updatedAt > ($approvedAt + 1)) return ['ok'=>false,'error'=>'action_changed_after_approval'];
    $risk = (string)($action['risk_level'] ?? 'low');
    $requireDistinct = function_exists('sf_is_production') && sf_is_production()
        ? !function_exists('sf_env_bool') || sf_env_bool('SF_AI_REQUIRE_DISTINCT_EXECUTOR', true)
        : (function_exists('sf_env_bool') && sf_env_bool('SF_AI_REQUIRE_DISTINCT_EXECUTOR', false));
    if ($requireDistinct && in_array($risk, ['high','critical'], true)) {
        $approvedBy = (int)($action['approved_by_user_id'] ?? 0);
        $current = function_exists('sf_current_user_id') ? (int)sf_current_user_id() : 0;
        if ($approvedBy > 0 && $approvedBy === $current) return ['ok'=>false,'error'=>'distinct_executor_required'];
    }
    return ['ok'=>true];
}

function sf_agentic_validate_image_bytes(string $bytes, int $maxBytes = 15728640): array
{
    if ($bytes === '' || strlen($bytes) > $maxBytes || strlen($bytes) < 100) return ['ok'=>false,'error'=>'invalid_image_data'];
    $info = function_exists('getimagesizefromstring') ? @getimagesizefromstring($bytes) : false;
    if (!$info || empty($info['mime']) || !in_array($info['mime'], ['image/png','image/jpeg','image/webp'], true)) return ['ok'=>false,'error'=>'invalid_image_data'];
    $width = (int)($info[0] ?? 0); $height = (int)($info[1] ?? 0);
    if ($width < 64 || $height < 64 || $width > 8192 || $height > 8192) return ['ok'=>false,'error'=>'invalid_image_dimensions'];
    return ['ok'=>true,'mime'=>$info['mime'],'width'=>$width,'height'=>$height];
}

function sf_agentic_bounded_output(array $data, array $schema): array
{
    $out = [];
    foreach ($schema as $key => $rule) {
        if (!array_key_exists($key, $data)) continue;
        if (is_int($rule)) $out[$key] = sf_agentic_text($data[$key], $rule);
        elseif ($rule === 'string_list') {
            $values = is_array($data[$key]) ? $data[$key] : [];
            $out[$key] = array_slice(array_values(array_filter(array_map(static fn($v) => sf_agentic_text($v, 160), $values))), 0, 40);
        }
    }
    return $out;
}
