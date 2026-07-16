<?php

declare(strict_types=1);

if (defined('SF_STANDALONE_INSTALLER_LOADED')) {
    return;
}
define('SF_STANDALONE_INSTALLER_LOADED', true);

function sf_install_standalone_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $path = sf_install_root() . '/config/standalone-install.php';
    $loaded = is_file($path) ? require $path : [];
    $config = is_array($loaded) ? $loaded : [];

    $environment = getenv('SF_STANDALONE_INSTALL_BYPASS');
    if ($environment !== false && trim((string)$environment) !== '') {
        $config['enabled'] = filter_var($environment, FILTER_VALIDATE_BOOL);
    }

    return array_merge([
        'enabled' => false,
        'mode' => 'licensed',
        'license_id' => 'STANDALONE-INSTALL',
        'customer_name' => 'Standalone Installation',
        'customer_email' => '',
        'edition' => 'standalone',
        'allowed_domains' => ['*'],
    ], $config);
}

function sf_install_license_bypass_enabled(): bool
{
    return !empty(sf_install_standalone_config()['enabled']);
}

function sf_install_bootstrap_standalone_license(): void
{
    if (!sf_install_license_bypass_enabled() || sf_install_is_locked()) {
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $session = sf_license_setup_session();
    $record = is_array($session['record'] ?? null) ? $session['record'] : [];
    if ($record && in_array((string)($record['status'] ?? ''), ['active', 'development'], true)) {
        if (empty($_GET['step']) || (string)$_GET['step'] === 'license') {
            $_GET['step'] = 'server';
        }
        return;
    }

    $config = sf_install_standalone_config();
    $product = sf_license_product();
    $record = [
        'license_id' => (string)$config['license_id'],
        'product_id' => (string)$product['product_id'],
        'status' => 'development',
        'customer_name' => (string)$config['customer_name'],
        'customer_email' => (string)$config['customer_email'],
        'edition' => (string)$config['edition'],
        'allowed_domains' => array_values((array)$config['allowed_domains']),
        'max_activations' => 1,
        'issued_at' => date('c'),
        'expires_at' => null,
        'updates_until' => null,
        'notes' => 'Standalone installer bypass. Licensing runtime remains available and can be restored by disabling config/standalone-install.php.',
    ];

    $_SESSION['sf_install_license'] = [
        'record' => $record,
        'record_fingerprint' => hash('sha256', json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        'key_fingerprint' => 'STANDALONE-BYPASS',
        'validated_at' => date('c'),
        'provider' => 'standalone_bypass',
    ];

    if (empty($_GET['step']) || (string)$_GET['step'] === 'license') {
        $_GET['step'] = 'server';
    }
}
