<?php

declare(strict_types=1);

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
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sf_install_is_locked(): bool
{
    return is_file(sf_install_lock_path());
}

function sf_install_current_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($script === 'setup') {
        $script = '';
    } elseif (str_ends_with($script, '/setup')) {
        $script = substr($script, 0, -6);
    }
    return $scheme . '://' . $host . ($script ? '/' . trim($script, '/') : '');
}

function sf_install_flash(string $type, string $message): void
{
    $_SESSION['sf_install_flash'][] = ['type' => $type, 'message' => $message];
}

function sf_install_flashes(): array
{
    $items = $_SESSION['sf_install_flash'] ?? [];
    unset($_SESSION['sf_install_flash']);
    return is_array($items) ? $items : [];
}

function sf_install_setup_url(string $step = 'license'): string
{
    return '../setup/index.php?step=' . rawurlencode($step);
}

function sf_install_redirect(string $step = 'license'): void
{
    header('Location: ' . sf_install_setup_url($step));
    exit;
}

function sf_install_label_map(): array
{
    return [
        '001' => 'Membership video tracking',
        '002' => 'Video playlist runtime seed',
        '003' => 'Media upload storage metadata',
        '004' => 'Billing entitlements',
        '005' => 'Merch order runtime',
        '006' => 'Email notifications',
        '007' => 'Site settings installer',
        '008' => 'Payment gateway adapter',
        '009' => 'Episode video admin v2',
        '010' => 'Production readiness QA harness',
        '011' => 'Content import seed manager',
        '012' => 'Audio player entitlements v2',
        '013' => 'Gateway publishing workflow v1',
        '014' => 'Feed personalization and engagement analytics',
        '015' => 'Membership tiers and launch revenue dashboard',
        '016' => 'Member lifecycle and support help desk',
        '017' => 'Ops scheduler and member messaging',
        '018' => 'Admin roles security audit',
        '019' => 'Backup and deployment release manager',
        '020' => 'Monitoring and incident alerts',
        '021' => 'Storyboarding and AI settings',
        '022' => 'Live commerce and Stripe Connect',
        '023' => 'Protected media storage and transcoding',
        '024' => 'Launch content and catalog operations',
        '025' => 'Staging activation and release candidates',
        '026' => 'Production cutover and hypercare',
    ];
}

function sf_install_plan(): array
{
    $root = sf_install_root();
    $plan = ['base' => ['label' => 'Base streaming platform schema', 'file' => 'database/stonefellow_streaming_platform.sql']];
    $labels = sf_install_label_map();
    foreach (glob($root . '/database/migrations/[0-9][0-9][0-9]_*.sql') ?: [] as $path) {
        $name = basename($path);
        $key = substr($name, 0, 3);
        $fallback = ucwords(str_replace(['_', '-'], ' ', substr($name, 4, -4)));
        $plan[$key] = ['label' => $labels[$key] ?? $fallback, 'file' => 'database/migrations/' . $name];
    }
    uksort($plan, static function (string $a, string $b): int {
        if ($a === 'base') return -1;
        if ($b === 'base') return 1;
        return strcmp($a, $b);
    });
    return $plan;
}

