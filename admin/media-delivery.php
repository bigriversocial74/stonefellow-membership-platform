<?php

declare(strict_types=1);

$pageTitle = 'Media Delivery';
$pageDescription = 'Review signed streaming, protected media paths, HLS sessions, CDN readiness, and entitlement delivery status.';
$pageClass = 'membership-page admin-catalog-page';
require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/media_delivery.php';
require_once __DIR__ . '/../includes/media_pipeline.php';
require __DIR__ . '/../includes/header.php';

$videos = sf_admin_videos();
$videoRows = [];
foreach ($videos as $video) {
  $public = ($video['access_level'] ?? 'subscriber') === 'public';
  $source = sf_media_video_playback($video, $public || sf_access_allows('admin'));
  $manifest = sf_mp_ready_object('video',(int)($video['id']??0),'manifest');
  $videoRows[] = [
    'title' => $video['title'] ?? 'Video','slug' => $video['slug'] ?? '','access' => $video['access_level'] ?? 'subscriber','status' => $video['status'] ?? 'draft',
    'file_type' => $manifest?'HLS':$source['file_type'],'file_path' => $manifest?($manifest['storage_key']??''):$source['file_path'],'exists' => $manifest?true:$source['exists'],
    'url' => $manifest?sf_mp_manifest_url($manifest,sf_current_user_id(),900):$source['url'],
  ];
}
$summary=sf_mp_provider_summary();$usage=sf_mp_storage_usage_summary();$delivery=sf_mp_delivery_summary();$queue=sf_mp_queue_summary();
$protectedChecks = [
  'assets/audio/full/.htaccess' => is_file(__DIR__ . '/../assets/audio/full/.htaccess'),
  'assets/video/episodes/.htaccess' => is_file(__DIR__ . '/../assets/video/episodes/.htaccess'),
  'assets/video/live/.htaccess' => is_file(__DIR__ . '/../assets/video/live/.htaccess'),
  'storage/private_media/.htaccess' => is_file(__DIR__ . '/../storage/private_media/.htaccess'),
  'storage/private_media_v2/.htaccess' => is_file(__DIR__ . '/../storage/private_media_v2/.htaccess'),
];

sf_admin_shell_start('Secure Media', 'Delivery + entitlement gate', 'Review signed range streams, adaptive HLS, protected source folders, provider status, and member access behavior.', 'media-delivery');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-pipeline.php') ?>"><span>Pipeline</span><strong>Storage + Transcoding</strong><small>Uploads, jobs, HLS, waveforms, provider health.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('stream.php') ?>"><span>Legacy Endpoint</span><strong>stream.php</strong><small>Signed byte-range stream endpoint.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('api/media-token.php') ?>"><span>Token API</span><strong>media-token</strong><small>POST-only signed URL issuer.</small></a>
  <div class="sf-admin-action-card"><span>Storage</span><strong><?= sf_admin_h(strtoupper((string)$summary['driver'])) ?></strong><small><?= !empty($summary['storage_ready'])?'Ready':'Needs configuration' ?></small></div>
  <div class="sf-admin-action-card"><span>Delivery</span><strong><?= number_format((int)($delivery['active_sessions']??0)) ?></strong><small>Active signed HLS sessions.</small></div>
  <div class="sf-admin-action-card"><span>Queue</span><strong><?= number_format((int)(($queue['queued']??0)+($queue['retry']??0)+($queue['running']??0))) ?></strong><small>Pending/running media jobs.</small></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Protection Status</span><h2>Server folders and provider controls</h2></div><small><?= number_format((int)($usage['objects']??0)) ?> objects · <?= sf_admin_format_bytes((int)($usage['bytes']??0)) ?></small></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Path / Control</th><th>Status</th><th>Note</th></tr></thead><tbody>
    <?php foreach ($protectedChecks as $path => $ok): ?><tr><td><strong><?= sf_admin_h($path) ?></strong></td><td><?= $ok ? sf_admin_status_badge('published') : sf_admin_status_badge('draft') ?></td><td><?= $ok ? 'Direct-web deny rule present.' : 'Add server-level deny rules when using this local path.' ?></td></tr><?php endforeach; ?>
    <tr><td><strong>FFmpeg / FFprobe</strong></td><td><?= !empty($summary['ffmpeg_ready'])&&!empty($summary['ffprobe_ready'])?sf_admin_status_badge('published'):sf_admin_status_badge('draft') ?></td><td>Required for previews, waveforms, metadata probing, posters, and HLS.</td></tr>
    <tr><td><strong>Signed CDN delivery</strong></td><td><?= sf_mp_env('SF_MEDIA_CDN_BASE_URL')!==''&&strlen(sf_mp_env('SF_MEDIA_CDN_SIGNING_KEY'))>=32?sf_admin_status_badge('published'):sf_admin_status_badge('draft') ?></td><td>Optional. S3 presigned delivery remains available without a CDN.</td></tr>
  </tbody></table></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video Delivery</span><h2><?= count($videoRows) ?> video records</h2></div><a href="<?= sf_url('admin/videos.php') ?>">Manage Videos</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Video</th><th>Access</th><th>Status</th><th>Source</th><th>Object / File</th><th>Signed URL</th></tr></thead><tbody>
    <?php foreach ($videoRows as $row): ?><tr><td><strong><?= sf_admin_h($row['title']) ?></strong><small><?= sf_admin_h($row['slug']) ?></small></td><td><?= sf_admin_h(sf_access_label($row['access'])) ?></td><td><?= sf_admin_status_badge((string)$row['status']) ?></td><td><?= sf_admin_h($row['file_type']) ?></td><td><strong><?= sf_admin_h($row['file_path'] ?: 'No source') ?></strong><small><?= $row['exists'] ? 'Source registered and ready' : 'Source missing or unavailable' ?></small></td><td><?php if ($row['url']): ?><a href="<?= sf_admin_h($row['url']) ?>" target="_blank" rel="noopener">Open signed URL</a><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
