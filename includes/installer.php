<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sf_install_root(): string
{
    return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

function sf_install_config_dir(): string
{
    return sf_install_root() . '/config';
}

function sf_install_storage_dir(): string
{
    return sf_install_root() . '/storage';
}

function sf_install_config_path(): string
{
    return sf_install_config_dir() . '/local.php';
}

function sf_install_lock_path(): string
{
    return sf_install_storage_dir() . '/install.lock';
}

function sf_install_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sf_install_is_locked(): bool
{
    return is_file(sf_install_lock_path());
}

function sf_install_current_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $script = trim($script, '/');
    return $scheme . '://' . $host . ($script ? '/' . $script : '');
}

function sf_install_flash(string $type, string $message): void
{
    $_SESSION['sf_install_flash'][] = ['type' => $type, 'message' => $message];
}

function sf_install_flashes(): array
{
    $messages = $_SESSION['sf_install_flash'] ?? [];
    unset($_SESSION['sf_install_flash']);
    return is_array($messages) ? $messages : [];
}

function sf_install_redirect(string $step = 'server'): void
{
    header('Location: install.php?step=' . urlencode($step));
    exit;
}

function sf_install_plan(): array
{
    return [
        'base' => ['label' => 'Base streaming platform schema', 'file' => 'database/stonefellow_streaming_platform.sql'],
        '001' => ['label' => 'Membership video tracking', 'file' => 'database/migrations/001_membership_video_tracking.sql'],
        '002' => ['label' => 'Video playlist runtime seed', 'file' => 'database/migrations/002_video_playlist_runtime_seed.sql'],
        '003' => ['label' => 'Media upload storage metadata', 'file' => 'database/migrations/003_media_upload_storage_metadata.sql'],
        '004' => ['label' => 'Billing entitlements', 'file' => 'database/migrations/004_billing_entitlements.sql'],
        '005' => ['label' => 'Merch order runtime', 'file' => 'database/migrations/005_merch_order_runtime.sql'],
        '006' => ['label' => 'Email notifications', 'file' => 'database/migrations/006_email_notifications.sql'],
        '007' => ['label' => 'Site settings installer', 'file' => 'database/migrations/007_site_settings_installer.sql'],
        '008' => ['label' => 'Payment gateway adapter', 'file' => 'database/migrations/008_payment_gateway_adapter.sql'],
        '009' => ['label' => 'Episode video admin v2', 'file' => 'database/migrations/009_episode_video_admin_v2.sql'],
        '010' => ['label' => 'Production readiness QA harness', 'file' => 'database/migrations/010_production_readiness_qa_harness.sql'],
        '011' => ['label' => 'Content import seed manager', 'file' => 'database/migrations/011_content_import_seed_manager.sql'],
        '012' => ['label' => 'Audio player entitlements v2', 'file' => 'database/migrations/012_audio_player_entitlements_v2.sql'],
        '013' => ['label' => 'Gateway publishing workflow v1', 'file' => 'database/migrations/013_gateway_publishing_workflow_v1.sql'],
        '014' => ['label' => 'Feed personalization and engagement analytics', 'file' => 'database/migrations/014_feed_personalization_engagement_analytics.sql'],
        '015' => ['label' => 'Membership tiers and launch revenue dashboard', 'file' => 'database/migrations/015_membership_tiers_revenue_dashboard.sql'],
        '016' => ['label' => 'Member lifecycle and support help desk', 'file' => 'database/migrations/016_member_lifecycle_support_helpdesk.sql'],
        '017' => ['label' => 'Ops scheduler and member messaging', 'file' => 'database/migrations/017_ops_scheduler_member_messaging.sql'],
        '018' => ['label' => 'Admin roles security audit', 'file' => 'database/migrations/018_admin_roles_security_audit.sql'],
        '019' => ['label' => 'Backup and deployment release manager', 'file' => 'database/migrations/019_backup_release_manager.sql'],
        '020' => ['label' => 'Monitoring and incident alerts', 'file' => 'database/migrations/020_monitoring_incident_alerts.sql'],
        '021' => ['label' => 'Storyboarding and AI settings', 'file' => 'database/migrations/021_storyboarding_ai_settings.sql'],
    ];
}

