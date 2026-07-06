<?php
$pageTitle = 'Manage Songs';
$pageDescription = 'Create songs, assign albums, add audio file paths, and publish subscriber access.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
  if (!sf_admin_db_ready() || !sf_admin_table_exists('songs')) {
    sf_admin_flash('warning', 'Songs table is not available. Configure the database and run the base SQL first.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;

  if ($action === 'save_song') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
      sf_admin_flash('error', 'Song title is required.');
      sf_admin_redirect();
    }
    $slug = trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($title);
    $payload = [
      'album_id' => sf_admin_int($_POST['album_id'] ?? null),
      'title' => $title,
      'slug' => $slug,
      'artist' => trim((string)($_POST['artist'] ?? 'Stonefellow')) ?: 'Stonefellow',
      'track_number' => sf_admin_int($_POST['track_number'] ?? null),
      'duration_seconds' => sf_admin_int($_POST['duration_seconds'] ?? null),
      'cover_asset_id' => sf_admin_int($_POST['cover_asset_id'] ?? null),
      'access_level' => $_POST['access_level'] ?? 'subscriber',
      'is_featured' => sf_admin_checkbox('is_featured'),
      'status' => $_POST['status'] ?? 'draft',
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM songs WHERE id = ?', [$id]) : null;
    if ($id > 0) {
      sf_admin_execute('UPDATE songs SET album_id=?, title=?, slug=?, artist=?, track_number=?, duration_seconds=?, cover_asset_id=?, access_level=?, is_featured=?, status=? WHERE id=?', array_merge(array_values($payload), [$id]));
      sf_admin_audit('update_song', 'song', $id, $before, $payload);
      sf_admin_flash('success', 'Song updated.');
    } else {
      sf_admin_execute('INSERT INTO songs (album_id, title, slug, artist, track_number, duration_seconds, cover_asset_id, access_level, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_song', 'song', $newId, null, $payload);
      sf_admin_flash('success', 'Song created.');
      sf_admin_redirect(sf_url('admin/music-songs.php?edit=' . $newId));
    }
  }

  if ($action === 'delete_song' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM songs WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM songs WHERE id = ?', [$id]);
    sf_admin_audit('delete_song', 'song', $id, $before, null);
    sf_admin_flash('success', 'Song deleted.');
  }

  if ($action === 'save_song_file') {
    if (!sf_admin_table_exists('song_files')) {
      sf_admin_flash('warning', 'Song files table is not available. Run the base SQL first.');
      sf_admin_redirect();
    }
    $songId = sf_admin_int($_POST['song_id'] ?? null, 0) ?? 0;
    $fileId = sf_admin_int($_POST['file_id'] ?? null, 0) ?? 0;
    $filePath = trim((string)($_POST['file_path'] ?? ''));
    $audioAssetId = sf_admin_int($_POST['audio_asset_id'] ?? null, 0) ?? 0;
    $audioAsset = $audioAssetId > 0 ? sf_admin_asset_by_id($audioAssetId) : null;
    if ($filePath === '' && $audioAsset) {
      $filePath = trim((string)($audioAsset['file_path'] ?? ''));
    }
    $mimeType = trim((string)($_POST['mime_type'] ?? '')) ?: trim((string)($audioAsset['mime_type'] ?? '')) ?: 'audio/wav';
    if ($songId <= 0 || $filePath === '') {
      sf_admin_flash('error', 'Select a song and choose an uploaded audio asset or enter an audio file path.');
      sf_admin_redirect();
    }
    $payload = [
      'song_id' => $songId,
      'file_type' => $_POST['file_type'] ?? 'full',
      'file_path' => $filePath,
      'duration_seconds' => sf_admin_int($_POST['file_duration_seconds'] ?? null),
      'preview_seconds' => sf_admin_int($_POST['preview_seconds'] ?? null),
      'bitrate_kbps' => sf_admin_int($_POST['bitrate_kbps'] ?? null),
      'mime_type' => $mimeType,
      'is_primary' => sf_admin_checkbox('file_is_primary'),
    ];
    if ($payload['is_primary']) {
      sf_admin_execute('UPDATE song_files SET is_primary = 0 WHERE song_id = ? AND file_type = ?', [$songId, $payload['file_type']]);
    }
    $before = $fileId > 0 ? sf_admin_fetch_one('SELECT * FROM song_files WHERE id = ?', [$fileId]) : null;
    if ($fileId > 0) {
      sf_admin_execute('UPDATE song_files SET song_id=?, file_type=?, file_path=?, duration_seconds=?, preview_seconds=?, bitrate_kbps=?, mime_type=?, is_primary=? WHERE id=?', array_merge(array_values($payload), [$fileId]));
      sf_admin_audit('update_song_file', 'song_file', $fileId, $before, $payload);
      sf_admin_flash('success', 'Audio file updated.');
    } else {
      sf_admin_execute('INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, bitrate_kbps, mime_type, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_song_file', 'song_file', $newId, null, $payload);
      sf_admin_flash('success', 'Audio file added.');
    }
    sf_admin_redirect(sf_url('admin/music-songs.php?edit=' . $songId));
  }

  if ($action === 'delete_song_file') {
    $fileId = sf_admin_int($_POST['file_id'] ?? null, 0) ?? 0;
    $songId = sf_admin_int($_POST['song_id'] ?? null, 0) ?? 0;
    if ($fileId > 0 && sf_admin_table_exists('song_files')) {
      $before = sf_admin_fetch_one('SELECT * FROM song_files WHERE id = ?', [$fileId]);
      sf_admin_execute('DELETE FROM song_files WHERE id = ?', [$fileId]);
      sf_admin_audit('delete_song_file', 'song_file', $fileId, $before, null);
      sf_admin_flash('success', 'Audio file deleted.');
    }
    sf_admin_redirect(sf_url('admin/music-songs.php?edit=' . $songId));
  }

  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$songs = sf_admin_songs();
$albums = sf_admin_albums();
$assets = sf_admin_assets('image');
$audioAssets = sf_admin_assets('audio');
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_admin_selected_row($songs, 'songs', $editId) ?: [];
$fileEditId = sf_admin_int($_GET['file_edit'] ?? null, 0) ?? 0;
$fileEdit = $fileEditId > 0 && sf_admin_table_exists('song_files') ? sf_admin_fetch_one('SELECT * FROM song_files WHERE id = ?', [$fileEditId]) : null;
$fileRows = sf_admin_file_rows('song_files', 'song_id', (int)($edit['id'] ?? 0));

sf_admin_shell_start('Songs', 'Manage songs and audio files', 'Create individual tracks, assign album order, set access level, and add preview/full audio file paths.', 'songs');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Song Records</span><h2><?= count($songs) ?> songs</h2></div><a href="<?= sf_url('admin/music-songs.php') ?>">New Song</a></div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table">
        <thead><tr><th>#</th><th>Song</th><th>Album</th><th>Access</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($songs as $song): ?>
          <tr>
            <td><?= sf_admin_h($song['track_number'] ?? $song['track'] ?? '—') ?></td>
            <td><strong><?= sf_admin_h($song['title'] ?? '') ?></strong><small><?= sf_admin_h($song['slug'] ?? '') ?></small></td>
            <td><?= sf_admin_h($song['album_title'] ?? '—') ?></td>
            <td><?= sf_admin_h(sf_access_label((string)($song['access_level'] ?? $song['access'] ?? 'subscriber'))) ?></td>
            <td><?= sf_admin_status_badge((string)($song['status'] ?? 'published')) ?></td>
            <td><a href="<?= sf_url('admin/music-songs.php?edit=' . (int)($song['id'] ?? 0)) ?>">Edit</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Create' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'New song' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_song"><input type="hidden" name="id" value="<?= sf_admin_h($edit['id'] ?? '') ?>">
      <label>Song Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated if blank"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid">
        <label>Album<?= sf_admin_relation_select('album_id', $albums, $edit['album_id'] ?? '') ?></label>
        <label>Artist<input name="artist" value="<?= sf_admin_h($edit['artist'] ?? 'Stonefellow') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Track #<input type="number" name="track_number" value="<?= sf_admin_h($edit['track_number'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Duration Seconds<input type="number" name="duration_seconds" value="<?= sf_admin_h($edit['duration_seconds'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <label>Cover Image<?= sf_admin_asset_select('cover_asset_id', $assets, $edit['cover_asset_id'] ?? '', 'image') ?></label>
      <?= sf_admin_asset_preview_by_id($edit['cover_asset_id'] ?? null, 'image') ?>
      <p class="sf-admin-form-note"><a href="<?= sf_url('admin/uploads.php?type=image') ?>">Upload or manage cover images</a></p>
      <div class="sf-admin-form-grid">
        <label>Access<?= sf_admin_select('access_level', ['free_preview'=>'Public Preview','subscriber'=>'Subscriber','premium'=>'Premium'], $edit['access_level'] ?? 'subscriber') ?></label>
        <label>Status<?= sf_admin_select('status', ['draft'=>'Draft','published'=>'Published','archived'=>'Archived'], $edit['status'] ?? 'draft') ?></label>
      </div>
      <label class="sf-admin-check"><input type="checkbox" name="is_featured" value="1" <?= !empty($edit['is_featured']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Featured song</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $edit ? 'Save Song' : 'Create Song' ?></button></div>
    </form>
    <?php if ($edit): ?>
      <form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_song"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Song') ?></form>
    <?php endif; ?>
  </article>
</section>

<?php if ($edit): ?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audio Files</span><h2>Preview and full tracks</h2></div></div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table">
        <thead><tr><th>Type</th><th>Path</th><th>Duration</th><th>Primary</th><th></th></tr></thead>
        <tbody>
          <?php if (!$fileRows): ?><tr><td colspan="5">No audio files added yet.</td></tr><?php endif; ?>
          <?php foreach ($fileRows as $file): ?>
          <tr>
            <td><?= sf_admin_h($file['file_type'] ?? '') ?></td><td><strong><?= sf_admin_h($file['file_path'] ?? '') ?></strong><small><?= sf_admin_h($file['mime_type'] ?? '') ?></small></td><td><?= sf_admin_h($file['duration_seconds'] ?? '—') ?></td><td><?= !empty($file['is_primary']) ? 'Yes' : 'No' ?></td>
            <td><a href="<?= sf_url('admin/music-songs.php?edit=' . (int)($edit['id'] ?? 0) . '&file_edit=' . (int)($file['id'] ?? 0)) ?>">Edit</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $fileEdit ? 'Edit File' : 'Add File' ?></span><h2>Audio source</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_song_file"><input type="hidden" name="song_id" value="<?= (int)($edit['id'] ?? 0) ?>"><input type="hidden" name="file_id" value="<?= sf_admin_h($fileEdit['id'] ?? '') ?>">
      <div class="sf-admin-form-grid">
        <label>File Type<?= sf_admin_select('file_type', ['preview'=>'Preview','full'=>'Full','live'=>'Live','demo'=>'Demo','acoustic'=>'Acoustic'], $fileEdit['file_type'] ?? 'full') ?></label>
        <label>MIME Type<input name="mime_type" value="<?= sf_admin_h($fileEdit['mime_type'] ?? 'audio/wav') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <label>Choose Uploaded Audio<?= sf_admin_asset_path_select('audio_asset_id', $audioAssets, '', 'Choose audio from asset library') ?></label>
      <label>File Path<input name="file_path" value="<?= sf_admin_h($fileEdit['file_path'] ?? '') ?>" placeholder="audio/full/song-name.wav or choose uploaded audio above"<?= sf_admin_form_disabled_attr() ?>></label>
      <?= sf_admin_asset_preview(null, $fileEdit['file_path'] ?? '', 'audio') ?>
      <p class="sf-admin-form-note"><a href="<?= sf_url('admin/uploads.php?type=audio') ?>">Upload or manage audio files</a></p>
      <div class="sf-admin-form-grid"><label>Duration Seconds<input type="number" name="file_duration_seconds" value="<?= sf_admin_h($fileEdit['duration_seconds'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Preview Seconds<input type="number" name="preview_seconds" value="<?= sf_admin_h($fileEdit['preview_seconds'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Bitrate kbps<input type="number" name="bitrate_kbps" value="<?= sf_admin_h($fileEdit['bitrate_kbps'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label class="sf-admin-check"><input type="checkbox" name="file_is_primary" value="1" <?= !empty($fileEdit['is_primary']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Primary source for this type</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $fileEdit ? 'Save File' : 'Add File' ?></button></div>
    </form>
    <?php if ($fileEdit): ?><form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_song_file"><input type="hidden" name="song_id" value="<?= (int)($edit['id'] ?? 0) ?>"><input type="hidden" name="file_id" value="<?= (int)($fileEdit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Audio File') ?></form><?php endif; ?>
  </article>
</section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
