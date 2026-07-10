<?php
$pageTitle = 'Notifications Admin';
$pageDescription = 'Manage Stonefellow email queue leases, retries, transactional logs, and provider health.';
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
        sf_admin_flash(
            $count > 0 ? 'success' : 'warning',
            $count > 0
                ? 'Seeded/updated ' . $count . ' templates.'
                : 'Notification template table is unavailable.'
        );
    } elseif ($action === 'dispatch_pending') {
        $result = sf_notify_dispatch_pending(50);
        sf_admin_flash(
            (int)$result['failed'] > 0 ? 'warning' : 'success',
            'Dispatch complete: ' . (int)$result['processed'] . ' processed, '
                . (int)$result['sent'] . ' sent, '
                . (int)$result['failed'] . ' failed.'
        );
    } elseif ($action === 'resend' && sf_notify_ready()) {
        $logId = (int)($_POST['log_id'] ?? 0);
        $row = $logId > 0
            ? sf_admin_fetch_one('SELECT status FROM notification_logs WHERE id = ? LIMIT 1', [$logId])
            : null;

        if (!$row || !in_array((string)$row['status'], ['failed', 'canceled'], true)) {
            sf_admin_flash('error', 'Only failed or canceled notifications can be retried.');
        } else {
            sf_admin_execute(
                "UPDATE notification_logs
                 SET status='queued', attempts=0, error_message=NULL, scheduled_at=NULL,
                     provider_message_id=NULL, updated_at=NOW()
                 WHERE id=?",
                [$logId]
            );
            $ok = sf_notify_dispatch_log($logId);
            sf_admin_flash(
                $ok ? 'success' : 'error',
                $ok
                    ? 'Notification retry was accepted by the provider.'
                    : 'Notification retry failed or remains queued for backoff.'
            );
        }
    } elseif ($action === 'send_test') {
        $templateKey = trim((string)($_POST['template_key'] ?? 'welcome'));
        $email = sf_delivery_safe_email((string)($_POST['test_email'] ?? ''));
        $name = sf_delivery_clean_header((string)($_POST['test_name'] ?? 'Stonefellow Admin'), 190);

        if ($email === '') {
            sf_admin_flash('error', 'Enter a valid test email.');
        } else {
            $stamp = date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
            $logId = sf_notify_send_template(
                $templateKey,
                ['email' => $email, 'name' => $name],
                [
                    'recipient_name' => $name,
                    'plan_name' => 'All Access',
                    'period_end' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'invoice_number' => 'TEST-' . $stamp,
                    'amount' => '$9.99',
                    'order_number' => 'SF-TEST',
                    'order_total' => '$49.00',
                    'receipt_url' => sf_notify_absolute_url('order-confirmation.php?order=SF-TEST'),
                    'admin_order_url' => sf_notify_absolute_url('admin/orders.php'),
                    'reset_url' => sf_notify_absolute_url('reset-password.php?token=preview'),
                    'expires_minutes' => 45,
                    'fulfillment_note' => 'Test fulfillment notification.',
                    'payment_status' => 'failed',
                    'provider_payment_id' => 'test_payment_id',
                    'error_message' => 'Test alert.',
                    'sender_name' => 'Stonefellow',
                    'playlist_name' => 'Road Crew Preview',
                    'playlist_url' => sf_notify_absolute_url('playlists.php'),
                    'message_subject' => 'Test message',
                    'message_body' => 'Test message body.',
                    'message_text' => 'Test message body.',
                    'action_url' => sf_notify_absolute_url('messages.php'),
                ],
                [
                    'notification_type' => 'admin',
                    'metadata' => ['event' => 'admin_test_send'],
                    'idempotency_key' => 'admin-test-' . $stamp,
                    'dispatch' => true,
                ]
            );

            $statusRow = $logId
                ? sf_admin_fetch_one('SELECT status, error_message FROM notification_logs WHERE id = ?', [$logId])
                : null;
            $status = (string)($statusRow['status'] ?? '');
            $ok = $statusRow && in_array($status, ['sent', 'queued'], true);

            if ($ok) {
                sf_admin_flash('success', 'Test notification logged as #' . $logId . ' with status ' . $status . '.');
            } else {
                $message = 'Test notification failed.';
                if (!empty($statusRow['error_message'])) {
                    $message = 'Test notification failed: ' . (string)$statusRow['error_message'];
                }
                sf_admin_flash('error', $message);
            }
        }
    }

    sf_admin_redirect(sf_url('admin/notifications.php'));
}

