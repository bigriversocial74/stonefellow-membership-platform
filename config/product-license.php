<?php

declare(strict_types=1);

return [
    'product_id' => 'VP3-STONEFELLOW-001',
    'product_name' => 'Stonefellow Membership Platform',
    'product_family' => 'VP3 Media Group',
    'default_edition' => 'professional',
    'provider' => getenv('SF_LICENSE_PROVIDER') ?: 'offline_ledger',
    'remote_endpoint' => getenv('SF_LICENSE_ENDPOINT') ?: '',
    'remote_timeout_seconds' => 8,
    'receipt_grace_days' => 14,
];