function sf_install_checks(): array
{
    $root = sf_install_root();
    $dirs = [
        'Configuration directory' => sf_install_config_dir(),
        'Private storage' => sf_install_storage_dir(),
        'Image uploads' => $root . '/assets/images/uploads',
        'Audio uploads' => $root . '/assets/audio/uploads',
        'Video uploads' => $root . '/assets/video/uploads',
        'Document uploads' => $root . '/assets/documents/uploads',
    ];
    $checks = [
        ['key' => 'php', 'label' => 'PHP 8.1 or newer', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'required' => true, 'detail' => 'Detected PHP ' . PHP_VERSION],
        ['key' => 'pdo', 'label' => 'PDO extension', 'ok' => extension_loaded('pdo'), 'required' => true, 'detail' => extension_loaded('pdo') ? 'Loaded' : 'Missing'],
        ['key' => 'pdo_mysql', 'label' => 'PDO MySQL extension', 'ok' => extension_loaded('pdo_mysql'), 'required' => true, 'detail' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing'],
        ['key' => 'json', 'label' => 'JSON extension', 'ok' => extension_loaded('json'), 'required' => true, 'detail' => extension_loaded('json') ? 'Loaded' : 'Missing'],
        ['key' => 'fileinfo', 'label' => 'Fileinfo extension', 'ok' => extension_loaded('fileinfo'), 'required' => true, 'detail' => extension_loaded('fileinfo') ? 'Loaded' : 'Missing'],
        ['key' => 'openssl', 'label' => 'OpenSSL extension', 'ok' => extension_loaded('openssl'), 'required' => true, 'detail' => extension_loaded('openssl') ? 'Loaded' : 'Missing'],
        ['key' => 'mbstring', 'label' => 'Mbstring extension', 'ok' => extension_loaded('mbstring'), 'required' => false, 'detail' => extension_loaded('mbstring') ? 'Loaded' : 'Recommended'],
    ];
    foreach ($dirs as $label => $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        $checks[] = [
            'key' => 'writable_' . strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label) ?? ''),
            'label' => $label . ' writable',
            'ok' => is_dir($path) && is_writable($path),
            'required' => true,
            'detail' => is_dir($path) ? (is_writable($path) ? 'Writable' : 'Not writable') : 'Missing',
        ];
    }
    foreach (sf_install_plan() as $key => $item) {
        $file = $root . '/' . $item['file'];
        $checks[] = [
            'key' => 'sql_' . $key,
            'label' => 'Migration package ' . $key,
            'ok' => is_file($file),
            'required' => true,
            'detail' => is_file($file) ? $item['label'] : 'Required SQL file is missing',
        ];
    }
    return $checks;
}

function sf_install_required_checks_pass(array $checks): bool
{
    foreach ($checks as $check) {
        if (!empty($check['required']) && empty($check['ok'])) {
            return false;
        }
    }
    return true;
}

function sf_install_check_score(array $checks): int
{
    return $checks ? (int)round(count(array_filter($checks, static fn($c) => !empty($c['ok']))) / count($checks) * 100) : 0;
}

function sf_install_saved_db(): array
{
    return is_array($_SESSION['sf_install_db'] ?? null) ? $_SESSION['sf_install_db'] : [];
}

function sf_install_db_ready(): bool
{
    $db = sf_install_saved_db();
    return trim((string)($db['host'] ?? '')) !== ''
        && trim((string)($db['name'] ?? '')) !== ''
        && trim((string)($db['user'] ?? '')) !== '';
}

function sf_install_sql_ready(): bool
{
    $results = $_SESSION['sf_install_sql_results'] ?? [];
    if (!is_array($results) || !$results) {
        return false;
    }
    foreach ($results as $row) {
        if (in_array((string)($row['status'] ?? ''), ['failed', 'missing'], true)) {
            return false;
        }
    }
    return true;
}

function sf_install_connect(array $db): PDO
{
    $host = trim((string)($db['host'] ?? ''));
    $port = (int)($db['port'] ?? 3306);
    $name = trim((string)($db['name'] ?? ''));
    $user = trim((string)($db['user'] ?? ''));
    $pass = (string)($db['pass'] ?? '');
    $charset = strtolower(trim((string)($db['charset'] ?? 'utf8mb4')));
    if ($host === '' || $name === '' || $user === '') throw new RuntimeException('Database host, name, and user are required.');
    if (str_contains($host, ';') || str_contains($name, ';')) throw new RuntimeException('Database configuration contains unsafe DSN characters.');
    if ($port < 1 || $port > 65535) throw new RuntimeException('Database port is invalid.');
    if (!in_array($charset, ['utf8mb4', 'utf8'], true)) $charset = 'utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
    return new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", $user, $pass, $options);
}

function sf_install_identifier(string $value): string
{
    return str_replace(['`', '\\', "\0"], '', $value);
}

function sf_install_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([sf_install_identifier($table)]);
    return (bool)$stmt->fetchColumn();
}

function sf_install_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . sf_install_identifier($table) . '` LIKE ?');
    $stmt->execute([$column]);
    return (bool)$stmt->fetchColumn();
}

