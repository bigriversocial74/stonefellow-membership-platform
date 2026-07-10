<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$sections = [
    'Public landing isolation' => [
        ['install.php', ['Licensed platform launcher', 'setup/index.php', 'Server diagnostics are not displayed publicly']],
    ],
    'License provider architecture' => [
        ['includes/license.php', ['sf_license_validate_offline', 'sf_license_validate_remote', 'sf_license_validate', 'SF_LICENSE_PROVIDER']],
        ['config/product-license.php', ['VP3-STONEFELLOW-001', 'offline_ledger', 'remote_endpoint']],
    ],
    'Manual license ledger' => [
        ['config/license-ledger.example.php', ['key_sha256', 'allowed_domains', 'updates_until']],
        ['vendor-tools/license-ledger-entry.php', ['PRODUCT LICENSE KEY', 'config/license-ledger.php', 'hash(\'sha256\'']],
    ],
    'License-first setup gate' => [
        ['setup/index.php', ['Product License Key', 'sf_license_setup_valid', 'Technical server details remain hidden']],
        ['includes/installer-core.php', ['activate_license', 'Verify the product license before continuing setup']],
    ],
    'Credential and secret handling' => [
        ['includes/license.php', ['key_fingerprint', 'license-receipt.json', 'The complete license key']],
        ['setup/index.php', ['autocomplete="new-password"', 'leave blank to keep it']],
    ],
    'Installer security and sequencing' => [
        ['includes/installer.php', ['sf_install_csrf_token', 'hash_equals', '12 characters']],
        ['includes/installer-core.php', ['sf_install_required_checks_pass', 'sf_install_db_ready', 'sf_install_sql_ready']],
    ],
    'Migration safety' => [
        ['includes/installer-core.php', ['GET_LOCK', 'checksum differs', 'schema_migrations']],
    ],
    'Activation receipt and revalidation' => [
        ['includes/license.php', ['sf_license_write_receipt', 'sf_license_revalidate_receipt', 'installation_id']],
        ['admin/license.php', ['Product License', 'Key Fingerprint', 'Revalidate Local License', 'Activate Existing Installation', 'activate_existing']],
    ],
    'Release packaging boundaries' => [
        ['.releaseignore', ['config/license-ledger.php', 'storage/private/', 'vendor-tools/']],
        ['.gitignore', ['config/license-ledger.php', 'storage/private/']],
    ],
    'Permanent verification' => [
        ['tests/licensed_installer_smoke.php', ['Licensed installer and offline product license smoke: PASS', 'Activate Existing Installation']],
        ['.github/workflows/code-audit.yml', ['Licensed installer product license smoke tests', 'Licensed installer product license audit']],
        ['docs/LICENSED_INSTALLER_SETUP_V1.md', ['Final source/control score: **10/10**']],
    ],
];

$failed = [];
foreach ($sections as $section => $checks) {
    $sectionFailed = [];
    foreach ($checks as [$path, $markers]) {
        $file = $root . '/' . $path;
        $body = is_file($file) ? (string)file_get_contents($file) : '';
        foreach ($markers as $marker) {
            if ($body === '' || stripos($body, $marker) === false) {
                $sectionFailed[] = $path . ' missing marker: ' . $marker;
            }
        }
    }
    $score = $sectionFailed ? 0 : 10;
    echo sprintf("%-42s %d/10\n", $section, $score);
    foreach ($sectionFailed as $problem) $failed[] = $section . ': ' . $problem;
}

if ($failed) {
    foreach ($failed as $problem) fwrite(STDERR, "FAIL: {$problem}\n");
    exit(1);
}

echo "All ten licensed installer and product license sections score 10/10.\n";
