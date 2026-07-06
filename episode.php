<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/membership.php';
require_once __DIR__ . '/includes/posts.php';

$slug = $_GET['slug'] ?? 'first-to-fall';
$currentEpisode = sf_episode_by_slug($episodes, $slug);
$episodeVideo = sf_video_by_episode_slug($videoCatalog, $currentEpisode['slug'] ?? $slug, 'episode') ?? $videoCatalog[0];
$trailerVideo = sf_video_by_episode_slug($videoCatalog, $currentEpisode['slug'] ?? $slug, 'trailer') ?? $videoCatalog[1];
$relatedSongs = sf_songs_for_episode($catalogSongs, $currentEpisode['title'] ?? 'First to Fall');
$member = sf_member_snapshot();
$canWatch = sf_access_allows($episodeVideo['access_level'] ?? 'subscriber', $member['access_level']);

$pageTitle = ($currentEpisode['number'] ?? 'Episode') . ' — ' . ($currentEpisode['title'] ?? 'Stonefellow');
$pageDescription = $currentEpisode['description'] ?? 'Stonefellow episode page with watch access, member progress, soundtrack links, and fan comments.';
$pageClass = 'episode-detail-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell sf-episode-detail-shell">
  <section class="sf-episode-hero" style="--episode-hero:url('<?= sf_asset($episodeVideo['hero'] ?? $currentEpisode['image']) ?>')">
    <div class="sf-episode-hero-copy">
      <div class="sf-kicker-row"><span><?= htmlspecialchars($currentEpisode['number'] ?? 'Season 1') ?></span><span><?= htmlspecialchars(sf_access_label($episodeVideo['access_level'] ?? 'subscriber')) ?></span></div>
      <h1><?= htmlspecialchars($currentEpisode['title'] ?? 'Stonefellow Episode') ?></h1>
      <p><?= htmlspecialchars($currentEpisode['description'] ?? $episodeVideo['description'] ?? '') ?></p>
      <div class="sf-episode-meta-row"><span><?= htmlspecialchars($episodeVideo['runtime'] ?? $currentEpisode['runtime'] ?? '48 min') ?></span><span><?= htmlspecialchars($episodeVideo['status'] === 'published' ? 'Available now' : 'Coming soon') ?></span><span><?= htmlspecialchars($member['access_label']) ?></span><span><?= (int)sf_post_comment_count('episode', (int)($currentEpisode['id'] ?? 0), (string)($currentEpisode['slug'] ?? $slug)) ?> comments</span></div>
      <div class="sf-episode-action-row"><?php if ($canWatch && ($episodeVideo['status'] ?? '') === 'published'): ?><a class="sf-primary-action" href="<?= sf_url('watch.php?slug=' . urlencode($episodeVideo['slug'])) ?>">▶ Watch Episode</a><?php else: ?><a class="sf-primary-action" href="<?= sf_url('subscribe.php') ?>">Unlock Episode</a><?php endif; ?><a class="sf-secondary-action" href="<?= sf_url('watch.php?slug=' . urlencode($trailerVideo['slug'])) ?>">Watch Trailer</a><a class="sf-secondary-action" href="#fan-thread">Comments</a></div>
    </div>
    <div class="sf-episode-hero-card"><img src="<?= sf_asset($episodeVideo['poster'] ?? $currentEpisode['image']) ?>" alt="<?= htmlspecialchars($currentEpisode['title'] ?? 'Episode') ?> poster"><div class="sf-access-card"><strong><?= $canWatch ? 'Ready to watch' : 'Membership required' ?></strong><span><?= $canWatch ? 'Episode tracking and resume are enabled.' : 'Subscribe to unlock full episode playback.' ?></span></div></div>
  </section>
  <section class="sf-member-grid sf-episode-grid"><article class="sf-member-panel sf-progress-panel"><span class="sf-panel-eyebrow">Episode Tracking</span><h2>Continue from any device</h2><p>Watch events are sent to the tracking endpoint, then stored in video and episode progress tables when a member is signed in.</p><div class="sf-progress-stack"><div><strong>Watch progress</strong><span><?= (int)($episodeVideo['resume_percent'] ?? 0) ?>%</span></div><div class="sf-wide-progress"><i style="width:<?= (int)($episodeVideo['resume_percent'] ?? 0) ?>%"></i></div></div></article><article class="sf-member-panel sf-access-panel"><span class="sf-panel-eyebrow">Access Rule</span><h2><?= htmlspecialchars(sf_access_label($episodeVideo['access_level'] ?? 'subscriber')) ?></h2><p>This page checks the member access level before linking to the full watch screen. Public trailer access remains available for guests.</p><a href="<?= sf_url('member.php') ?>">Open member dashboard</a></article></section>
  <section class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Episode Videos</span><h2>Watch options</h2></div><a href="<?= sf_url('episodes.php') ?>">All Episodes</a></div><div class="sf-video-card-grid"><?php foreach ([$episodeVideo, $trailerVideo] as $video): ?><a class="sf-video-card" href="<?= sf_url('watch.php?slug=' . urlencode($video['slug'])) ?>"><img src="<?= sf_asset($video['poster']) ?>" alt="<?= htmlspecialchars($video['title']) ?> poster"><span><?= htmlspecialchars(sf_access_label($video['access_level'])) ?></span><strong><?= htmlspecialchars($video['title']) ?></strong><small><?= htmlspecialchars($video['runtime']) ?> · <?= htmlspecialchars(str_replace('_', ' ', $video['video_type'])) ?></small></a><?php endforeach; ?></div></section>
  <section class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Soundtrack Links</span><h2>Songs from this episode</h2></div><a href="<?= sf_url('player.php') ?>">Open Player</a></div><div class="sf-audio-list-panel"><?php foreach ($relatedSongs as $song): ?><a class="sf-audio-list-row" href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover"><span><?= htmlspecialchars($song['track']) ?></span><strong><?= htmlspecialchars($song['title']) ?></strong><em><?= htmlspecialchars($song['duration']) ?></em></a><?php endforeach; ?></div></section>
  <div id="fan-thread"><?php sf_inline_comment_widget('episode', (int)($currentEpisode['id'] ?? 0), (string)($currentEpisode['slug'] ?? $slug), 'Episode comments'); ?></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
