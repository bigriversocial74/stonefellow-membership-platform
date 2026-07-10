<?php
require_once __DIR__ . '/admin_catalog.php';
require_once __DIR__ . '/ai_governance.php';

function sf_ai_db(): ?PDO { return sf_admin_db(); }
function sf_ai_ready(): bool { return sf_ai_db() instanceof PDO && sf_admin_table_exists('ai_provider_settings'); }
function sf_ai_h($value): string { return sf_admin_h($value); }

function sf_ai_secret_material(): string
{
    $secret = trim((string)(getenv('SF_AI_SETTINGS_SECRET') ?: ''));
    if ($secret !== '') return $secret;
    if (function_exists('sf_is_production') && sf_is_production()) return '';
    foreach (['SF_APP_KEY','SF_HASH_SALT','SF_MEDIA_SIGNING_KEY'] as $name) {
        $value = trim((string)(getenv($name) ?: ''));
        if ($value !== '') return $value;
    }
    return '';
}

function sf_ai_secret_key(): string
{
    $seed = sf_ai_secret_material();
    return $seed === '' ? '' : hash('sha256', $seed, true);
}

function sf_ai_crypto_ready(): bool
{
    if (strlen(sf_ai_secret_material()) < 32) return false;
    if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt') || !function_exists('openssl_get_cipher_methods')) return false;
    return in_array('aes-256-gcm', openssl_get_cipher_methods(), true);
}

function sf_ai_encrypt_secret(string $plain): ?string
{
    $plain = trim($plain);
    if ($plain === '' || !sf_ai_crypto_ready()) return null;
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', sf_ai_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, 'stonefellow-ai-settings-v1');
    if ($cipher === false || strlen($tag) !== 16) return null;
    return 'v2:' . base64_encode($iv . $tag . $cipher);
}

function sf_ai_decrypt_secret(?string $stored): string
{
    $stored = trim((string)$stored);
    if ($stored === '' || !sf_ai_crypto_ready()) return '';
    $version = str_starts_with($stored, 'v2:') ? 'v2' : (str_starts_with($stored, 'v1:') ? 'v1' : '');
    if ($version === '') return '';
    $raw = base64_decode(substr($stored, 3), true);
    if ($raw === false || strlen($raw) < 29) return '';
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $aad = $version === 'v2' ? 'stonefellow-ai-settings-v1' : '';
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', sf_ai_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, $aad);
    return is_string($plain) ? $plain : '';
}

function sf_ai_mask_key(?string $last4, ?string $hint = null): string
{
    $last4 = trim((string)$last4);
    $hint = trim((string)$hint);
    if ($last4 !== '') return '•••• •••• •••• ' . $last4;
    if ($hint !== '') return $hint;
    return 'Not configured';
}

function sf_ai_default_providers(): array
{
    return [
        ['provider_key'=>'chatgpt','provider_label'=>'ChatGPT / OpenAI','provider_type'=>'multimodal','default_model'=>'gpt-4.1','image_model'=>'gpt-image-1','key_status'=>'missing','is_default_text'=>1,'is_default_image'=>1,'monthly_budget_cents'=>0,'monthly_token_limit'=>0,'monthly_image_limit'=>0,'timeout_seconds'=>90,'max_retries'=>2,'temperature'=>'0.70','status'=>'inactive','api_key_last4'=>'','api_key_hint'=>''],
        ['provider_key'=>'claude','provider_label'=>'Claude / Anthropic','provider_type'=>'text','default_model'=>'claude-3-5-sonnet-latest','image_model'=>'','key_status'=>'missing','is_default_text'=>0,'is_default_image'=>0,'monthly_budget_cents'=>0,'monthly_token_limit'=>0,'monthly_image_limit'=>0,'timeout_seconds'=>90,'max_retries'=>2,'temperature'=>'0.70','status'=>'inactive','api_key_last4'=>'','api_key_hint'=>''],
    ];
}

