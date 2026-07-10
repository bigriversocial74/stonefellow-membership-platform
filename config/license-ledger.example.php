<?php

declare(strict_types=1);

/*
 * Copy this file to config/license-ledger.php and replace the sample entry.
 * Never store a plain-text license key here. Generate the SHA-256 hash with:
 * php vendor-tools/license-ledger-entry.php --customer="Customer Name" --email="customer@example.com" --domain="example.com"
 */
return [
    [
        'license_id' => 'LIC-000001',
        'product_id' => 'VP3-STONEFELLOW-001',
        'key_sha256' => 'replace-with-64-character-sha256',
        'status' => 'active',
        'customer_name' => 'Customer Name',
        'customer_email' => 'customer@example.com',
        'edition' => 'professional',
        'allowed_domains' => ['example.com', 'www.example.com'],
        'max_activations' => 1,
        'issued_at' => '2026-07-10',
        'expires_at' => null,
        'updates_until' => null,
        'notes' => '',
    ],
];
