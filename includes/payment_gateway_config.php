<?php

declare(strict_types=1);

function sf_payment_provider(): string
{
    $provider = strtolower((string)(sf_get_setting('payment_provider', getenv('SF_PAYMENT_PROVIDER') ?: 'sandbox') ?: 'sandbox'));
    return in_array($provider, ['sandbox','stripe','paypal'], true) ? $provider : 'sandbox';
}

function sf_payment_mode(): string
{
    $mode = strtolower((string)(getenv('SF_PAYMENT_MODE') ?: sf_get_setting('payment_mode', 'sandbox') ?: 'sandbox'));
    return in_array($mode, ['sandbox','test','live'], true) ? $mode : 'sandbox';
}

function sf_payment_provider_label(?string $provider = null): string
{
    $provider = $provider ?: sf_payment_provider();
    return ['sandbox' => 'Sandbox', 'stripe' => 'Stripe', 'paypal' => 'PayPal'][$provider] ?? ucfirst($provider);
}

function sf_payment_secret_mask(?string $value): string
{
    $value = (string)$value;
    return $value === '' ? 'missing' : substr($value, 0, 4) . '…' . substr($value, -4);
}

function sf_payment_gateway_ready(?string $provider = null): bool
{
    $provider = $provider ?: sf_payment_provider();
    if ($provider === 'sandbox') return sf_revenue_sandbox_allowed('subscription');
    if ($provider === 'stripe') {
        if (!sf_commerce_secret_ready()) return false;
        $merchant = sf_commerce_default_merchant();
        return $merchant && sf_commerce_payment_account_ready(sf_commerce_payment_account((int)$merchant['id'], 'stripe'));
    }
    return false;
}

function sf_payment_absolute_url(string $path): string
{
    return sf_commerce_absolute_url($path);
}

function sf_payment_gateway_status(): array
{
    $provider = sf_payment_provider();
    $merchant = sf_commerce_default_merchant();
    $account = $merchant ? sf_commerce_payment_account((int)$merchant['id'], 'stripe') : null;
    return [
        'provider' => $provider,
        'label' => sf_payment_provider_label($provider),
        'ready' => sf_payment_gateway_ready($provider),
        'mode' => sf_payment_mode(),
        'stripe_public' => sf_get_setting('stripe_publishable_key', getenv('SF_STRIPE_PUBLISHABLE_KEY') ?: ''),
        'paypal_client_id' => sf_get_setting('paypal_client_id', getenv('SF_PAYPAL_CLIENT_ID') ?: ''),
        'stripe_secret' => sf_payment_secret_mask(getenv('SF_STRIPE_SECRET_KEY') ?: ''),
        'webhook_secret' => sf_payment_secret_mask(getenv('SF_STRIPE_WEBHOOK_SECRET') ?: ''),
        'merchant' => $merchant,
        'payment_account' => $account,
    ];
}

function sf_payment_http_post(string $url, array $fields, array $headers = []): array
{
    $allowed = ['https://api.stripe.com/v1/checkout/sessions'];
    if (!in_array($url, $allowed, true)) return ['ok' => false, 'status' => 0, 'error' => 'payment_endpoint_not_allowed', 'body' => []];
    if (!function_exists('curl_init')) return ['ok' => false, 'status' => 0, 'error' => 'curl_missing', 'body' => []];
    $body = http_build_query($fields);
    if (strlen($body) > 262144) return ['ok' => false, 'status' => 0, 'error' => 'payment_payload_too_large', 'body' => []];
    $raw = '';
    $overflow = false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Stonefellow-Payments/1.0',
        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$raw, &$overflow): int {
            if (strlen($raw) + strlen($chunk) > 1048576) {
                $overflow = true;
                return 0;
            }
            $raw .= $chunk;
            return strlen($chunk);
        },
    ]);
    $ran = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($overflow) return ['ok' => false, 'status' => $code, 'error' => 'payment_response_too_large', 'body' => []];
    $json = json_decode($raw, true, 64);
    $ok = $ran !== false && $code >= 200 && $code < 300 && is_array($json);
    return [
        'ok' => $ok,
        'status' => $code,
        'error' => $error ?: ((string)($json['error']['message'] ?? '') ?: ($code >= 400 ? 'http_' . $code : (!is_array($json) ? 'invalid_payment_json' : ''))),
        'body' => is_array($json) ? $json : [],
    ];
}

