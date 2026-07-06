<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/media_delivery.php';

$slug = $_GET['slug'] ?? 'first-to-fall-full-episode';
$currentVideo = sf_video_by_slug($videoCatalog, $slug) ?? $videoCatalog[0];
$currentEpisode = !empty($currentVideo['episode_slug']) ? sf_episode_by_slug($episodes, $currentVideo['episode_slug']) : null;
$member = sf_member_snapshot();
$requiredLevel = $currentVideo['access_level'] ?? 'subscriber';
$canWatch = sf_access_allows($requiredLevel, $member['access_level']);
$isPublished = ($currentVideo['status'] ?? 'draft') === 'published';
$playback = sf_media_video_playback($currentVideo, $canWatch && $isPublished);
$playbackReady = $playback['url'] !== '' && !empty($playback['exists']);
$nextVideo = sf_media_video_next($videoCatalog, $currentVideo);
$nextUrl = $nextVideo ? sf_url('watch.php?slug=' . urlencode($nextVideo['slug'])) : '';
$chapters = [
  ['time' => '0:00', 'label' => 'Cold open'],
  ['time' => '8:00', 'label' => 'Band conflict'],
  ['time' => '18:00', 'label' => 'Studio scene'],
  ['time' => '32:00', 'label' => 'Showdown'],
  ['time' => '44:00', 'label' => 'Final beat'],
];

$pageTitle = 'Watch ' . ($currentVideo['title'] ?? 'Stonefellow');
$pageDescription = 'Stonefellow secure video player page with signed playback, access checks, watch progress, and next episode controls.';
$pageClass = 'watch-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-watch-shell" data-sf-video-page data-video-id="<?= (int)$currentVideo['id'] ?>" data-episode-id="<?= (int)($currentVideo['episode_id'] ?? 0) ?>" data-required-access="<?= htmlspecialchars($requiredLevel) ?>" data-next-url="<?= htmlspecialchars($nextUrl) ?>">
  <header class="sf-watch-topbar">
    <div>
      <a href="<?= sf_url(!empty($currentVideo['episode_slug']) ? 'episode.php?slug=' . urlencode($currentVideo['episode_slug']) : 'episodes.php') ?>">← Back</a>
      <span><?= htmlspecialchars($currentVideo['video_type'] === 'trailer' ? 'Public trailer' : sf_access_label($requiredLevel)) ?></span>
      <span>Signed <?= htmlspecialchars($playback['file_type']) ?></span>
    </div>
    <div>
      <a href="<?= sf_url('member.php') ?>">Member Home</a>
      <a href="<?= sf_url('subscribe.php') ?>">Subscribe</a>
    </div>
  </header>

  <section class="sf-video-stage">
    <video class="sf-watch-video" controls preload="metadata" poster="<?= sf_asset($currentVideo['poster']) ?>" data-sf-video-player>
      <?php if ($playbackReady): ?>
        <source src="<?= htmlspecialchars($playback['url']) ?>" type="<?= htmlspecialchars($playback['mime_type']) ?>">
      <?php endif; ?>
      Your browser does not support the video tag.
    </video>

    <?php if (!$canWatch && $requiredLevel !== 'public'): ?>
      <div class="sf-video-lock-overlay">
        <span>Membership Required</span>
        <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
        <p>You are seeing the preview path when available. Subscribe to unlock signed full-episode streaming, progress sync, and member library features.</p>
        <a href="<?= sf_url('subscribe.php') ?>">Unlock Access</a>
      </div>
    <?php elseif (!$isPublished): ?>
      <div class="sf-video-lock-overlay">
        <span>Coming Soon</span>
        <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
        <p>This video is registered in the catalog and access system. Publish it from the admin catalog when ready.</p>
        <a href="<?= sf_url('episodes.php') ?>">Back to Episodes</a>
      </div>
    <?php elseif (!$playbackReady): ?>
      <div class="sf-video-lock-overlay">
        <span>Source Needed</span>
        <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
        <p>The secure watch page is ready. Add the media file for <code><?= htmlspecialchars($playback['file_path'] ?: 'stream source') ?></code> or update the video catalog source path.</p>
        <a href="<?= sf_url('admin/videos.php') ?>">Open Video Admin</a>
      </div>
    <?php endif; ?>
  </section>

  <section class="sf-watch-info-grid">
    <article class="sf-watch-main-copy">
      <span class="sf-panel-eyebrow"><?= htmlspecialchars($currentEpisode['number'] ?? str_replace('_', ' ', $currentVideo['video_type'])) ?></span>
      <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
      <p><?= htmlspecialchars($currentVideo['description'] ?? '') ?></p>
      <div class="sf-episode-meta-row">
        <span><?= htmlspecialchars($currentVideo['runtime']) ?></span>
        <span><?= htmlspecialchars(sf_access_label($requiredLevel)) ?></span>
        <span><?= $canWatch ? 'Full access' : 'Preview mode' ?></span>
        <span data-sf-video-save-state>Tracking ready</span>
      </div>
      <div class="sf-episode-action-row">
        <?php if ($nextVideo): ?><a class="sf-secondary-action" href="<?= htmlspecialchars($nextUrl) ?>">Next: <?= htmlspecialchars($nextVideo['title']) ?></a><?php endif; ?>
      </div>
    </article>

    <aside class="sf-member-panel sf-watch-progress-card">
      <span class="sf-panel-eyebrow">Resume Tracking</span>
      <h2 data-sf-resume-label>0:00 saved</h2>
      <div class="sf-wide-progress"><i data-sf-video-progress-bar style="width:0%"></i></div>
      <p>Playback uses signed URLs through <code>stream.php</code>. Progress events sync to <code>api/video-track.php</code>. The current browser resumes automatically when saved progress exists.</p>
    </aside>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Chapter Markers</span><h2>Episode timeline</h2></div></div>
    <div class="sf-video-card-grid">
      <?php foreach ($chapters as $chapter): ?>
        <article class="sf-video-card">
          <span><?= htmlspecialchars($chapter['time']) ?></span>
          <strong><?= htmlspecialchars($chapter['label']) ?></strong>
          <small>Chapter marker</small>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head">
      <div><span class="sf-panel-eyebrow">More to Watch</span><h2>Episode library</h2></div>
      <a href="<?= sf_url('episodes.php') ?>">Browse all</a>
    </div>
    <div class="sf-video-card-grid">
      <?php foreach (array_slice($videoCatalog, 0, 4) as $video): ?>
        <a class="sf-video-card" href="<?= sf_url('watch.php?slug=' . urlencode($video['slug'])) ?>">
          <img src="<?= sf_asset($video['poster']) ?>" alt="<?= htmlspecialchars($video['title']) ?> poster">
          <span><?= htmlspecialchars(sf_access_label($video['access_level'])) ?></span>
          <strong><?= htmlspecialchars($video['title']) ?></strong>
          <small><?= htmlspecialchars($video['runtime']) ?> · <?= htmlspecialchars(str_replace('_', ' ', $video['video_type'])) ?></small>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
