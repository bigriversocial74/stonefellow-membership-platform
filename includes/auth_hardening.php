<?php

declare(strict_types=1);

if (defined('SF_AUTH_HARDENING_LOADED')) return;
define('SF_AUTH_HARDENING_LOADED', true);

function sf_auth_hardening_env_int(string $name, int $default, int $min, int $max): int
{
    $value = getenv($name);
    $parsed = ($value !== false && is_numeric($value)) ? (int)$value : $default;
    return max($min, min($max, $parsed));
}

function sf_auth_password_min_length(): int
{
    return sf_auth_hardening_env_int('SF_PASSWORD_MIN_LENGTH', 12, 12, 128);
}

function sf_auth_password_policy_error(string $password, string $email = ''): ?string
{
    $length = strlen($password);
    $min = sf_auth_password_min_length();
    if ($length < $min) return 'Password must be at least ' . $min . ' characters.';
    if ($length > 4096) return 'Password is too long.';
    $normalizedEmail = strtolower(trim($email));
    $local = strstr($normalizedEmail, '@', true) ?: '';
    if ($local !== '' && strlen($local) >= 4 && str_contains(strtolower($password), $local)) {
        return 'Password must not contain your email name.';
    }
    $common = ['password1234', 'qwerty123456', 'letmein123456', 'stonefellow', '123456789012'];
    if (in_array(strtolower($password), $common, true)) return 'Choose a less common password.';
    return null;
}

function sf_auth_hardening_hash(string $value): string
{
    $secret = trim((string)(getenv('SF_HASH_SALT') ?: getenv('SF_APP_KEY') ?: ''));
    return $secret !== '' ? hash_hmac('sha256', $value, $secret) : hash('sha256', $value);
}

function sf_auth_hardening_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

function sf_auth_hardening_login_identifiers(string $email): array
{
    $email = strtolower(trim($email));
    $ip = sf_auth_hardening_ip();
    return [
        'email_raw' => substr($email, 0, 190),
        'email_hash' => sf_auth_hardening_hash('email|' . $email),
        'ip_raw' => $ip,
        'ip_hash' => sf_auth_hardening_hash('ip|' . $ip),
        'agent_hash' => sf_auth_hardening_hash('ua|' . substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)),
    ];
}

function sf_auth_hardening_login_allowed(string $email): array
{
    $limit = sf_auth_hardening_env_int('SF_LOGIN_FAILURE_LIMIT', 10, 3, 100);
    $window = sf_auth_hardening_env_int('SF_LOGIN_WINDOW_SECONDS', 900, 60, 86400);
    $ids = sf_auth_hardening_login_identifiers($email);
    $pdo = function_exists('sf_db') ? sf_db() : null;
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE success=0 AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND) AND (email IN (?,?) OR ip_address IN (?,?))');
            $stmt->execute([$window, $ids['email_raw'], $ids['email_hash'], $ids['ip_raw'], $ids['ip_hash']]);
            $count = (int)$stmt->fetchColumn();
            return ['allowed' => $count < $limit, 'remaining' => max(0, $limit - $count), 'retry_after' => $window];
        } catch (Throwable $e) {
            error_log('Stonefellow auth rate-limit lookup failed: ' . $e->getMessage());
        }
    }
    return function_exists('sf_security_session_rate_limit')
        ? sf_security_session_rate_limit('login|' . $ids['email_hash'] . '|' . $ids['ip_hash'], $limit, $window)
        : ['allowed' => true, 'remaining' => $limit, 'retry_after' => $window];
}

