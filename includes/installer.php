<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/installer-hardening.php';
require_once __DIR__ . '/installer-core.php';

function sf_install_csrf_token(): string {
    if (empty($_SESSION['sf_install_csrf']) || !is_string($_SESSION['sf_install_csrf'])) $_SESSION['sf_install_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['sf_install_csrf'];
}
function sf_install_csrf_field(): string { return '<input type="hidden" name="install_csrf" value="'.htmlspecialchars(sf_install_csrf_token(),ENT_QUOTES,'UTF-8').'">'; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $provided=(string)($_POST['install_csrf'] ?? '');
    if ($provided==='' || !hash_equals(sf_install_csrf_token(),$provided)) {
        sf_install_flash('error','Installer security check failed. Refresh the page and try again.');
        sf_install_redirect('server');
    }
    if (sf_install_is_locked()) {
        sf_install_flash('error','Installer is locked. Remove storage/install.lock manually only after creating a backup.');
        sf_install_redirect('done');
    }
    $password=(string)($_POST['admin_password'] ?? $_POST['password'] ?? '');
    if ($password!=='' && strlen($password)<12) {
        sf_install_flash('error','Owner password must be at least 12 characters.');
        sf_install_redirect('admin');
    }
}

ob_start(static function(string $html): string {
    if ($html==='' || strpos($html,'VP3 Media Group Platform Installer')===false) return $html;
    return str_replace('<form method="post">','<form method="post">'.sf_install_csrf_field(),$html);
});
