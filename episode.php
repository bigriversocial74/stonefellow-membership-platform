<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/membership.php';
require_once __DIR__ . '/includes/posts.php';
require __DIR__ . '/includes/desertrio_theme.php';

$slug = $_GET['slug'] ?? 'first-to-fall';
$currentEpisode = sf_episode_by_slug($episodes, $slug);
$episodeVideo = sf_video_by_episode_slug($videoCatalog, $currentEpisode['slug'] ?? $slug, 'episode') ?? $videoCatalog[0];
$trailerVideo = sf_video_by_episode_slug($videoCatalog, $currentEpisode['slug'] ?? $slug, 'trailer') ?? $videoCatalog[1];
$member = sf_member_snapshot();
$canWatch = sf_access_allows($episodeVideo['access_level'] ?? 'subscriber', $member['access_level']);

$displayEpisode = $desertRioEpisodes[0];
foreach ($desertRioEpisodes as $candidate) {
    if (($candidate['slug'] ?? '') === ($currentEpisode['slug'] ?? $slug)) {
        $displayEpisode = $candidate;
        break;
    }
}

$pageTitle = ($displayEpisode['season'] ?? 'Episode') . ' — ' . ($displayEpisode['title'] ?? 'DesertRio');
$pageDescription = $displayEpisode['description'] ?? 'DesertRio episode details, membership access, watch progress, and fan comments.';
$pageClass = 'episode-detail-page membership-page desertrio-episode-detail-template';
$pageExtraStyles = ['css/desertrio-video.css'];
require __DIR__ . '/includes/header.php';
?>

<section class="dr-episode-detail">
  <section class="dr-detail-hero" aria-labelledby="dr-detail-title">
    <div class="dr-detail-hero-media">
      <img src="<?= sf_asset($displayEpisode['image']) ?>" alt="<?= htmlspecialchars($displayEpisode['title'], ENT_QUOTES, 'UTF-8') ?> episode scene" fetchpriority="high">
    </div>
    <div class="dr-detail-hero-shade" aria-hidden="true"></div>
    <div class="dr-detail-hero-inner">
      <div class="dr-detail-copy">
        <div class="dr-detail-kicker">
          <span><?= htmlspecialchars($displayEpisode['season'], ENT_QUOTES, 'UTF-8') ?></span>
          <span><?= htmlspecialchars(sf_access_label($episodeVideo['access_level'] ?? 'subscriber'), ENT_QUOTES, 'UTF-8') ?></span>
          <span><?= htmlspecialchars(($episodeVideo['status'] ?? '') === 'published' ? 'Available Now' : 'Coming Soon', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <h1 id="dr-detail-title"><?= htmlspecialchars($displayEpisode['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($displayEpisode['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <div class="dr-detail-meta">
          <span><?= htmlspecialchars($displayEpisode['runtime'], ENT_QUOTES, 'UTF-8') ?></span>
          <span><?= htmlspecialchars($member['access_label'], ENT_QUOTES, 'UTF-8') ?></span>
          <span><?= (int)sf_post_comment_count('episode', (int)($currentEpisode['id'] ?? 0), (string)($currentEpisode['slug'] ?? $slug)) ?> Comments</span>
        </div>
        <div class="dr-detail-actions">
          <?php if ($canWatch && ($episodeVideo['status'] ?? '') === 'published'): ?>
            <a class="dr-button dr-button-primary" href="<?= sf_url('watch.php?slug=' . urlencode($episodeVideo['slug'])) ?>">Watch Episode <span class="dr-button-play" aria-hidden="true">▷</span></a>
          <?php else: ?>
            <a class="dr-button dr-button-primary" href="<?= sf_url('subscribe.php') ?>">Unlock Episode</a>
          <?php endif; ?>
          <a class="dr-button" href="<?= sf_url('watch.php?slug=' . urlencode($trailerVideo['slug'])) ?>">Watch Trailer</a>
          <a class="dr-button" href="#fan-thread">Comments</a>
        </div>
      </div>

      <aside class="dr-access-card">
        <strong><?= $canWatch ? 'Ready to Watch' : 'Membership Required' ?></strong>
        <p><?= $canWatch ? 'Secure playback, resume tracking, and member library tools are enabled for this episode.' : 'Subscribe to unlock the full episode, progress sync, and member viewing features.' ?></p>
        <a class="dr-button dr-button-primary" href="<?= sf_url($canWatch ? 'member.php' : 'subscribe.php') ?>"><?= $canWatch ? 'Open Member Home' : 'View Membership' ?></a>
      </aside>
    </div>
  </section>

  <section class="dr-detail-content">
    <div class="dr-detail-grid">
      <article class="dr-detail-panel">
        <small>Episode Tracking</small>
        <h2>Continue From Any Device</h2>
        <p>Playback events continue to use the shared platform tracking runtime, including saved position, resume state, and member library synchronization.</p>
        <div class="dr-progress-row">
          <div class="dr-progress-head"><strong>Watch Progress</strong><span><?= (int)($episodeVideo['resume_percent'] ?? 0) ?>%</span></div>
          <div class="dr-progress-track"><i style="width:<?= (int)($episodeVideo['resume_percent'] ?? 0) ?>%"></i></div>
        </div>
      </article>
      <article class="dr-detail-panel">
        <small>Access</small>
        <h2><?= htmlspecialchars(sf_access_label($episodeVideo['access_level'] ?? 'subscriber'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Guest trailer access remains public. Full episodes continue to respect the existing member access level and secure media delivery rules.</p>
      </article>
    </div>
  </section>

  <section class="dr-related-section" aria-labelledby="dr-related-title">
    <header class="dr-section-head">
      <div><span></span><h2 id="dr-related-title">More Episodes</h2><span></span></div>
      <p>Continue inside the DesertRio circle.</p>
    </header>
    <div class="dr-related-grid">
      <?php foreach (array_slice($desertRioEpisodes, 0, 4) as $episode): ?>
        <a class="dr-related-card" href="<?= sf_url('episode.php?slug=' . urlencode($episode['slug'])) ?>">
          <img src="<?= sf_asset($episode['image']) ?>" alt="<?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?> episode scene" loading="lazy" decoding="async">
          <div><small><?= htmlspecialchars($episode['season'], ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($episode['runtime'], ENT_QUOTES, 'UTF-8') ?></span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="dr-comment-wrap" id="fan-thread">
    <?php sf_inline_comment_widget('episode', (int)($currentEpisode['id'] ?? 0), (string)($currentEpisode['slug'] ?? $slug), 'Episode comments'); ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