function sf_install_checks(): array
{
    $root = sf_install_root();
    $dirs = [
        'config' => sf_install_config_dir(),
        'storage' => sf_install_storage_dir(),
        'assets/images/uploads' => $root . '/assets/images/uploads',
        'assets/audio/uploads' => $root . '/assets/audio/uploads',
        'assets/video/uploads' => $root . '/assets/video/uploads',
        'assets/documents/uploads' => $root . '/assets/documents/uploads',
    ];

    $checks = [
        ['label' => 'PHP 8.1+', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'detail' => PHP_VERSION],
        ['label' => 'PDO extension', 'ok' => extension_loaded('pdo'), 'detail' => extension_loaded('pdo') ? 'Loaded' : 'Missing'],
        ['label' => 'PDO MySQL extension', 'ok' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Required on production'],
        ['label' => 'JSON extension', 'ok' => extension_loaded('json'), 'detail' => extension_loaded('json') ? 'Loaded' : 'Missing'],
        ['label' => 'Fileinfo extension', 'ok' => extension_loaded('fileinfo'), 'detail' => extension_loaded('fileinfo') ? 'Loaded' : 'Recommended for uploads'],
    ];

    foreach ($dirs as $label => $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        $checks[] = ['label' => 'Writable: ' . $label, 'ok' => is_dir($path) && is_writable($path), 'detail' => is_dir($path) ? (is_writable($path) ? 'Writable' : 'Not writable') : 'Missing'];
    }

    foreach (sf_install_plan() as $key => $item) {
        $file = $root . '/' . $item['file'];
        $checks[] = ['label' => 'SQL: ' . $key, 'ok' => is_file($file), 'detail' => $item['file']];
    }

    return $checks;
}

function sf_install_check_score(array $checks): int
{
    return $checks ? (int) round((count(array_filter($checks, static fn($c) => !empty($c['ok']))) / count($checks)) * 100) : 0;
}

function sf_install_saved_db(): array
{
    return $_SESSION['sf_install_db'] ?? [];
}

function sf_install_connect(array $db): PDO
{
    $host = trim((string) ($db['host'] ?? ''));
    $port = trim((string) ($db['port'] ?? '3306')) ?: '3306';
    $name = trim((string) ($db['name'] ?? ''));
    $user = trim((string) ($db['user'] ?? ''));
    $pass = (string) ($db['pass'] ?? '');
    $charset = trim((string) ($db['charset'] ?? 'utf8mb4')) ?: 'utf8mb4';

    if ($host === '' || $name === '' || $user === '') {
        throw new RuntimeException('Database host, name, and user are required.');
    }

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function sf_install_identifier(string $value): string
{
    return str_replace(['`', '\\', "\0"], '', $value);
}

function sf_install_table_exists(PDO $pdo, string $table): bool
{
    $sql = 'SHOW TABLES LIKE ' . $pdo->quote(sf_install_identifier($table));
    $statement = $pdo->query($sql);
    return (bool) ($statement ? $statement->fetchColumn() : false);
}

function sf_install_column_exists(PDO $pdo, string $table, string $column): bool
{
    $sql = 'SHOW COLUMNS FROM `' . sf_install_identifier($table) . '` LIKE ' . $pdo->quote($column);
    $statement = $pdo->query($sql);
    return (bool) ($statement ? $statement->fetchColumn() : false);
}

function sf_install_schema_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration_key VARCHAR(40) NOT NULL UNIQUE, file_path VARCHAR(255) NOT NULL, checksum_sha256 VARCHAR(64) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function sf_install_migration_applied(PDO $pdo, string $key, string $checksum): bool
{
    if (!sf_install_table_exists($pdo, 'schema_migrations')) {
        return false;
    }
    $statement = $pdo->prepare('SELECT checksum_sha256 FROM schema_migrations WHERE migration_key=? LIMIT 1');
    $statement->execute([$key]);
    $old = $statement->fetchColumn();
    return is_string($old) && $old === $checksum;
}

function sf_install_mark_migration(PDO $pdo, string $key, string $file, string $checksum): void
{
    $statement = $pdo->prepare('INSERT INTO schema_migrations (migration_key,file_path,checksum_sha256) VALUES (?,?,?) ON DUPLICATE KEY UPDATE file_path=VALUES(file_path), checksum_sha256=VALUES(checksum_sha256), applied_at=NOW()');
    $statement->execute([$key, $file, $checksum]);
}

function sf_install_split_sql(string $sql): array
{
    $out = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $ch = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($quote) {
            $buffer .= $ch;
            if ($ch === '\\' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
                continue;
            }
            if ($ch === $quote) {
                $quote = null;
            }
            continue;
        }

        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $buffer .= $ch;
            continue;
        }

        if ($ch === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }

        if ($ch === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }

        if ($ch === '/' && $next === '*') {
            $i += 2;
            while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i++;
            continue;
        }

        if ($ch === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $out[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $out[] = $statement;
    }

    return $out;
}

function sf_install_execute_statement(PDO $pdo, string $stmt): void
{
    try {
        $pdo->exec($stmt);
        return;
    } catch (PDOException $e) {
        if (stripos($stmt, 'DELIMITER ') === 0) {
            return;
        }

        if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+(.*)$/is', trim($stmt), $matches) && stripos($matches[2], 'ADD COLUMN IF NOT EXISTS') !== false) {
            $table = sf_install_identifier($matches[1]);
            $parts = preg_split('/,\s*(?=ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS)/i', trim($matches[2]));

            foreach ($parts as $part) {
                if (preg_match('/^ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?\s+(.*)$/is', trim($part), $columnMatch)) {
                    $column = sf_install_identifier($columnMatch[1]);
                    if (!sf_install_column_exists($pdo, $table, $column)) {
                        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $columnMatch[2]);
                    }
                } else {
                    $pdo->exec('ALTER TABLE `' . $table . '` ' . $part);
                }
            }
            return;
        }

        throw $e;
    }
}