function sf_ai_providers(): array
{
    if (!sf_ai_ready()) return sf_ai_default_providers();
    $rows = sf_admin_fetch_all('SELECT * FROM ai_provider_settings ORDER BY provider_key ASC');
    return $rows ?: sf_ai_default_providers();
}

function sf_ai_provider(string $providerKey): ?array
{
    foreach (sf_ai_providers() as $provider) if (($provider['provider_key'] ?? '') === $providerKey) return $provider;
    return null;
}

function sf_ai_provider_options(): array
{
    $options = [];
    foreach (sf_ai_providers() as $provider) $options[(string)$provider['provider_key']] = (string)$provider['provider_label'];
    return $options ?: ['chatgpt'=>'ChatGPT / OpenAI','claude'=>'Claude / Anthropic'];
}

function sf_ai_provider_runtime_ready(array $provider, string $requestType = 'text'): bool
{
    if (!in_array((string)($provider['provider_key'] ?? ''), ['chatgpt','claude'], true)) return false;
    if ($requestType === 'image' && ($provider['provider_key'] ?? '') !== 'chatgpt') return false;
    return ($provider['status'] ?? '') === 'active'
        && in_array((string)($provider['key_status'] ?? ''), ['configured','connected'], true)
        && sf_ai_decrypt_secret($provider['encrypted_api_key'] ?? '') !== '';
}

