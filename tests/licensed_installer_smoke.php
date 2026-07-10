<?php

declare(strict_types=1);

putenv('SF_LICENSE_PROVIDER=offline_ledger');
$root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$temp = sys_get_temp_dir() . '/stonefellow-license-test-' . bin2hex(random_bytes(5));
@mkdir($temp, 0775, true);
$ledgerPath = $temp . '/license-ledger.php';
$receiptPath = $temp . '/license-receipt.json';
putenv('SF_LICENSE_LEDGER_PATH=' . $ledgerPath);
putenv('SF_LICENSE_RECEIPT_PATH=' . $receiptPath);
$_SERVER['HTTP_HOST'] = 'media.example.com';

require_once $root . '/includes/license.php';

function sf_li_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$key = 'SFP-ABCD-EFGH-JKLM-NPQR-STUV';
$record = [
    'license_id' => 'LIC-TEST-001',
    'product_id' => 'VP3-STONEFELLOW-001',
    'key_sha256' => hash('sha256', $key),
    'status' => 'active',
    'customer_name' => 'Test Customer',
    'customer_email' => 'test@example.com',
    'edition' => 'professional',
    'allowed_domains' => ['*.example.com'],
    'max_activations' => 1,
    'issued_at' => date('Y-m-d'),
    'expires_at' => null,
    'updates_until' => '2030-12-31',
];
file_put_contents($ledgerPath, "<?php\nreturn " . var_export([$record], true) . ";\n");

$valid = sf_license_validate($key);
sf_li_assert(!empty($valid['ok']), 'valid offline ledger key should pass');
sf_li_assert(($valid['record']['license_id'] ?? '') === 'LIC-TEST-001', 'license ID should be preserved');
sf_li_assert(!array_key_exists('key_sha256', $valid['record']), 'public record must not expose key hash');
sf_li_assert(sf_license_key_fingerprint($key) !== $key, 'fingerprint must not contain the key');

$wrong = sf_license_validate('SFP-ZZZZ-ZZZZ-ZZZZ-ZZZZ-ZZZZ');
sf_li_assert(empty($wrong['ok']) && ($wrong['code'] ?? '') === 'not_found', 'unknown key should fail closed');

$_SERVER['HTTP_HOST'] = 'unauthorized.test';
$domain = sf_license_validate($key);
sf_li_assert(empty($domain['ok']) && ($domain['code'] ?? '') === 'domain_mismatch', 'unauthorized domain should fail');
$_SERVER['HTTP_HOST'] = 'media.example.com';

$activated = sf_license_activate_setup($key);
sf_li_assert(!empty($activated['ok']) && sf_license_setup_valid(), 'setup activation should create a valid session');
$receipt = sf_license_write_receipt('https://media.example.com');
sf_li_assert(is_file($receiptPath), 'receipt should be written to private path');
sf_li_assert(($receipt['key_fingerprint'] ?? '') !== '' && !str_contains((string)file_get_contents($receiptPath), $key), 'receipt must not store full key');
$revalidated = sf_license_revalidate_receipt();
sf_li_assert(!empty($revalidated['ok']), 'receipt should revalidate against ledger');

$landing = (string)file_get_contents($root . '/install.php');
$setup = (string)file_get_contents($root . '/setup/index.php');
$installer = (string)file_get_contents($root . '/includes/installer.php');
$adminLicense = (string)file_get_contents($root . '/admin/license.php');
sf_li_assert(strpos($landing, 'sf_install_checks') === false, 'public installer landing must not run technical checks');
sf_li_assert(strpos($landing, 'PDO MySQL') === false, 'public installer landing must not expose extension diagnostics');
sf_li_assert(strpos($setup, 'Product License Key') !== false, 'setup must begin with a product license key');
sf_li_assert(strpos($setup, 'X-Robots-Tag') !== false, 'setup must be excluded from indexing');
sf_li_assert(strpos($installer, 'installer-hardening.php') === false, 'installer must not depend on output-buffer HTML patching');
sf_li_assert(strpos($adminLicense, 'activate_existing') !== false && strpos($adminLicense, 'Activate Existing Installation') !== false, 'existing installations must have a protected activation path');

@unlink($ledgerPath);
@unlink($receiptPath);
@rmdir($temp);
echo "Licensed installer and offline product license smoke: PASS\n";
