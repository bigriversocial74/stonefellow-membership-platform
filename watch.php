<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/membership.php';

$slug = $_GET['slug'] ?? 'first-to-fall-full-episode';
$currentVideo = sf_video_by_slug($videoCatalog, $slug) ?? $videoCatalog[0];
$currentEpisode = !empty($currentVideo['episode_slug']) ? sf_episode_by_slug($episodes, $currentVideo['episode_slug']) : null;
$member = sf_member_snapshot();
$requiredLevel = $currentVideo['access_level'] ?? 'subscriber';
$canWatch = sf_access_allows($requiredLevel, $member['access_level']);
$isPublished = ($currentVideo['status'] ?? 'draft') === 'published';
$streamSource = $canWatch && $isPublished ? ($currentVideo['stream_src'] ?? '') : ($currentVideo['preview_src'] ?? '');

$pageTitle = 'Watch ' . ($currentVideo['title'] ?? 'Stonefellow');
$pageDescription = 'Stonefellow video player page with access checks, watch progress, and episode tracking.';
$pageClass = 'watch-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-watch-shell" data-sf-video-page data-video-id="<?= (int)$currentVideo['id'] ?>" data-episode-id="<?= (int)($currentVideo['episode_id'] ?? 0) ?>" data-required-access="<?= htmlspecialchars($requiredLevel) ?>">
  <header class="sf-watch-topbar">
    <div>
      <a href="<?= sf_url(!empty($currentVideo['episode_slug']) ? 'episode.php?slug=' . urlencode($currentVideo['episode_slug']) : 'episodes.php') ?>">← Back</a>
      <span><?= htmlspecialchars($currentVideo['video_type'] === 'trailer' ? 'Public trailer' : sf_access_label($requiredLevel)) ?></span>
    </div>
    <div>
      <a href="<?= sf_url('member.php') ?>">Member Home</a>
      <a href="<?= sf_url('subscribe.php') ?>">Subscribe</a>
    </div>
  </header>

  <section class="sf-video-stage">
    <video class="sf-watch-video" controls preload="metadata" poster="<?= sf_asset($currentVideo['poster']) ?>" data-sf-video-player>
      <?php if ($streamSource !== ''): ?>
        <source src="<?= sf_asset($streamSource) ?>" type="video/mp4">
      <?php endif; ?>
      Your browser does not support the video tag.
    </video>

    <?php if (!$canWatch && $requiredLevel !== 'public'): ?>
      <div class="sf-video-lock-overlay">
        <span>Membership Required</span>
        <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
        <p>Subscribe to unlock the full episode, member playlists, watch history, and resume tracking.</p>
        <a href="<?= sf_url('subscribe.php') ?>">Unlock Access</a>
      </div>
    <?php elseif (!$isPublished): ?>
      <div class="sf-video-lock-overlay">
        <span>Coming Soon</span>
        <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
        <p>This video is already registered in the catalog and access system. Publish it from the admin catalog when ready.</p>
        <a href="<?= sf_url('episodes.php') ?>">Back to Episodes</a>
      </div>
    <?php elseif ($streamSource === ''): ?>
      <div class="sf-video-lock-overlay">
        <span>Source Needed</span>
        <h1><?= htmlspecialchars($currentVideo['title']) ?></h1>
        <p>The watch page is wired. Add the MP4 file at the expected asset path or update the database video file path.</p>
        <a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">View SQL Map</a>
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
        <span data-sf-video-save-state>Tracking ready</span>
      </div>
    </article>

    <aside class="sf-member-panel sf-watch-progress-card">
      <span class="sf-panel-eyebrow">Resume Tracking</span>
      <h2 data-sf-resume-label>0:00 saved</h2>
      <div class="sf-wide-progress"><i data-sf-video-progress-bar style="width:0%"></i></div>
      <p>Progress events send to `api/video-track.php` and update video plus episode progress when a member session exists.</p>
    </aside>
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
