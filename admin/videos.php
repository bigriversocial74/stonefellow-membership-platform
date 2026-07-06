<?php
$pageTitle = 'Manage Videos';
$pageDescription = 'Create videos, attach episodes, manage file variants, and publish access levels.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
  if (!sf_admin_db_ready() || !sf_admin_table_exists('videos')) {
    sf_admin_flash('warning', 'Videos table is not available. Run migration 001 before using video admin.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;

  if ($action === 'save_video') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
      sf_admin_flash('error', 'Video title is required.');
      sf_admin_redirect();
    }
    $payload = [
      'episode_id' => sf_admin_int($_POST['episode_id'] ?? null),
      'title' => $title,
      'slug' => trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($title),
      'video_type' => $_POST['video_type'] ?? 'episode',
      'short_description' => sf_admin_nullable_string($_POST['short_description'] ?? ''),
      'description' => sf_admin_nullable_string($_POST['description'] ?? ''),
      'runtime_seconds' => sf_admin_int($_POST['runtime_seconds'] ?? null),
      'poster_asset_id' => sf_admin_int($_POST['poster_asset_id'] ?? null),
      'access_level' => $_POST['access_level'] ?? 'subscriber',
      'release_at' => sf_admin_datetime_or_null('release_at'),
      'is_featured' => sf_admin_checkbox('is_featured'),
      'status' => $_POST['status'] ?? 'draft',
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM videos WHERE id = ?', [$id]) : null;
    if ($id > 0) {
      sf_admin_execute('UPDATE videos SET episode_id=?, title=?, slug=?, video_type=?, short_description=?, description=?, runtime_seconds=?, poster_asset_id=?, access_level=?, release_at=?, is_featured=?, status=? WHERE id=?', array_merge(array_values($payload), [$id]));
      sf_admin_audit('update_video', 'video', $id, $before, $payload);
      sf_admin_flash('success', 'Video updated.');
    } else {
      sf_admin_execute('INSERT INTO videos (episode_id, title, slug, video_type, short_description, description, runtime_seconds, poster_asset_id, access_level, release_at, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_video', 'video', $newId, null, $payload);
      sf_admin_flash('success', 'Video created.');
      sf_admin_redirect(sf_url('admin/videos.php?edit=' . $newId));
    }
  }

  if ($action === 'delete_video' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM videos WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM videos WHERE id = ?', [$id]);
    sf_admin_audit('delete_video', 'video', $id, $before, null);
    sf_admin_flash('success', 'Video deleted.');
  }

  if ($action === 'save_video_file') {
    if (!sf_admin_table_exists('video_files')) {
      sf_admin_flash('warning', 'Video files table is not available. Run migration 001 first.');
      sf_admin_redirect();
    }
    $videoId = sf_admin_int($_POST['video_id'] ?? null, 0) ?? 0;
    $fileId = sf_admin_int($_POST['file_id'] ?? null, 0) ?? 0;
    $filePath = trim((string)($_POST['file_path'] ?? ''));
    $videoAssetId = sf_admin_int($_POST['video_asset_id'] ?? null, 0) ?? 0;
    $videoAsset = $videoAssetId > 0 ? sf_admin_asset_by_id($videoAssetId) : null;
    if ($filePath === '' && $videoAsset) {
      $filePath = trim((string)($videoAsset['file_path'] ?? ''));
    }
    $mimeType = trim((string)($_POST['mime_type'] ?? '')) ?: trim((string)($videoAsset['mime_type'] ?? '')) ?: 'video/mp4';
    if ($videoId <= 0 || $filePath === '') {
      sf_admin_flash('error', 'Select a video and choose an uploaded video asset or enter a video file path.');
      sf_admin_redirect();
    }
    $payload = [
      'video_id' => $videoId,
      'file_type' => $_POST['file_type'] ?? 'stream',
      'file_path' => $filePath,
      'mime_type' => $mimeType,
      'duration_seconds' => sf_admin_int($_POST['file_duration_seconds'] ?? null),
      'resolution_label' => sf_admin_nullable_string($_POST['resolution_label'] ?? ''),
      'bitrate_kbps' => sf_admin_int($_POST['bitrate_kbps'] ?? null),
      'language_code' => sf_admin_nullable_string($_POST['language_code'] ?? ''),
      'is_primary' => sf_admin_checkbox('file_is_primary'),
    ];
    if ($payload['is_primary']) {
      sf_admin_execute('UPDATE video_files SET is_primary = 0 WHERE video_id = ? AND file_type = ?', [$videoId, $payload['file_type']]);
    }
    $before = $fileId > 0 ? sf_admin_fetch_one('SELECT * FROM video_files WHERE id = ?', [$fileId]) : null;
    if ($fileId > 0) {
      sf_admin_execute('UPDATE video_files SET video_id=?, file_type=?, file_path=?, mime_type=?, duration_seconds=?, resolution_label=?, bitrate_kbps=?, language_code=?, is_primary=? WHERE id=?', array_merge(array_values($payload), [$fileId]));
      sf_admin_audit('update_video_file', 'video_file', $fileId, $before, $payload);
      sf_admin_flash('success', 'Video file updated.');
    } else {
      sf_admin_execute('INSERT INTO video_files (video_id, file_type, file_path, mime_type, duration_seconds, resolution_label, bitrate_kbps, language_code, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_video_file', 'video_file', $newId, null, $payload);
      sf_admin_flash('success', 'Video file added.');
    }
    sf_admin_redirect(sf_url('admin/videos.php?edit=' . $videoId));
  }

  if ($action === 'delete_video_file') {
    $fileId = sf_admin_int($_POST['file_id'] ?? null, 0) ?? 0;
    $videoId = sf_admin_int($_POST['video_id'] ?? null, 0) ?? 0;
    if ($fileId > 0 && sf_admin_table_exists('video_files')) {
      $before = sf_admin_fetch_one('SELECT * FROM video_files WHERE id = ?', [$fileId]);
      sf_admin_execute('DELETE FROM video_files WHERE id = ?', [$fileId]);
      sf_admin_audit('delete_video_file', 'video_file', $fileId, $before, null);
      sf_admin_flash('success', 'Video file deleted.');
    }
    sf_admin_redirect(sf_url('admin/videos.php?edit=' . $videoId));
  }

  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$videos = sf_admin_videos();
$episodesList = sf_admin_episodes();
$assets = sf_admin_assets('image');
$videoAssets = sf_admin_assets('video');
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_admin_selected_row($videos, 'videos', $editId) ?: [];
$fileEditId = sf_admin_int($_GET['file_edit'] ?? null, 0) ?? 0;
$fileEdit = $fileEditId > 0 && sf_admin_table_exists('video_files') ? sf_admin_fetch_one('SELECT * FROM video_files WHERE id = ?', [$fileEditId]) : null;
$fileRows = sf_admin_file_rows('video_files', 'video_id', (int)($edit['id'] ?? 0));

sf_admin_shell_start('Videos', 'Manage videos and streaming files', 'Attach videos to episodes, set membership access, publish trailers/clips, and map video file variants.', 'videos');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video Records</span><h2><?= count($videos) ?> videos</h2></div><a href="<?= sf_url('admin/videos.php') ?>">New Video</a></div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table">
        <thead><tr><th>Video</th><th>Type</th><th>Episode</th><th>Access</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($videos as $video): ?>
          <tr>
            <td><strong><?= sf_admin_h($video['title'] ?? '') ?></strong><small><?= sf_admin_h($video['slug'] ?? '') ?></small></td>
            <td><?= sf_admin_h(ucfirst(str_replace('_', ' ', (string)($video['video_type'] ?? 'episode')))) ?></td>
            <td><?= sf_admin_h($video['episode_title'] ?? $video['episode_slug'] ?? 'Standalone') ?></td>
            <td><?= sf_admin_h(sf_access_label((string)($video['access_level'] ?? 'subscriber'))) ?></td>
            <td><?= sf_admin_status_badge((string)($video['status'] ?? 'draft')) ?></td>
            <td><a href="<?= sf_url('admin/videos.php?edit=' . (int)($video['id'] ?? 0)) ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Create' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'New video' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_video"><input type="hidden" name="id" value="<?= sf_admin_h($edit['id'] ?? '') ?>">
      <label>Video Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated if blank"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid">
        <label>Episode<?= sf_admin_relation_select('episode_id', $episodesList, $edit['episode_id'] ?? '') ?></label>
        <label>Type<?= sf_admin_select('video_type', ['episode'=>'Episode','trailer'=>'Trailer','clip'=>'Clip','behind_scenes'=>'Behind Scenes','live_session'=>'Live Session','music_video'=>'Music Video','bonus'=>'Bonus'], $edit['video_type'] ?? 'episode') ?></label>
      </div>
      <label>Short Description<textarea name="short_description" rows="3"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['short_description'] ?? '') ?></textarea></label>
      <label>Long Description<textarea name="description" rows="5"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['description'] ?? '') ?></textarea></label>
      <label>Poster Image<?= sf_admin_asset_select('poster_asset_id', $assets, $edit['poster_asset_id'] ?? '', 'image') ?></label>
      <?= sf_admin_asset_preview_by_id($edit['poster_asset_id'] ?? null, 'image') ?>
      <p class="sf-admin-form-note"><a href="<?= sf_url('admin/uploads.php?type=image') ?>">Upload or manage poster images</a></p>
      <div class="sf-admin-form-grid"><label>Runtime Seconds<input type="number" name="runtime_seconds" value="<?= sf_admin_h($edit['runtime_seconds'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Release Date/Time<input type="datetime-local" name="release_at" value="<?= sf_admin_h(isset($edit['release_at']) ? str_replace(' ', 'T', substr((string)$edit['release_at'], 0, 16)) : '') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-grid"><label>Access<?= sf_admin_select('access_level', ['public'=>'Public','free_account'=>'Free Account','subscriber'=>'Subscriber','premium'=>'Premium','founding_fan'=>'Founding Fan'], $edit['access_level'] ?? 'subscriber') ?></label><label>Status<?= sf_admin_select('status', ['draft'=>'Draft','published'=>'Published','archived'=>'Archived'], $edit['status'] ?? 'draft') ?></label></div>
      <label class="sf-admin-check"><input type="checkbox" name="is_featured" value="1" <?= !empty($edit['is_featured']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Featured video</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $edit ? 'Save Video' : 'Create Video' ?></button></div>
    </form>
    <?php if ($edit): ?><form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_video"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Video') ?></form><?php endif; ?>
  </article>
</section>

<?php if ($edit): ?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video Files</span><h2>Stream, trailer, preview</h2></div></div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table"><thead><tr><th>Type</th><th>Path</th><th>Resolution</th><th>Primary</th><th></th></tr></thead><tbody>
        <?php if (!$fileRows): ?><tr><td colspan="5">No video file variants added yet.</td></tr><?php endif; ?>
        <?php foreach ($fileRows as $file): ?>
        <tr><td><?= sf_admin_h($file['file_type'] ?? '') ?></td><td><strong><?= sf_admin_h($file['file_path'] ?? '') ?></strong><small><?= sf_admin_h($file['mime_type'] ?? '') ?></small></td><td><?= sf_admin_h($file['resolution_label'] ?? '—') ?></td><td><?= !empty($file['is_primary']) ? 'Yes' : 'No' ?></td><td><a href="<?= sf_url('admin/videos.php?edit=' . (int)($edit['id'] ?? 0) . '&file_edit=' . (int)($file['id'] ?? 0)) ?>">Edit</a></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $fileEdit ? 'Edit File' : 'Add File' ?></span><h2>Video source</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_video_file"><input type="hidden" name="video_id" value="<?= (int)($edit['id'] ?? 0) ?>"><input type="hidden" name="file_id" value="<?= sf_admin_h($fileEdit['id'] ?? '') ?>">
      <div class="sf-admin-form-grid"><label>File Type<?= sf_admin_select('file_type', ['preview'=>'Preview','stream'=>'Stream','download'=>'Download','trailer'=>'Trailer','mobile'=>'Mobile','hd'=>'HD','subtitle'=>'Subtitle'], $fileEdit['file_type'] ?? 'stream') ?></label><label>MIME Type<input name="mime_type" value="<?= sf_admin_h($fileEdit['mime_type'] ?? 'video/mp4') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label>Choose Uploaded Video<?= sf_admin_asset_path_select('video_asset_id', $videoAssets, '', 'Choose video from asset library') ?></label>
      <label>File Path<input name="file_path" value="<?= sf_admin_h($fileEdit['file_path'] ?? '') ?>" placeholder="video/episodes/first-to-fall.mp4 or choose uploaded video above"<?= sf_admin_form_disabled_attr() ?>></label>
      <?= sf_admin_asset_preview(null, $fileEdit['file_path'] ?? '', 'video') ?>
      <p class="sf-admin-form-note"><a href="<?= sf_url('admin/uploads.php?type=video') ?>">Upload or manage video files</a></p>
      <div class="sf-admin-form-grid"><label>Duration Seconds<input type="number" name="file_duration_seconds" value="<?= sf_admin_h($fileEdit['duration_seconds'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Resolution<input name="resolution_label" value="<?= sf_admin_h($fileEdit['resolution_label'] ?? '') ?>" placeholder="1080p"<?= sf_admin_form_disabled_attr() ?>></label><label>Bitrate kbps<input type="number" name="bitrate_kbps" value="<?= sf_admin_h($fileEdit['bitrate_kbps'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Language<input name="language_code" value="<?= sf_admin_h($fileEdit['language_code'] ?? '') ?>" placeholder="en"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label class="sf-admin-check"><input type="checkbox" name="file_is_primary" value="1" <?= !empty($fileEdit['is_primary']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Primary source for this type</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $fileEdit ? 'Save File' : 'Add File' ?></button></div>
    </form>
    <?php if ($fileEdit): ?><form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_video_file"><input type="hidden" name="video_id" value="<?= (int)($edit['id'] ?? 0) ?>"><input type="hidden" name="file_id" value="<?= (int)($fileEdit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Video File') ?></form><?php endif; ?>
  </article>
</section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
