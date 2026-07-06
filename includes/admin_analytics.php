<?php
require_once __DIR__ . '/admin_catalog.php';

function sf_analytics_days(): int {
  $allowed = [7, 30, 90, 365];
  $days = isset($_GET['days']) && is_numeric($_GET['days']) ? (int)$_GET['days'] : 30;
  return in_array($days, $allowed, true) ? $days : 30;
}

function sf_analytics_since(int $days): string {
  return (new DateTimeImmutable('-' . max(1, $days) . ' days'))->format('Y-m-d H:i:s');
}

function sf_analytics_url(string $path, int $days): string {
  return sf_url($path . '?days=' . $days);
}

function sf_analytics_time(int $seconds): string {
  $seconds = max(0, (int)$seconds);
  if ($seconds < 60) {
    return $seconds . 's';
  }
  $minutes = intdiv($seconds, 60);
  if ($minutes < 60) {
    return $minutes . 'm ' . ($seconds % 60) . 's';
  }
  $hours = intdiv($minutes, 60);
  $remainingMinutes = $minutes % 60;
  return $hours . 'h ' . $remainingMinutes . 'm';
}

function sf_analytics_money(int $cents): string {
  return '$' . number_format($cents / 100, 2);
}

function sf_analytics_percent($value, $total): string {
  $value = (float)$value;
  $total = (float)$total;
  if ($total <= 0) {
    return '0%';
  }
  return number_format(($value / $total) * 100, 1) . '%';
}

function sf_analytics_metric_card(string $label, string $value, string $note = ''): void {
  echo '<article class="sf-admin-stat-card sf-analytics-stat-card"><span>' . sf_admin_h($label) . '</span><strong>' . sf_admin_h($value) . '</strong><small>' . sf_admin_h($note) . '</small></article>';
}

function sf_analytics_range_tabs(int $activeDays): void {
  $tabs = [7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '365 days'];
  echo '<div class="sf-analytics-range-tabs" aria-label="Analytics date range">';
  foreach ($tabs as $days => $label) {
    $class = $days === $activeDays ? 'is-active' : '';
    $path = trim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $basename = basename($path) ?: 'analytics.php';
    echo '<a class="' . $class . '" href="' . sf_admin_h(sf_url('admin/' . $basename . '?days=' . $days)) . '">' . sf_admin_h($label) . '</a>';
  }
  echo '</div>';
}

function sf_analytics_has_tracking_tables(): bool {
  return sf_admin_table_exists('audio_play_events') || sf_admin_table_exists('video_watch_events') || sf_admin_table_exists('user_song_progress') || sf_admin_table_exists('user_video_progress');
}

