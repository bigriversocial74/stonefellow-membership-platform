<?php
$pageTitle = 'Publishing Workflow';
$pageDescription = 'Draft, scheduled, published, archived, and early-access publishing controls.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/publishing.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $type = (string)($_POST['content_type'] ?? '');
  $id = (int)($_POST['content_id'] ?? 0);
  $data = [
    'status' => (string)($_POST['status'] ?? 'draft'),
    'release_at' => sf_admin_datetime_or_null('release_at'),
    'publish_window_start' => sf_admin_datetime_or_null('publish_window_start'),
    'publish_window_end' => sf_admin_datetime_or_null('publish_window_end'),
    'access_level' => (string)($_POST['access_level'] ?? 'subscriber'),
    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
  ];
  if ($id > 0 && sf_publish_apply($type, $id, $data)) {
    sf_admin_flash('success', 'Publishing settings updated.');
  } else {
    sf_admin_flash('warning', 'Publishing settings were not saved. Confirm database tables and columns exist.');
  }
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$items = sf_publish_items();
$due = sf_publish_run_due();
$stats = ['draft'=>0,'scheduled'=>0,'published'=>0,'archived'=>0,'expired'=>0];
foreach ($items as $item) { $state = $item['computed_state'] ?? 'draft'; $stats[$state] = ($stats[$state] ?? 0) + 1; }
sf_admin_shell_start('Publishing', 'Release workflow v1', 'Manage draft, scheduled, published, archived, early-access, and featured content states across music, video, episodes, albums, and products.', 'publishing');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Published</span><strong><?= (int)($stats['published'] ?? 0) ?></strong><small>Live now.</small></div>
  <div class="sf-admin-action-card"><span>Scheduled</span><strong><?= (int)($stats['scheduled'] ?? 0) ?></strong><small>Release date is in the future.</small></div>
  <div class="sf-admin-action-card"><span>Drafts</span><strong><?= (int)($stats['draft'] ?? 0) ?></strong><small>Not public yet.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/release-schedule.php') ?>"><span>Calendar</span><strong>Release Schedule</strong><small>Episode/video calendar view.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Workflow Rules</span><h2>Publishing controls</h2></div></div>
  <div class="sf-admin-roadmap">
    <div><span>✓</span><strong>Draft</strong><p>Content is saved but unavailable.</p></div>
    <div><span>✓</span><strong>Scheduled</strong><p>Content is queued by release date/time and can be promoted by the due-runner.</p></div>
    <div><span>✓</span><strong>Published</strong><p>Content can be displayed if release windows and access rules pass.</p></div>
    <div><span>✓</span><strong>Early Access</strong><p>Use access level to reserve content for subscribers, premium, or founding fans.</p></div>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Content Registry</span><h2><?= count($items) ?> items</h2></div><small><?= (int)($due['changed'] ?? 0) ?> due items promoted this load.</small></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Content</th><th>State</th><th>Release</th><th>Access</th><th>Update</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><strong><?= sf_admin_h($item['display_title'] ?? '') ?></strong><small><?= sf_admin_h(($item['content_type'] ?? '') . ' · ' . ($item['slug'] ?? '')) ?></small></td>
        <td><?= sf_admin_status_badge((string)($item['computed_state'] ?? 'draft')) ?></td>
        <td><?= sf_admin_h($item['release_at'] ?? $item['publish_window_start'] ?? 'Unscheduled') ?></td>
        <td><?= sf_admin_h(sf_access_label((string)($item['access_level'] ?? 'public'))) ?></td>
        <td>
          <form class="sf-admin-inline-form" method="post">
            <?= sf_csrf_field() ?>
            <input type="hidden" name="content_type" value="<?= sf_admin_h($item['content_type'] ?? '') ?>">
            <input type="hidden" name="content_id" value="<?= (int)($item['id'] ?? 0) ?>">
            <?= sf_admin_select('status', sf_publish_statuses(), (string)($item['status'] ?? 'draft')) ?>
            <?= sf_admin_select('access_level', sf_publish_access_levels(), (string)($item['access_level'] ?? 'subscriber')) ?>
            <input type="datetime-local" name="release_at" value="<?= !empty($item['release_at']) ? sf_admin_h(str_replace(' ', 'T', substr((string)$item['release_at'], 0, 16))) : '' ?>">
            <label class="sf-admin-check"><input type="checkbox" name="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>> Featured</label>
            <button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="5">No publishable database content found yet. Run the installer and import/seed content.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