function sf_payment_create_stripe_checkout(array $payload): array
{
    $secret = (string)(getenv('SF_STRIPE_SECRET_KEY') ?: '');
    if ($secret === '') return ['ok' => false, 'error' => 'Missing SF_STRIPE_SECRET_KEY'];
    $token = (string)($payload['checkout_token'] ?? '');
    $amount = max(0, (int)($payload['amount_cents'] ?? 0));
    $currency = strtolower(sf_revenue_normalize_currency($payload['currency'] ?? 'USD'));
    if (!preg_match('/^[a-f0-9]{48}$/i', $token) || $amount <= 0 || $currency === '') return ['ok' => false, 'error' => 'Invalid checkout payload'];
    $merchant = sf_commerce_default_merchant();
    $account = $merchant ? sf_commerce_payment_account((int)$merchant['id'], 'stripe') : null;
    if (!$merchant || !sf_commerce_payment_account_ready($account)) return ['ok' => false, 'error' => 'Stripe Connect merchant onboarding is incomplete.'];
    $fields = [
        'mode' => 'subscription',
        'success_url' => sf_payment_absolute_url('billing-success.php?token=' . urlencode($token) . '&provider=stripe'),
        'cancel_url' => sf_payment_absolute_url('billing-cancel.php?token=' . urlencode($token)),
        'client_reference_id' => $token,
        'line_items[0][quantity]' => 1,
        'line_items[0][price_data][currency]' => $currency,
        'line_items[0][price_data][unit_amount]' => $amount,
        'line_items[0][price_data][recurring][interval]' => ($payload['billing_interval'] ?? 'month') === 'year' ? 'year' : 'month',
        'line_items[0][price_data][product_data][name]' => substr((string)($payload['plan_name'] ?? 'Stonefellow Membership'), 0, 190),
        'metadata[checkout_kind]' => 'subscription',
        'metadata[checkout_token]' => $token,
        'metadata[merchant_id]' => (int)$merchant['id'],
        'metadata[payment_account_id]' => (int)$account['id'],
        'subscription_data[metadata][checkout_kind]' => 'subscription',
        'subscription_data[metadata][checkout_token]' => $token,
        'subscription_data[metadata][merchant_id]' => (int)$merchant['id'],
        'subscription_data[metadata][payment_account_id]' => (int)$account['id'],
        'subscription_data[transfer_data][destination]' => (string)$account['provider_account_id'],
    ];
    $bps = max(0, min(10000, (int)($merchant['platform_fee_bps'] ?? 0)));
    $override = getenv('SF_STRIPE_PLATFORM_FEE_BPS');
    if ($override !== false && $override !== '') $bps = max(0, min(10000, (int)$override));
    if ($bps > 0) $fields['subscription_data[application_fee_percent]'] = number_format($bps / 100, 2, '.', '');
    if (!empty($payload['customer_email']) && filter_var($payload['customer_email'], FILTER_VALIDATE_EMAIL)) $fields['customer_email'] = (string)$payload['customer_email'];
    $response = sf_payment_http_post('https://api.stripe.com/v1/checkout/sessions', $fields, [
        'Authorization: Bearer ' . $secret,
        'Content-Type: application/x-www-form-urlencoded',
        'Idempotency-Key: stonefellow-sub-' . $token,
    ]);
    if (!$response['ok']) return ['ok' => false, 'error' => $response['error'] ?: 'Stripe checkout creation failed', 'provider' => 'stripe', 'mode' => sf_payment_mode()];
    $session = $response['body'];
    $id = (string)($session['id'] ?? '');
    $url = (string)($session['url'] ?? '');
    if ($id === '' || !preg_match('~^https://checkout\.stripe\.com/~', $url)) return ['ok' => false, 'error' => 'Stripe returned an invalid checkout session.'];
    return ['ok' => true, 'provider' => 'stripe', 'provider_checkout_id' => $id, 'checkout_url' => $url, 'mode' => sf_payment_mode(), 'raw' => sf_revenue_redact_payload($session)];
}

function sf_payment_create_checkout(array $payload): array
{
    $provider = sf_payment_provider();
    $token = (string)($payload['checkout_token'] ?? bin2hex(random_bytes(24)));
    if ($provider === 'stripe') {
        if (!sf_payment_gateway_ready('stripe')) return ['ok' => false, 'provider' => 'stripe', 'error' => 'Stripe credentials, webhook secret, or connected merchant onboarding are incomplete.'];
        return sf_payment_create_stripe_checkout($payload);
    }
    if ($provider === 'paypal') return ['ok' => false, 'provider' => 'paypal', 'error' => 'PayPal checkout is not enabled until signed webhook verification is implemented.'];
    if (!sf_revenue_sandbox_allowed('subscription')) return ['ok' => false, 'provider' => 'sandbox', 'error' => 'Sandbox subscription checkout is disabled in production.'];
    return ['ok' => true, 'provider' => 'sandbox', 'provider_checkout_id' => 'sandbox_checkout_' . substr(hash('sha256', $token), 0, 18), 'checkout_url' => (string)($payload['local_checkout_url'] ?? ''), 'mode' => 'sandbox'];
}
