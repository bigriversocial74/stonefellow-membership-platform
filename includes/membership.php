<?php
require_once __DIR__ . '/auth.php';

function sf_access_rank(string $level): int {
  $ranks = [
    'public' => 0,
    'free_preview' => 0,
    'free_account' => 1,
    'subscriber' => 2,
    'monthly-access' => 2,
    'annual-access' => 2,
    'premium' => 3,
    'founding_fan' => 4,
    'founding-fan' => 4,
    'admin' => 9,
  ];
  return $ranks[$level] ?? 0;
}

function sf_access_label(string $level): string {
  $labels = [
    'public' => 'Public',
    'free_preview' => 'Public Preview',
    'free_account' => 'Free Account',
    'subscriber' => 'Subscriber',
    'monthly-access' => 'Monthly Access',
    'annual-access' => 'Annual Access',
    'premium' => 'Premium',
    'founding_fan' => 'Founding Fan',
    'founding-fan' => 'Founding Fan',
    'admin' => 'Admin',
  ];
  return $labels[$level] ?? ucfirst(str_replace(['_', '-'], ' ', $level));
}

function sf_normalize_access_level(?string $level): string {
  $level = (string)$level;
  if ($level === 'founding-fan') {
    return 'founding_fan';
  }
  if ($level === 'monthly-access' || $level === 'annual-access') {
    return 'subscriber';
  }
  return $level ?: 'public';
}

function sf_current_access_level(): string {
  $demoLevel = getenv('SF_DEMO_ACCESS_LEVEL');
  if ($demoLevel) {
    return sf_normalize_access_level($demoLevel);
  }

  $user = sf_auth_user();
  if (!$user) {
    return 'public';
  }

  if (($user['role'] ?? '') === 'admin') {
    return 'admin';
  }

  $pdo = sf_db();
  if (!$pdo) {
    return 'free_account';
  }

  try {
    $stmt = $pdo->prepare("\n      SELECT sp.slug, sp.plan_tier, us.status\n      FROM user_subscriptions us\n      INNER JOIN subscription_plans sp ON sp.id = us.plan_id\n      WHERE us.user_id = ?\n        AND us.status IN ('active','trialing')\n        AND sp.status = 'active'\n        AND (us.current_period_end IS NULL OR us.current_period_end >= NOW())\n      ORDER BY us.current_period_end DESC, us.id DESC\n      LIMIT 1\n    ");
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();
    if ($row) {
      $tier = (string)($row['plan_tier'] ?? '');
      $slug = (string)($row['slug'] ?? '');
      if ($tier === 'founding_fan' || $slug === 'founding-fan') {
        return 'founding_fan';
      }
      if ($tier === 'annual' || $tier === 'monthly' || $slug !== '') {
        return 'subscriber';
      }
    }
  } catch (Throwable $e) {
    error_log('Stonefellow access lookup failed: ' . $e->getMessage());
  }

  return 'free_account';
}

function sf_access_allows(string $requiredLevel, ?string $currentLevel = null): bool {
  $requiredLevel = sf_normalize_access_level($requiredLevel);
  $currentLevel = sf_normalize_access_level($currentLevel ?: sf_current_access_level());
  return sf_access_rank($currentLevel) >= sf_access_rank($requiredLevel);
}

function sf_user_has_direct_grant(string $contentType, ?int $contentId = null, ?int $userId = null): bool {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  if (!$userId || !$pdo) {
    return false;
  }
  try {
    $stmt = $pdo->prepare("\n      SELECT id\n      FROM content_access_grants\n      WHERE user_id = ?\n        AND content_type = ?\n        AND (content_id IS NULL OR content_id = ?)\n        AND (starts_at IS NULL OR starts_at <= NOW())\n        AND (expires_at IS NULL OR expires_at >= NOW())\n      LIMIT 1\n    ");
    $stmt->execute([$userId, $contentType, $contentId]);
    return (bool)$stmt->fetch();
  } catch (Throwable $e) {
    return false;
  }
}

function sf_member_snapshot(): array {
  $user = sf_auth_user();
  $level = sf_current_access_level();
  $subscription = $user ? sf_user_subscription((int)$user['id']) : null;
  return [
    'user_id' => $user ? (int)$user['id'] : null,
    'email' => $user['email'] ?? null,
    'display_name' => $user['display_name'] ?? null,
    'role' => $user['role'] ?? null,
    'subscription' => $subscription,
    'plan_name' => $subscription['plan_name'] ?? null,
    'plan_slug' => $subscription['plan_slug'] ?? null,
    'access_level' => $level,
    'access_label' => sf_access_label($level),
    'can_stream_full_music' => sf_access_allows('subscriber', $level),
    'can_watch_episodes' => sf_access_allows('subscriber', $level),
    'can_manage_playlists' => sf_access_allows('subscriber', $level),
    'can_view_premium_video' => sf_access_allows('premium', $level),
  ];
}

function sf_access_gate_markup(string $title, string $message, string $cta = 'Subscribe Now'): string {
  return '<div class="sf-access-gate"><span>Membership Required</span><h2>' . sf_auth_h($title) . '</h2><p>' . sf_auth_h($message) . '</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="' . sf_url('subscribe.php') . '">' . sf_auth_h($cta) . '</a><a class="sf-secondary-action" href="' . sf_url('signin.php') . '">Sign In</a></div></div>';
}
?>