function sf_install_run_sql(PDO $pdo): array
{
    sf_install_schema_table($pdo);
    $results = [];
    $root = sf_install_root();

    foreach (sf_install_plan() as $key => $item) {
        $path = $root . '/' . $item['file'];
        if (!is_file($path)) {
            $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'missing', 'detail' => 'File missing'];
            continue;
        }

        $sql = (string) file_get_contents($path);
        $checksum = hash('sha256', $sql);

        if (sf_install_migration_applied($pdo, $key, $checksum)) {
            $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'skipped', 'detail' => 'Already applied'];
            continue;
        }

        try {
            $pdo->beginTransaction();
            foreach (sf_install_split_sql($sql) as $stmt) {
                sf_install_execute_statement($pdo, $stmt);
            }
            sf_install_mark_migration($pdo, $key, $item['file'], $checksum);
            $pdo->commit();
            $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'applied', 'detail' => 'Applied successfully'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'failed', 'detail' => $e->getMessage()];
            break;
        }
    }

    $_SESSION['sf_install_sql_results'] = $results;
    return $results;
}

function sf_install_write_config(array $db, array $site): void
{
    if (!is_dir(sf_install_config_dir())) {
        @mkdir(sf_install_config_dir(), 0775, true);
    }

    $config = [
        'site' => [
            'name' => $site['site_name'] ?? 'Stonefellow',
            'tagline' => $site['site_tagline'] ?? 'Watch the show. Stream the music. Wear the story.',
            'base_url' => $site['base_url'] ?? '',
            'support_email' => $site['support_email'] ?? 'support@stonefellow.tv',
        ],
        'database' => [
            'host' => $db['host'],
            'port' => $db['port'] ?? '3306',
            'name' => $db['name'],
            'user' => $db['user'],
            'pass' => $db['pass'],
            'charset' => $db['charset'] ?? 'utf8mb4',
        ],
        'installed_at' => date('c'),
    ];

    $php = "<?php\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents(sf_install_config_path(), $php) === false) {
        throw new RuntimeException('Could not write config/local.php. Check folder permissions.');
    }
    @chmod(sf_install_config_path(), 0640);
}