function sf_install_schema_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,migration_key VARCHAR(40) NOT NULL UNIQUE,file_path VARCHAR(255) NOT NULL,checksum_sha256 VARCHAR(64) NOT NULL,applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function sf_install_migration_checksum(PDO $pdo, string $key): ?string
{
    if (!sf_install_table_exists($pdo, 'schema_migrations')) return null;
    $stmt = $pdo->prepare('SELECT checksum_sha256 FROM schema_migrations WHERE migration_key=? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return is_string($value) ? $value : null;
}

function sf_install_mark_migration(PDO $pdo, string $key, string $file, string $checksum): void
{
    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration_key,file_path,checksum_sha256) VALUES (?,?,?)');
    $stmt->execute([$key, $file, $checksum]);
}

function sf_install_split_sql(string $sql): array
{
    $out = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';
        if ($quote) {
            $buffer .= $char;
            if ($char === '\\' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
                continue;
            }
            if ($char === $quote) $quote = null;
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }
        if ($char === '#') {
            while ($i < $length && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }
        if ($char === '/' && $next === '*') {
            $i += 2;
            while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) $i++;
            $i++;
            continue;
        }
        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') $out[] = $statement;
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }
    $statement = trim($buffer);
    if ($statement !== '') $out[] = $statement;
    return $out;
}

function sf_install_execute_statement(PDO $pdo, string $statement): void
{
    try {
        $pdo->exec($statement);
        return;
    } catch (PDOException $e) {
        if (stripos($statement, 'DELIMITER ') === 0) return;
        if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+(.*)$/is', trim($statement), $match) && stripos($match[2], 'ADD COLUMN IF NOT EXISTS') !== false) {
            $table = sf_install_identifier($match[1]);
            $parts = preg_split('/,\s*(?=ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS)/i', trim($match[2]));
            foreach ($parts ?: [] as $part) {
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
    $lock = 'stonefellow_installer_migrations';
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?,0)');
    $lockStmt->execute([$lock]);
    if ((int)$lockStmt->fetchColumn() !== 1) {
        return [['key' => 'lock', 'label' => 'Migration lock', 'status' => 'failed', 'detail' => 'Another installer or migration process is running.']];
    }

    $results = [];
    $root = sf_install_root();
    try {
        foreach (sf_install_plan() as $key => $item) {
            $path = $root . '/' . $item['file'];
            if (!is_file($path)) {
                $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'missing', 'detail' => 'Required migration file is missing.'];
                break;
            }
            $sql = (string)file_get_contents($path);
            $checksum = hash('sha256', $sql);
            $old = sf_install_migration_checksum($pdo, (string)$key);
            if ($old !== null) {
                if (hash_equals($old, $checksum)) {
                    $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'skipped', 'detail' => 'Already applied with matching checksum.'];
                    continue;
                }
                $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'failed', 'detail' => 'Applied migration checksum differs from the package. Create a new migration instead of modifying history.'];
                break;
            }
            try {
                foreach (sf_install_split_sql($sql) as $statement) sf_install_execute_statement($pdo, $statement);
                sf_install_mark_migration($pdo, (string)$key, $item['file'], $checksum);
                $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'applied', 'detail' => 'Applied successfully.'];
            } catch (Throwable $e) {
                $results[] = ['key' => $key, 'label' => $item['label'], 'status' => 'failed', 'detail' => $e->getMessage()];
                break;
            }
        }
    } finally {
        try {
            $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $stmt->execute([$lock]);
        } catch (Throwable $ignore) {
        }
    }
    $_SESSION['sf_install_sql_results'] = $results;
    return $results;
}

function sf_install_atomic_write(string $path, string $content, int $mode = 0640): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Could not create the required configuration directory.');
    $temp = $path . '.tmp.' . bin2hex(random_bytes(6));
    if (file_put_contents($temp, $content, LOCK_EX) === false) throw new RuntimeException('Could not write the temporary configuration file.');
    @chmod($temp, $mode);
    if (!@rename($temp, $path)) {
        @unlink($temp);
        throw new RuntimeException('Could not finalize the configuration file.');
    }
}

function sf_install_write_config(array $db, array $site): void
{
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
    sf_install_atomic_write(sf_install_config_path(), "<?php\nreturn " . var_export($config, true) . ";\n", 0640);
}

