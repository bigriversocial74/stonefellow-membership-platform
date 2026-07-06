<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notifications.php';

const SF_REMEMBER_COOKIE = 'sf_remember';

function sf_auth_flash(string $type, string $message): void {
  $_SESSION['sf_auth_flash'][] = ['type' => $type, 'message' => $message];
}

function sf_auth_flashes(): array {
  $items = $_SESSION['sf_auth_flash'] ?? [];
  unset($_SESSION['sf_auth_flash']);
  return is_array($items) ? $items : [];
}

function sf_redirect(string $url): void {
  header('Location: ' . $url);
  exit;
}

function sf_safe_next_url(?string $next): string {
  $next = trim((string)$next);
  if ($next === '' || preg_match('~^(https?:)?//~i', $next) || str_contains($next, "\n") || str_contains($next, "\r")) {
    return sf_url('member.php');
  }
  return $next;
}

function sf_csrf_token(): string {
  if (empty($_SESSION['sf_csrf_token'])) {
    $_SESSION['sf_csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['sf_csrf_token'];
}

function sf_csrf_field(): string {
  return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(sf_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function sf_verify_csrf(?string $token): bool {
  return is_string($token) && isset($_SESSION['sf_csrf_token']) && hash_equals($_SESSION['sf_csrf_token'], $token);
}

function sf_auth_h(?string $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sf_normalize_email(string $email): string {
  return strtolower(trim($email));
}

function sf_password_rules_message(): string {
  return 'Password must be at least 8 characters.';
}

function sf_auth_db_required(): ?PDO {
  $pdo = sf_db();
  if (!$pdo) {
    sf_auth_flash('warning', 'Database is not configured yet. Set SF_DB_HOST, SF_DB_NAME, SF_DB_USER, and SF_DB_PASS to enable live membership accounts.');
    return null;
  }
  return $pdo;
}

function sf_record_login_attempt(string $email, bool $success): void {
  $pdo = sf_db();
  if (!$pdo) {
    return;
  }
  try {
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, user_agent, success) VALUES (?, ?, ?, ?)');
    $stmt->execute([
      substr(sf_normalize_email($email), 0, 190),
      substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
      substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
      $success ? 1 : 0,
    ]);
  } catch (Throwable $e) {
    error_log('Stonefellow login attempt logging failed: ' . $e->getMessage());
  }
}

function sf_auth_user(): ?array {
  static $cached = false;
  if ($cached !== false) {
    return $cached;
  }

  $pdo = sf_db();
  if (!$pdo) {
    $cached = null;
    return null;
  }

  $userId = sf_current_user_id();
  if (!$userId) {
    sf_auth_try_remember_login();
    $userId = sf_current_user_id();
  }

  if (!$userId) {
    $cached = null;
    return null;
  }

  try {
    $stmt = $pdo->prepare('SELECT id, email, display_name, role, status, email_verified_at, last_login_at, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || ($user['status'] ?? '') !== 'active') {
      sf_auth_logout(false);
      $cached = null;
      return null;
    }
    $cached = $user;
    return $cached;
  } catch (Throwable $e) {
    error_log('Stonefellow auth user lookup failed: ' . $e->getMessage());
    $cached = null;
    return null;
  }
}

function sf_auth_try_remember_login(): void {
  if (!empty($_SESSION['sf_user_id']) || empty($_COOKIE[SF_REMEMBER_COOKIE])) {
    return;
  }
  $pdo = sf_db();
  if (!$pdo) {
    return;
  }
  $parts = explode(':', (string)$_COOKIE[SF_REMEMBER_COOKIE], 2);
  if (count($parts) !== 2) {
    return;
  }
  [$selector, $validator] = $parts;
  if (!preg_match('/^[a-f0-9]{32,64}$/i', $selector) || strlen($validator) < 32) {
    return;
  }
  try {
    $stmt = $pdo->prepare("\n      SELECT t.id, t.user_id, t.token_hash, u.status\n      FROM user_auth_tokens t\n      INNER JOIN users u ON u.id = t.user_id\n      WHERE t.selector = ? AND t.token_type = 'remember_me' AND t.used_at IS NULL AND t.expires_at > NOW()\n      LIMIT 1\n    ");
    $stmt->execute([$selector]);
    $row = $stmt->fetch();
    if (!$row || ($row['status'] ?? '') !== 'active') {
      return;
    }
    if (hash_equals((string)$row['token_hash'], hash('sha256', $validator))) {
      session_regenerate_id(true);
      $_SESSION['sf_user_id'] = (int)$row['user_id'];
      $_SESSION['sf_login_at'] = time();
    }
  } catch (Throwable $e) {
    error_log('Stonefellow remember login failed: ' . $e->getMessage());
  }
}

function sf_auth_create_remember_token(int $userId): void {
  $pdo = sf_db();
  if (!$pdo) {
    return;
  }
  $selector = bin2hex(random_bytes(16));
  $validator = bin2hex(random_bytes(32));
  $expires = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
  try {
    $stmt = $pdo->prepare("INSERT INTO user_auth_tokens (user_id, selector, token_hash, token_type, expires_at) VALUES (?, ?, ?, 'remember_me', ?)");
    $stmt->execute([$userId, $selector, hash('sha256', $validator), $expires]);
    setcookie(SF_REMEMBER_COOKIE, $selector . ':' . $validator, [
      'expires' => time() + 60 * 60 * 24 * 30,
      'path' => '/',
      'httponly' => true,
      'samesite' => 'Lax',
      'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
  } catch (Throwable $e) {
    error_log('Stonefellow remember token failed: ' . $e->getMessage());
  }
}

function sf_auth_login(string $email, string $password, bool $remember = false): bool {
  $pdo = sf_auth_db_required();
  if (!$pdo) {
    return false;
  }
  $email = sf_normalize_email($email);
  try {
    $stmt = $pdo->prepare('SELECT id, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || ($user['status'] ?? '') !== 'active' || !password_verify($password, (string)$user['password_hash'])) {
      sf_record_login_attempt($email, false);
      sf_auth_flash('error', 'Invalid email or password.');
      return false;
    }
    session_regenerate_id(true);
    $_SESSION['sf_user_id'] = (int)$user['id'];
    $_SESSION['sf_login_at'] = time();
    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
    sf_record_login_attempt($email, true);
    if ($remember) {
      sf_auth_create_remember_token((int)$user['id']);
    }
    return true;
  } catch (Throwable $e) {
    error_log('Stonefellow login failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Sign in failed. Check database tables and try again.');
    return false;
  }
}

function sf_auth_register(string $displayName, string $email, string $password, string $passwordConfirm, bool $termsAccepted): bool {
  $pdo = sf_auth_db_required();
  if (!$pdo) {
    return false;
  }
  $displayName = trim($displayName);
  $email = sf_normalize_email($email);
  if ($displayName === '') {
    sf_auth_flash('error', 'Display name is required.');
    return false;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sf_auth_flash('error', 'Enter a valid email address.');
    return false;
  }
  if (strlen($password) < 8) {
    sf_auth_flash('error', sf_password_rules_message());
    return false;
  }
  if (!hash_equals($password, $passwordConfirm)) {
    sf_auth_flash('error', 'Passwords do not match.');
    return false;
  }
  if (!$termsAccepted) {
    sf_auth_flash('error', 'You must agree to the Terms of Service and Privacy Policy.');
    return false;
  }

  try {
    $existing = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $existing->execute([$email]);
    if ($existing->fetch()) {
      sf_auth_flash('error', 'An account already exists for that email address.');
      return false;
    }

    $role = 'user';
    $count = $pdo->query('SELECT COUNT(*) AS total FROM users')->fetch();
    if ((int)($count['total'] ?? 0) === 0) {
      $role = 'admin';
    }

    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, display_name, role, status) VALUES (?, ?, ?, ?, \'active\')');
    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $displayName, $role]);
    $userId = (int)$pdo->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['sf_user_id'] = $userId;
    $_SESSION['sf_login_at'] = time();
    sf_auth_flash('success', $role === 'admin' ? 'Account created. Because this is the first user, it was set as admin.' : 'Account created. Welcome to Stonefellow.');
    sf_notify_send_template('welcome', [
      'user_id' => $userId,
      'email' => $email,
      'name' => $displayName,
    ], [
      'member_url' => sf_notify_absolute_url('member.php'),
      'role' => $role,
    ], [
      'notification_type' => 'auth',
      'metadata' => ['event' => 'user_registered', 'role' => $role],
      'dispatch' => true,
    ]);
    return true;
  } catch (Throwable $e) {
    error_log('Stonefellow registration failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Account creation failed. Check database tables and try again.');
    return false;
  }
}

function sf_auth_logout(bool $redirect = true): void {
  if (!empty($_COOKIE[SF_REMEMBER_COOKIE])) {
    $parts = explode(':', (string)$_COOKIE[SF_REMEMBER_COOKIE], 2);
    if (count($parts) === 2 && sf_db()) {
      try {
        sf_db()?->prepare('UPDATE user_auth_tokens SET used_at = NOW() WHERE selector = ?')->execute([$parts[0]]);
      } catch (Throwable $e) {
        error_log('Stonefellow logout remember cleanup failed: ' . $e->getMessage());
      }
    }
    setcookie(SF_REMEMBER_COOKIE, '', [
      'expires' => time() - 3600,
      'path' => '/',
      'httponly' => true,
      'samesite' => 'Lax',
      'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
  }
  unset($_SESSION['sf_user_id'], $_SESSION['user_id'], $_SESSION['member_id'], $_SESSION['sf_login_at']);
  if ($redirect) {
    sf_auth_flash('success', 'You have been signed out.');
    sf_redirect(sf_url('signin.php'));
  }
}

function sf_password_reset_create(string $email): ?string {
  $pdo = sf_auth_db_required();
  if (!$pdo) {
    return null;
  }
  $email = sf_normalize_email($email);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sf_auth_flash('error', 'Enter a valid email address.');
    return null;
  }
  try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND status = \'active\' LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
      return null;
    }
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $expires = (new DateTimeImmutable('+45 minutes'))->format('Y-m-d H:i:s');
    $pdo->prepare("UPDATE user_auth_tokens SET used_at = NOW() WHERE user_id = ? AND token_type = 'password_reset' AND used_at IS NULL")->execute([(int)$user['id']]);
    $insert = $pdo->prepare("INSERT INTO user_auth_tokens (user_id, selector, token_hash, token_type, expires_at) VALUES (?, ?, ?, 'password_reset', ?)");
    $insert->execute([(int)$user['id'], $selector, hash('sha256', $validator), $expires]);
    $token = $selector . ':' . $validator;
    sf_notify_send_template('password_reset', [
      'user_id' => (int)$user['id'],
      'email' => $email,
      'name' => $email,
    ], [
      'reset_url' => sf_notify_absolute_url('reset-password.php?token=' . urlencode($token)),
      'expires_minutes' => 45,
    ], [
      'notification_type' => 'auth',
      'metadata' => ['event' => 'password_reset_requested'],
      'dispatch' => true,
    ]);
    return $token;
  } catch (Throwable $e) {
    error_log('Stonefellow password reset create failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Password reset failed. Check database tables and try again.');
    return null;
  }
}

function sf_password_reset_apply(string $token, string $password, string $passwordConfirm): bool {
  $pdo = sf_auth_db_required();
  if (!$pdo) {
    return false;
  }
  if (strlen($password) < 8) {
    sf_auth_flash('error', sf_password_rules_message());
    return false;
  }
  if (!hash_equals($password, $passwordConfirm)) {
    sf_auth_flash('error', 'Passwords do not match.');
    return false;
  }
  $parts = explode(':', trim($token), 2);
  if (count($parts) !== 2) {
    sf_auth_flash('error', 'Invalid or expired reset link.');
    return false;
  }
  [$selector, $validator] = $parts;
  try {
    $stmt = $pdo->prepare("SELECT id, user_id, token_hash FROM user_auth_tokens WHERE selector = ? AND token_type = 'password_reset' AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$selector]);
    $row = $stmt->fetch();
    if (!$row || !hash_equals((string)$row['token_hash'], hash('sha256', $validator))) {
      sf_auth_flash('error', 'Invalid or expired reset link.');
      return false;
    }
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['user_id']]);
    $pdo->prepare('UPDATE user_auth_tokens SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
    $pdo->commit();
    sf_auth_flash('success', 'Password updated. Sign in with your new password.');
    return true;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Stonefellow password reset apply failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Password reset failed. Try again.');
    return false;
  }
}

function sf_require_login(?string $next = null): array {
  $user = sf_auth_user();
  if ($user) {
    return $user;
  }
  $target = $next ?: ($_SERVER['REQUEST_URI'] ?? sf_url('member.php'));
  sf_auth_flash('warning', 'Sign in to continue.');
  sf_redirect(sf_url('signin.php?next=' . urlencode($target)));
}

function sf_require_admin(): ?array {
  $pdo = sf_db();
  if (!$pdo) {
    return null;
  }
  $user = sf_require_login();
  if (($user['role'] ?? '') !== 'admin') {
    sf_auth_flash('error', 'Admin access required.');
    sf_redirect(sf_url('member.php'));
  }
  return $user;
}

function sf_user_subscription(?int $userId = null): ?array {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  if (!$userId || !$pdo) {
    return null;
  }
  try {
    $stmt = $pdo->prepare("\n      SELECT us.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.price_cents, sp.billing_interval,\n             sp.allows_full_music, sp.allows_offline_downloads, sp.status AS plan_status\n      FROM user_subscriptions us\n      INNER JOIN subscription_plans sp ON sp.id = us.plan_id\n      WHERE us.user_id = ?\n        AND us.status IN ('active','trialing')\n        AND (us.current_period_end IS NULL OR us.current_period_end >= NOW())\n      ORDER BY us.current_period_end DESC, us.id DESC\n      LIMIT 1\n    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
  } catch (Throwable $e) {
    error_log('Stonefellow subscription lookup failed: ' . $e->getMessage());
    return null;
  }
}

function sf_activate_subscription(int $planId, string $status = 'active'): bool {
  $pdo = sf_auth_db_required();
  $userId = sf_current_user_id();
  if (!$pdo || !$userId) {
    sf_auth_flash('warning', 'Sign in before activating a subscription.');
    return false;
  }
  try {
    $plan = $pdo->prepare("SELECT id, name, slug, billing_interval FROM subscription_plans WHERE id = ? AND status = 'active' LIMIT 1");
    $plan->execute([$planId]);
    $planRow = $plan->fetch();
    if (!$planRow) {
      sf_auth_flash('error', 'Subscription plan not found.');
      return false;
    }
    $end = (new DateTimeImmutable(($planRow['billing_interval'] ?? 'month') === 'year' ? '+1 year' : '+1 month'))->format('Y-m-d H:i:s');
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE user_subscriptions SET status = 'canceled', updated_at = NOW() WHERE user_id = ? AND status IN ('active','trialing')")->execute([$userId]);
    $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, status, current_period_start, current_period_end, external_subscription_id) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt->execute([$userId, (int)$planRow['id'], $status, $end, 'manual-admin-or-sandbox']);
    $pdo->commit();
    sf_auth_flash('success', 'Membership activated: ' . $planRow['name']);
    return true;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Stonefellow subscription activation failed: ' . $e->getMessage());
    sf_auth_flash('error', 'Membership activation failed.');
    return false;
  }
}

function sf_db_plan_options(): array {
  $pdo = sf_db();
  if (!$pdo) {
    return [];
  }
  try {
    $stmt = $pdo->query("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY is_featured DESC, price_cents ASC, id ASC");
    return $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    error_log('Stonefellow plan lookup failed: ' . $e->getMessage());
    return [];
  }
}
?>