function sf_install_create_admin(PDO $pdo, array $admin): int
{
    $name = trim((string) ($admin['name'] ?? ''));
    $email = strtolower(trim((string) ($admin['email'] ?? '')));
    $pass = (string) ($admin['password'] ?? '');
    $confirm = (string) ($admin['password_confirm'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || $pass !== $confirm) {
        throw new RuntimeException('Admin name, valid email, and matching 8+ character passwords are required.');
    }

    $columns = [
        'email' => $email,
        'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
        'display_name' => $name,
        'role' => 'admin',
        'status' => 'active',
    ];

    if (sf_install_column_exists($pdo, 'users', 'email_verified_at')) {
        $columns['email_verified_at'] = date('Y-m-d H:i:s');
    }

    $existing = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $existing->execute([$email]);
    $id = (int) ($existing->fetchColumn() ?: 0);

    if ($id > 0) {
        $sets = [];
        foreach ($columns as $key => $value) {
            $sets[] = '`' . $key . '`=?';
        }
        $pdo->prepare('UPDATE users SET ' . implode(',', $sets) . ' WHERE id=?')->execute(array_merge(array_values($columns), [$id]));
        return $id;
    }

    $keys = array_keys($columns);
    $pdo->prepare('INSERT INTO users (`' . implode('`,`', $keys) . '`) VALUES (' . implode(',', array_fill(0, count($keys), '?')) . ')')->execute(array_values($columns));
    return (int) $pdo->lastInsertId();
}

function sf_install_save_settings(PDO $pdo, array $site, ?int $adminId = null): void
{
    if (!sf_install_table_exists($pdo, 'site_settings')) {
        return;
    }

    $rows = [
        'site_name' => $site['site_name'] ?? 'Stonefellow',
        'site_tagline' => $site['site_tagline'] ?? 'Watch the show. Stream the music. Wear the story.',
        'base_url' => $site['base_url'] ?? '',
        'support_email' => $site['support_email'] ?? 'support@stonefellow.tv',
        'admin_email' => $site['admin_email'] ?? ($site['support_email'] ?? 'support@stonefellow.tv'),
        'payment_provider' => $site['payment_provider'] ?? 'sandbox',
        'member_signup_enabled' => '1',
        'checkout_enabled' => '1',
    ];

    $hasUser = sf_install_column_exists($pdo, 'site_settings', 'updated_by_user_id');
    foreach ($rows as $key => $value) {
        if ($hasUser) {
            $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value,setting_group,is_public,updated_by_user_id) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), is_public=VALUES(is_public), updated_by_user_id=VALUES(updated_by_user_id), updated_at=NOW()')->execute([$key, (string) $value, 'site', 1, $adminId]);
        } else {
            $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value,setting_group,is_public) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), is_public=VALUES(is_public)')->execute([$key, (string) $value, 'site', 1]);
        }
    }
}

function sf_install_finish(array $site, int $adminId): void
{
    if (!is_dir(sf_install_storage_dir())) {
        @mkdir(sf_install_storage_dir(), 0775, true);
    }

    $lock = [
        'installed_at' => date('c'),
        'admin_user_id' => $adminId,
        'site_name' => $site['site_name'] ?? 'Stonefellow',
        'base_url' => $site['base_url'] ?? '',
    ];

    if (file_put_contents(sf_install_lock_path(), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        throw new RuntimeException('Could not write storage/install.lock. Check folder permissions.');
    }
    @chmod(sf_install_lock_path(), 0640);
}

function sf_install_handle_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'test_db') {
            $db = [
                'host' => trim((string) ($_POST['db_host'] ?? '')),
                'port' => trim((string) ($_POST['db_port'] ?? '3306')),
                'name' => trim((string) ($_POST['db_name'] ?? '')),
                'user' => trim((string) ($_POST['db_user'] ?? '')),
                'pass' => (string) ($_POST['db_pass'] ?? ''),
                'charset' => 'utf8mb4',
            ];
            $pdo = sf_install_connect($db);
            $pdo->query('SELECT 1');
            $_SESSION['sf_install_db'] = $db;
            sf_install_flash('success', 'Database connection confirmed.');
            sf_install_redirect('sql');
        }

        if ($action === 'run_sql') {
            $db = sf_install_saved_db();
            $pdo = sf_install_connect($db);
            $results = sf_install_run_sql($pdo);
            $failed = array_filter($results, static fn($row) => $row['status'] === 'failed' || $row['status'] === 'missing');
            sf_install_flash($failed ? 'error' : 'success', $failed ? 'SQL install stopped. Review the failed item.' : 'SQL installed successfully.');
            sf_install_redirect($failed ? 'sql' : 'admin');
        }

        if ($action === 'finish') {
            $db = sf_install_saved_db();
            $pdo = sf_install_connect($db);
            $adminId = sf_install_create_admin($pdo, $_POST);
            sf_install_save_settings($pdo, $_POST, $adminId);
            sf_install_write_config($db, $_POST);
            sf_install_finish($_POST, $adminId);
            sf_install_flash('success', 'Stonefellow installation is complete.');
            sf_install_redirect('done');
        }
    } catch (Throwable $e) {
        sf_install_flash('error', $e->getMessage());
        sf_install_redirect($_GET['step'] ?? 'server');
    }
}
?>