function sf_auth_hardening_scrub_login_attempt(string $email): void
{
    $pdo = function_exists('sf_db') ? sf_db() : null;
    if (!$pdo instanceof PDO) return;
    $ids = sf_auth_hardening_login_identifiers($email);
    try {
        $stmt = $pdo->prepare('UPDATE login_attempts SET email=?, ip_address=?, user_agent=? WHERE email=? AND ip_address=? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
        $stmt->execute([$ids['email_hash'], $ids['ip_hash'], $ids['agent_hash'], $ids['email_raw'], $ids['ip_raw']]);
    } catch (Throwable $e) {
        error_log('Stonefellow login privacy scrub failed: ' . $e->getMessage());
    }
}

function sf_auth_secure_login(string $email, string $password, bool $remember = false): bool
{
    $gate = sf_auth_hardening_login_allowed($email);
    if (empty($gate['allowed'])) {
        if (function_exists('sf_auth_flash')) sf_auth_flash('error', 'Too many sign-in attempts. Try again later.');
        return false;
    }
    $allowRemember = !function_exists('sf_is_production') || !sf_is_production() || sf_env_bool('SF_ALLOW_REMEMBER_ME', false);
    $ok = sf_auth_login($email, $password, $remember && $allowRemember);
    sf_auth_hardening_scrub_login_attempt($email);
    if ($ok) {
        $_SESSION['sf_auth_last_activity'] = time();
        $_SESSION['sf_auth_absolute_expires_at'] = time() + sf_auth_hardening_env_int('SF_AUTH_ABSOLUTE_SESSION_SECONDS', 43200, 1800, 604800);
        $_SESSION['sf_auth_fingerprint'] = sf_auth_hardening_hash('session|' . substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
        $pdo = sf_db();
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare('SELECT id,password_hash FROM users WHERE email=? LIMIT 1');
                $stmt->execute([strtolower(trim($email))]);
                $row = $stmt->fetch();
                if ($row && password_needs_rehash((string)$row['password_hash'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['id']]);
                }
            } catch (Throwable $e) {
                error_log('Stonefellow password rehash check failed: ' . $e->getMessage());
            }
        }
    }
    return $ok;
}

function sf_auth_secure_register(string $displayName, string $email, string $password, string $confirm, bool $terms): bool
{
    if ($error = sf_auth_password_policy_error($password, $email)) {
        sf_auth_flash('error', $error);
        return false;
    }
    $gate = sf_security_session_rate_limit('register|' . sf_auth_hardening_hash(sf_auth_hardening_ip()), 5, 3600);
    if (empty($gate['allowed'])) {
        sf_auth_flash('error', 'Too many account creation attempts. Try again later.');
        return false;
    }
    $pdo = sf_db();
    if ($pdo instanceof PDO) {
        try {
            $total = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($total === 0 && function_exists('sf_is_production') && sf_is_production() && !sf_env_bool('SF_ALLOW_PUBLIC_FIRST_ADMIN', false)) {
                sf_auth_flash('error', 'Owner setup must be completed through the protected installer before public registration.');
                return false;
            }
            $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $check->execute([strtolower(trim($email))]);
            if ($check->fetchColumn()) {
                sf_auth_flash('error', 'Unable to create an account with those details.');
                return false;
            }
        } catch (Throwable $e) {
            error_log('Stonefellow registration preflight failed: ' . $e->getMessage());
            sf_auth_flash('error', 'Account creation is temporarily unavailable.');
            return false;
        }
    }
    $ok = sf_auth_register($displayName, $email, $password, $confirm, $terms);
    if (!$ok) {
        sf_auth_flashes();
        sf_auth_flash('error', 'Unable to create an account with those details.');
    }
    return $ok;
}

function sf_auth_secure_reset_create(string $email): ?string
{
    $gate = sf_security_session_rate_limit('password-reset|' . sf_auth_hardening_hash(strtolower(trim($email)) . '|' . sf_auth_hardening_ip()), 3, 3600);
    if (empty($gate['allowed'])) return null;
    $token = sf_password_reset_create($email);
    $show = (!function_exists('sf_is_production') || !sf_is_production()) && sf_env_bool('SF_SHOW_DEVELOPMENT_RESET_LINK', false);
    return $show ? $token : null;
}

