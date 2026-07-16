<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/license.php';
require_once __DIR__ . '/installer-core.php';
require_once __DIR__ . '/standalone-installer.php';
require_once __DIR__ . '/installer-mysql-compat.php';

sf_install_bootstrap_standalone_license();

function sf_install_csrf_token(): string
{
    if (empty($_SESSION['sf_install_csrf']) || !is_string($_SESSION['sf_install_csrf'])) {
        $_SESSION['sf_install_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['sf_install_csrf'];
}

function sf_install_csrf_field(): string
{
    return '<input type="hidden" name="install_csrf" value="' . htmlspecialchars(sf_install_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $provided = (string)($_POST['install_csrf'] ?? '');
    if ($provided === '' || !hash_equals(sf_install_csrf_token(), $provided)) {
        sf_install_flash('error', 'Installer security check failed. Refresh the page and try again.');
        sf_install_redirect(sf_install_license_bypass_enabled() ? 'server' : 'license');
    }
    if (sf_install_is_locked()) {
        sf_install_flash('error', 'Likenessing is already installed. Sign in to the administrator dashboard to manage the platform.');
        sf_install_redirect('done');
    }
    $password = (string)($_POST['admin_password'] ?? $_POST['password'] ?? '');
    if ($password !== '' && strlen($password) < 12) {
        sf_install_flash('error', 'Owner password must be at least 12 characters.');
        sf_install_redirect('admin');
    }
}

// Handle installer POST actions with MySQL/MariaDB metadata compatibility.
// Successful actions redirect and exit before setup/index.php reaches the
// original handler; GET requests pass through without side effects.
sf_install_handle_post_compat();
