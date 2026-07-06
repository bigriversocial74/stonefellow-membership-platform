<?php
$pageTitle = 'Media Delivery';
$pageDescription = 'Review signed streaming, protected media paths, and entitlement delivery status.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/media_delivery.php';
require __DIR__ . '/../includes/header.php';

$videos = sf_admin_videos();
$videoRows = [];
foreach ($videos as $video) {
  $public = ($video['access_level'] ?? 'subscriber') === 'public';
  $source = sf_media_video_playback($video, $public || sf_access_allows('admin'));
  $videoRows[] = [
    'title' => $video['title'] ?? 'Video',
    'slug' => $video['slug'] ?? '',
    'access' => $video['access_level'] ?? 'subscriber',
    'status' => $video['status'] ?? 'draft',
    'file_type' => $source['file_type'],
    'file_path' => $source['file_path'],
    'exists' => $source['exists'],
    'url' => $source['url'],
  ];
}

$protectedChecks = [
  'assets/audio/full/.htaccess' => is_file(__DIR__ . '/../assets/audio/full/.htaccess'),
  'assets/video/episodes/.htaccess' => is_file(__DIR__ . '/../assets/video/episodes/.htaccess'),
  'assets/video/live/.htaccess' => is_file(__DIR__ . '/../assets/video/live/.htaccess'),
  'storage/private_media/.htaccess' => is_file(__DIR__ . '/../storage/private_media/.htaccess'),
];

sf_admin_shell_start('Secure Media', 'Delivery + entitlement gate', 'Review signed stream URLs, protected source folders, and member access behavior for video/audio delivery.', 'media-delivery');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('stream.php') ?>"><span>Endpoint</span><strong>stream.php</strong><small>Signed range-stream endpoint.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('api/media-token.php') ?>"><span>API</span><strong>media-token</strong><small>POST-only signed URL issuer.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('docs/SECURE_MEDIA_VIDEO_PLAYER_V1.md') ?>"><span>Docs</span><strong>Delivery Guide</strong><small>Setup and protection notes.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Protection Status</span><h2>Server folders</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Path</th><th>Status</th><th>Note</th></tr></thead><tbody>
    <?php foreach ($protectedChecks as $path => $ok): ?>
      <tr><td><strong><?= sf_admin_h($path) ?></strong></td><td><?= $ok ? sf_admin_status_badge('published') : sf_admin_status_badge('draft') ?></td><td><?= $ok ? 'Apache deny rule present.' : 'Add server-level deny rule if storing protected files here.' ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video Delivery</span><h2><?= count($videoRows) ?> video records</h2></div><a href="<?= sf_url('admin/videos.php') ?>">Manage Videos</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Video</th><th>Access</th><th>Status</th><th>Source</th><th>File</th><th>Signed URL</th></tr></thead><tbody>
    <?php foreach ($videoRows as $row): ?>
      <tr>
        <td><strong><?= sf_admin_h($row['title']) ?></strong><small><?= sf_admin_h($row['slug']) ?></small></td>
        <td><?= sf_admin_h(sf_access_label($row['access'])) ?></td>
        <td><?= sf_admin_status_badge((string)$row['status']) ?></td>
        <td><?= sf_admin_h($row['file_type']) ?></td>
        <td><strong><?= sf_admin_h($row['file_path'] ?: 'No source') ?></strong><small><?= $row['exists'] ? 'File exists' : 'File missing or protected path not found' ?></small></td>
        <td><?php if ($row['url']): ?><a href="<?= sf_admin_h($row['url']) ?>" target="_blank" rel="noopener">Open signed URL</a><?php else: ?>—<?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