function sf_analytics_overview(int $days): array {
  $since = sf_analytics_since($days);
  $audio = ['events' => 0, 'seconds' => 0, 'completes' => 0, 'songs' => 0, 'listeners' => 0];
  $video = ['events' => 0, 'seconds' => 0, 'completes' => 0, 'videos' => 0, 'viewers' => 0];
  $members = ['total' => 0, 'new' => 0, 'active_subscriptions' => 0, 'recent_logins' => 0, 'playlists' => 0];
  $commerce = ['orders' => 0, 'revenue_cents' => 0, 'items' => 0];

  if (sf_admin_table_exists('audio_play_events')) {
    $row = sf_admin_fetch_one("SELECT COUNT(*) AS events, COALESCE(SUM(seconds_played),0) AS seconds, SUM(CASE WHEN event_type='complete' THEN 1 ELSE 0 END) AS completes, COUNT(DISTINCT song_id) AS songs, COUNT(DISTINCT user_id) AS listeners FROM audio_play_events WHERE created_at >= ?", [$since]);
    $audio = array_merge($audio, array_map('intval', $row ?: []));
  } else {
    $audio['songs'] = count(sf_admin_songs());
  }

  if (sf_admin_table_exists('video_watch_events')) {
    $row = sf_admin_fetch_one("SELECT COUNT(*) AS events, COALESCE(SUM(seconds_watched),0) AS seconds, SUM(CASE WHEN event_type='complete' THEN 1 ELSE 0 END) AS completes, COUNT(DISTINCT video_id) AS videos, COUNT(DISTINCT user_id) AS viewers FROM video_watch_events WHERE created_at >= ?", [$since]);
    $video = array_merge($video, array_map('intval', $row ?: []));
  } else {
    $video['videos'] = count(sf_admin_videos());
  }

  if (sf_admin_table_exists('users')) {
    $members['total'] = (int)(sf_admin_fetch_one('SELECT COUNT(*) AS total FROM users')['total'] ?? 0);
    $members['new'] = (int)(sf_admin_fetch_one('SELECT COUNT(*) AS total FROM users WHERE created_at >= ?', [$since])['total'] ?? 0);
    if (sf_admin_column_exists('users', 'last_login_at')) {
      $members['recent_logins'] = (int)(sf_admin_fetch_one('SELECT COUNT(*) AS total FROM users WHERE last_login_at >= ?', [$since])['total'] ?? 0);
    }
  }
  if (sf_admin_table_exists('user_subscriptions')) {
    $members['active_subscriptions'] = (int)(sf_admin_fetch_one("SELECT COUNT(*) AS total FROM user_subscriptions WHERE status IN ('active','trialing') AND (current_period_end IS NULL OR current_period_end >= NOW())")['total'] ?? 0);
  }
  if (sf_admin_table_exists('playlists')) {
    $members['playlists'] = (int)(sf_admin_fetch_one('SELECT COUNT(*) AS total FROM playlists WHERE created_at >= ?', [$since])['total'] ?? 0);
  }
  if (sf_admin_table_exists('orders')) {
    $row = sf_admin_fetch_one("SELECT COUNT(*) AS orders, COALESCE(SUM(total_cents),0) AS revenue_cents FROM orders WHERE created_at >= ? AND status IN ('paid','fulfilled')", [$since]);
    $commerce['orders'] = (int)($row['orders'] ?? 0);
    $commerce['revenue_cents'] = (int)($row['revenue_cents'] ?? 0);
  }
  if (sf_admin_table_exists('order_items')) {
    $row = sf_admin_fetch_one("SELECT COALESCE(SUM(oi.quantity),0) AS items FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE o.created_at >= ? AND o.status IN ('paid','fulfilled')", [$since]);
    $commerce['items'] = (int)($row['items'] ?? 0);
  }

  return compact('audio', 'video', 'members', 'commerce');
}

function sf_analytics_audio_top_songs(int $days, int $limit = 12): array {
  if (!sf_admin_table_exists('audio_play_events')) {
    $rows = [];
    foreach (array_slice(sf_admin_songs(), 0, $limit) as $index => $song) {
      $rows[] = [
        'song_id' => $song['id'] ?? $index + 1,
        'title' => $song['title'] ?? 'Song',
        'album_title' => $song['album_title'] ?? 'The Road Is Calling',
        'events' => 0,
        'listeners' => 0,
        'seconds' => 0,
        'completes' => 0,
        'completion_rate' => 0,
      ];
    }
    return $rows;
  }
  return sf_admin_fetch_all(
    "SELECT s.id AS song_id, s.title, a.title AS album_title,
            COUNT(e.id) AS events,
            COUNT(DISTINCT e.user_id) AS listeners,
            COALESCE(SUM(e.seconds_played),0) AS seconds,
            SUM(CASE WHEN e.event_type='complete' THEN 1 ELSE 0 END) AS completes,
            AVG(e.percent_complete) AS avg_percent
     FROM audio_play_events e
     INNER JOIN songs s ON s.id = e.song_id
     LEFT JOIN albums a ON a.id = s.album_id
     WHERE e.created_at >= ?
     GROUP BY s.id, s.title, a.title
     ORDER BY seconds DESC, events DESC
     LIMIT " . (int)$limit,
    [sf_analytics_since($days)]
  );
}