function sf_ai_save_provider(array $payload): bool
{
    if (!sf_ai_ready()) return false;
    $providerKey = strtolower(trim((string)($payload['provider_key'] ?? '')));
    if (!in_array($providerKey, ['chatgpt','claude'], true)) {
        sf_admin_flash('error', 'Only the configured OpenAI and Anthropic adapters can be saved.');
        return false;
    }
    $existing = sf_ai_provider($providerKey);
    $plainKey = trim((string)($payload['api_key'] ?? ''));
    $encrypted = null;
    $last4 = null;
    $keyStatus = (string)($payload['key_status'] ?? ($existing['key_status'] ?? 'missing'));
    if (!in_array($keyStatus, ['missing','configured','needs_test','connected','failed','disabled'], true)) $keyStatus = 'missing';

    if ($plainKey !== '') {
        if (strlen($plainKey) < 16 || strlen($plainKey) > 512) {
            sf_admin_flash('error', 'The provider key length is invalid.');
            return false;
        }
        $encrypted = sf_ai_encrypt_secret($plainKey);
        if ($encrypted === null) {
            sf_admin_flash('error', 'Set SF_AI_SETTINGS_SECRET to a separate 32+ character random value before saving provider secrets.');
            return false;
        }
        $last4 = substr($plainKey, -4);
        $keyStatus = 'configured';
    }

    $type = (string)($payload['provider_type'] ?? ($providerKey === 'chatgpt' ? 'multimodal' : 'text'));
    if (!in_array($type, ['text','image','multimodal'], true)) $type = 'text';
    if ($providerKey === 'claude') $type = 'text';
    $status = (string)($payload['status'] ?? 'inactive');
    if (!in_array($status, ['active','inactive','disabled'], true)) $status = 'inactive';
    $defaultModel = sf_agentic_model_name($payload['default_model'] ?? '');
    $imageModel = $providerKey === 'chatgpt' ? sf_agentic_model_name($payload['image_model'] ?? '') : '';
    if ($defaultModel === '') {
        sf_admin_flash('error', 'Enter a valid provider model identifier.');
        return false;
    }
    if ($status === 'active' && $plainKey === '' && (!$existing || sf_ai_decrypt_secret($existing['encrypted_api_key'] ?? '') === '')) {
        sf_admin_flash('error', 'A securely encrypted provider key is required before activation.');
        return false;
    }

    $temperature = max(0, min(2, (float)($payload['temperature'] ?? 0.7)));
    $fields = [
        'provider_label'=>sf_agentic_text($payload['provider_label'] ?? '', 120, $providerKey === 'chatgpt' ? 'ChatGPT / OpenAI' : 'Claude / Anthropic'),
        'provider_type'=>$type,
        'default_model'=>$defaultModel,
        'image_model'=>$imageModel,
        'key_status'=>$keyStatus,
        'is_default_text'=>!empty($payload['is_default_text']) ? 1 : 0,
        'is_default_image'=>$providerKey === 'chatgpt' && !empty($payload['is_default_image']) ? 1 : 0,
        'monthly_budget_cents'=>max(0, min(100000000, (int)($payload['monthly_budget_cents'] ?? 0))),
        'monthly_token_limit'=>max(0, min(2000000000, (int)($payload['monthly_token_limit'] ?? 0))),
        'monthly_image_limit'=>max(0, min(1000000, (int)($payload['monthly_image_limit'] ?? 0))),
        'timeout_seconds'=>max(10, min(300, (int)($payload['timeout_seconds'] ?? 90))),
        'max_retries'=>max(0, min(5, (int)($payload['max_retries'] ?? 2))),
        'temperature'=>number_format($temperature, 2, '.', ''),
        'status'=>$status,
        'updated_by_user_id'=>sf_current_user_id(),
    ];
    if ($plainKey !== '') {
        $fields['encrypted_api_key'] = $encrypted;
        $fields['api_key_last4'] = $last4;
        $fields['api_key_hint'] = sf_ai_mask_key($last4);
    } elseif (!$existing) {
        $fields['encrypted_api_key'] = null;
        $fields['api_key_last4'] = null;
        $fields['api_key_hint'] = null;
    }

    $columns = array_merge(['provider_key'], array_keys($fields), ['created_by_user_id']);
    $values = array_merge([$providerKey], array_values($fields), [sf_current_user_id()]);
    $assignments = [];
    foreach (array_keys($fields) as $field) $assignments[] = '`' . $field . '`=VALUES(`' . $field . '`)';
    $sql = 'INSERT INTO ai_provider_settings (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $assignments);
    $ok = sf_admin_execute($sql, $values);
    if ($ok) {
        if (!empty($fields['is_default_text'])) sf_admin_execute('UPDATE ai_provider_settings SET is_default_text = IF(provider_key = ?, 1, 0)', [$providerKey]);
        if (!empty($fields['is_default_image'])) sf_admin_execute('UPDATE ai_provider_settings SET is_default_image = IF(provider_key = ?, 1, 0)', [$providerKey]);
        sf_admin_audit('update_ai_provider_settings', 'ai_provider_settings', null, null, [
            'provider_key'=>$providerKey,
            'status'=>$fields['status'],
            'key_status'=>$fields['key_status'],
            'monthly_budget_cents'=>$fields['monthly_budget_cents'],
            'monthly_token_limit'=>$fields['monthly_token_limit'],
            'monthly_image_limit'=>$fields['monthly_image_limit'],
        ]);
    }
    return $ok;
}

function sf_ai_usage_summary(): array
{
    if (!sf_ai_ready() || !sf_admin_table_exists('ai_usage_events')) return ['events'=>0,'tokens'=>0,'images'=>0,'cost_cents'=>0];
    $row = sf_admin_fetch_one("SELECT COUNT(*) AS events, COALESCE(SUM(prompt_tokens + completion_tokens),0) AS tokens, COALESCE(SUM(image_count),0) AS images, COALESCE(SUM(estimated_cost_cents),0) AS cost_cents FROM ai_usage_events WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    return ['events'=>(int)($row['events'] ?? 0),'tokens'=>(int)($row['tokens'] ?? 0),'images'=>(int)($row['images'] ?? 0),'cost_cents'=>(int)($row['cost_cents'] ?? 0)];
}

function sf_ai_format_cents($cents): string { return '$' . number_format(((int)$cents) / 100, 2); }
function sf_ai_status_badge(string $status): string { return sf_admin_status_badge($status ?: 'missing'); }

sf_agentic_guard_request();
?>
