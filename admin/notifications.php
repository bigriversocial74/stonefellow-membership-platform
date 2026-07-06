<?php
$pageTitle = 'Notifications Admin';
$pageDescription = 'Manage Stonefellow email notifications, queue status, transactional logs, and provider health.';
$pageClass = 'membership-page admin-catalog-page notifications-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/notifications.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect(sf_url('admin/notifications.php'));
  }
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'seed_templates') {
    $count = sf_notify_seed_default_templates();
    sf_admin_flash($count > 0 ? 'success' : 'warning', $count > 0 ? 'Seeded/updated ' . $count . ' templates.' : 'Notification template table is not available yet.');
  }
  if ($action === 'dispatch_pending') {
    $result = sf_notify_dispatch_pending(50);
    sf_admin_flash('success', 'Dispatch complete: ' . (int)$result['processed'] . ' processed, ' . (int)$result['sent'] . ' sent, ' . (int)$result['failed'] . ' failed.');
  }
  if ($action === 'resend' && sf_notify_ready()) {
    $logId = sf_admin_int($_POST['log_id'] ?? null, 0) ?? 0;
    if ($logId > 0) {
      sf_admin_execute("UPDATE notification_logs SET status='queued', error_message=NULL, scheduled_at=NULL, updated_at=NOW() WHERE id=?", [$logId]);
      sf_notify_dispatch_log($logId);
      sf_admin_flash('success', 'Notification resent.');
    }
  }
  if ($action === 'send_test') {
    $templateKey = trim((string)($_POST['template_key'] ?? 'welcome'));
    $email = trim((string)($_POST['test_email'] ?? ''));
    $name = trim((string)($_POST['test_name'] ?? 'Stonefellow Admin'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      sf_admin_flash('error', 'Enter a valid test email.');
    } else {
      $logId = sf_notify_send_template($templateKey, ['email' => $email, 'name' => $name], [
        'recipient_name' => $name,
        'plan_name' => 'All Access',
        'period_end' => date('Y-m-d H:i:s', strtotime('+30 days')),
        'invoice_number' => 'TEST-' . date('YmdHis'),
        'amount' => '$9.99',
        'order_number' => 'SF-TEST',
        'order_total' => '$49.00',
        'receipt_url' => sf_notify_absolute_url('order-confirmation.php?order=SF-TEST'),
        'admin_order_url' => sf_notify_absolute_url('admin/orders.php'),
        'reset_url' => sf_notify_absolute_url('reset-password.php?token=preview'),
        'expires_minutes' => 45,
        'fulfillment_note' => 'This is a test fulfillment notification.',
        'payment_status' => 'failed',
        'provider_payment_id' => 'test_payment_id',
        'error_message' => 'This is a test alert.',
        'sender_name' => 'Stonefellow',
        'playlist_name' => 'Road Crew Preview',
        'playlist_url' => sf_notify_absolute_url('playlists.php'),
      ], ['notification_type' => 'admin_test', 'metadata' => ['event' => 'admin_test_send'], 'dispatch' => true]);
      sf_admin_flash('success', 'Test notification created' . ($logId ? ' as log #' . $logId : '') . '.');
    }
  }
  sf_admin_redirect(sf_url('admin/notifications.php'));
}

