<?php

declare(strict_types=1);

/**
 * DesertRio standalone installer POST handler.
 *
 * Some MySQL/MariaDB versions reject placeholders in SHOW TABLES/COLUMNS
 * while PDO native prepares are enabled. The shared installer intentionally
 * uses native prepares, so this branch enables emulated prepares only for the
 * standalone installation connection before schema and metadata operations.
 */
function sf_desertrio_install_redirect(string $step): void
{
    header('Location: ../setup/standalone.php?step=' . rawurlencode($step));
    exit;
}

function sf_desertrio_install_compatible_connection(array $db): PDO
{
    $pdo = sf_install_connect($db);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    return $pdo;
}

function sf_desertrio_install_handle_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = (string)($_POST['action'] ?? '');

    try {
        if (!sf_license_setup_valid()) {
            throw new RuntimeException('The temporary standalone installer session is unavailable. Reload the installer and try again.');
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

            $pdo = sf_desertrio_install_compatible_connection($db);
            $pdo->query('SELECT 1');
            $_SESSION['sf_install_db'] = $db;
            sf_install_flash('success', 'Database connection confirmed.');
            sf_desertrio_install_redirect('sql');
        }

        if ($action === 'run_sql') {
            if (!sf_install_db_ready()) {
                throw new RuntimeException('Confirm the database connection before installing migrations.');
            }

            $pdo = sf_desertrio_install_compatible_connection(sf_install_saved_db());
            $results = sf_install_run_sql($pdo);
            $failed = array_filter(
                $results,
                static fn(array $row): bool => in_array((string)($row['status'] ?? ''), ['failed', 'missing'], true)
            );

            sf_install_flash(
                $failed ? 'error' : 'success',
                $failed
                    ? 'The database install stopped safely. Review the failed item.'
                    : 'The platform database was installed successfully.'
            );
            sf_desertrio_install_redirect($failed ? 'sql' : 'admin');
        }

        if ($action === 'finish') {
            if (!sf_install_sql_ready()) {
                throw new RuntimeException('Complete the database migrations before creating the owner account.');
            }

            $db = sf_install_saved_db();
            $pdo = sf_desertrio_install_compatible_connection($db);
            $pdo->beginTransaction();

            try {
                $adminId = sf_install_create_admin($pdo, $_POST);
                sf_install_save_settings($pdo, $_POST, $adminId);
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }

            sf_install_write_config($db, $_POST);
            sf_license_write_receipt((string)($_POST['base_url'] ?? ''));
            sf_install_finish($_POST, $adminId);
            unset($_SESSION['sf_install_db'], $_SESSION['sf_install_license']);
            sf_install_flash('success', 'DesertRio installation is complete.');
            sf_desertrio_install_redirect('done');
        }

        throw new RuntimeException('The requested installer action is not supported.');
    } catch (Throwable $exception) {
        sf_install_flash('error', $exception->getMessage());
        $fallback = preg_replace('/[^a-z]/', '', strtolower((string)($_GET['step'] ?? 'server'))) ?: 'server';
        sf_desertrio_install_redirect(in_array($fallback, ['server', 'db', 'sql', 'admin', 'done'], true) ? $fallback : 'server');
    }
}
