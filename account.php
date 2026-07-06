<?php
$pageTitle = 'Account';
$pageDescription = 'Manage your Stonefellow account, plan, security, playlists, and watch progress.';
$pageClass = 'membership-page account-page';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/membership.php';

$user = sf_require_login();
$member = sf_member_snapshot();
$pdo = sf_db();
$stats = [
  'Audio Plays' => 0,
  'Videos Watched' => 0,
  'Playlists' => 0,
  'Access Grants' => 0,
];
$recentAudio = [];
$recentVideo = [];
$grants = [];

if ($pdo) {
  try {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM audio_play_events WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $stats['Audio Plays'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM video_watch_events WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $stats['Videos Watched'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM playlists WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $stats['Playlists'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM content_access_grants WHERE user_id = ? AND (expires_at IS NULL OR expires_at >= NOW())');
    $stmt->execute([(int)$user['id']]);
    $stats['Access Grants'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare("\n      SELECT usp.song_id, s.title, usp.last_position_seconds, usp.play_count, usp.completed_count, usp.last_played_at\n      FROM user_song_progress usp\n      LEFT JOIN songs s ON s.id = usp.song_id\n      WHERE usp.user_id = ?\n      ORDER BY usp.last_played_at DESC\n      LIMIT 5\n    ");
    $stmt->execute([(int)$user['id']]);
    $recentAudio = $stmt->fetchAll() ?: [];

    $stmt = $pdo->prepare("\n      SELECT uvp.video_id, v.title, uvp.last_position_seconds, uvp.watch_count, uvp.completed_count, uvp.last_watched_at\n      FROM user_video_progress uvp\n      LEFT JOIN videos v ON v.id = uvp.video_id\n      WHERE uvp.user_id = ?\n      ORDER BY uvp.last_watched_at DESC\n      LIMIT 5\n    ");
    $stmt->execute([(int)$user['id']]);
    $recentVideo = $stmt->fetchAll() ?: [];

    $stmt = $pdo->prepare("\n      SELECT content_type, content_id, grant_type, access_level, starts_at, expires_at, created_at\n      FROM content_access_grants\n      WHERE user_id = ?\n      ORDER BY created_at DESC\n      LIMIT 8\n    ");
    $stmt->execute([(int)$user['id']]);
    $grants = $stmt->fetchAll() ?: [];
  } catch (Throwable $e) {
    error_log('Stonefellow account page stats failed: ' . $e->getMessage());
  }
}

require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div>
      <span class="sf-panel-eyebrow">Account</span>
      <h1><?= sf_auth_h($user['display_name'] ?: $user['email']) ?></h1>
      <p>Manage your membership access, account security, playlists, audio tracking, video progress, and direct content grants.</p>
      <div class="sf-episode-action-row">
        <a class="sf-primary-action" href="<?= sf_url('member.php') ?>">Member Dashboard</a>
        <a class="sf-secondary-action" href="<?= sf_url('account-billing.php') ?>">Billing</a>
        <a class="sf-secondary-action" href="<?= sf_url('logout.php') ?>">Logout</a>
      </div>
    </div>
    <article class="sf-member-status-card">
      <span>Current Access</span>
      <strong><?= sf_auth_h($member['access_label']) ?></strong>
      <small><?= $member['plan_name'] ? sf_auth_h($member['plan_name']) : 'No paid plan active yet' ?></small>
      <a href="<?= sf_url('account-billing.php') ?>">Manage Billing</a>
    </article>
  </section>

  <section class="sf-admin-card-grid sf-account-stats-grid">
    <?php foreach ($stats as $label => $value): ?>
      <article class="sf-admin-stat-card"><span><?= sf_auth_h($label) ?></span><strong><?= number_format((int)$value) ?></strong><small><?= $pdo ? 'Database backed' : 'Database not configured' ?></small></article>
    <?php endforeach; ?>
  </section>

  <section class="sf-admin-two-col">
    <article class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Profile</span><h2>Account details</h2></div></div>
      <div class="sf-admin-detail-list">
        <div><span>Email</span><strong><?= sf_auth_h($user['email']) ?></strong></div>
        <div><span>Role</span><strong><?= sf_auth_h($user['role']) ?></strong></div>
        <div><span>Status</span><strong><?= sf_auth_h($user['status']) ?></strong></div>
        <div><span>Last login</span><strong><?= sf_auth_h($user['last_login_at'] ?: 'Not recorded') ?></strong></div>
      </div>
    </article>
    <article class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Subscription</span><h2>Plan status</h2></div><a href="<?= sf_url('account-billing.php') ?>">Billing</a></div>
      <div class="sf-admin-detail-list">
        <div><span>Plan</span><strong><?= sf_auth_h($member['plan_name'] ?: 'Free Account') ?></strong></div>
        <div><span>Access</span><strong><?= sf_auth_h($member['access_label']) ?></strong></div>
        <div><span>Music</span><strong><?= $member['can_stream_full_music'] ? 'Unlocked' : 'Locked' ?></strong></div>
        <div><span>Video</span><strong><?= $member['can_watch_episodes'] ? 'Unlocked' : 'Locked' ?></strong></div>
      </div>
    </article>
  </section>

  <section class="sf-admin-two-col">
    <article class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audio</span><h2>Recent listening</h2></div><a href="<?= sf_url('player.php') ?>">Player</a></div>
      <div class="sf-admin-list">
        <?php foreach ($recentAudio as $row): ?>
          <div class="sf-admin-list-row"><strong><?= sf_auth_h($row['title'] ?: 'Song #' . ($row['song_id'] ?? '')) ?></strong><span><?= (int)$row['play_count'] ?> plays · <?= (int)$row['last_position_seconds'] ?>s saved</span></div>
        <?php endforeach; ?>
        <?php if (!$recentAudio): ?><p class="sf-admin-copy">No audio progress saved yet.</p><?php endif; ?>
      </div>
    </article>
    <article class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video</span><h2>Recent watching</h2></div><a href="<?= sf_url('episodes.php') ?>">Episodes</a></div>
      <div class="sf-admin-list">
        <?php foreach ($recentVideo as $row): ?>
          <div class="sf-admin-list-row"><strong><?= sf_auth_h($row['title'] ?: 'Video #' . ($row['video_id'] ?? '')) ?></strong><span><?= (int)$row['watch_count'] ?> watches · <?= (int)$row['last_position_seconds'] ?>s saved</span></div>
        <?php endforeach; ?>
        <?php if (!$recentVideo): ?><p class="sf-admin-copy">No video progress saved yet.</p><?php endif; ?>
      </div>
    </article>
  </section>

  <section class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Direct Access</span><h2>Content grants</h2></div></div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table">
        <thead><tr><th>Type</th><th>Content ID</th><th>Grant</th><th>Access</th><th>Expires</th></tr></thead>
        <tbody>
          <?php foreach ($grants as $grant): ?>
            <tr><td><?= sf_auth_h($grant['content_type']) ?></td><td><?= sf_auth_h((string)$grant['content_id']) ?></td><td><?= sf_auth_h($grant['grant_type']) ?></td><td><?= sf_auth_h($grant['access_level']) ?></td><td><?= sf_auth_h($grant['expires_at'] ?: 'Never') ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$grants): ?><tr><td colspan="5">No direct content grants yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
