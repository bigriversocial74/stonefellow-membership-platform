<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/media_delivery.php';
require_once __DIR__ . '/includes/media_pipeline.php';
require_once __DIR__ . '/includes/posts.php';
require __DIR__ . '/includes/desertrio_theme.php';

$slug = $_GET['slug'] ?? 'first-to-fall-full-episode';
$currentVideo = sf_video_by_slug($videoCatalog, $slug) ?? $videoCatalog[0];
$currentEpisode = !empty($currentVideo['episode_slug']) ? sf_episode_by_slug($episodes, $currentVideo['episode_slug']) : null;
$member = sf_member_snapshot();
$requiredLevel = $currentVideo['access_level'] ?? 'subscriber';
$canWatch = sf_access_allows($requiredLevel, $member['access_level']);
$isPublished = ($currentVideo['status'] ?? 'draft') === 'published';
$playback = sf_media_video_playback($currentVideo, $canWatch && $isPublished);
$manifestObject = ($canWatch && $isPublished && !empty($currentVideo['id'])) ? sf_mp_ready_object('video', (int)$currentVideo['id'], 'manifest') : null;
if ($manifestObject) {
    $playback = [
        'file_type' => 'adaptive HLS',
        'file_path' => (string)$manifestObject['storage_key'],
        'exists' => true,
        'mime_type' => 'application/vnd.apple.mpegurl',
        'url' => sf_mp_manifest_url($manifestObject, sf_current_user_id(), 1800),
    ];
}
$playbackReady = $playback['url'] !== '' && !empty($playback['exists']);
$isHls = $playbackReady && $playback['mime_type'] === 'application/vnd.apple.mpegurl';
$nextVideo = sf_media_video_next($videoCatalog, $currentVideo);
$nextUrl = $nextVideo ? sf_url('watch.php?slug=' . urlencode($nextVideo['slug'])) : '';

$displayEpisode = $desertRioEpisodes[0];
foreach ($desertRioEpisodes as $candidate) {
    if (($candidate['slug'] ?? '') === ($currentVideo['episode_slug'] ?? '')) {
        $displayEpisode = $candidate;
        break;
    }
}
$displayTitle = ($currentVideo['video_type'] ?? '') === 'trailer'
    ? $displayEpisode['title'] . ' — Trailer'
    : $displayEpisode['title'];
$chapters = [
    ['time' => '0:00', 'label' => 'Cold Open'],
    ['time' => '8:00', 'label' => 'Poolside Arrival'],
    ['time' => '18:00', 'label' => 'Private Conversation'],
    ['time' => '32:00', 'label' => 'The Confrontation'],
    ['time' => '41:00', 'label' => 'Final Reveal'],
];

$pageTitle = 'Watch ' . $displayTitle;
$pageDescription = 'Secure DesertRio video playback with member access, progress tracking, next episode controls, and fan comments.';
$pageClass = 'watch-page membership-page desertrio-watch-template';
$pageExtraStyles = ['css/desertrio-video.css'];
require __DIR__ . '/includes/header.php';
?>

<section
  class="dr-watch-shell"
  data-sf-video-page
  data-video-id="<?= (int)$currentVideo['id'] ?>"
  data-episode-id="<?= (int)($currentVideo['episode_id'] ?? 0) ?>"
  data-required-access="<?= htmlspecialchars($requiredLevel, ENT_QUOTES, 'UTF-8') ?>"
  data-next-url="<?= htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') ?>"
