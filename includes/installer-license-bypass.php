<?php

declare(strict_types=1);

/**
 * Temporary standalone-installer bridge for show-theme branches.
 *
 * The original licensing runtime remains untouched. When enabled in
 * config/product-license.php, setup receives a development-only session
 * record so the installer can run without displaying or validating a key.
 * The generated receipt is written to a separate private bypass file and is
 * not used by the normal application license runtime after installation.
 */
function sf_install_license_bypass_enabled(): bool
{
    $product = sf_license_product();
    return !empty($product['installer_bypass']);
}

function sf_install_license_bypass_receipt_path(): string
{
    return sf_license_root() . '/storage/private/license-bypass-receipt.json';
}

function sf_install_license_bypass_bootstrap(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!sf_install_license_bypass_enabled()) {
        if ((string)($_SESSION['sf_install_license']['provider'] ?? '') === 'installer_bypass') {
            unset($_SESSION['sf_install_license']);
        }
        return;
    }

    putenv('SF_LICENSE_RECEIPT_PATH=' . sf_install_license_bypass_receipt_path());

    $product = sf_license_product();
    $record = [
        'license_id' => 'BYPASS-DESERTRIO-STANDALONE',
        'product_id' => (string)$product['product_id'],
        'status' => 'development',
        'customer_name' => 'DesertRio Standalone Build',
        'customer_email' => '',
        'edition' => (string)($product['default_edition'] ?? 'professional'),
        'allowed_domains' => [],
        'max_activations' => 1,
        'issued_at' => date('Y-m-d'),
        'expires_at' => null,
        'updates_until' => null,
        'notes' => 'Temporary installer bypass. No product key was requested or stored.',
    ];

    $_SESSION['sf_install_license'] = [
        'record' => $record,
        'record_fingerprint' => hash('sha256', 'installer-bypass|' . $record['product_id']),
        'key_fingerprint' => 'BYPASS-MODE',
        'validated_at' => date('c'),
        'provider' => 'installer_bypass',
    ];
}