function sf_analytics_video_top_videos(int $days, int $limit = 12): array {
  if (!sf_admin_table_exists('video_watch_events')) {
    $rows = [];
    foreach (array_slice(sf_admin_videos(), 0, $limit) as $index => $video) {
      $rows[] = [
        'video_id' => $video['id'] ?? $index + 1,
        'title' => $video['title'] ?? 'Video',
        'episode_title' => $video['episode_title'] ?? $video['episode_slug'] ?? 'Standalone',
        'events' => 0,
        'viewers' => 0,
        'seconds' => 0,
        'completes' => 0,
        'avg_percent' => 0,
      ];
    }
    return $rows;
  }
  return sf_admin_fetch_all(
    "SELECT v.id AS video_id, v.title, ep.title AS episode_title,
            COUNT(e.id) AS events,
            COUNT(DISTINCT e.user_id) AS viewers,
            COALESCE(SUM(e.seconds_watched),0) AS seconds,
            SUM(CASE WHEN e.event_type='complete' THEN 1 ELSE 0 END) AS completes,
            AVG(e.percent_complete) AS avg_percent
     FROM video_watch_events e
     INNER JOIN videos v ON v.id = e.video_id
     LEFT JOIN episodes ep ON ep.id = v.episode_id
     WHERE e.created_at >= ?
     GROUP BY v.id, v.title, ep.title
     ORDER BY seconds DESC, events DESC
     LIMIT " . (int)$limit,
    [sf_analytics_since($days)]
  );
}

function sf_analytics_audio_events(int $days, int $limit = 40): array {
  if (!sf_admin_table_exists('audio_play_events')) {
    return [];
  }
  return sf_admin_fetch_all(
    "SELECT e.*, s.title AS song_title, u.email, u.display_name
     FROM audio_play_events e
     INNER JOIN songs s ON s.id = e.song_id
     LEFT JOIN users u ON u.id = e.user_id
     WHERE e.created_at >= ?
     ORDER BY e.created_at DESC, e.id DESC
     LIMIT " . (int)$limit,
    [sf_analytics_since($days)]
  );
}

function sf_analytics_video_events(int $days, int $limit = 40): array {
  if (!sf_admin_table_exists('video_watch_events')) {
    return [];
  }
  return sf_admin_fetch_all(
    "SELECT e.*, v.title AS video_title, ep.title AS episode_title, u.email, u.display_name
     FROM video_watch_events e
     INNER JOIN videos v ON v.id = e.video_id
     LEFT JOIN episodes ep ON ep.id = e.episode_id
     LEFT JOIN users u ON u.id = e.user_id
     WHERE e.created_at >= ?
     ORDER BY e.created_at DESC, e.id DESC
     LIMIT " . (int)$limit,
    [sf_analytics_since($days)]
  );
}

