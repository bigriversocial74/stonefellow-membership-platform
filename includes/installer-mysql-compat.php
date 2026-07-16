<?php

declare(strict_types=1);

/**
 * Installer-only MySQL compatibility adapter.
 *
 * Some MySQL/MariaDB versions reject native parameter markers in metadata
 * statements such as SHOW TABLES LIKE ? and SHOW COLUMNS ... LIKE ?. The
 * existing installer core uses those statements while intentionally disabling
 * emulated prepares. Enabling emulation on the installer connection lets PDO
 * quote those metadata values safely without changing the platform runtime.
 */
function sf_install_mysql_compat_connection(array $db): PDO
{
    $pdo = sf_install_connect($db);
    if ((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    }
    return $pdo;
}

function sf_install_handle_post_compat(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'activate_license') {
            $result = sf_license_activate_setup((string)($_POST['license_key'] ?? ''));
            sf_install_flash(!empty($result['ok']) ? 'success' : 'error', (string)($result['message'] ?? 'License validation failed.'));
            sf_install_redirect(!empty($result['ok']) ? 'server' : 'license');
        }

        if (!sf_license_setup_valid()) {
            throw new RuntimeException('Verify the product license before continuing setup.');
        }

        if ($action === 'test_db') {
            if (!sf_install_required_checks_pass(sf_install_checks())) {
                throw new RuntimeException('Resolve the required server checks before connecting the database.');
            }
            $existing = sf_install_saved_db();
            $password = (string)($_POST['db_pass'] ?? '');
            if ($password === '' && isset($existing['pass'])) {
                $password = (string)$existing['pass'];
            }
            $db = [
                'host' => trim((string)($_POST['db_host'] ?? '')),
                'port' => trim((string)($_POST['db_port'] ?? '3306')),
                'name' => trim((string)($_POST['db_name'] ?? '')),
                'user' => trim((string)($_POST['db_user'] ?? '')),
                'pass' => $password,
                'charset' => 'utf8mb4',
            ];
            $pdo = sf_install_mysql_compat_connection($db);
            $pdo->query('SELECT 1');
            $_SESSION['sf_install_db'] = $db;
            sf_install_flash('success', 'Database connection confirmed.');
            sf_install_redirect('sql');
        }

        if ($action === 'run_sql') {
            if (!sf_install_db_ready()) {
                throw new RuntimeException('Confirm the database connection before installing migrations.');
            }
            $pdo = sf_install_mysql_compat_connection(sf_install_saved_db());
            $results = sf_install_run_sql($pdo);
            $failed = array_filter($results, static fn($row) => in_array(($row['status'] ?? ''), ['failed', 'missing'], true));
            sf_install_flash($failed ? 'error' : 'success', $failed ? 'The database install stopped safely. Review the failed item.' : 'The platform database was installed successfully.');
            sf_install_redirect($failed ? 'sql' : 'admin');
        }

        if ($action === 'finish') {
            if (!sf_install_sql_ready()) {
                throw new RuntimeException('Complete the database migrations before creating the owner account.');
            }
            $db = sf_install_saved_db();
            $pdo = sf_install_mysql_compat_connection($db);
            $pdo->beginTransaction();
            try {
                $adminId = sf_install_create_admin($pdo, $_POST);
                sf_install_save_settings($pdo, $_POST, $adminId);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            sf_install_write_config($db, $_POST);
            sf_license_write_receipt((string)($_POST['base_url'] ?? ''));
            sf_install_finish($_POST, $adminId);
            unset($_SESSION['sf_install_db'], $_SESSION['sf_install_license']);
            sf_install_flash('success', 'Likenessing installation is complete.');
            sf_install_redirect('done');
        }
    } catch (Throwable $e) {
        sf_install_flash('error', $e->getMessage());
        $fallback = (string)($_GET['step'] ?? (sf_install_license_bypass_enabled() ? 'server' : 'license'));
        $allowed = sf_install_license_bypass_enabled()
            ? ['server', 'db', 'sql', 'admin', 'done']
            : ['license', 'server', 'db', 'sql', 'admin', 'done'];
        sf_install_redirect(in_array($fallback, $allowed, true) ? $fallback : (sf_install_license_bypass_enabled() ? 'server' : 'license'));
    }
}