>
  <header class="dr-watch-topbar">
    <div>
      <a href="<?= sf_url(!empty($currentVideo['episode_slug']) ? 'episode.php?slug=' . urlencode($currentVideo['episode_slug']) : 'episodes.php') ?>">← Back</a>
      <span><?= htmlspecialchars(($currentVideo['video_type'] ?? '') === 'trailer' ? 'Public Trailer' : sf_access_label($requiredLevel), ENT_QUOTES, 'UTF-8') ?></span>
      <span>Secure <?= htmlspecialchars($playback['file_type'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div>
      <a href="#fan-thread"><?= (int)sf_post_comment_count('video', (int)$currentVideo['id'], (string)$currentVideo['slug']) ?> Comments</a>
      <a href="<?= sf_url('member.php') ?>">Member Home</a>
      <a href="<?= sf_url('subscribe.php') ?>">Subscribe</a>
    </div>
  </header>

  <section class="dr-watch-stage">
    <video
      class="dr-watch-video"
      controls
      preload="metadata"
      poster="<?= sf_asset($displayEpisode['image']) ?>"
      data-sf-video-player
      <?= $isHls ? 'data-hls-source="' . htmlspecialchars($playback['url'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>
    >
      <?php if ($playbackReady && !$isHls): ?>
        <source src="<?= htmlspecialchars($playback['url'], ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($playback['mime_type'], ENT_QUOTES, 'UTF-8') ?>">
      <?php elseif ($playbackReady): ?>
        <source src="<?= htmlspecialchars($playback['url'], ENT_QUOTES, 'UTF-8') ?>" type="application/vnd.apple.mpegurl">
      <?php endif; ?>
      Your browser does not support the video tag.
    </video>

    <?php if (!$canWatch && $requiredLevel !== 'public'): ?>
      <div class="dr-watch-lock"><span>Membership Required</span><h1><?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>Subscribe to unlock secure full-episode streaming, resume tracking, and member library features.</p><a class="dr-button dr-button-primary" href="<?= sf_url('subscribe.php') ?>">Unlock Access</a></div>
    <?php elseif (!$isPublished): ?>
      <div class="dr-watch-lock"><span>Coming Soon</span><h1><?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>This video remains registered in the shared catalog and access system. Publish it from the admin catalog when ready.</p><a class="dr-button dr-button-primary" href="<?= sf_url('episodes.php') ?>">Back to Episodes</a></div>
    <?php elseif (!$playbackReady): ?>
      <div class="dr-watch-lock"><span>Source Needed</span><h1><?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>The secure watch page is ready. Upload and process the protected video master from the existing media pipeline.</p><?php if (($member['role'] ?? '') === 'admin'): ?><a class="dr-button dr-button-primary" href="<?= sf_url('admin/media-pipeline.php') ?>">Open Media Pipeline</a><?php else: ?><a class="dr-button dr-button-primary" href="<?= sf_url('episodes.php') ?>">Back to Episodes</a><?php endif; ?></div>
    <?php endif; ?>
  </section>

  <section class="dr-watch-info">
    <article class="dr-watch-main">
      <small><?= htmlspecialchars($displayEpisode['season'], ENT_QUOTES, 'UTF-8') ?></small>
      <h1><?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($displayEpisode['description'], ENT_QUOTES, 'UTF-8') ?></p>
      <div class="dr-watch-meta">
        <span><?= htmlspecialchars($displayEpisode['runtime'], ENT_QUOTES, 'UTF-8') ?></span>
        <span><?= htmlspecialchars(sf_access_label($requiredLevel), ENT_QUOTES, 'UTF-8') ?></span>
        <span><?= $canWatch ? 'Full Access' : 'Preview Mode' ?></span>
        <span data-sf-video-save-state>Tracking Ready</span>
      </div>
      <div class="dr-watch-actions">
        <?php if ($nextVideo): ?><a class="dr-button" href="<?= htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') ?>">Next Episode</a><?php endif; ?>
        <button class="dr-button" type="button" data-sf-library-save data-content-type="video" data-content-id="<?= (int)$currentVideo['id'] ?>" data-library-status="watchlist">Save to Watchlist</button>
        <button class="dr-button" type="button" data-sf-library-save data-content-type="video" data-content-id="<?= (int)$currentVideo['id'] ?>" data-library-status="saved">Save to Library</button>
        <a class="dr-button" href="#fan-thread">Comments</a>
      </div>
    </article>

    <aside class="dr-watch-progress">
      <small>Resume Tracking</small>
      <h2 data-sf-resume-label>0:00 Saved</h2>
      <div class="dr-progress-track"><i data-sf-video-progress-bar style="width:0%"></i></div>
      <p>Processed full episodes continue to use signed adaptive HLS delivery. Playback progress syncs to the member library.</p>
    </aside>
  </section>

  <section class="dr-watch-library">
    <header class="dr-section-head">
      <div><span></span><h2>Episode Timeline</h2><span></span></div>
      <p>Key moments from the episode.</p>
    </header>
    <div class="dr-chapter-grid">
      <?php foreach ($chapters as $chapter): ?>
        <article class="dr-chapter-card"><span><?= htmlspecialchars($chapter['time'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($chapter['label'], ENT_QUOTES, 'UTF-8') ?></strong></article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="dr-related-section dr-watch-library" aria-labelledby="dr-watch-more-title">
    <header class="dr-section-head">
      <div><span></span><h2 id="dr-watch-more-title">Up Next</h2><span></span></div>
      <p>Continue watching DesertRio.</p>
    </header>
    <div class="dr-related-grid">
      <?php foreach (array_slice($desertRioEpisodes, 0, 4) as $episode): ?>
        <a class="dr-related-card" href="<?= sf_url('watch.php?slug=' . urlencode($episode['video_slug'])) ?>">
          <img src="<?= sf_asset($episode['image']) ?>" alt="<?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?> episode scene" loading="lazy" decoding="async">
          <div><small><?= htmlspecialchars($episode['season'], ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($episode['runtime'], ENT_QUOTES, 'UTF-8') ?></span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="dr-comment-wrap dr-watch-library" id="fan-thread">
    <?php sf_inline_comment_widget('video', (int)$currentVideo['id'], (string)$currentVideo['slug'], 'Video comments'); ?>
  </div>
</section>

<?php if ($isHls): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.6.16/dist/hls.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const video = document.querySelector('[data-hls-source]');
  const src = video?.dataset.hlsSource;
  if (!video || !src) return;
  if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = src;
    return;
  }
  if (window.Hls && Hls.isSupported()) {
    const hls = new Hls({ enableWorker: true, lowLatencyMode: false, capLevelToPlayerSize: true });
    hls.loadSource(src);
    hls.attachMedia(video);
    window.addEventListener('beforeunload', () => hls.destroy(), { once: true });
  }
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