$summary = sf_notify_summary();
$logs = sf_notify_recent_logs(100);
$templates = sf_notify_templates();
$provider = sf_notify_provider();

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Notification Runtime v1', 'Email + Notifications', 'Review transactional email health, send test messages, dispatch queued notifications, and inspect delivery logs.', 'notifications');
?>
<section class="sf-admin-metrics-grid">
  <article><span>Templates</span><strong><?= number_format((int)$summary['templates']) ?></strong><small>Email template records</small></article>
  <article><span>Queued</span><strong><?= number_format((int)$summary['queued']) ?></strong><small>Waiting for dispatch</small></article>
  <article><span>Sent</span><strong><?= number_format((int)$summary['sent']) ?></strong><small>Provider accepted/logged</small></article>
  <article><span>Failed</span><strong><?= number_format((int)$summary['failed']) ?></strong><small>Needs review</small></article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Provider</span><h2>Runtime status</h2></div>
    <a href="<?= sf_url('docs/EMAIL_NOTIFICATION_RUNTIME_V1.md') ?>">Docs</a>
  </div>
  <div class="sf-admin-roadmap">
    <div><span><?= sf_notify_ready() ? '✓' : '!' ?></span><strong><?= sf_notify_ready() ? 'Notification tables ready' : 'Migration needed' ?></strong><p><?= sf_notify_ready() ? 'Templates, logs, preferences, and webhook events can save to MySQL.' : 'Run database/migrations/006_email_notifications.sql to enable database-backed email logs.' ?></p></div>
    <div><span>✓</span><strong><?= sf_admin_h($provider) ?> provider</strong><p>Default log/sandbox mode records messages without sending. Use SF_MAIL_PROVIDER=mail for PHP mail() handoff.</p></div>
    <div><span>→</span><strong>Production adapter</strong><p>Webhook endpoint and provider fields are ready for SendGrid, Postmark, Mailgun, SES, or SMTP adapter work.</p></div>
  </div>
</section>

<section class="sf-admin-two-col sf-admin-catalog-layout">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Controls</span><h2>Queue actions</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="seed_templates">
      <p class="sf-admin-copy">Install or refresh the default transactional templates. Safe to run multiple times.</p>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Seed Default Templates</button></div>
    </form>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="dispatch_pending">
      <p class="sf-admin-copy">Send up to 50 queued notifications that are ready for dispatch.</p>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_notify_ready() ? '' : ' disabled' ?>>Dispatch Pending</button></div>
    </form>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Test</span><h2>Send template preview</h2></div><a href="<?= sf_url('admin/email-templates.php') ?>">Edit Templates</a></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="send_test">
      <label>Template
        <select name="template_key">
          <?php foreach ($templates as $template): ?>
            <option value="<?= sf_admin_h($template['template_key'] ?? '') ?>"><?= sf_admin_h(($template['category'] ?? 'email') . ' / ' . ($template['template_key'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Test recipient email<input type="email" name="test_email" value="<?= sf_admin_h(getenv('SF_ADMIN_TEST_EMAIL') ?: '') ?>" placeholder="you@example.com" required></label>
      <label>Recipient name<input type="text" name="test_name" value="Stonefellow Admin"></label>
      <div class="sf-admin-form-actions"><button type="submit">Create Test Notification</button></div>
    </form>
  </article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Delivery Log</span><h2>Recent notifications</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Status</th><th>Recipient</th><th>Template</th><th>Subject</th><th>Provider</th><th>Attempts</th><th>Created</th><th></th></tr></thead><tbody>
    <?php if (!$logs): ?><tr><td colspan="8">No notifications logged yet. Send a test or trigger signup, reset, checkout, or order flows.</td></tr><?php endif; ?>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= sf_admin_status_badge((string)($log['status'] ?? 'queued')) ?><?php if (!empty($log['error_message'])): ?><br><small><?= sf_admin_h($log['error_message']) ?></small><?php endif; ?></td>
        <td><?= sf_admin_h($log['recipient_name'] ?? $log['display_name'] ?? '') ?><br><small><?= sf_admin_h($log['recipient_email'] ?? '') ?></small></td>
        <td><?= sf_admin_h($log['template_key'] ?? '') ?><br><small><?= sf_admin_h($log['notification_type'] ?? '') ?></small></td>
        <td><?= sf_admin_h($log['subject'] ?? '') ?><br><small><?= sf_admin_h($log['body_preview'] ?? '') ?></small></td>
        <td><?= sf_admin_h($log['provider'] ?? '') ?><br><small><?= sf_admin_h($log['provider_message_id'] ?? '') ?></small></td>
        <td><?= (int)($log['attempts'] ?? 0) ?></td>
        <td><?= sf_admin_h($log['created_at'] ?? '') ?></td>
        <td><?php if (sf_notify_ready() && !empty($log['id'])): ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="resend"><input type="hidden" name="log_id" value="<?= (int)$log['id'] ?>"><button type="submit">Resend</button></form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
