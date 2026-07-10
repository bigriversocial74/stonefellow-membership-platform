<?php

declare(strict_types=1);

function sf_payment_extract_header(array $headers, string $name): string
{
    $lookup = strtolower(str_replace('_', '-', $name));
    foreach ($headers as $key => $value) {
        $normalized = strtolower(str_replace('_', '-', preg_replace('/^HTTP_/', '', (string)$key)));
        if ($normalized === $lookup) return is_array($value) ? (string)reset($value) : (string)$value;
    }
    return '';
}

function sf_payment_verify_stripe_signature(string $raw, string $header, string $secret, int $tolerance = 300): bool
{
    if ($secret === '' || $header === '') return false;
    $parts = [];
    foreach (explode(',', $header) as $piece) {
        [$key, $value] = array_pad(explode('=', trim($piece), 2), 2, '');
        $parts[$key][] = $value;
    }
    $time = (int)($parts['t'][0] ?? 0);
    if (!$time || abs(time() - $time) > $tolerance) return false;
    $expected = hash_hmac('sha256', $time . '.' . $raw, $secret);
    foreach ($parts['v1'] ?? [] as $signature) if (preg_match('/^[a-f0-9]{64}$/i', $signature) && hash_equals($expected, $signature)) return true;
    return false;
}

function sf_payment_verify_webhook(string $provider, string $raw, array $headers = []): bool
{
    if ($provider === 'sandbox') return !sf_revenue_is_production() && sf_revenue_env_bool('SF_ALLOW_UNSIGNED_SANDBOX_WEBHOOKS', false);
    if ($provider === 'stripe') return sf_payment_verify_stripe_signature($raw, sf_payment_extract_header($headers, 'STRIPE_SIGNATURE'), getenv('SF_STRIPE_WEBHOOK_SECRET') ?: '');
    return false;
}

function sf_payment_record_gateway_event(string $provider, string $type, array $payload, string $status = 'received', ?string $error = null): void
{
    $pdo = sf_db();
    if (!$pdo) return;
    try {
        $id = (string)($payload['id'] ?? $payload['event_id'] ?? hash('sha256', $provider . $type . json_encode($payload)));
        if (sf_settings_table_exists('payment_gateway_webhook_events')) {
            $stmt = $pdo->prepare('INSERT INTO payment_gateway_webhook_events (provider,provider_event_id,event_type,status,payload_json,error_message,processed_at) VALUES (?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status),payload_json=VALUES(payload_json),error_message=VALUES(error_message),processed_at=NOW()');
            $stmt->execute([$provider, substr($id, 0, 190), substr($type, 0, 120), $status, json_encode(sf_revenue_redact_payload($payload), JSON_UNESCAPED_SLASHES), $error ? substr($error, 0, 2000) : null]);
        }
    } catch (Throwable $e) {
        error_log('Stonefellow gateway event log failed: ' . $e->getMessage());
    }
}

function sf_payment_event_object(array $payload): array
{
    $object = $payload['data']['object'] ?? $payload['resource'] ?? $payload;
    return is_array($object) ? $object : [];
}

function sf_payment_checkout_token_from_event(array $payload): string
{
    $object = sf_payment_event_object($payload);
    return (string)($object['client_reference_id'] ?? $object['metadata']['checkout_token'] ?? $object['subscription_details']['metadata']['checkout_token'] ?? '');
}