function sf_auth_secure_reset_apply(string $token, string $password, string $confirm): bool
{
    if ($error = sf_auth_password_policy_error($password)) {
        sf_auth_flash('error', $error);
        return false;
    }
    $selector = explode(':', trim($token), 2)[0] ?? '';
    $pdo = sf_db();
    $userId = 0;
    if ($pdo instanceof PDO && $selector !== '') {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM user_auth_tokens WHERE selector=? AND token_type='password_reset' LIMIT 1");
            $stmt->execute([$selector]);
            $userId = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Stonefellow reset user lookup failed: ' . $e->getMessage());
        }
    }
    $ok = sf_password_reset_apply($token, $password, $confirm);
    if ($ok && $pdo instanceof PDO && $userId > 0) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE user_auth_tokens SET used_at=COALESCE(used_at,NOW()) WHERE user_id=?')->execute([$userId]);
            if (function_exists('sf_sec_table_exists') && sf_sec_table_exists('admin_security_sessions')) {
                $pdo->prepare("UPDATE admin_security_sessions SET status='revoked', revoked_at=COALESCE(revoked_at,NOW()) WHERE user_id=? AND status='active'")->execute([$userId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Stonefellow post-reset token revocation failed: ' . $e->getMessage());
        }
    }
    return $ok;
}

function sf_auth_hardening_prepare_request(): void
{
    if (PHP_SAPI === 'cli') return;
    $production = function_exists('sf_is_production') && sf_is_production();
    $allowRemember = !$production || sf_env_bool('SF_ALLOW_REMEMBER_ME', false);
    if (!$allowRemember && !empty($_COOKIE['sf_remember'])) {
        $parts = explode(':', (string)$_COOKIE['sf_remember'], 2);
        $pdo = function_exists('sf_db') ? sf_db() : null;
        if ($pdo instanceof PDO && !empty($parts[0])) {
            try { $pdo->prepare('UPDATE user_auth_tokens SET used_at=COALESCE(used_at,NOW()) WHERE selector=?')->execute([$parts[0]]); } catch (Throwable $e) {}
        }
        setcookie('sf_remember', '', ['expires' => time() - 3600, 'path' => function_exists('sf_security_cookie_path') ? sf_security_cookie_path() : '/', 'secure' => function_exists('sf_is_https') && sf_is_https(), 'httponly' => true, 'samesite' => 'Lax']);
        unset($_COOKIE['sf_remember']);
    }
    if (empty($_SESSION['sf_user_id']) && empty($_SESSION['user_id']) && empty($_SESSION['member_id'])) return;
    $now = time();
    $idle = sf_auth_hardening_env_int('SF_AUTH_IDLE_SESSION_SECONDS', 7200, 300, 86400);
    $last = (int)($_SESSION['sf_auth_last_activity'] ?? $_SESSION['sf_login_at'] ?? $now);
    $absolute = (int)($_SESSION['sf_auth_absolute_expires_at'] ?? ($now + sf_auth_hardening_env_int('SF_AUTH_ABSOLUTE_SESSION_SECONDS', 43200, 1800, 604800)));
    $fingerprint = sf_auth_hardening_hash('session|' . substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
    $stored = (string)($_SESSION['sf_auth_fingerprint'] ?? $fingerprint);
    if (($now - $last) > $idle || $now > $absolute || !hash_equals($stored, $fingerprint)) {
        unset($_SESSION['sf_user_id'], $_SESSION['user_id'], $_SESSION['member_id'], $_SESSION['sf_login_at'], $_SESSION['sf_auth_last_activity'], $_SESSION['sf_auth_absolute_expires_at'], $_SESSION['sf_auth_fingerprint']);
        $_SESSION['sf_auth_flash'][] = ['type' => 'warning', 'message' => 'Your secure session expired. Sign in again.'];
        session_regenerate_id(true);
        return;
    }
    $_SESSION['sf_auth_last_activity'] = $now;
    $_SESSION['sf_auth_absolute_expires_at'] = $absolute;
    $_SESSION['sf_auth_fingerprint'] = $fingerprint;
}
