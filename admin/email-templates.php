<?php
$pageTitle = 'Email Templates';
$pageDescription = 'Manage Stonefellow transactional email template subject lines, HTML bodies, text bodies, and variables.';
$pageClass = 'membership-page admin-catalog-page notifications-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/notifications.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect(sf_url('admin/email-templates.php'));
  }
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'seed_templates') {
    $count = sf_notify_seed_default_templates();
    sf_admin_flash($count > 0 ? 'success' : 'warning', $count > 0 ? 'Seeded/updated ' . $count . ' templates.' : 'Template table is not available yet.');
    sf_admin_redirect(sf_url('admin/email-templates.php'));
  }
  if ($action === 'save_template') {
    $ok = sf_notify_save_template($_POST);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Template saved.' : 'Template could not be saved. Check required fields and JSON variables.');
    sf_admin_redirect(sf_url('admin/email-templates.php?edit=' . (int)($_POST['id'] ?? 0)));
  }
}

$templates = sf_notify_templates();
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$editing = $editId > 0 ? sf_notify_template_by_id($editId) : ($templates[0] ?? null);
if (!$editing && $templates) {
  $editing = $templates[0];
}

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Notification Runtime v1', 'Email Templates', 'Manage reusable email templates for auth, billing, merch orders, admin alerts, and member notifications.', 'email-templates');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/notifications.php') ?>"><span>Logs</span><strong>Notification Queue</strong><small>Review sends, failures, and provider status.</small></a>
  <form class="sf-admin-action-card" method="post" style="display:block;">
    <?= sf_csrf_field() ?>
    <input type="hidden" name="action" value="seed_templates">
    <span>Seed</span><strong>Refresh Defaults</strong><small>Install/update default transactional templates.</small><br><br>
    <button type="submit"<?= sf_admin_form_disabled_attr() ?>>Seed Templates</button>
  </form>
</section>

<section class="sf-admin-two-col sf-admin-catalog-layout">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Templates</span><h2>Email library</h2></div></div>
    <div class="sf-admin-list">
      <?php foreach ($templates as $template): ?>
        <?php $id = (int)($template['id'] ?? 0); $selected = $editing && (($id > 0 && $id === (int)($editing['id'] ?? 0)) || (($template['template_key'] ?? '') === ($editing['template_key'] ?? ''))); ?>
        <a class="sf-admin-list-row <?= $selected ? 'is-selected' : '' ?>" href="<?= $id > 0 ? sf_url('admin/email-templates.php?edit=' . $id) : '#' ?>">
          <strong><?= sf_admin_h($template['name'] ?? $template['template_key'] ?? '') ?></strong>
          <span><?= sf_admin_h($template['category'] ?? '') ?> · <?= sf_admin_h($template['template_key'] ?? '') ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$templates): ?><p class="sf-admin-copy">No templates found. Run migration 006, then seed defaults.</p><?php endif; ?>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Edit</span><h2><?= $editing ? sf_admin_h($editing['template_key'] ?? 'Template') : 'New template' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_template">
      <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
      <div class="sf-admin-form-grid">
        <label>Template Key<input name="template_key" value="<?= sf_admin_h($editing['template_key'] ?? '') ?>" placeholder="welcome" required<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Name<input name="name" value="<?= sf_admin_h($editing['name'] ?? '') ?>" placeholder="Welcome Email" required<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Category<input name="category" value="<?= sf_admin_h($editing['category'] ?? 'transactional') ?>" placeholder="auth"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Status <?= sf_admin_select('status', ['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'], $editing['status'] ?? 'active') ?></label>
      </div>
      <label>Subject<input name="subject" value="<?= sf_admin_h($editing['subject'] ?? '') ?>" placeholder="Welcome to {{site_name}}" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>HTML Body<textarea name="html_body" rows="10" required<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($editing['html_body'] ?? '') ?></textarea></label>
      <label>Text Body<textarea name="text_body" rows="5"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($editing['text_body'] ?? '') ?></textarea></label>
      <label>Variables JSON<textarea name="variables_json" rows="3"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h(is_string($editing['variables_json'] ?? null) ? $editing['variables_json'] : json_encode($editing['variables_json'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea></label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Template</button></div>
    </form>
  </article>
</section>

<?php if ($editing): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Preview</span><h2>Rendered sample</h2></div></div>
  <?php
    $previewVars = sf_notify_merge_vars([
      'recipient_name' => 'Stonefellow Fan',
      'plan_name' => 'All Access',
      'period_end' => date('Y-m-d H:i:s', strtotime('+30 days')),
      'invoice_number' => 'PREVIEW-001',
      'amount' => '$9.99',
      'order_number' => 'SF-PREVIEW',
      'order_total' => '$49.00',
      'receipt_url' => sf_notify_absolute_url('order-confirmation.php?order=SF-PREVIEW'),
      'admin_order_url' => sf_notify_absolute_url('admin/orders.php'),
      'reset_url' => sf_notify_absolute_url('reset-password.php?token=preview'),
      'expires_minutes' => 45,
      'fulfillment_note' => 'Your order is complete.',
      'payment_status' => 'paid',
      'provider_payment_id' => 'preview_payment',
      'error_message' => '',
      'sender_name' => 'Stonefellow',
      'playlist_name' => 'Road Crew Preview',
      'playlist_url' => sf_notify_absolute_url('playlists.php'),
    ], ['email' => 'fan@example.com', 'name' => 'Stonefellow Fan']);
  ?>
  <div class="sf-admin-preview-card">
    <h3><?= sf_admin_h(sf_notify_render((string)($editing['subject'] ?? ''), $previewVars)) ?></h3>
    <div><?= sf_notify_render((string)($editing['html_body'] ?? ''), $previewVars) ?></div>
  </div>
</section>
<?php endif; ?>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
