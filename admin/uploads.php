<?php
$pageTitle = 'Manage Media Assets';
$pageDescription = 'Upload, preview, and register image, audio, video, and document assets used by the Stonefellow catalog.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
  if (!sf_admin_db_ready() || !sf_admin_table_exists('media_assets')) {
    sf_admin_flash('warning', 'Media assets table is not available. Configure the database and run the base SQL first.');
    sf_admin_redirect();
  }

  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;

  if ($action === 'upload_asset') {
    $result = sf_admin_handle_upload(
      'media_file',
      $_POST['upload_file_type'] ?? 'auto',
      (string)($_POST['upload_usage_key'] ?? ''),
      (string)($_POST['upload_title'] ?? ''),
      (string)($_POST['upload_alt_text'] ?? '')
    );
    sf_admin_flash($result['ok'] ? 'success' : 'error', $result['message'] ?? ($result['ok'] ? 'Uploaded.' : 'Upload failed.'));
    if (!empty($result['ok']) && !empty($result['id'])) {
      sf_admin_redirect(sf_url('admin/uploads.php?edit=' . (int)$result['id']));
    }
    sf_admin_redirect();
  }

  if ($action === 'save_asset') {
    $title = trim((string)($_POST['title'] ?? ''));
    $filePath = trim((string)($_POST['file_path'] ?? ''));
    if ($title === '' || $filePath === '') {
      sf_admin_flash('error', 'Asset title and file path are required.');
      sf_admin_redirect();
    }
    $payload = [
      'title' => $title,
      'file_path' => $filePath,
      'file_type' => $_POST['file_type'] ?? 'image',
      'alt_text' => sf_admin_nullable_string($_POST['alt_text'] ?? ''),
      'usage_key' => sf_admin_nullable_string($_POST['usage_key'] ?? ''),
      'original_filename' => sf_admin_nullable_string($_POST['original_filename'] ?? ''),
      'mime_type' => sf_admin_nullable_string($_POST['mime_type'] ?? ''),
      'file_size_bytes' => sf_admin_int($_POST['file_size_bytes'] ?? null),
      'storage_disk' => sf_admin_nullable_string($_POST['storage_disk'] ?? 'local_assets'),
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM media_assets WHERE id = ?', [$id]) : null;
    if ($id > 0) {
      sf_admin_update_media_asset($id, $payload);
      sf_admin_audit('update_media_asset', 'media_asset', $id, $before, $payload);
      sf_admin_flash('success', 'Media asset updated.');
    } else {
      $newId = sf_admin_insert_media_asset($payload);
      sf_admin_audit('create_media_asset', 'media_asset', $newId, null, $payload);
      sf_admin_flash('success', 'Media asset registered.');
      sf_admin_redirect(sf_url('admin/uploads.php?edit=' . $newId));
    }
  }

  if ($action === 'delete_asset' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM media_assets WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM media_assets WHERE id = ?', [$id]);
    sf_admin_audit('delete_media_asset', 'media_asset', $id, $before, null);
    sf_admin_flash('success', 'Media asset deleted from the registry. The physical file is not removed automatically.');
  }

  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$typeFilter = trim((string)($_GET['type'] ?? ''));
$validTypes = ['image','audio','video','document'];
$assets = in_array($typeFilter, $validTypes, true) ? sf_admin_assets($typeFilter) : sf_admin_assets();
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = $editId > 0 && sf_admin_table_exists('media_assets') ? sf_admin_fetch_one('SELECT * FROM media_assets WHERE id = ?', [$editId]) : [];
$assetCounts = [];
foreach ($validTypes as $assetType) {
  $assetCounts[$assetType] = count(sf_admin_assets($assetType));
}

sf_admin_shell_start('Assets', 'Upload and manage media assets', 'Upload local files, register CDN paths, preview assets, and attach them to albums, songs, episodes, and videos.', 'uploads');
?>
<section class="sf-admin-card-grid sf-admin-upload-stats">
  <?php foreach ($validTypes as $assetType): ?>
    <a class="sf-admin-stat-card" href="<?= sf_url('admin/uploads.php?type=' . $assetType) ?>">
      <span><?= sf_admin_h($assetType) ?></span>
      <strong><?= (int)$assetCounts[$assetType] ?></strong>
      <small><?= $assetType === 'image' ? 'Covers, posters, banners' : ($assetType === 'audio' ? 'Song previews and full tracks' : ($assetType === 'video' ? 'Episodes, trailers, clips' : 'PDFs and support files')) ?></small>
    </a>
  <?php endforeach; ?>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Asset Library</span><h2><?= count($assets) ?><?= $typeFilter ? ' ' . sf_admin_h($typeFilter) : '' ?> assets</h2></div>
      <div class="sf-admin-inline-links"><a href="<?= sf_url('admin/uploads.php') ?>">All</a><a href="<?= sf_url('admin/uploads.php') ?>">New Asset</a></div>
    </div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table sf-admin-asset-table">
        <thead><tr><th>Preview</th><th>Asset</th><th>Type</th><th>Size</th><th>Usage</th><th></th></tr></thead>
        <tbody>
        <?php if (!$assets): ?><tr><td colspan="6">No database media assets yet. Upload a file or register a path after DB setup.</td></tr><?php endif; ?>
        <?php foreach ($assets as $asset): ?>
          <tr>
            <td><?= sf_admin_asset_preview($asset) ?></td>
            <td><strong><?= sf_admin_h($asset['title'] ?? '') ?></strong><small><?= sf_admin_h($asset['file_path'] ?? '') ?></small></td>
            <td><?= sf_admin_h($asset['file_type'] ?? '') ?></td>
            <td><?= sf_admin_h(sf_admin_format_bytes($asset['file_size_bytes'] ?? 0)) ?></td>
            <td><?= sf_admin_h($asset['usage_key'] ?? '—') ?></td>
            <td><a href="<?= sf_url('admin/uploads.php?edit=' . (int)($asset['id'] ?? 0)) ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Upload</span><h2>Upload new file</h2></div></div>
    <form class="sf-admin-form sf-admin-upload-form" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_asset">
      <label>File<input type="file" name="media_file" required<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid">
        <label>Detected Type<?= sf_admin_select('upload_file_type', ['auto'=>'Auto Detect','image'=>'Image','audio'=>'Audio','video'=>'Video','document'=>'Document'], 'auto') ?></label>
        <label>Usage Key<input name="upload_usage_key" placeholder="album_cover, song_full, episode_stream"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <label>Title<input name="upload_title" placeholder="Optional title; file name used if blank"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Alt Text / Description<input name="upload_alt_text" placeholder="Recommended for image accessibility"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Upload Asset</button></div>
    </form>
    <div class="sf-admin-help-card"><strong>Upload limits</strong><p>Images: 12MB. Audio: 120MB. Video: 800MB. Documents: 30MB. Files save under <code>assets/images/uploads</code>, <code>assets/audio/uploads</code>, <code>assets/video/uploads</code>, or <code>assets/documents/uploads</code>.</p></div>
  </article>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Register' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'Register existing path' ?></h2></div></div>
    <?php if ($edit): ?><?= sf_admin_asset_preview($edit) ?><?php endif; ?>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_asset"><input type="hidden" name="id" value="<?= sf_admin_h($edit['id'] ?? '') ?>">
      <label>Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>File Path<input name="file_path" value="<?= sf_admin_h($edit['file_path'] ?? '') ?>" placeholder="images/music/soundtrack-cover.png or https://cdn..." required<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid"><label>File Type<?= sf_admin_select('file_type', ['image'=>'Image','audio'=>'Audio','video'=>'Video','document'=>'Document'], $edit['file_type'] ?? 'image') ?></label><label>Usage Key<input name="usage_key" value="<?= sf_admin_h($edit['usage_key'] ?? '') ?>" placeholder="album_cover, episode_poster"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label>Alt Text<input name="alt_text" value="<?= sf_admin_h($edit['alt_text'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid"><label>Original Filename<input name="original_filename" value="<?= sf_admin_h($edit['original_filename'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>MIME Type<input name="mime_type" value="<?= sf_admin_h($edit['mime_type'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-grid"><label>File Size Bytes<input type="number" name="file_size_bytes" value="<?= sf_admin_h($edit['file_size_bytes'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Storage Disk<input name="storage_disk" value="<?= sf_admin_h($edit['storage_disk'] ?? 'local_assets') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $edit ? 'Save Asset' : 'Register Asset' ?></button></div>
    </form>
    <?php if ($edit): ?><form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_asset"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Asset') ?></form><?php endif; ?>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Next assignment</span><h2>Use uploaded files</h2></div></div>
    <div class="sf-admin-roadmap sf-admin-roadmap-compact">
      <div><span>01</span><strong>Images</strong><p>Assign uploaded cover/poster assets from Albums, Songs, and Videos.</p></div>
      <div><span>02</span><strong>Audio</strong><p>Choose uploaded audio assets when adding song preview/full file variants.</p></div>
      <div><span>03</span><strong>Video</strong><p>Choose uploaded video assets when adding episode stream, preview, or trailer variants.</p></div>
      <div><span>04</span><strong>CDN-ready</strong><p>Register external CDN URLs manually in the path form without uploading a file.</p></div>
    </div>
    <div class="sf-admin-help-card"><strong>Production note</strong><p>Local uploads are enough for staging and low-volume launches. For production video streaming, use this registry as the control layer and point paths to Bunny, S3/CloudFront, Mux, Vimeo OTT, or another streaming storage provider.</p></div>
  </article>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