$summary = sf_notify_summary();
$logs = sf_notify_recent_logs(100);
$templates = sf_notify_templates();
$provider = sf_notify_provider();
$production = sf_is_production();
$mask = static function (string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return '';
    $local = $parts[0];
    return substr($local, 0, 1) . str_repeat('*', max(2, strlen($local) - 1)) . '@' . $parts[1];
};

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start(
    'Notification Runtime v1',
    'Email + Notifications',
    'Review idempotent delivery, bounded retries, provider status, and privacy-reduced logs.',
    'notifications'
);
?>
<section class="sf-admin-metrics-grid">
  <article><span>Templates</span><strong><?= (int)$summary['templates'] ?></strong><small>Email template records</small></article>
  <article><span>Queued</span><strong><?= (int)$summary['queued'] ?></strong><small>Waiting/backoff</small></article>
  <article><span>Sent</span><strong><?= (int)$summary['sent'] ?></strong><small>Provider accepted</small></article>
  <article><span>Failed / canceled</span><strong><?= (int)$summary['failed'] + (int)$summary['canceled'] ?></strong><small>Needs review</small></article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Provider</span><h2>Runtime status</h2></div>
    <a href="<?= sf_url('docs/EMAIL_NOTIFICATION_RUNTIME_V1.md') ?>">Docs</a>
  </div>
  <div class="sf-admin-roadmap">
    <div><span><?= sf_notify_ready() ? '✓' : '!' ?></span><strong><?= sf_notify_ready() ? 'Notification tables ready' : 'Migration needed' ?></strong><p>Queue rows use database locks, idempotency keys, max attempts, and exponential backoff.</p></div>
    <div><span><?= $production && in_array($provider, ['log','sandbox','preview'], true) ? '!' : '✓' ?></span><strong><?= sf_admin_h($provider) ?> provider</strong><p><?= $production && in_array($provider, ['log','sandbox','preview'], true) ? 'Log/sandbox providers fail closed in production unless explicitly enabled.' : 'Provider is selected; verify live credentials and delivery evidence.' ?></p></div>
    <div><span>→</span><strong>Signed webhooks</strong><p>Provider events require HMAC signatures and idempotent event IDs.</p></div>
  </div>
</section>

<section class="sf-admin-two-col sf-admin-catalog-layout">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Controls</span><h2>Queue actions</h2></div></div>
    <form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="seed_templates"><p class="sf-admin-copy">Refresh validated default templates.</p><div class="sf-admin-form-actions"><button type="submit">Seed Default Templates</button></div></form>
    <form class="sf-admin-form" method="post" onsubmit="return confirm('Dispatch up to 50 eligible queued notifications now?')"><?= sf_csrf_field() ?><input type="hidden" name="action" value="dispatch_pending"><p class="sf-admin-copy">Messages under retry backoff or an active delivery lock are skipped.</p><div class="sf-admin-form-actions"><button type="submit"<?= sf_notify_ready() ? '' : ' disabled' ?>>Dispatch Pending</button></div></form>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Test</span><h2>Send template test</h2></div><a href="<?= sf_url('admin/email-templates.php') ?>">Edit Templates</a></div>
    <form class="sf-admin-form" method="post" onsubmit="return confirm('Send this test through the configured provider?')"><?= sf_csrf_field() ?><input type="hidden" name="action" value="send_test"><label>Template<select name="template_key"><?php foreach ($templates as $template): ?><option value="<?= sf_admin_h($template['template_key'] ?? '') ?>"><?= sf_admin_h(($template['category'] ?? 'email') . ' / ' . ($template['template_key'] ?? '')) ?></option><?php endforeach; ?></select></label><label>Test recipient email<input type="email" name="test_email" maxlength="190" value="<?= sf_admin_h(getenv('SF_ADMIN_TEST_EMAIL') ?: '') ?>" required></label><label>Recipient name<input type="text" name="test_name" maxlength="190" value="Stonefellow Admin"></label><div class="sf-admin-form-actions"><button type="submit">Send Test</button></div></form>
  </article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Delivery Log</span><h2>Recent notifications</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Status</th><th>Recipient</th><th>Template</th><th>Subject</th><th>Provider</th><th>Attempts</th><th>Next / created</th><th></th></tr></thead><tbody>
  <?php foreach ($logs as $log): ?>
    <tr>
      <td><?= sf_admin_status_badge((string)($log['status'] ?? 'queued')) ?><?php if (!empty($log['error_message'])): ?><br><small><?= sf_admin_h($log['error_message']) ?></small><?php endif; ?></td>
      <td><?= sf_admin_h($log['recipient_name'] ?? $log['display_name'] ?? '') ?><br><small><?= sf_admin_h($mask((string)($log['recipient_email'] ?? ''))) ?></small></td>
      <td><?= sf_admin_h($log['template_key'] ?? '') ?><br><small><?= sf_admin_h($log['notification_type'] ?? '') ?></small></td>
      <td><?= sf_admin_h($log['subject'] ?? '') ?><br><small><?= sf_admin_h($log['body_preview'] ?? '') ?></small></td>
      <td><?= sf_admin_h($log['provider'] ?? '') ?><br><small><?= sf_admin_h($log['provider_message_id'] ?? '') ?></small></td>
      <td><?= (int)($log['attempts'] ?? 0) ?></td>
      <td><?= sf_admin_h($log['scheduled_at'] ?? $log['created_at'] ?? '') ?></td>
      <td><?php if (in_array((string)($log['status'] ?? ''), ['failed','canceled'], true)): ?><form method="post" onsubmit="return confirm('Reset attempts and retry this notification?')"><?= sf_csrf_field() ?><input type="hidden" name="action" value="resend"><input type="hidden" name="log_id" value="<?= (int)$log['id'] ?>"><button type="submit">Retry</button></form><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$logs): ?><tr><td colspan="8">No notification logs yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
