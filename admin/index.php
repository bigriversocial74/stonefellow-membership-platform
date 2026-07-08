<?php
$pageTitle = 'Admin Dashboard';
$pageDescription = 'Stonefellow operating dashboard for sales, customers, streaming, music, comments, and fan engagement.';
$pageClass = 'membership-page admin-catalog-page admin-dashboard-page';
require __DIR__ . '/../includes/admin_catalog.php';

function sf_dash_money(int $cents): string { return '$' . number_format($cents / 100, 2); }
function sf_dash_int($value): string { return number_format((int)($value ?? 0)); }
function sf_dash_minutes($seconds): string { $seconds = (int)($seconds ?? 0); return $seconds > 0 ? number_format((int)round($seconds / 60)) . ' min' : '0 min'; }
function sf_dash_snip($value, int $length = 120): string { $value = trim((string)$value); if (strlen($value) <= $length) return $value; return rtrim(substr($value, 0, $length - 1)) . '…'; }
function sf_dash_count(string $table, string $where = '1=1', array $params = []): int { if (!sf_admin_table_exists($table)) return 0; $row = sf_admin_fetch_one('SELECT COUNT(*) AS total FROM `' . str_replace('`','',$table) . '` WHERE ' . $where, $params); return (int)($row['total'] ?? 0); }
function sf_dash_scalar(string $table, string $expression, string $where = '1=1', array $params = []): int { if (!sf_admin_table_exists($table)) return 0; $row = sf_admin_fetch_one('SELECT COALESCE(' . $expression . ', 0) AS total FROM `' . str_replace('`','',$table) . '` WHERE ' . $where, $params); return (int)($row['total'] ?? 0); }
function sf_dash_top_watched_episode(): array {
  if (sf_admin_table_exists('video_watch_events') && sf_admin_table_exists('episodes')) {
    $row = sf_admin_fetch_one("SELECT e.title, e.episode_number, COUNT(vwe.id) AS views, COALESCE(SUM(vwe.seconds_watched),0) AS seconds_total FROM video_watch_events vwe INNER JOIN episodes e ON e.id = vwe.episode_id WHERE vwe.episode_id IS NOT NULL GROUP BY e.id, e.title, e.episode_number ORDER BY views DESC, seconds_total DESC LIMIT 1");
    if ($row) return ['title'=>$row['title'] ?? 'No episode yet','meta'=>'Episode ' . (int)($row['episode_number'] ?? 0),'value'=>sf_dash_int($row['views'] ?? 0) . ' watches','sub'=>sf_dash_minutes($row['seconds_total'] ?? 0) . ' watched'];
  }
  if (sf_admin_table_exists('user_episode_progress') && sf_admin_table_exists('episodes')) {
    $row = sf_admin_fetch_one("SELECT e.title, e.episode_number, COUNT(uep.id) AS views, COALESCE(SUM(uep.total_seconds_watched),0) AS seconds_total FROM user_episode_progress uep INNER JOIN episodes e ON e.id = uep.episode_id GROUP BY e.id, e.title, e.episode_number ORDER BY views DESC, seconds_total DESC LIMIT 1");
    if ($row) return ['title'=>$row['title'] ?? 'No episode yet','meta'=>'Episode ' . (int)($row['episode_number'] ?? 0),'value'=>sf_dash_int($row['views'] ?? 0) . ' member views','sub'=>sf_dash_minutes($row['seconds_total'] ?? 0) . ' watched'];
  }
  $fallback = sf_admin_table_exists('episodes') ? sf_admin_fetch_one("SELECT title, episode_number FROM episodes WHERE status = 'published' ORDER BY season_number ASC, episode_number ASC LIMIT 1") : null;
  return ['title'=>$fallback['title'] ?? 'No episode data yet','meta'=>isset($fallback['episode_number']) ? 'Episode ' . (int)$fallback['episode_number'] : 'Waiting for tracking','value'=>'0 watches','sub'=>'Tracking starts after views are recorded'];
}
function sf_dash_top_song(): array {
  if (sf_admin_table_exists('audio_play_events') && sf_admin_table_exists('songs')) {
    $row = sf_admin_fetch_one("SELECT s.title, COUNT(ape.id) AS plays, COALESCE(SUM(ape.seconds_played),0) AS seconds_total FROM audio_play_events ape INNER JOIN songs s ON s.id = ape.song_id GROUP BY s.id, s.title ORDER BY plays DESC, seconds_total DESC LIMIT 1");
    if ($row) return ['title'=>$row['title'] ?? 'No song yet','meta'=>'Most listened song','value'=>sf_dash_int($row['plays'] ?? 0) . ' plays','sub'=>sf_dash_minutes($row['seconds_total'] ?? 0) . ' listened'];
  }
  if (sf_admin_table_exists('user_play_history') && sf_admin_table_exists('songs')) {
    $row = sf_admin_fetch_one("SELECT s.title, COUNT(uph.id) AS plays, COALESCE(SUM(uph.seconds_played),0) AS seconds_total FROM user_play_history uph INNER JOIN songs s ON s.id = uph.song_id GROUP BY s.id, s.title ORDER BY plays DESC, seconds_total DESC LIMIT 1");
    if ($row) return ['title'=>$row['title'] ?? 'No song yet','meta'=>'Most listened song','value'=>sf_dash_int($row['plays'] ?? 0) . ' plays','sub'=>sf_dash_minutes($row['seconds_total'] ?? 0) . ' listened'];
  }
  $fallback = sf_admin_table_exists('songs') ? sf_admin_fetch_one("SELECT title FROM songs WHERE status = 'published' ORDER BY is_featured DESC, track_number ASC LIMIT 1") : null;
  return ['title'=>$fallback['title'] ?? 'No song data yet','meta'=>'Most listened song','value'=>'0 plays','sub'=>'Tracking starts after audio plays'];
}
function sf_dash_top_album(): array {
  if (sf_admin_table_exists('audio_play_events') && sf_admin_table_exists('songs') && sf_admin_table_exists('albums')) {
    $row = sf_admin_fetch_one("SELECT a.title, COUNT(ape.id) AS plays, COALESCE(SUM(ape.seconds_played),0) AS seconds_total FROM audio_play_events ape INNER JOIN songs s ON s.id = ape.song_id LEFT JOIN albums a ON a.id = s.album_id GROUP BY a.id, a.title ORDER BY plays DESC, seconds_total DESC LIMIT 1");
    if ($row) return ['title'=>$row['title'] ?? 'Unassigned Album','meta'=>'Top album','value'=>sf_dash_int($row['plays'] ?? 0) . ' song plays','sub'=>sf_dash_minutes($row['seconds_total'] ?? 0) . ' listened'];
  }
  if (sf_admin_table_exists('user_play_history') && sf_admin_table_exists('songs') && sf_admin_table_exists('albums')) {
    $row = sf_admin_fetch_one("SELECT a.title, COUNT(uph.id) AS plays, COALESCE(SUM(uph.seconds_played),0) AS seconds_total FROM user_play_history uph INNER JOIN songs s ON s.id = uph.song_id LEFT JOIN albums a ON a.id = s.album_id GROUP BY a.id, a.title ORDER BY plays DESC, seconds_total DESC LIMIT 1");
    if ($row) return ['title'=>$row['title'] ?? 'Unassigned Album','meta'=>'Top album','value'=>sf_dash_int($row['plays'] ?? 0) . ' song plays','sub'=>sf_dash_minutes($row['seconds_total'] ?? 0) . ' listened'];
  }
  $fallback = sf_admin_table_exists('albums') ? sf_admin_fetch_one("SELECT title FROM albums WHERE status = 'published' ORDER BY release_date DESC, id DESC LIMIT 1") : null;
  return ['title'=>$fallback['title'] ?? 'No album data yet','meta'=>'Top album','value'=>'0 plays','sub'=>'Tracking starts after audio plays'];
}
function sf_dash_biggest_fan(): array {
  if (sf_admin_table_exists('member_engagement_scores') && sf_admin_table_exists('users')) {
    $row = sf_admin_fetch_one("SELECT u.display_name, u.email, mes.score, mes.comment_count, mes.reaction_count, mes.stream_count FROM member_engagement_scores mes INNER JOIN users u ON u.id = mes.user_id ORDER BY mes.score DESC, mes.last_engaged_at DESC LIMIT 1");
    if ($row) return ['title'=>trim((string)($row['display_name'] ?? '')) ?: ($row['email'] ?? 'Member'), 'meta'=>'Biggest fan', 'value'=>sf_dash_int($row['score'] ?? 0) . ' score', 'sub'=>sf_dash_int(($row['comment_count'] ?? 0) + ($row['reaction_count'] ?? 0) + ($row['stream_count'] ?? 0)) . ' tracked actions'];
  }
  if (sf_admin_table_exists('fan_comments') && sf_admin_table_exists('users')) {
    $row = sf_admin_fetch_one("SELECT u.display_name, u.email, COUNT(fc.id) AS comments FROM fan_comments fc INNER JOIN users u ON u.id = fc.user_id GROUP BY u.id, u.display_name, u.email ORDER BY comments DESC, MAX(fc.created_at) DESC LIMIT 1");
    if ($row) return ['title'=>trim((string)($row['display_name'] ?? '')) ?: ($row['email'] ?? 'Member'), 'meta'=>'Biggest fan', 'value'=>sf_dash_int($row['comments'] ?? 0) . ' comments', 'sub'=>'Based on comment activity'];
  }
  return ['title'=>'No fan data yet','meta'=>'Biggest fan','value'=>'0 score','sub'=>'Engagement appears after member activity'];
}

