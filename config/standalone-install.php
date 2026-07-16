<?php

declare(strict_types=1);

/**
 * Likenessing standalone deployment mode.
 *
 * Set `enabled` to false to restore the normal product-license-first setup
 * without removing or rewriting any licensing runtime files.
 */
return [
    'enabled' => true,
    'mode' => 'standalone',
    'license_id' => 'LIKENESSING-STANDALONE',
    'customer_name' => 'Likenessing Productions',
    'customer_email' => '',
    'edition' => 'standalone',
    'allowed_domains' => ['*'],
];
