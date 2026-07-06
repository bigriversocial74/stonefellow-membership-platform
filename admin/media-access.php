<?php
$pageTitle = 'Membership Access Rules';
$pageDescription = 'Manage subscription plans and content grants for Stonefellow membership access.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
  if (!sf_admin_db_ready()) {
    sf_admin_flash('warning', 'Database is not configured. Access rules require MySQL tables.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;

  if ($action === 'save_plan') {
    if (!sf_admin_table_exists('subscription_plans')) {
      sf_admin_flash('warning', 'Subscription plans table is not available.');
      sf_admin_redirect();
    }
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
      sf_admin_flash('error', 'Plan name is required.');
      sf_admin_redirect();
    }
    $payload = [
      'name' => $name,
      'slug' => trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($name),
      'price_cents' => sf_admin_int($_POST['price_cents'] ?? null, 0) ?? 0,
      'billing_interval' => $_POST['billing_interval'] ?? 'month',
      'description' => sf_admin_nullable_string($_POST['description'] ?? ''),
      'allows_full_music' => sf_admin_checkbox('allows_full_music'),
      'allows_offline_downloads' => sf_admin_checkbox('allows_offline_downloads'),
      'is_featured' => sf_admin_checkbox('is_featured'),
      'status' => $_POST['status'] ?? 'active',
    ];
    $optional = ['allows_video_streaming','allows_episode_tracking','allows_playlists'];
    foreach ($optional as $column) {
      if (sf_admin_column_exists('subscription_plans', $column)) {
        $payload[$column] = sf_admin_checkbox($column);
      }
    }
    if (sf_admin_column_exists('subscription_plans', 'max_playlists')) {
      $payload['max_playlists'] = sf_admin_int($_POST['max_playlists'] ?? null);
    }
    if (sf_admin_column_exists('subscription_plans', 'max_playlist_tracks')) {
      $payload['max_playlist_tracks'] = sf_admin_int($_POST['max_playlist_tracks'] ?? null);
    }
    if (sf_admin_column_exists('subscription_plans', 'plan_tier')) {
      $payload['plan_tier'] = $_POST['plan_tier'] ?? 'monthly';
    }

    $columns = array_keys($payload);
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM subscription_plans WHERE id = ?', [$id]) : null;
    if ($id > 0) {
      $assignments = implode(', ', array_map(static fn($col) => $col . '=?', $columns));
      sf_admin_execute('UPDATE subscription_plans SET ' . $assignments . ' WHERE id=?', array_merge(array_values($payload), [$id]));
      sf_admin_audit('update_subscription_plan', 'subscription_plan', $id, $before, $payload);
      sf_admin_flash('success', 'Subscription plan updated.');
    } else {
      $placeholders = implode(', ', array_fill(0, count($columns), '?'));
      sf_admin_execute('INSERT INTO subscription_plans (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_subscription_plan', 'subscription_plan', $newId, null, $payload);
      sf_admin_flash('success', 'Subscription plan created.');
      sf_admin_redirect(sf_url('admin/media-access.php?plan_edit=' . $newId));
    }
  }

  if ($action === 'delete_plan' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM subscription_plans WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM subscription_plans WHERE id = ?', [$id]);
    sf_admin_audit('delete_subscription_plan', 'subscription_plan', $id, $before, null);
    sf_admin_flash('success', 'Subscription plan deleted.');
  }

  if ($action === 'save_grant') {
    if (!sf_admin_table_exists('content_access_grants')) {
      sf_admin_flash('warning', 'Content access grants table is not available. Run migration 001 first.');
      sf_admin_redirect();
    }
    $userId = sf_admin_int($_POST['user_id'] ?? null, 0) ?? 0;
    if ($userId <= 0) {
      sf_admin_flash('error', 'A user ID is required for an access grant.');
      sf_admin_redirect();
    }
    $payload = [
      'user_id' => $userId,
      'content_type' => $_POST['content_type'] ?? 'site_feature',
      'content_id' => sf_admin_int($_POST['content_id'] ?? null),
      'grant_type' => $_POST['grant_type'] ?? 'admin_grant',
      'access_level' => $_POST['access_level'] ?? 'subscriber',
      'starts_at' => sf_admin_datetime_or_null('starts_at'),
      'expires_at' => sf_admin_datetime_or_null('expires_at'),
      'created_by_user_id' => sf_current_user_id(),
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM content_access_grants WHERE id = ?', [$id]) : null;
    if ($id > 0) {
      sf_admin_execute('UPDATE content_access_grants SET user_id=?, content_type=?, content_id=?, grant_type=?, access_level=?, starts_at=?, expires_at=?, created_by_user_id=? WHERE id=?', array_merge(array_values($payload), [$id]));
      sf_admin_audit('update_content_grant', 'content_access_grant', $id, $before, $payload);
      sf_admin_flash('success', 'Access grant updated.');
    } else {
      sf_admin_execute('INSERT INTO content_access_grants (user_id, content_type, content_id, grant_type, access_level, starts_at, expires_at, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_content_grant', 'content_access_grant', $newId, null, $payload);
      sf_admin_flash('success', 'Access grant created.');
      sf_admin_redirect(sf_url('admin/media-access.php?grant_edit=' . $newId));
    }
  }

  if ($action === 'delete_grant' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM content_access_grants WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM content_access_grants WHERE id = ?', [$id]);
    sf_admin_audit('delete_content_grant', 'content_access_grant', $id, $before, null);
    sf_admin_flash('success', 'Access grant deleted.');
  }

  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$plans = sf_admin_table_exists('subscription_plans') ? sf_admin_fetch_all('SELECT * FROM subscription_plans ORDER BY is_featured DESC, price_cents ASC, id ASC') : [];
$grants = sf_admin_table_exists('content_access_grants') ? sf_admin_fetch_all('SELECT cag.*, u.email, u.display_name FROM content_access_grants cag LEFT JOIN users u ON u.id = cag.user_id ORDER BY cag.created_at DESC, cag.id DESC LIMIT 200') : [];
$users = sf_admin_table_exists('users') ? sf_admin_fetch_all('SELECT id, email, display_name, role, status FROM users ORDER BY created_at DESC, id DESC LIMIT 200') : [];
$planEditId = sf_admin_int($_GET['plan_edit'] ?? null, 0) ?? 0;
$planEdit = $planEditId > 0 && sf_admin_table_exists('subscription_plans') ? sf_admin_fetch_one('SELECT * FROM subscription_plans WHERE id = ?', [$planEditId]) : [];
$grantEditId = sf_admin_int($_GET['grant_edit'] ?? null, 0) ?? 0;
$grantEdit = $grantEditId > 0 && sf_admin_table_exists('content_access_grants') ? sf_admin_fetch_one('SELECT * FROM content_access_grants WHERE id = ?', [$grantEditId]) : [];
$hasPlanExtras = sf_admin_column_exists('subscription_plans', 'allows_video_streaming');

sf_admin_shell_start('Access', 'Manage memberships and content grants', 'Control subscription plan capabilities and assign direct content access grants to members.', 'access');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Subscription Plans</span><h2><?= count($plans) ?> plans</h2></div><a href="<?= sf_url('admin/media-access.php') ?>">New Plan</a></div>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Plan</th><th>Price</th><th>Capabilities</th><th>Status</th><th></th></tr></thead><tbody>
      <?php if (!$plans): ?><tr><td colspan="5">No plans found. Run the base SQL and migration 001 seed.</td></tr><?php endif; ?>
      <?php foreach ($plans as $plan): ?>
      <tr><td><strong><?= sf_admin_h($plan['name'] ?? '') ?></strong><small><?= sf_admin_h($plan['slug'] ?? '') ?></small></td><td>$<?= number_format(((int)($plan['price_cents'] ?? 0)) / 100, 2) ?> / <?= sf_admin_h($plan['billing_interval'] ?? 'month') ?></td><td><?= !empty($plan['allows_full_music']) ? 'Music ' : '' ?><?= !empty($plan['allows_video_streaming']) ? 'Video ' : '' ?><?= !empty($plan['allows_playlists']) ? 'Playlists' : '' ?></td><td><?= sf_admin_status_badge((string)($plan['status'] ?? 'active')) ?></td><td><a href="<?= sf_url('admin/media-access.php?plan_edit=' . (int)($plan['id'] ?? 0)) ?>">Edit</a></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $planEdit ? 'Edit Plan' : 'Create Plan' ?></span><h2><?= $planEdit ? sf_admin_h($planEdit['name'] ?? '') : 'Subscription plan' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_plan"><input type="hidden" name="id" value="<?= sf_admin_h($planEdit['id'] ?? '') ?>">
      <label>Name<input name="name" value="<?= sf_admin_h($planEdit['name'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Slug<input name="slug" value="<?= sf_admin_h($planEdit['slug'] ?? '') ?>" placeholder="auto-generated if blank"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid"><label>Price Cents<input type="number" name="price_cents" value="<?= sf_admin_h($planEdit['price_cents'] ?? 0) ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Billing<?= sf_admin_select('billing_interval', ['month'=>'Month','year'=>'Year'], $planEdit['billing_interval'] ?? 'month') ?></label></div>
      <label>Description<textarea name="description" rows="4"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($planEdit['description'] ?? '') ?></textarea></label>
      <div class="sf-admin-check-grid">
        <label class="sf-admin-check"><input type="checkbox" name="allows_full_music" <?= !empty($planEdit['allows_full_music']) || !$planEdit ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Full music</label>
        <label class="sf-admin-check"><input type="checkbox" name="allows_offline_downloads" <?= !empty($planEdit['allows_offline_downloads']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Downloads</label>
        <?php if ($hasPlanExtras): ?>
          <label class="sf-admin-check"><input type="checkbox" name="allows_video_streaming" <?= !empty($planEdit['allows_video_streaming']) || !$planEdit ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Video streaming</label>
          <label class="sf-admin-check"><input type="checkbox" name="allows_episode_tracking" <?= !empty($planEdit['allows_episode_tracking']) || !$planEdit ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Episode tracking</label>
          <label class="sf-admin-check"><input type="checkbox" name="allows_playlists" <?= !empty($planEdit['allows_playlists']) || !$planEdit ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Playlists</label>
        <?php endif; ?>
        <label class="sf-admin-check"><input type="checkbox" name="is_featured" <?= !empty($planEdit['is_featured']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Featured</label>
      </div>
      <?php if ($hasPlanExtras): ?><div class="sf-admin-form-grid"><label>Max Playlists<input type="number" name="max_playlists" value="<?= sf_admin_h($planEdit['max_playlists'] ?? '') ?>" placeholder="blank = unlimited"<?= sf_admin_form_disabled_attr() ?>></label><label>Max Tracks<input type="number" name="max_playlist_tracks" value="<?= sf_admin_h($planEdit['max_playlist_tracks'] ?? '') ?>" placeholder="blank = unlimited"<?= sf_admin_form_disabled_attr() ?>></label><label>Tier<?= sf_admin_select('plan_tier', ['free'=>'Free','monthly'=>'Monthly','annual'=>'Annual','founding_fan'=>'Founding Fan','admin'=>'Admin'], $planEdit['plan_tier'] ?? 'monthly') ?></label></div><?php endif; ?>
      <label>Status<?= sf_admin_select('status', ['active'=>'Active','inactive'=>'Inactive'], $planEdit['status'] ?? 'active') ?></label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $planEdit ? 'Save Plan' : 'Create Plan' ?></button></div>
    </form>
    <?php if ($planEdit): ?><form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_plan"><input type="hidden" name="id" value="<?= (int)($planEdit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Plan') ?></form><?php endif; ?>
  </article>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Direct Grants</span><h2><?= count($grants) ?> access grants</h2></div><a href="<?= sf_url('admin/media-access.php') ?>">New Grant</a></div>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>User</th><th>Content</th><th>Access</th><th>Expires</th><th></th></tr></thead><tbody>
      <?php if (!$grants): ?><tr><td colspan="5">No direct grants yet.</td></tr><?php endif; ?>
      <?php foreach ($grants as $grant): ?>
      <tr><td><strong><?= sf_admin_h($grant['display_name'] ?: $grant['email'] ?: ('User #' . ($grant['user_id'] ?? ''))) ?></strong><small>ID <?= sf_admin_h($grant['user_id'] ?? '') ?></small></td><td><?= sf_admin_h(($grant['content_type'] ?? '') . ' #' . ($grant['content_id'] ?? 'all')) ?></td><td><?= sf_admin_h(sf_access_label((string)($grant['access_level'] ?? 'subscriber'))) ?></td><td><?= sf_admin_h($grant['expires_at'] ?? 'No expiry') ?></td><td><a href="<?= sf_url('admin/media-access.php?grant_edit=' . (int)($grant['id'] ?? 0)) ?>">Edit</a></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $grantEdit ? 'Edit Grant' : 'Create Grant' ?></span><h2>Content access grant</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_grant"><input type="hidden" name="id" value="<?= sf_admin_h($grantEdit['id'] ?? '') ?>">
      <label>User
        <select name="user_id">
          <option value="">Select user</option>
          <?php foreach ($users as $user): ?><option value="<?= (int)($user['id'] ?? 0) ?>" <?= ((string)($user['id'] ?? '') === (string)($grantEdit['user_id'] ?? '')) ? 'selected' : '' ?>><?= sf_admin_h(($user['display_name'] ?: $user['email'] ?: 'User') . ' — #' . ($user['id'] ?? '')) ?></option><?php endforeach; ?>
        </select>
      </label>
      <div class="sf-admin-form-grid"><label>Content Type<?= sf_admin_select('content_type', ['album'=>'Album','song'=>'Song','playlist'=>'Playlist','episode'=>'Episode','video'=>'Video','product'=>'Product','site_feature'=>'Site Feature'], $grantEdit['content_type'] ?? 'site_feature') ?></label><label>Content ID<input type="number" name="content_id" value="<?= sf_admin_h($grantEdit['content_id'] ?? '') ?>" placeholder="blank = all of type"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-grid"><label>Grant Type<?= sf_admin_select('grant_type', ['subscription'=>'Subscription','purchase'=>'Purchase','admin_grant'=>'Admin Grant','promo'=>'Promo','founding_fan'=>'Founding Fan'], $grantEdit['grant_type'] ?? 'admin_grant') ?></label><label>Access Level<?= sf_admin_select('access_level', ['public'=>'Public','free_account'=>'Free Account','subscriber'=>'Subscriber','premium'=>'Premium','founding_fan'=>'Founding Fan','admin'=>'Admin'], $grantEdit['access_level'] ?? 'subscriber') ?></label></div>
      <div class="sf-admin-form-grid"><label>Starts At<input type="datetime-local" name="starts_at" value="<?= sf_admin_h(isset($grantEdit['starts_at']) ? str_replace(' ', 'T', substr((string)$grantEdit['starts_at'], 0, 16)) : '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Expires At<input type="datetime-local" name="expires_at" value="<?= sf_admin_h(isset($grantEdit['expires_at']) ? str_replace(' ', 'T', substr((string)$grantEdit['expires_at'], 0, 16)) : '') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $grantEdit ? 'Save Grant' : 'Create Grant' ?></button></div>
    </form>
    <?php if ($grantEdit): ?><form method="post" class="sf-admin-delete-form"><input type="hidden" name="action" value="delete_grant"><input type="hidden" name="id" value="<?= (int)($grantEdit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Grant') ?></form><?php endif; ?>
  </article>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
