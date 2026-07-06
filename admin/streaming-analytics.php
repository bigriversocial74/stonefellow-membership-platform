<?php
$pageTitle = 'Streaming Analytics v2';
$pageDescription = 'Stonefellow streaming analytics v2 for engagement, library saves, conversion, revenue, and top content.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/analytics_v2.php';
require __DIR__ . '/../includes/header.php';
$days = sf_analytics_days();
$snapshot = sf_analytics_v2_snapshot($days);
$scorecards = sf_analytics_v2_stage_score($snapshot);
sf_admin_shell_start('Streaming Analytics v2', 'Engagement intelligence', 'Measure stream quality, member conversion, library saves, top content, and revenue per member.', 'streaming-analytics');
sf_analytics_range_tabs($days);
?>
<section class="sf-admin-card-grid sf-analytics-card-grid">
  <?php foreach ($scorecards as $card): ?>
    <?php sf_analytics_metric_card($card['label'], (string)$card['value'], (string)$card['note']); ?>
  <?php endforeach; ?>
</section>
<section class="sf-admin-two-col">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming</span><h2>Daily audio + video events</h2></div><a href="<?= sf_analytics_url('admin/analytics.php',$days) ?>">Dashboard v1</a></div><?php sf_analytics_bars($snapshot['daily'], 'audio'); ?><div style="height:12px"></div><?php sf_analytics_bars($snapshot['daily'], 'video'); ?></article>
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Library</span><h2>Member saves</h2></div><a href="<?= sf_url('admin/search-discovery.php') ?>">Discovery</a></div><div class="sf-admin-roadmap"><div><span><?= (int)$snapshot['library']['items'] ?></span><strong>Total saves</strong><p>Library records touched in range.</p></div><div><span><?= (int)$snapshot['library']['watchlist'] ?></span><strong>Watchlist</strong><p>Videos and episodes queued.</p></div><div><span><?= (int)$snapshot['library']['liked'] ?></span><strong>Liked</strong><p>Favorite tracks and moments.</p></div><div><span><?= (int)$snapshot['library']['completed'] ?></span><strong>Completed</strong><p>Finished content.</p></div></div></article>
</section>
<section class="sf-admin-two-col">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audio</span><h2>Top songs</h2></div><a href="<?= sf_analytics_url('admin/audio-analytics.php',$days) ?>">Audio Report</a></div><div class="sf-admin-list"><?php foreach ($snapshot['top_songs'] as $song): ?><a class="sf-admin-list-row" href="<?= sf_url('admin/music-songs.php?edit='.(int)($song['song_id']??0)) ?>"><strong><?= sf_admin_h($song['title'] ?? 'Song') ?></strong><span><?= sf_analytics_time((int)($song['seconds'] ?? 0)) ?></span><em><?= number_format((int)($song['events'] ?? 0)) ?> events · <?= number_format((int)($song['listeners'] ?? 0)) ?> listeners</em></a><?php endforeach; ?></div></article>
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video</span><h2>Top videos</h2></div><a href="<?= sf_analytics_url('admin/video-analytics.php',$days) ?>">Video Report</a></div><div class="sf-admin-list"><?php foreach ($snapshot['top_videos'] as $video): ?><a class="sf-admin-list-row" href="<?= sf_url('admin/videos.php?edit='.(int)($video['video_id']??0)) ?>"><strong><?= sf_admin_h($video['title'] ?? 'Video') ?></strong><span><?= sf_analytics_time((int)($video['seconds'] ?? 0)) ?></span><em><?= number_format((int)($video['events'] ?? 0)) ?> events · <?= number_format((int)($video['viewers'] ?? 0)) ?> viewers</em></a><?php endforeach; ?></div></article>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">API</span><h2>Analytics summary endpoint</h2></div><a href="<?= sf_url('api/analytics-summary.php?days='.$days) ?>">Open JSON</a></div><p class="sf-admin-copy">The v2 endpoint is ready for future dashboards, investor snapshots, and mobile/admin widgets.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
