<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/revenue_access_governance.php';

if (defined('SF_COMMERCE_PROVIDER_LOADED')) return;
define('SF_COMMERCE_PROVIDER_LOADED', true);

function sf_commerce_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];
    $pdo = sf_db();
    if (!$pdo || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) return $cache[$table] = false;
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return $cache[$table] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Stonefellow commerce table check failed: ' . $e->getMessage());
        return $cache[$table] = false;
    }
}

function sf_commerce_provider_registry(): array
{
    return [
        'stripe' => [
            'key' => 'stripe',
            'label' => 'Stripe',
            'implemented' => true,
            'onboarding' => 'stripe_hosted_connect',
            'checkout' => 'stripe_checkout_destination_charge',
        ],
        'paypal' => [
            'key' => 'paypal',
            'label' => 'PayPal',
            'implemented' => false,
            'onboarding' => null,
            'checkout' => null,
        ],
        'square' => [
            'key' => 'square',
            'label' => 'Square',
            'implemented' => false,
            'onboarding' => null,
            'checkout' => null,
        ],
    ];
}

function sf_commerce_provider_is_implemented(string $provider): bool
{
    $registry = sf_commerce_provider_registry();
    return !empty($registry[$provider]['implemented']);
}

function sf_commerce_mode(): string
{
    $mode = strtolower(trim((string)(getenv('SF_PAYMENT_MODE') ?: sf_get_setting('payment_mode', 'test'))));
    return $mode === 'live' ? 'live' : 'test';
}

function sf_commerce_absolute_url(string $path): string
{
    $url = function_exists('sf_url') ? sf_url($path) : $path;
    if (preg_match('~^https?://~i', $url)) return $url;
    $https = function_exists('sf_is_https') ? sf_is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = sf_revenue_request_host() ?: 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . '/' . ltrim($url, '/');
}

function sf_commerce_secret_ready(): bool
{
    $secret = trim((string)(getenv('SF_STRIPE_SECRET_KEY') ?: ''));
    $webhook = trim((string)(getenv('SF_STRIPE_WEBHOOK_SECRET') ?: ''));
    if ($secret === '' || $webhook === '') return false;
    if (sf_commerce_mode() === 'live') return str_starts_with($secret, 'sk_live_') && strlen($webhook) >= 24;
    return str_starts_with($secret, 'sk_test_') && strlen($webhook) >= 24;
}

function sf_commerce_default_merchant(): ?array
{
    if (!sf_commerce_table_exists('commerce_merchants')) return null;
    try {
        $stmt = sf_db()->query("SELECT * FROM commerce_merchants WHERE status='active' ORDER BY id ASC LIMIT 1");
        $row = $stmt ? $stmt->fetch() : false;
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('Stonefellow merchant lookup failed: ' . $e->getMessage());
        return null;
    }
}