function sf_analytics_member_activity(int $days, int $limit = 80): array {
  if (!sf_admin_table_exists('users')) {
    return [];
  }
  $selectAudio = sf_admin_table_exists('audio_play_events') ? "(SELECT COUNT(*) FROM audio_play_events ape WHERE ape.user_id = u.id AND ape.created_at >= ?) AS audio_events, (SELECT COALESCE(SUM(ape.seconds_played),0) FROM audio_play_events ape WHERE ape.user_id = u.id AND ape.created_at >= ?) AS audio_seconds" : "0 AS audio_events, 0 AS audio_seconds";
  $selectVideo = sf_admin_table_exists('video_watch_events') ? "(SELECT COUNT(*) FROM video_watch_events vwe WHERE vwe.user_id = u.id AND vwe.created_at >= ?) AS video_events, (SELECT COALESCE(SUM(vwe.seconds_watched),0) FROM video_watch_events vwe WHERE vwe.user_id = u.id AND vwe.created_at >= ?) AS video_seconds" : "0 AS video_events, 0 AS video_seconds";
  $selectPlaylists = sf_admin_table_exists('playlists') ? "(SELECT COUNT(*) FROM playlists p WHERE p.user_id = u.id) AS playlist_count" : "0 AS playlist_count";
  $selectPlan = sf_admin_table_exists('user_subscriptions') && sf_admin_table_exists('subscription_plans') ? "(SELECT sp.name FROM user_subscriptions us LEFT JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.user_id = u.id ORDER BY FIELD(us.status,'active','trialing','past_due','canceled','expired'), us.current_period_end DESC, us.id DESC LIMIT 1) AS plan_name, (SELECT us.status FROM user_subscriptions us WHERE us.user_id = u.id ORDER BY FIELD(us.status,'active','trialing','past_due','canceled','expired'), us.current_period_end DESC, us.id DESC LIMIT 1) AS subscription_status" : "NULL AS plan_name, NULL AS subscription_status";
  $sql = "SELECT u.id, u.email, u.display_name, u.role, u.status, u.created_at, " . (sf_admin_column_exists('users', 'last_login_at') ? 'u.last_login_at' : 'NULL AS last_login_at') . ", {$selectAudio}, {$selectVideo}, {$selectPlaylists}, {$selectPlan} FROM users u ORDER BY (audio_events + video_events) DESC, u.created_at DESC LIMIT " . (int)$limit;
  $params = [];
  if (sf_admin_table_exists('audio_play_events')) {
    $params[] = sf_analytics_since($days);
    $params[] = sf_analytics_since($days);
  }
  if (sf_admin_table_exists('video_watch_events')) {
    $params[] = sf_analytics_since($days);
    $params[] = sf_analytics_since($days);
  }
  return sf_admin_fetch_all($sql, $params);
}

function sf_analytics_daily_activity(int $days): array {
  $days = min(max(1, $days), 365);
  $map = [];
  for ($i = $days - 1; $i >= 0; $i--) {
    $key = (new DateTimeImmutable('-' . $i . ' days'))->format('Y-m-d');
    $map[$key] = ['day' => $key, 'audio' => 0, 'video' => 0, 'members' => 0];
  }
  if (sf_admin_table_exists('audio_play_events')) {
    $rows = sf_admin_fetch_all('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM audio_play_events WHERE created_at >= ? GROUP BY DATE(created_at)', [sf_analytics_since($days)]);
    foreach ($rows as $row) {
      if (isset($map[$row['day']])) { $map[$row['day']]['audio'] = (int)$row['total']; }
    }
  }
  if (sf_admin_table_exists('video_watch_events')) {
    $rows = sf_admin_fetch_all('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM video_watch_events WHERE created_at >= ? GROUP BY DATE(created_at)', [sf_analytics_since($days)]);
    foreach ($rows as $row) {
      if (isset($map[$row['day']])) { $map[$row['day']]['video'] = (int)$row['total']; }
    }
  }
  if (sf_admin_table_exists('users')) {
    $rows = sf_admin_fetch_all('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM users WHERE created_at >= ? GROUP BY DATE(created_at)', [sf_analytics_since($days)]);
    foreach ($rows as $row) {
      if (isset($map[$row['day']])) { $map[$row['day']]['members'] = (int)$row['total']; }
    }
  }
  return array_values($map);
}

function sf_analytics_bars(array $rows, string $key, string $labelKey = 'day'): void {
  $max = 0;
  foreach ($rows as $row) {
    $max = max($max, (int)($row[$key] ?? 0));
  }
  echo '<div class="sf-analytics-bars">';
  foreach ($rows as $row) {
    $value = (int)($row[$key] ?? 0);
    $height = $max > 0 ? max(4, (int)round(($value / $max) * 100)) : 4;
    echo '<div class="sf-analytics-bar" title="' . sf_admin_h(($row[$labelKey] ?? '') . ': ' . $value) . '"><span style="height:' . $height . '%"></span><small>' . sf_admin_h(substr((string)($row[$labelKey] ?? ''), -5)) . '</small></div>';
  }
  echo '</div>';
}
?>
