<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/license.php';
require_once __DIR__ . '/installer-license-bypass.php';
sf_install_license_bypass_bootstrap();
require_once __DIR__ . '/installer-core.php';

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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && sf_install_license_bypass_enabled()) {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($script, '/setup/index.php')) {
        $requestedStep = preg_replace('/[^a-z]/', '', strtolower((string)($_GET['step'] ?? 'server'))) ?: 'server';
        if ($requestedStep === 'license') {
            $requestedStep = 'server';
        }
        header('Location: standalone.php?step=' . rawurlencode($requestedStep));
        exit;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $provided = (string)($_POST['install_csrf'] ?? '');
    if ($provided === '' || !hash_equals(sf_install_csrf_token(), $provided)) {
        sf_install_flash('error', 'Installer security check failed. Refresh the page and try again.');
        sf_install_redirect(sf_install_license_bypass_enabled() ? 'server' : 'license');
    }
    if (sf_install_is_locked()) {
        sf_install_flash('error', 'DesertRio is already installed. Sign in to the administrator dashboard to manage the platform.');
        sf_install_redirect('done');
    }
    $password = (string)($_POST['admin_password'] ?? $_POST['password'] ?? '');
    if ($password !== '' && strlen($password) < 12) {
        sf_install_flash('error', 'Owner password must be at least 12 characters.');
        sf_install_redirect('admin');
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (sf_install_license_bypass_enabled() && str_ends_with($script, '/setup/standalone.php')) {
        require_once __DIR__ . '/desertrio-installer-handler.php';
        sf_desertrio_install_handle_post();
    }
}