$totalSales = sf_dash_scalar('orders', 'SUM(total_cents)', "status IN ('paid','fulfilled')");
$totalOrders = sf_dash_count('orders', "status IN ('paid','fulfilled')");
$avgOrder = $totalOrders > 0 ? (int)round($totalSales / $totalOrders) : 0;
$newCustomers = sf_dash_count('users', "role <> 'admin' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$totalCustomers = sf_dash_count('users', "role <> 'admin'");
$activeMembers = sf_dash_count('user_subscriptions', "status IN ('active','trialing')");
$totalComments = sf_dash_count('fan_comments');
$approvedComments = sf_dash_count('fan_comments', "status = 'approved'");
$pendingComments = sf_dash_count('fan_comments', "status = 'pending'");
$commentReactions = sf_dash_scalar('fan_comments', 'SUM(reaction_count)');
$topEpisode = sf_dash_top_watched_episode();
$topSong = sf_dash_top_song();
$topAlbum = sf_dash_top_album();
$biggestFan = sf_dash_biggest_fan();
$topComments = sf_admin_table_exists('fan_comments') ? sf_admin_fetch_all("SELECT fc.body, fc.reaction_count, fc.content_type, fc.status, fc.created_at, u.display_name, u.email FROM fan_comments fc LEFT JOIN users u ON u.id = fc.user_id WHERE fc.status IN ('approved','pending') ORDER BY fc.reaction_count DESC, fc.created_at DESC LIMIT 5") : [];
$recentOrders = sf_admin_table_exists('orders') ? sf_admin_fetch_all("SELECT order_number, status, total_cents, customer_email, shipping_name, created_at FROM orders ORDER BY created_at DESC LIMIT 5") : [];
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Dashboard', 'Stonefellow Admin Dashboard', 'Live operating view for revenue, customers, streaming performance, music activity, comments, and fan engagement.', 'index');
?>
<style>
.admin-dashboard-page .sf-dashboard-hero{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(280px,.55fr);gap:16px;margin-bottom:18px}.admin-dashboard-page .sf-dashboard-hero-card{padding:22px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(135deg,rgba(232,198,127,.13),rgba(255,255,255,.035));box-shadow:0 20px 56px rgba(0,0,0,.28)}.admin-dashboard-page .sf-dashboard-hero-card h1{margin:6px 0 8px;color:#fff;font-size:clamp(34px,5vw,58px);letter-spacing:-.05em}.admin-dashboard-page .sf-dashboard-hero-card p{max-width:760px;color:rgba(255,255,255,.68)}.admin-dashboard-page .sf-dashboard-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.admin-dashboard-page .sf-dashboard-kpi{min-height:126px;padding:16px;border:1px solid rgba(232,198,127,.15);border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025))}.admin-dashboard-page .sf-dashboard-kpi span,.admin-dashboard-page .sf-dashboard-feature span{display:block;color:rgba(232,198,127,.82);font-size:11px;font-weight:950;letter-spacing:.1em;text-transform:uppercase}.admin-dashboard-page .sf-dashboard-kpi strong{display:block;margin-top:10px;color:#fff;font-size:clamp(26px,3vw,38px);line-height:1}.admin-dashboard-page .sf-dashboard-kpi small,.admin-dashboard-page .sf-dashboard-feature small{display:block;margin-top:8px;color:rgba(255,255,255,.62);line-height:1.45}.admin-dashboard-page .sf-dashboard-feature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.admin-dashboard-page .sf-dashboard-feature{min-height:176px;padding:16px;border:1px solid rgba(232,198,127,.14);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.022))}.admin-dashboard-page .sf-dashboard-feature strong{display:block;margin:10px 0;color:#fff;font-size:22px;line-height:1.05}.admin-dashboard-page .sf-dashboard-two-col{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;margin-bottom:18px}.admin-dashboard-page .sf-dashboard-list{display:grid;gap:10px}.admin-dashboard-page .sf-dashboard-list-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;padding:13px;border:1px solid rgba(255,255,255,.08);border-radius:15px;background:rgba(0,0,0,.15)}.admin-dashboard-page .sf-dashboard-list-row strong{color:#fff}.admin-dashboard-page .sf-dashboard-list-row small{display:block;margin-top:4px;color:rgba(255,255,255,.58);line-height:1.45}.admin-dashboard-page .sf-dashboard-list-row span{color:#f5d98d;font-weight:950;white-space:nowrap}.admin-dashboard-page .sf-dashboard-actions{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.admin-dashboard-page .sf-dashboard-actions a{padding:14px;border:1px solid rgba(232,198,127,.16);border-radius:16px;background:rgba(255,255,255,.035);color:#f5d98d;font-weight:950;text-align:center}@media(max-width:1180px){.admin-dashboard-page .sf-dashboard-kpi-grid,.admin-dashboard-page .sf-dashboard-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-dashboard-page .sf-dashboard-hero,.admin-dashboard-page .sf-dashboard-two-col{grid-template-columns:1fr}.admin-dashboard-page .sf-dashboard-actions{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:680px){.admin-dashboard-page .sf-dashboard-kpi-grid,.admin-dashboard-page .sf-dashboard-feature-grid,.admin-dashboard-page .sf-dashboard-actions{grid-template-columns:1fr}}
</style>

<section class="sf-dashboard-hero">
  <div class="sf-dashboard-hero-card"><span class="sf-panel-eyebrow">Platform Snapshot</span><h1>Today’s operating dashboard</h1><p>Revenue, customers, streaming activity, music performance, fan comments, and high-value engagement are now surfaced first instead of internal build notes.</p></div>
  <div class="sf-dashboard-hero-card"><span class="sf-panel-eyebrow">Biggest Fan</span><h1><?= sf_admin_h($biggestFan['title']) ?></h1><p><strong><?= sf_admin_h($biggestFan['value']) ?></strong><br><?= sf_admin_h($biggestFan['sub']) ?></p></div>
</section>

<section class="sf-dashboard-kpi-grid">
  <div class="sf-dashboard-kpi"><span>Total Sales</span><strong><?= sf_dash_money($totalSales) ?></strong><small><?= sf_dash_int($totalOrders) ?> paid/fulfilled orders · <?= sf_dash_money($avgOrder) ?> average order.</small></div>
  <div class="sf-dashboard-kpi"><span>New Customers</span><strong><?= sf_dash_int($newCustomers) ?></strong><small>Last 30 days · <?= sf_dash_int($totalCustomers) ?> total customer accounts.</small></div>
  <div class="sf-dashboard-kpi"><span>Active Members</span><strong><?= sf_dash_int($activeMembers) ?></strong><small>Active or trialing subscriptions.</small></div>
  <div class="sf-dashboard-kpi"><span>Total Comments</span><strong><?= sf_dash_int($totalComments) ?></strong><small><?= sf_dash_int($approvedComments) ?> approved · <?= sf_dash_int($pendingComments) ?> pending · <?= sf_dash_int($commentReactions) ?> reactions.</small></div>
</section>

<section class="sf-dashboard-feature-grid">
  <?php foreach ([$topEpisode, $topSong, $topAlbum, $biggestFan] as $feature): ?>
    <article class="sf-dashboard-feature"><span><?= sf_admin_h($feature['meta']) ?></span><strong><?= sf_admin_h($feature['title']) ?></strong><small><?= sf_admin_h($feature['value']) ?></small><small><?= sf_admin_h($feature['sub']) ?></small></article>
  <?php endforeach; ?>
</section>

<section class="sf-dashboard-two-col">
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Fan Community</span><h2>Top Comments</h2></div><a href="<?= sf_url('admin/engagement-analytics.php') ?>">Engagement</a></div><div class="sf-dashboard-list"><?php foreach ($topComments as $comment): ?><div class="sf-dashboard-list-row"><div><strong><?= sf_admin_h(trim((string)($comment['display_name'] ?? '')) ?: ($comment['email'] ?? 'Fan')) ?></strong><small><?= sf_admin_h(sf_dash_snip($comment['body'] ?? '', 140)) ?></small><small><?= sf_admin_h($comment['content_type'] ?? 'content') ?> · <?= sf_admin_h($comment['status'] ?? '') ?> · <?= sf_admin_h($comment['created_at'] ?? '') ?></small></div><span><?= sf_dash_int($comment['reaction_count'] ?? 0) ?> reactions</span></div><?php endforeach; ?><?php if (!$topComments): ?><div class="sf-dashboard-list-row"><div><strong>No comments yet</strong><small>Fan comments will appear here once members begin posting.</small></div><span>0</span></div><?php endif; ?></div></div>
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Commerce</span><h2>Recent Orders</h2></div><a href="<?= sf_url('admin/orders.php') ?>">Orders</a></div><div class="sf-dashboard-list"><?php foreach ($recentOrders as $order): ?><div class="sf-dashboard-list-row"><div><strong><?= sf_admin_h($order['order_number'] ?? 'Order') ?></strong><small><?= sf_admin_h($order['shipping_name'] ?: ($order['customer_email'] ?? 'Customer')) ?></small><small><?= sf_admin_h($order['status'] ?? '') ?> · <?= sf_admin_h($order['created_at'] ?? '') ?></small></div><span><?= sf_dash_money((int)($order['total_cents'] ?? 0)) ?></span></div><?php endforeach; ?><?php if (!$recentOrders): ?><div class="sf-dashboard-list-row"><div><strong>No orders yet</strong><small>Paid and recent merch orders will appear here.</small></div><span><?= sf_dash_money(0) ?></span></div><?php endif; ?></div></div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Quick Actions</span><h2>Manage the platform</h2></div><span class="sf-admin-mini-pill">Live Admin</span></div><div class="sf-dashboard-actions"><a href="<?= sf_url('admin/members.php') ?>">Members</a><a href="<?= sf_url('admin/orders.php') ?>">Orders</a><a href="<?= sf_url('admin/revenue-dashboard.php') ?>">Revenue</a><a href="<?= sf_url('admin/engagement-analytics.php') ?>">Engagement</a><a href="<?= sf_url('admin/music.php') ?>">Media</a></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
