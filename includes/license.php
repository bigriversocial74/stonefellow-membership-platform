<?php

declare(strict_types=1);

if (defined('SF_LICENSE_RUNTIME_LOADED')) {
    return;
}
define('SF_LICENSE_RUNTIME_LOADED', true);

function sf_license_root(): string
{
    return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

function sf_license_product_config_path(): string
{
    $override = trim((string)(getenv('SF_LICENSE_PRODUCT_CONFIG') ?: ''));
    return $override !== '' ? $override : sf_license_root() . '/config/product-license.php';
}

function sf_license_ledger_path(): string
{
    $override = trim((string)(getenv('SF_LICENSE_LEDGER_PATH') ?: ''));
    return $override !== '' ? $override : sf_license_root() . '/config/license-ledger.php';
}

function sf_license_receipt_path(): string
{
    $override = trim((string)(getenv('SF_LICENSE_RECEIPT_PATH') ?: ''));
    return $override !== '' ? $override : sf_license_root() . '/storage/private/license-receipt.json';
}

function sf_license_product(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $path = sf_license_product_config_path();
    $loaded = is_file($path) ? require $path : [];
    if (!is_array($loaded)) {
        $loaded = [];
    }

    $config = array_merge([
        'product_id' => 'VP3-STONEFELLOW-001',
        'product_name' => 'Stonefellow Membership Platform',
        'product_family' => 'VP3 Media Group',
        'default_edition' => 'professional',
        'provider' => getenv('SF_LICENSE_PROVIDER') ?: 'offline_ledger',
        'remote_endpoint' => getenv('SF_LICENSE_ENDPOINT') ?: '',
        'remote_timeout_seconds' => 8,
        'receipt_grace_days' => 14,
    ], $loaded);

    $config['product_id'] = strtoupper(trim((string)$config['product_id']));
    $config['provider'] = strtolower(trim((string)$config['provider']));
    return $config;
}

function sf_license_normalize_key(string $key): string
{
    $key = strtoupper(trim($key));
    $key = preg_replace('/\s+/', '', $key) ?? '';
    $key = preg_replace('/[^A-Z0-9-]/', '', $key) ?? '';
    return trim($key, '-');
}

function sf_license_key_hash(string $key): string
{
    return hash('sha256', sf_license_normalize_key($key));
}

// The complete license key is never persisted; only this short fingerprint is retained.
function sf_license_key_fingerprint(string $key): string
{
    return strtoupper(implode('-', str_split(substr(sf_license_key_hash($key), 0, 16), 4)));
}

function sf_license_current_host(): string
{
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    return trim($host, '.');
}

function sf_license_domain_matches(string $host, string $pattern): bool
{
    $host = strtolower(trim($host, '.'));
    $pattern = strtolower(trim($pattern, '.'));
    if ($pattern === '' || $pattern === '*') {
        return true;
    }
    if ($host === $pattern) {
        return true;
    }
    if (str_starts_with($pattern, '*.')) {
        $suffix = substr($pattern, 1);
        return $suffix !== '' && str_ends_with($host, $suffix) && $host !== ltrim($suffix, '.');
    }
    return false;
}

function sf_license_load_ledger(): array
{
    $path = sf_license_ledger_path();
    if (!is_file($path)) {
        return [];
    }
    $ledger = require $path;
    return is_array($ledger) ? array_values(array_filter($ledger, 'is_array')) : [];
}

function sf_license_find_by_id(string $licenseId): ?array
{
    foreach (sf_license_load_ledger() as $record) {
        if (hash_equals((string)($record['license_id'] ?? ''), $licenseId)) {
            return $record;
        }
    }
    return null;
}

function sf_license_public_record(array $record): array
{
    $allowedDomains = $record['allowed_domains'] ?? [];
    if (!is_array($allowedDomains)) {
        $allowedDomains = array_filter(array_map('trim', explode(',', (string)$allowedDomains)));
    }

    return [
        'license_id' => (string)($record['license_id'] ?? ''),
        'product_id' => strtoupper((string)($record['product_id'] ?? '')),
        'status' => strtolower((string)($record['status'] ?? 'inactive')),
        'customer_name' => (string)($record['customer_name'] ?? ''),
        'customer_email' => (string)($record['customer_email'] ?? ''),
        'edition' => (string)($record['edition'] ?? sf_license_product()['default_edition']),
        'allowed_domains' => array_values($allowedDomains),
        'max_activations' => max(1, (int)($record['max_activations'] ?? 1)),
        'issued_at' => $record['issued_at'] ?? null,
        'expires_at' => $record['expires_at'] ?? null,
        'updates_until' => $record['updates_until'] ?? null,
        'notes' => (string)($record['notes'] ?? ''),
    ];
}

function sf_license_record_fingerprint(array $record): string
{
    $public = sf_license_public_record($record);
    ksort($public);
    return hash('sha256', json_encode($public, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function sf_license_validate_offline(string $key, array $context = []): array
{
    $normalized = sf_license_normalize_key($key);
    if (strlen($normalized) < 12) {
        return ['ok' => false, 'code' => 'invalid_format', 'message' => 'Enter a valid product license key.'];
    }

    $product = sf_license_product();
    $ledger = sf_license_load_ledger();
    if (!$ledger) {
        return ['ok' => false, 'code' => 'ledger_missing', 'message' => 'The product license ledger is not configured.'];
    }

    $keyHash = sf_license_key_hash($normalized);
    $host = strtolower(trim((string)($context['host'] ?? sf_license_current_host())));
    $today = new DateTimeImmutable('today');

    foreach ($ledger as $record) {
        $storedHash = strtolower(trim((string)($record['key_sha256'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $storedHash) || !hash_equals($storedHash, $keyHash)) {
            continue;
        }

        $public = sf_license_public_record($record);
        if (!hash_equals($product['product_id'], $public['product_id'])) {
            return ['ok' => false, 'code' => 'wrong_product', 'message' => 'This key is not valid for this product.'];
        }
        if (!in_array($public['status'], ['active', 'development'], true)) {
            return ['ok' => false, 'code' => 'inactive', 'message' => 'This product license is not active.'];
        }
        if (!empty($public['expires_at'])) {
            try {
                $expires = new DateTimeImmutable((string)$public['expires_at']);
                if ($expires < $today) {
                    return ['ok' => false, 'code' => 'expired', 'message' => 'This product license has expired.'];
                }
            } catch (Throwable $e) {
                return ['ok' => false, 'code' => 'invalid_expiration', 'message' => 'The license expiration value is invalid.'];
            }
        }
        if ($public['allowed_domains']) {
            $matched = false;
            foreach ($public['allowed_domains'] as $domain) {
                if (sf_license_domain_matches($host, (string)$domain)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return ['ok' => false, 'code' => 'domain_mismatch', 'message' => 'This license is not authorized for the current domain.'];
            }
        }

        return [
            'ok' => true,
            'code' => 'active',
            'message' => 'Product license verified.',
            'record' => $public,
            'record_fingerprint' => sf_license_record_fingerprint($record),
            'key_fingerprint' => sf_license_key_fingerprint($normalized),
            'validated_at' => date('c'),
            'provider' => 'offline_ledger',
        ];
    }

    return ['ok' => false, 'code' => 'not_found', 'message' => 'The product license key was not found.'];
}

function sf_license_validate_remote(string $key, array $context = []): array
{
    if (function_exists('sf_license_remote_provider_validate')) {
        $result = sf_license_remote_provider_validate($key, $context);
        return is_array($result) ? $result : ['ok' => false, 'code' => 'provider_error', 'message' => 'The remote license provider returned an invalid response.'];
    }
    return ['ok' => false, 'code' => 'provider_unavailable', 'message' => 'The remote license provider is not configured yet.'];
}

function sf_license_validate(string $key, array $context = []): array
{
    $provider = sf_license_product()['provider'];
    return $provider === 'remote_api'
        ? sf_license_validate_remote($key, $context)
        : sf_license_validate_offline($key, $context);
}

function sf_license_activate_setup(string $key): array
{
    $result = sf_license_validate($key, ['host' => sf_license_current_host(), 'purpose' => 'installer']);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($result['ok'])) {
        $_SESSION['sf_install_license'] = [
            'record' => $result['record'],
            'record_fingerprint' => $result['record_fingerprint'],
            'key_fingerprint' => $result['key_fingerprint'],
            'validated_at' => $result['validated_at'],
            'provider' => $result['provider'],
        ];
        session_regenerate_id(true);
    } else {
        unset($_SESSION['sf_install_license']);
    }
    return $result;
}

function sf_license_setup_session(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $value = $_SESSION['sf_install_license'] ?? null;
    return is_array($value) ? $value : null;
}

function sf_license_setup_valid(): bool
{
    $session = sf_license_setup_session();
    $record = $session['record'] ?? null;
    return is_array($record) && in_array((string)($record['status'] ?? ''), ['active', 'development'], true);
}

function sf_license_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
}

function sf_license_atomic_write(string $path, string $content): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the private license storage directory.');
    }
    $temporary = $path . '.tmp.' . bin2hex(random_bytes(5));
    if (file_put_contents($temporary, $content, LOCK_EX) === false) {
        throw new RuntimeException('Could not write the license receipt.');
    }
    @chmod($temporary, 0640);
    if (!@rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('Could not finalize the license receipt.');
    }
}

function sf_license_write_receipt(?string $baseUrl = null): array
{
    $session = sf_license_setup_session();
    if (!$session || empty($session['record']) || !is_array($session['record'])) {
        throw new RuntimeException('A verified product license is required before installation can finish.');
    }

    $existing = sf_license_receipt();
    $receipt = [
        'schema_version' => 1,
        'installation_id' => (string)($existing['installation_id'] ?? sf_license_uuid()),
        'license_id' => (string)($session['record']['license_id'] ?? ''),
        'product_id' => (string)($session['record']['product_id'] ?? ''),
        'product_name' => (string)sf_license_product()['product_name'],
        'customer_name' => (string)($session['record']['customer_name'] ?? ''),
        'customer_email' => (string)($session['record']['customer_email'] ?? ''),
        'edition' => (string)($session['record']['edition'] ?? ''),
        'authorized_domains' => array_values((array)($session['record']['allowed_domains'] ?? [])),
        'activated_domain' => sf_license_current_host(),
        'base_url' => trim((string)$baseUrl),
        'activated_at' => (string)($existing['activated_at'] ?? date('c')),
        'last_validated_at' => date('c'),
        'key_fingerprint' => (string)($session['key_fingerprint'] ?? ''),
        'ledger_record_fingerprint' => (string)($session['record_fingerprint'] ?? ''),
        'provider' => (string)($session['provider'] ?? 'offline_ledger'),
        'status' => (string)($session['record']['status'] ?? 'active'),
        'expires_at' => $session['record']['expires_at'] ?? null,
        'updates_until' => $session['record']['updates_until'] ?? null,
    ];

    sf_license_atomic_write(
        sf_license_receipt_path(),
        json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
    );
    return $receipt;
}

function sf_license_receipt(): ?array
{
    $path = sf_license_receipt_path();
    if (!is_file($path)) {
        return null;
    }
    try {
        $data = json_decode((string)file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_license_revalidate_receipt(): array
{
    $receipt = sf_license_receipt();
    if (!$receipt) {
        return ['ok' => false, 'status' => 'missing', 'message' => 'No product license receipt is installed.'];
    }

    $record = sf_license_find_by_id((string)($receipt['license_id'] ?? ''));
    if (!$record) {
        return [
            'ok' => true,
            'status' => 'receipt_only',
            'message' => 'The local receipt is present, but its ledger record is unavailable.',
            'receipt' => $receipt,
        ];
    }

    $public = sf_license_public_record($record);
    $product = sf_license_product();
    $host = sf_license_current_host();
    $reasons = [];
    if (!hash_equals($product['product_id'], $public['product_id'])) {
        $reasons[] = 'Product ID does not match.';
    }
    if (!in_array($public['status'], ['active', 'development'], true)) {
        $reasons[] = 'License status is ' . $public['status'] . '.';
    }
    if (!empty($public['expires_at'])) {
        try {
            if (new DateTimeImmutable((string)$public['expires_at']) < new DateTimeImmutable('today')) {
                $reasons[] = 'License has expired.';
            }
        } catch (Throwable $e) {
            $reasons[] = 'License expiration is invalid.';
        }
    }
    if ($public['allowed_domains']) {
        $matched = false;
        foreach ($public['allowed_domains'] as $domain) {
            if (sf_license_domain_matches($host, (string)$domain)) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $reasons[] = 'Current domain is not authorized.';
        }
    }

    return [
        'ok' => !$reasons,
        'status' => $reasons ? 'invalid' : 'active',
        'message' => $reasons ? implode(' ', $reasons) : 'Product license is active.',
        'receipt' => $receipt,
        'record' => $public,
        'record_fingerprint' => sf_license_record_fingerprint($record),
    ];
}

function sf_license_status(): array
{
    $validation = sf_license_revalidate_receipt();
    $receipt = $validation['receipt'] ?? sf_license_receipt();
    return [
        'ok' => !empty($validation['ok']),
        'status' => (string)($validation['status'] ?? 'missing'),
        'message' => (string)($validation['message'] ?? 'No license status available.'),
        'product' => sf_license_product(),
        'receipt' => is_array($receipt) ? $receipt : null,
        'record' => is_array($validation['record'] ?? null) ? $validation['record'] : null,
    ];
}