function sf_install_create_admin(PDO $pdo, array $admin): int
{
    $name = trim((string)($admin['name'] ?? ''));
    $email = strtolower(trim((string)($admin['email'] ?? '')));
    $password = (string)($admin['password'] ?? '');
    $confirm = (string)($admin['password_confirm'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !hash_equals($password, $confirm)) {
        throw new RuntimeException('Owner name, valid email, and matching 12+ character passwords are required.');
    }
    $columns = [
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'display_name' => $name,
        'role' => 'admin',
        'status' => 'active',
    ];
    if (sf_install_column_exists($pdo, 'users', 'email_verified_at')) $columns['email_verified_at'] = date('Y-m-d H:i:s');
    $existing = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $existing->execute([$email]);
    $id = (int)($existing->fetchColumn() ?: 0);
    if ($id > 0) {
        $sets = [];
        foreach ($columns as $key => $value) $sets[] = '`' . $key . '`=?';
        $pdo->prepare('UPDATE users SET ' . implode(',', $sets) . ' WHERE id=?')->execute(array_merge(array_values($columns), [$id]));
        return $id;
    }
    $keys = array_keys($columns);
    $pdo->prepare('INSERT INTO users (`' . implode('`,`', $keys) . '`) VALUES (' . implode(',', array_fill(0, count($keys), '?')) . ')')->execute(array_values($columns));
    return (int)$pdo->lastInsertId();
}

function sf_install_save_settings(PDO $pdo, array $site, ?int $adminId = null): void
{
    if (!sf_install_table_exists($pdo, 'site_settings')) return;
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
            $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value,setting_group,is_public,updated_by_user_id) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_public=VALUES(is_public),updated_by_user_id=VALUES(updated_by_user_id),updated_at=NOW()')->execute([$key, (string)$value, 'site', 1, $adminId]);
        } else {
            $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value,setting_group,is_public) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_public=VALUES(is_public)')->execute([$key, (string)$value, 'site', 1]);
        }
    }
}

function sf_install_finish(array $site, int $adminId): void
{
    $lock = [
        'installed_at' => date('c'),
        'admin_user_id' => $adminId,
        'site_name' => $site['site_name'] ?? 'Stonefellow',
        'base_url' => $site['base_url'] ?? '',
        'installation_id' => sf_license_receipt()['installation_id'] ?? null,
        'product_id' => sf_license_product()['product_id'],
    ];
    sf_install_atomic_write(sf_install_lock_path(), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", 0640);
}

function sf_install_handle_post(): void
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
            if (!sf_install_required_checks_pass(sf_install_checks())) throw new RuntimeException('Resolve the required server checks before connecting the database.');
            $existing = sf_install_saved_db();
            $password = (string)($_POST['db_pass'] ?? '');
            if ($password === '' && isset($existing['pass'])) $password = (string)$existing['pass'];
            $db = [
                'host' => trim((string)($_POST['db_host'] ?? '')),
                'port' => trim((string)($_POST['db_port'] ?? '3306')),
                'name' => trim((string)($_POST['db_name'] ?? '')),
                'user' => trim((string)($_POST['db_user'] ?? '')),
                'pass' => $password,
                'charset' => 'utf8mb4',
            ];
            $pdo = sf_install_connect($db);
            $pdo->query('SELECT 1');
            $_SESSION['sf_install_db'] = $db;
            sf_install_flash('success', 'Database connection confirmed.');
            sf_install_redirect('sql');
        }

        if ($action === 'run_sql') {
            if (!sf_install_db_ready()) throw new RuntimeException('Confirm the database connection before installing migrations.');
            $pdo = sf_install_connect(sf_install_saved_db());
            $results = sf_install_run_sql($pdo);
            $failed = array_filter($results, static fn($row) => in_array(($row['status'] ?? ''), ['failed', 'missing'], true));
            sf_install_flash($failed ? 'error' : 'success', $failed ? 'The database install stopped safely. Review the failed item.' : 'The platform database was installed successfully.');
            sf_install_redirect($failed ? 'sql' : 'admin');
        }

        if ($action === 'finish') {
            if (!sf_install_sql_ready()) throw new RuntimeException('Complete the database migrations before creating the owner account.');
            $db = sf_install_saved_db();
            $pdo = sf_install_connect($db);
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
            sf_install_flash('success', 'Stonefellow installation is complete.');
            sf_install_redirect('done');
        }
    } catch (Throwable $e) {
        sf_install_flash('error', $e->getMessage());
        $fallback = (string)($_GET['step'] ?? 'license');
        sf_install_redirect(in_array($fallback, ['license', 'server', 'db', 'sql', 'admin', 'done'], true) ? $fallback : 'license');
    }
}
