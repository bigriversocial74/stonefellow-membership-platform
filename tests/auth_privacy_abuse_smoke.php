<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY=testing-app-key-with-sufficient-length-1234567890');
putenv('SF_HASH_SALT=testing-hash-salt-with-sufficient-length-1234567890');
putenv('SF_PASSWORD_MIN_LENGTH=12');
putenv('SF_ALLOW_PUBLIC_FIRST_ADMIN=0');
putenv('SF_ALLOW_REMEMBER_ME=0');
putenv('SF_SHOW_DEVELOPMENT_RESET_LINK=0');

$_SERVER['REMOTE_ADDR'] = '192.0.2.55';
$_SERVER['HTTP_USER_AGENT'] = 'Stonefellow Security Smoke';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_security.php';
require_once __DIR__ . '/../includes/security_headers_hardening.php';
require_once __DIR__ . '/../includes/account_privacy.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) $failures[] = $message; };

$assert(sf_auth_password_min_length() === 12, 'Password minimum should be at least 12.');
$assert(sf_auth_password_policy_error('short') !== null, 'Short passwords should fail.');
$assert(sf_auth_password_policy_error('correct-horse-battery-staple', 'david@example.com') === null, 'Long non-common passwords should pass.');
$assert(sf_auth_password_policy_error('david-secure-password', 'david@example.com') !== null, 'Passwords containing the email local-part should fail.');

$hashA = sf_auth_hardening_hash('email|person@example.com');
$hashB = sf_auth_hardening_hash('email|person@example.com');
$assert(strlen($hashA) === 64 && hash_equals($hashA, $hashB), 'Privacy hashes should be stable SHA-256 values.');
$assert($hashA !== 'person@example.com', 'Privacy hashes must not expose raw identifiers.');

$rate1 = sf_security_session_rate_limit('auth-privacy-smoke', 1, 60);
$rate2 = sf_security_session_rate_limit('auth-privacy-smoke', 1, 60);
$assert($rate1['allowed'] === true && $rate2['allowed'] === false, 'Rate limiting should block repeated requests.');

$assert(sf_sec_route_permission('admin/orders.php') === 'admin.billing.manage', 'Orders should require billing permission.');
$assert(sf_sec_route_permission('admin/backups.php') === 'admin.ops.manage', 'Backups should require operations permission.');
$assert(sf_sec_route_permission('admin/storyboards.php') === 'admin.content.manage', 'Storyboards should require content permission.');
$assert(sf_sec_route_permission('admin/unknown-control.php') === 'admin.settings.manage', 'Unknown admin routes should fail closed.');

$policy = sf_security_content_policy();
foreach (["default-src 'self'", "object-src 'none'", "form-action 'self'", "frame-ancestors 'self'"] as $directive) {
    $assert(str_contains($policy, $directive), 'CSP should include ' . $directive . '.');
}

$root = dirname(__DIR__);
$required = [
    'signin.php' => ['sf_auth_secure_login', 'SF_ALLOW_REMEMBER_ME'],
    'signup.php' => ['sf_auth_secure_register', 'sf_auth_password_min_length'],
    'forgot-password.php' => ['sf_auth_secure_reset_create', 'reset instructions have been sent'],
    'reset-password.php' => ['sf_auth_secure_reset_apply', 'sf_auth_password_min_length'],
    'logout.php' => ['sf_auth_secure_logout'],
    'includes/auth_hardening.php' => ['SF_ALLOW_PUBLIC_FIRST_ADMIN', 'SF_SHOW_DEVELOPMENT_RESET_LINK', 'sf_auth_absolute_expires_at', 'password_needs_rehash'],
    'includes/admin_security.php' => ['revoked_admin_session_rejected', 'last_super_admin_removal_blocked', 'sf_sec_route_permission'],
    'account-privacy.php' => ['Download Account Export', 'DEACTIVATE'],
    'includes/account_privacy.php' => ['Content-Disposition', 'account_deactivated', 'retention_note'],
];
foreach ($required as $file => $markers) {
    $body = (string)file_get_contents($root . '/' . $file);
    foreach ($markers as $marker) $assert(str_contains($body, $marker), $file . ' should contain ' . $marker . '.');
}

if ($failures) {
    fwrite(STDERR, "Authentication/privacy smoke failures:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "Authentication privacy abuse smoke: PASS\n";