function sf_commerce_merchant_by_id(int $merchantId): ?array
{
    if ($merchantId <= 0 || !sf_commerce_table_exists('commerce_merchants')) return null;
    try {
        $stmt = sf_db()->prepare('SELECT * FROM commerce_merchants WHERE id=? LIMIT 1');
        $stmt->execute([$merchantId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_commerce_payment_account(int $merchantId, string $provider = 'stripe', ?string $mode = null): ?array
{
    if ($merchantId <= 0 || !sf_commerce_table_exists('merchant_payment_accounts')) return null;
    $mode = $mode ?: sf_commerce_mode();
    try {
        $stmt = sf_db()->prepare('SELECT * FROM merchant_payment_accounts WHERE merchant_id=? AND provider=? AND mode=? AND status=\'active\' ORDER BY is_default DESC,id DESC LIMIT 1');
        $stmt->execute([$merchantId, $provider, $mode]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('Stonefellow payment account lookup failed: ' . $e->getMessage());
        return null;
    }
}

function sf_commerce_payment_account_by_id(int $accountId): ?array
{
    if ($accountId <= 0 || !sf_commerce_table_exists('merchant_payment_accounts')) return null;
    try {
        $stmt = sf_db()->prepare('SELECT * FROM merchant_payment_accounts WHERE id=? LIMIT 1');
        $stmt->execute([$accountId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_commerce_payment_account_ready(?array $account): bool
{
    return is_array($account)
        && ($account['provider'] ?? '') === 'stripe'
        && ($account['status'] ?? '') === 'active'
        && ($account['onboarding_status'] ?? '') === 'complete'
        && !empty($account['charges_enabled'])
        && !empty($account['payouts_enabled'])
        && preg_match('/^acct_[A-Za-z0-9]+$/', (string)($account['provider_account_id'] ?? '')) === 1;
}

function sf_commerce_checkout_ready(): bool
{
    $merchant = sf_commerce_default_merchant();
    if (!$merchant || !sf_commerce_secret_ready()) return false;
    return sf_commerce_payment_account_ready(sf_commerce_payment_account((int)$merchant['id'], 'stripe'));
}

function sf_stripe_api_request(string $method, string $path, array $fields = [], string $idempotencyKey = ''): array
{
    $method = strtoupper($method);
    if (!in_array($method, ['GET', 'POST'], true)) return ['ok' => false, 'error' => 'stripe_method_not_allowed'];
    if (!preg_match('~^/(accounts(?:/[A-Za-z0-9_]+)?|account_links|checkout/sessions|refunds)$~', $path)) {
        return ['ok' => false, 'error' => 'stripe_endpoint_not_allowed'];
    }
    $secret = trim((string)(getenv('SF_STRIPE_SECRET_KEY') ?: ''));
    if ($secret === '' || !function_exists('curl_init')) return ['ok' => false, 'error' => 'stripe_runtime_unavailable'];
    $query = http_build_query($fields);
    if (strlen($query) > 262144) return ['ok' => false, 'error' => 'stripe_payload_too_large'];
    $url = 'https://api.stripe.com/v1' . $path;
    if ($method === 'GET' && $query !== '') $url .= '?' . $query;
    $headers = ['Authorization: Bearer ' . $secret, 'Content-Type: application/x-www-form-urlencoded'];
    if ($idempotencyKey !== '') $headers[] = 'Idempotency-Key: ' . substr($idempotencyKey, 0, 190);
    $raw = '';
    $overflow = false;
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'Stonefellow-Commerce/1.0',
        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$raw, &$overflow): int {
            if (strlen($raw) + strlen($chunk) > 1048576) {
                $overflow = true;
                return 0;
            }
            $raw .= $chunk;
            return strlen($chunk);
        },
    ];
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $query;
    }
    curl_setopt_array($ch, $options);
    $ran = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($overflow) return ['ok' => false, 'status' => $status, 'error' => 'stripe_response_too_large'];
    $json = json_decode($raw, true, 64);
    $ok = $ran !== false && $status >= 200 && $status < 300 && is_array($json);
    $message = (string)($json['error']['message'] ?? '');
    return [
        'ok' => $ok,
        'status' => $status,
        'error' => $ok ? '' : ($message ?: ($error ?: 'stripe_http_' . $status)),
        'body' => is_array($json) ? $json : [],
    ];
}

function sf_commerce_create_stripe_account(array $merchant): array
{
    if (!sf_commerce_secret_ready()) return ['ok' => false, 'error' => 'Stripe platform credentials are not configured.'];
    $merchantId = (int)($merchant['id'] ?? 0);
    if ($merchantId <= 0) return ['ok' => false, 'error' => 'Merchant is invalid.'];
    $existing = sf_commerce_payment_account($merchantId, 'stripe');
    if ($existing) return ['ok' => true, 'account' => $existing, 'existing' => true];
    $email = trim((string)($merchant['support_email'] ?? ''));
    $fields = [
        'controller[fees][payer]' => 'application',
        'controller[losses][payments]' => 'application',
        'controller[stripe_dashboard][type]' => 'express',
        'capabilities[card_payments][requested]' => 'true',
        'capabilities[transfers][requested]' => 'true',
        'business_profile[name]' => substr((string)($merchant['display_name'] ?? 'Stonefellow'), 0, 190),
        'business_profile[product_description]' => 'Music, video memberships, merchandise, and entertainment products.',
        'metadata[stonefellow_merchant_id]' => (string)$merchantId,
        'metadata[stonefellow_merchant_key]' => substr((string)($merchant['merchant_key'] ?? ''), 0, 80),
    ];
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) $fields['email'] = $email;
    $result = sf_stripe_api_request('POST', '/accounts', $fields, 'stonefellow-connect-account-' . $merchantId . '-' . sf_commerce_mode());
    if (empty($result['ok'])) return ['ok' => false, 'error' => $result['error'] ?: 'Stripe account creation failed.'];
    $stripe = $result['body'];
    $accountId = (string)($stripe['id'] ?? '');
    if (!preg_match('/^acct_[A-Za-z0-9]+$/', $accountId)) return ['ok' => false, 'error' => 'Stripe returned an invalid connected account.'];
    try {
        $stmt = sf_db()->prepare("INSERT INTO merchant_payment_accounts (merchant_id,provider,mode,provider_account_id,account_type,onboarding_status,charges_enabled,payouts_enabled,details_submitted,requirements_json,future_requirements_json,is_default,status,last_synced_at) VALUES (?,'stripe',?,?,?,'pending',?,?,?,?,?,1,'active',NOW())");
        $stmt->execute([
            $merchantId,
            sf_commerce_mode(),
            $accountId,
            'express',
            !empty($stripe['charges_enabled']) ? 1 : 0,
            !empty($stripe['payouts_enabled']) ? 1 : 0,
            !empty($stripe['details_submitted']) ? 1 : 0,
            json_encode($stripe['requirements'] ?? [], JSON_UNESCAPED_SLASHES),
            json_encode($stripe['future_requirements'] ?? [], JSON_UNESCAPED_SLASHES),
        ]);
        $account = sf_commerce_payment_account_by_id((int)sf_db()->lastInsertId());
        return ['ok' => true, 'account' => $account, 'raw' => sf_revenue_redact_payload($stripe)];
    } catch (Throwable $e) {
        error_log('Stonefellow Stripe account persistence failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Connected account could not be saved.'];
    }
}

function sf_commerce_account_status_from_stripe(array $stripe): string
{
    if (!empty($stripe['charges_enabled']) && !empty($stripe['payouts_enabled']) && !empty($stripe['details_submitted'])) return 'complete';
    $disabled = (string)($stripe['requirements']['disabled_reason'] ?? '');
    $currentlyDue = $stripe['requirements']['currently_due'] ?? [];
    if ($disabled !== '' || !empty($currentlyDue)) return 'restricted';
    return !empty($stripe['details_submitted']) ? 'pending' : 'not_started';
}

function sf_commerce_sync_stripe_account(array $account, ?array $stripeObject = null): array
{
    $accountId = (string)($account['provider_account_id'] ?? '');
    if (!preg_match('/^acct_[A-Za-z0-9]+$/', $accountId)) return ['ok' => false, 'error' => 'Connected account ID is invalid.'];
    if ($stripeObject === null) {
        $result = sf_stripe_api_request('GET', '/accounts/' . rawurlencode($accountId));
        if (empty($result['ok'])) return ['ok' => false, 'error' => $result['error'] ?: 'Stripe account sync failed.'];
        $stripeObject = $result['body'];
    }
    if ((string)($stripeObject['id'] ?? '') !== $accountId) return ['ok' => false, 'error' => 'Stripe account response mismatch.'];
    $status = sf_commerce_account_status_from_stripe($stripeObject);
    try {
        $stmt = sf_db()->prepare('UPDATE merchant_payment_accounts SET onboarding_status=?,charges_enabled=?,payouts_enabled=?,details_submitted=?,requirements_json=?,future_requirements_json=?,last_synced_at=NOW(),updated_at=NOW() WHERE id=?');
        $stmt->execute([
            $status,
            !empty($stripeObject['charges_enabled']) ? 1 : 0,
            !empty($stripeObject['payouts_enabled']) ? 1 : 0,
            !empty($stripeObject['details_submitted']) ? 1 : 0,
            json_encode($stripeObject['requirements'] ?? [], JSON_UNESCAPED_SLASHES),
            json_encode($stripeObject['future_requirements'] ?? [], JSON_UNESCAPED_SLASHES),
            (int)$account['id'],
        ]);
        return ['ok' => true, 'account' => sf_commerce_payment_account_by_id((int)$account['id']), 'status' => $status];
    } catch (Throwable $e) {
        error_log('Stonefellow Stripe account sync persistence failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Stripe account status could not be saved.'];
    }
}

function sf_commerce_create_onboarding_link(array $account): array
{
    if (!sf_commerce_table_exists('merchant_payment_onboarding_sessions')) return ['ok' => false, 'error' => 'Commerce migration 022 is required.'];
    $accountId = (int)($account['id'] ?? 0);
    $stripeId = (string)($account['provider_account_id'] ?? '');
    if ($accountId <= 0 || !preg_match('/^acct_[A-Za-z0-9]+$/', $stripeId)) return ['ok' => false, 'error' => 'Payment account is invalid.'];
    $token = bin2hex(random_bytes(32));
    $refreshPath = 'admin/payment-gateways.php?stripe_refresh=1&state=' . urlencode($token);
    $returnPath = 'admin/payment-gateways.php?stripe_return=1&state=' . urlencode($token);
    $refreshUrl = sf_commerce_absolute_url($refreshPath);
    $returnUrl = sf_commerce_absolute_url($returnPath);
    if (sf_commerce_mode() === 'live' && (!str_starts_with($refreshUrl, 'https://') || !str_starts_with($returnUrl, 'https://'))) {
        return ['ok' => false, 'error' => 'Live Stripe onboarding requires HTTPS return URLs.'];
    }
    $result = sf_stripe_api_request('POST', '/account_links', [
        'account' => $stripeId,
        'refresh_url' => $refreshUrl,
        'return_url' => $returnUrl,
        'type' => 'account_onboarding',
        'collection_options[fields]' => 'eventually_due',
        'collection_options[future_requirements]' => 'include',
    ]);
    if (empty($result['ok'])) return ['ok' => false, 'error' => $result['error'] ?: 'Stripe onboarding link creation failed.'];
    $url = (string)($result['body']['url'] ?? '');
    $expires = (int)($result['body']['expires_at'] ?? (time() + 300));
    if (!preg_match('~^https://connect\.stripe\.com/~', $url)) return ['ok' => false, 'error' => 'Stripe returned an invalid onboarding URL.'];
    try {
        $stmt = sf_db()->prepare("INSERT INTO merchant_payment_onboarding_sessions (payment_account_id,session_token,status,return_path,refresh_path,expires_at,metadata_json) VALUES (?,?,'redirected',?,?,FROM_UNIXTIME(?),?)");
        $stmt->execute([$accountId, $token, $returnPath, $refreshPath, $expires, json_encode(['stripe_expires_at' => $expires], JSON_UNESCAPED_SLASHES)]);
    } catch (Throwable $e) {
        error_log('Stonefellow onboarding session persistence failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Onboarding session could not be saved.'];
    }
    return ['ok' => true, 'url' => $url, 'state' => $token, 'expires_at' => $expires];
}

function sf_commerce_onboarding_session(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token) || !sf_commerce_table_exists('merchant_payment_onboarding_sessions')) return null;
    try {
        $stmt = sf_db()->prepare('SELECT s.*,a.merchant_id,a.provider,a.provider_account_id,a.status AS account_status FROM merchant_payment_onboarding_sessions s INNER JOIN merchant_payment_accounts a ON a.id=s.payment_account_id WHERE s.session_token=? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_commerce_mark_onboarding_returned(int $sessionId): void
{
    if ($sessionId <= 0) return;
    try {
        sf_db()?->prepare("UPDATE merchant_payment_onboarding_sessions SET status='returned',returned_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$sessionId]);
    } catch (Throwable $e) {
        error_log('Stonefellow onboarding return update failed: ' . $e->getMessage());
    }
}

function sf_commerce_platform_fee_cents(array $merchant, int $amountCents): int
{
    $bps = max(0, min(10000, (int)($merchant['platform_fee_bps'] ?? 0)));
    $override = getenv('SF_STRIPE_PLATFORM_FEE_BPS');
    if ($override !== false && $override !== '') $bps = max(0, min(10000, (int)$override));
    return (int)floor($amountCents * $bps / 10000);
}

function sf_commerce_provider_summary(): array
{
    $merchant = sf_commerce_default_merchant();
    $account = $merchant ? sf_commerce_payment_account((int)$merchant['id'], 'stripe') : null;
    return [
        'merchant' => $merchant,
        'account' => $account,
        'mode' => sf_commerce_mode(),
        'platform_credentials_ready' => sf_commerce_secret_ready(),
        'checkout_ready' => sf_commerce_payment_account_ready($account) && sf_commerce_secret_ready(),
        'providers' => sf_commerce_provider_registry(),
    ];
}
