<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This vendor licensing utility is CLI-only.\n");
    exit(1);
}

function vp3_arg(array $options, string $key, ?string $default = null): ?string
{
    $value = $options[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function vp3_random_key(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $groups = [];
    for ($group = 0; $group < 5; $group++) {
        $part = '';
        for ($i = 0; $i < 4; $i++) {
            $part .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $groups[] = $part;
    }
    return 'SFP-' . implode('-', $groups);
}

$options = getopt('', [
    'license-id::',
    'product-id::',
    'customer:',
    'email:',
    'domain::',
    'edition::',
    'status::',
    'expires::',
    'updates-until::',
    'max-activations::',
]);

$customer = vp3_arg($options, 'customer');
$email = strtolower((string)vp3_arg($options, 'email'));
if ($customer === null || $customer === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php vendor-tools/license-ledger-entry.php --customer=\"Customer Name\" --email=customer@example.com [--domain=example.com] [--license-id=LIC-000001]\n");
    exit(1);
}

$key = vp3_random_key();
$licenseId = vp3_arg($options, 'license-id', 'LIC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));
$productId = strtoupper((string)vp3_arg($options, 'product-id', 'VP3-STONEFELLOW-001'));
$domain = strtolower((string)vp3_arg($options, 'domain', ''));
$domains = $domain !== '' ? array_values(array_unique([$domain, str_starts_with($domain, 'www.') ? substr($domain, 4) : 'www.' . $domain])) : [];
$entry = [
    'license_id' => $licenseId,
    'product_id' => $productId,
    'key_sha256' => hash('sha256', $key),
    'status' => vp3_arg($options, 'status', 'active'),
    'customer_name' => $customer,
    'customer_email' => $email,
    'edition' => vp3_arg($options, 'edition', 'professional'),
    'allowed_domains' => $domains,
    'max_activations' => max(1, (int)vp3_arg($options, 'max-activations', '1')),
    'issued_at' => date('Y-m-d'),
    'expires_at' => vp3_arg($options, 'expires', '') ?: null,
    'updates_until' => vp3_arg($options, 'updates-until', '') ?: null,
    'notes' => '',
];

fwrite(STDOUT, "\nPRODUCT LICENSE KEY — SHARE ONCE WITH CUSTOMER\n");
fwrite(STDOUT, $key . "\n\n");
fwrite(STDOUT, "COPY THIS RECORD INTO config/license-ledger.php\n");
fwrite(STDOUT, var_export($entry, true) . ",\n\n");
fwrite(STDOUT, "Key fingerprint: " . strtoupper(implode('-', str_split(substr(hash('sha256', $key), 0, 16), 4))) . "\n");
fwrite(STDOUT, "Do not include this vendor utility in customer release packages.\n");
