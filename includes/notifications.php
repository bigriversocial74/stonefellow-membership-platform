<?php
require_once __DIR__ . '/db.php';

const SF_NOTIFICATION_SESSION_LOG = 'sf_notification_session_log';

function sf_notify_h($value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sf_notify_provider(): string {
  $provider = strtolower(trim((string)(getenv('SF_MAIL_PROVIDER') ?: getenv('SF_EMAIL_PROVIDER') ?: 'log')));
  return $provider !== '' ? $provider : 'log';
}

function sf_notify_from_email(): string {
  global $site;
  return trim((string)(getenv('SF_MAIL_FROM_EMAIL') ?: ($site['support_email'] ?? 'support@stonefellow.tv')));
}

function sf_notify_from_name(): string {
  global $site;
  return trim((string)(getenv('SF_MAIL_FROM_NAME') ?: ($site['name'] ?? 'Stonefellow')));
}

function sf_notify_absolute_url(string $path): string {
  $path = ltrim($path, '/');
  $base = trim((string)(getenv('SF_PUBLIC_URL') ?: ''), '/');
  if ($base !== '') {
    return $base . '/' . $path;
  }
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if ($host !== '') {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $scriptDir = preg_replace('~/(admin|api)$~', '', $scriptDir) ?: '';
    $prefix = trim($scriptDir, '/');
    return $scheme . '://' . $host . ($prefix !== '' ? '/' . $prefix : '') . '/' . $path;
  }
  return sf_url($path);
}

function sf_notify_money(int $cents, string $currency = 'USD'): string {
  return '$' . number_format($cents / 100, 2) . ($currency !== 'USD' ? ' ' . strtoupper($currency) : '');
}

function sf_notify_table_exists(string $table): bool {
  $pdo = sf_db();
  if (!$pdo) {
    return false;
  }
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    error_log('Stonefellow notification table check failed: ' . $e->getMessage());
    return false;
  }
}

function sf_notify_ready(): bool {
  return sf_db() instanceof PDO
    && sf_notify_table_exists('email_templates')
    && sf_notify_table_exists('notification_logs');
}

function sf_notify_default_templates(): array {
  $memberUrl = sf_notify_absolute_url('member.php');
  $billingUrl = sf_notify_absolute_url('account-billing.php');
  return [
    'welcome' => [
      'name' => 'Welcome Email',
      'category' => 'auth',
      'subject' => 'Welcome to {{site_name}}',
      'html_body' => '<h1>Welcome to {{site_name}}, {{recipient_name}}.</h1><p>Your account is active. You can now stream music, watch episodes, build playlists, and follow the Stonefellow story.</p><p><a href="{{member_url}}">Open your member dashboard</a></p>',
      'text_body' => 'Welcome to {{site_name}}, {{recipient_name}}. Open your member dashboard: {{member_url}}',
      'variables' => ['site_name','recipient_name','member_url'],
    ],
    'password_reset' => [
      'name' => 'Password Reset',
      'category' => 'auth',
      'subject' => 'Reset your {{site_name}} password',
      'html_body' => '<h1>Password reset request</h1><p>Use this secure link within {{expires_minutes}} minutes:</p><p><a href="{{reset_url}}">Reset password</a></p><p>If you did not request this, you can ignore this message.</p>',
      'text_body' => 'Reset your {{site_name}} password within {{expires_minutes}} minutes: {{reset_url}}',
      'variables' => ['site_name','expires_minutes','reset_url'],
    ],
    'subscription_started' => [
      'name' => 'Subscription Started',
      'category' => 'billing',
      'subject' => 'Your {{plan_name}} membership is active',
      'html_body' => '<h1>Your membership is active.</h1><p>Thanks for joining {{site_name}}. Your {{plan_name}} access is active through {{period_end}}.</p><p><a href="{{member_url}}">Open member dashboard</a></p>',
      'text_body' => 'Your {{plan_name}} membership is active through {{period_end}}. {{member_url}}',
      'variables' => ['plan_name','period_end','member_url'],
    ],
    'subscription_canceled' => [
      'name' => 'Subscription Canceled',
      'category' => 'billing',
      'subject' => 'Your {{site_name}} membership was canceled',
      'html_body' => '<h1>Membership canceled</h1><p>Your membership status changed to {{subscription_status}}. Access remains available until {{period_end}} when applicable.</p>',
      'text_body' => 'Your membership status changed to {{subscription_status}}. Access until: {{period_end}}',
      'variables' => ['subscription_status','period_end'],
    ],
    'payment_receipt' => [
      'name' => 'Payment Receipt',
      'category' => 'billing',
      'subject' => '{{site_name}} receipt {{invoice_number}}',
      'html_body' => '<h1>Payment receipt</h1><p>Invoice {{invoice_number}} was paid for {{amount}}.</p><p>Plan: {{plan_name}}</p><p><a href="{{billing_url}}">View billing</a></p>',
      'text_body' => 'Receipt {{invoice_number}} paid for {{amount}}. Plan: {{plan_name}}. {{billing_url}}',
      'variables' => ['invoice_number','amount','plan_name','billing_url'],
    ],
    'merch_order_confirmation' => [
      'name' => 'Merch Order Confirmation',
      'category' => 'commerce',
      'subject' => 'Stonefellow order {{order_number}} received',
      'html_body' => '<h1>Order received.</h1><p>Thanks, {{recipient_name}}. Your order {{order_number}} total is {{order_total}}.</p><p><a href="{{receipt_url}}">View receipt</a></p>',
      'text_body' => 'Order {{order_number}} received. Total: {{order_total}}. Receipt: {{receipt_url}}',
      'variables' => ['recipient_name','order_number','order_total','receipt_url'],
    ],
    'order_fulfilled' => [
      'name' => 'Order Fulfilled',
      'category' => 'commerce',
      'subject' => 'Stonefellow order {{order_number}} was fulfilled',
      'html_body' => '<h1>Your order was fulfilled.</h1><p>Order {{order_number}} has been marked fulfilled.</p><p>{{fulfillment_note}}</p><p><a href="{{receipt_url}}">View receipt</a></p>',
      'text_body' => 'Order {{order_number}} was fulfilled. {{fulfillment_note}} {{receipt_url}}',
      'variables' => ['order_number','fulfillment_note','receipt_url'],
    ],
    'admin_new_order' => [
      'name' => 'Admin New Order Alert',
      'category' => 'admin',
      'subject' => 'New Stonefellow order {{order_number}}',
      'html_body' => '<h1>New merch order</h1><p>{{recipient_name}} placed order {{order_number}} for {{order_total}}.</p><p><a href="{{admin_order_url}}">Review order</a></p>',
      'text_body' => 'New merch order {{order_number}} for {{order_total}}. Review: {{admin_order_url}}',
      'variables' => ['recipient_name','order_number','order_total','admin_order_url'],
    ],
    'admin_failed_payment' => [
      'name' => 'Admin Failed Payment Alert',
      'category' => 'admin',
      'subject' => 'Stonefellow payment alert: {{payment_status}}',
      'html_body' => '<h1>Payment alert</h1><p>Status: {{payment_status}}</p><p>Provider ref: {{provider_payment_id}}</p><p>{{error_message}}</p>',
      'text_body' => 'Payment alert {{payment_status}}. Provider ref: {{provider_payment_id}}. {{error_message}}',
      'variables' => ['payment_status','provider_payment_id','error_message'],
    ],
    'playlist_share' => [
      'name' => 'Playlist Share',
      'category' => 'member',
      'subject' => '{{recipient_name}} shared a Stonefellow playlist',
      'html_body' => '<h1>A Stonefellow playlist was shared with you.</h1><p>{{sender_name}} shared {{playlist_name}}.</p><p><a href="{{playlist_url}}">Open playlist</a></p>',
      'text_body' => '{{sender_name}} shared {{playlist_name}}: {{playlist_url}}',
      'variables' => ['sender_name','playlist_name','playlist_url'],
    ],
  ];
}

function sf_notify_seed_default_templates(): int {
  if (!sf_notify_table_exists('email_templates')) {
    return 0;
  }
  $pdo = sf_db();
  $count = 0;
  $sql = "INSERT INTO email_templates (template_key, name, category, subject, html_body, text_body, variables_json, status)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
          ON DUPLICATE KEY UPDATE name=VALUES(name), category=VALUES(category), subject=VALUES(subject), html_body=VALUES(html_body), text_body=VALUES(text_body), variables_json=VALUES(variables_json), updated_at=NOW()";
  $stmt = $pdo->prepare($sql);
  foreach (sf_notify_default_templates() as $key => $template) {
    $stmt->execute([
      $key,
      $template['name'],
      $template['category'],
      $template['subject'],
      $template['html_body'],
      $template['text_body'],
      json_encode($template['variables'] ?? [], JSON_UNESCAPED_SLASHES),
    ]);
    $count++;
  }
  return $count;
}

function sf_notify_template(string $templateKey): ?array {
  $templateKey = trim($templateKey);
  if ($templateKey === '') {
    return null;
  }
  if (sf_notify_table_exists('email_templates')) {
    try {
      $stmt = sf_db()->prepare("SELECT * FROM email_templates WHERE template_key = ? AND status = 'active' LIMIT 1");
      $stmt->execute([$templateKey]);
      $row = $stmt->fetch();
      if ($row) {
        return $row;
      }
    } catch (Throwable $e) {
      error_log('Stonefellow template lookup failed: ' . $e->getMessage());
    }
  }
  $defaults = sf_notify_default_templates();
  if (!isset($defaults[$templateKey])) {
    return null;
  }
  return [
    'template_key' => $templateKey,
    'name' => $defaults[$templateKey]['name'],
    'category' => $defaults[$templateKey]['category'],
    'subject' => $defaults[$templateKey]['subject'],
    'html_body' => $defaults[$templateKey]['html_body'],
    'text_body' => $defaults[$templateKey]['text_body'],
    'variables_json' => json_encode($defaults[$templateKey]['variables'] ?? [], JSON_UNESCAPED_SLASHES),
    'status' => 'active',
  ];
}

function sf_notify_merge_vars(array $vars, array $recipient = []): array {
  global $site;
  $merged = array_merge([
    'site_name' => $site['name'] ?? 'Stonefellow',
    'support_email' => $site['support_email'] ?? sf_notify_from_email(),
    'recipient_name' => $recipient['name'] ?? $recipient['display_name'] ?? '',
    'recipient_email' => $recipient['email'] ?? '',
    'member_url' => sf_notify_absolute_url('member.php'),
    'billing_url' => sf_notify_absolute_url('account-billing.php'),
  ], $vars);
  foreach ($merged as $key => $value) {
    if (is_array($value) || is_object($value)) {
      $merged[$key] = json_encode($value, JSON_UNESCAPED_SLASHES);
    }
  }
  return $merged;
}

function sf_notify_render(string $template, array $vars): string {
  return preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', static function ($matches) use ($vars) {
    $key = $matches[1];
    return array_key_exists($key, $vars) ? (string)$vars[$key] : '';
  }, $template) ?? $template;
}

function sf_notify_body_preview(string $text): string {
  $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
  return function_exists('mb_substr') ? mb_substr($clean, 0, 500) : substr($clean, 0, 500);
}

function sf_notify_user_recipient(?int $userId): ?array {
  if (!$userId || !sf_db()) {
    return null;
  }
  try {
    $stmt = sf_db()->prepare('SELECT id, email, display_name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || empty($row['email'])) {
      return null;
    }
    return ['user_id' => (int)$row['id'], 'email' => $row['email'], 'name' => $row['display_name'] ?: $row['email']];
  } catch (Throwable $e) {
    return null;
  }
}

function sf_notify_admin_recipients(): array {
  global $site;
  $recipients = [];
  $env = trim((string)(getenv('SF_ADMIN_EMAILS') ?: ''));
  if ($env !== '') {
    foreach (preg_split('/[,;]/', $env) ?: [] as $email) {
      $email = trim($email);
      if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $recipients[$email] = ['email' => $email, 'name' => 'Stonefellow Admin'];
      }
    }
  }
  if (sf_db()) {
    try {
      $rows = sf_db()->query("SELECT id, email, display_name FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id ASC LIMIT 25")->fetchAll() ?: [];
      foreach ($rows as $row) {
        if (!empty($row['email'])) {
          $recipients[$row['email']] = ['user_id' => (int)$row['id'], 'email' => $row['email'], 'name' => $row['display_name'] ?: 'Stonefellow Admin'];
        }
      }
    } catch (Throwable $e) {}
  }
  if (!$recipients && !empty($site['support_email']) && filter_var($site['support_email'], FILTER_VALIDATE_EMAIL)) {
    $recipients[$site['support_email']] = ['email' => $site['support_email'], 'name' => 'Stonefellow Admin'];
  }
  return array_values($recipients);
}

function sf_notify_recipient(array|string $recipient): array {
  if (is_string($recipient)) {
    return ['email' => trim($recipient), 'name' => ''];
  }
  return [
    'user_id' => isset($recipient['user_id']) ? (int)$recipient['user_id'] : (isset($recipient['id']) ? (int)$recipient['id'] : null),
    'email' => trim((string)($recipient['email'] ?? '')),
    'name' => trim((string)($recipient['name'] ?? $recipient['display_name'] ?? '')),
  ];
}

function sf_notify_log(array $payload): int {
  $payload['provider'] = $payload['provider'] ?? sf_notify_provider();
  if (sf_notify_ready()) {
    try {
      $stmt = sf_db()->prepare("INSERT INTO notification_logs
        (user_id, recipient_email, recipient_name, channel, notification_type, template_key, subject, rendered_html, rendered_text, body_preview, status, provider, attempts, error_message, metadata_json, scheduled_at, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([
        $payload['user_id'] ?? null,
        $payload['recipient_email'] ?? '',
        $payload['recipient_name'] ?? null,
        $payload['channel'] ?? 'email',
        $payload['notification_type'] ?? 'transactional',
        $payload['template_key'] ?? null,
        $payload['subject'] ?? null,
        $payload['rendered_html'] ?? null,
        $payload['rendered_text'] ?? null,
        $payload['body_preview'] ?? null,
        $payload['status'] ?? 'queued',
        $payload['provider'] ?? 'log',
        (int)($payload['attempts'] ?? 0),
        $payload['error_message'] ?? null,
        isset($payload['metadata_json']) ? (is_string($payload['metadata_json']) ? $payload['metadata_json'] : json_encode($payload['metadata_json'], JSON_UNESCAPED_SLASHES)) : null,
        $payload['scheduled_at'] ?? null,
        $payload['sent_at'] ?? null,
      ]);
      return (int)sf_db()->lastInsertId();
    } catch (Throwable $e) {
      error_log('Stonefellow notification log insert failed: ' . $e->getMessage());
    }
  }

  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  $_SESSION[SF_NOTIFICATION_SESSION_LOG] = $_SESSION[SF_NOTIFICATION_SESSION_LOG] ?? [];
  $payload['id'] = count($_SESSION[SF_NOTIFICATION_SESSION_LOG]) + 1;
  $payload['created_at'] = date('Y-m-d H:i:s');
  $_SESSION[SF_NOTIFICATION_SESSION_LOG][] = $payload;
  return (int)$payload['id'];
}

function sf_notify_transport_send(array $message): array {
  $provider = (string)($message['provider'] ?? sf_notify_provider());
  $to = (string)($message['recipient_email'] ?? '');
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    return ['ok' => false, 'error' => 'Recipient email is invalid.'];
  }

  if (in_array($provider, ['log','sandbox','preview'], true)) {
    return ['ok' => true, 'provider_message_id' => 'log_' . substr(hash('sha256', $to . microtime(true)), 0, 18)];
  }

  if ($provider === 'mail') {
    $subject = (string)($message['subject'] ?? 'Stonefellow notification');
    $text = (string)($message['rendered_text'] ?? strip_tags((string)($message['rendered_html'] ?? '')));
    $headers = [];
    $headers[] = 'From: ' . sf_notify_from_name() . ' <' . sf_notify_from_email() . '>';
    $headers[] = 'Reply-To: ' . sf_notify_from_email();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $ok = @mail($to, $subject, $text, implode("\r\n", $headers));
    return ['ok' => $ok, 'provider_message_id' => $ok ? 'mail_' . substr(hash('sha256', $to . microtime(true)), 0, 18) : null, 'error' => $ok ? null : 'mail() returned false.'];
  }

  return ['ok' => false, 'error' => 'Unsupported mail provider: ' . $provider];
}

function sf_notify_update_log_status(int $logId, string $status, ?string $providerMessageId = null, ?string $error = null): void {
  if ($logId <= 0 || !sf_notify_ready()) {
    return;
  }
  try {
    $sentSql = $status === 'sent' ? ', sent_at = NOW()' : '';
    $stmt = sf_db()->prepare("UPDATE notification_logs SET status = ?, provider_message_id = COALESCE(?, provider_message_id), error_message = ?, attempts = attempts + 1{$sentSql}, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $providerMessageId, $error, $logId]);
  } catch (Throwable $e) {
    error_log('Stonefellow notification status update failed: ' . $e->getMessage());
  }
}

function sf_notify_dispatch_log(int $logId): bool {
  if ($logId <= 0) {
    return false;
  }
  if (!sf_notify_ready()) {
    return true;
  }
  try {
    $stmt = sf_db()->prepare("SELECT * FROM notification_logs WHERE id = ? LIMIT 1");
    $stmt->execute([$logId]);
    $row = $stmt->fetch();
    if (!$row) {
      return false;
    }
    if (!in_array((string)$row['status'], ['queued','failed'], true)) {
      return true;
    }
    $result = sf_notify_transport_send($row);
    if (!empty($result['ok'])) {
      sf_notify_update_log_status($logId, 'sent', $result['provider_message_id'] ?? null, null);
      if (!empty($row['template_key']) && sf_notify_table_exists('email_templates')) {
        sf_db()->prepare('UPDATE email_templates SET last_sent_at = NOW() WHERE template_key = ?')->execute([$row['template_key']]);
      }
      return true;
    }
    sf_notify_update_log_status($logId, 'failed', null, $result['error'] ?? 'Unknown provider error.');
    return false;
  } catch (Throwable $e) {
    sf_notify_update_log_status($logId, 'failed', null, $e->getMessage());
    return false;
  }
}

function sf_notify_send_template(string $templateKey, array|string $recipient, array $vars = [], array $options = []): ?int {
  $recipient = sf_notify_recipient($recipient);
  $template = sf_notify_template($templateKey);
  $provider = $options['provider'] ?? sf_notify_provider();
  $channel = $options['channel'] ?? 'email';
  $type = $options['notification_type'] ?? ($template['category'] ?? 'transactional');
  $metadata = $options['metadata'] ?? [];

  if (!$template || !filter_var($recipient['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $logId = sf_notify_log([
      'user_id' => $recipient['user_id'] ?? null,
      'recipient_email' => $recipient['email'] ?? '',
      'recipient_name' => $recipient['name'] ?? null,
      'channel' => $channel,
      'notification_type' => $type,
      'template_key' => $templateKey,
      'subject' => $template['subject'] ?? null,
      'status' => 'skipped',
      'provider' => $provider,
      'error_message' => !$template ? 'Template not found.' : 'Recipient email missing or invalid.',
      'metadata_json' => $metadata,
    ]);
    return $logId;
  }

  $vars = sf_notify_merge_vars($vars, $recipient);
  $subject = sf_notify_render((string)$template['subject'], $vars);
  $html = sf_notify_render((string)$template['html_body'], $vars);
  $text = sf_notify_render((string)($template['text_body'] ?? strip_tags($html)), $vars);
  $scheduledAt = trim((string)($options['scheduled_at'] ?? '')) ?: null;
  $shouldDispatch = array_key_exists('dispatch', $options) ? (bool)$options['dispatch'] : true;
  $status = $scheduledAt ? 'queued' : ($options['status'] ?? 'queued');
  if (!sf_notify_ready() && $shouldDispatch && !$scheduledAt && in_array((string)$provider, ['log','sandbox','preview'], true)) {
    $status = 'sent';
  }

  $logId = sf_notify_log([
    'user_id' => $recipient['user_id'] ?? null,
    'recipient_email' => $recipient['email'],
    'recipient_name' => $recipient['name'] ?: null,
    'channel' => $channel,
    'notification_type' => $type,
    'template_key' => $templateKey,
    'subject' => $subject,
    'rendered_html' => $html,
    'rendered_text' => $text,
    'body_preview' => sf_notify_body_preview($text ?: $html),
    'status' => $status,
    'provider' => $provider,
    'metadata_json' => $metadata,
    'scheduled_at' => $scheduledAt,
  ]);

  if ($shouldDispatch && !$scheduledAt) {
    sf_notify_dispatch_log($logId);
  }
  return $logId;
}

function sf_notify_dispatch_pending(int $limit = 25): array {
  $limit = max(1, min(100, $limit));
  $result = ['processed' => 0, 'sent' => 0, 'failed' => 0];
  if (!sf_notify_ready()) {
    return $result;
  }
  try {
    $rows = sf_db()->query("SELECT id FROM notification_logs WHERE status = 'queued' AND (scheduled_at IS NULL OR scheduled_at <= NOW()) ORDER BY created_at ASC LIMIT " . (int)$limit)->fetchAll() ?: [];
    foreach ($rows as $row) {
      $result['processed']++;
      if (sf_notify_dispatch_log((int)$row['id'])) {
        $result['sent']++;
      } else {
        $result['failed']++;
      }
    }
  } catch (Throwable $e) {
    error_log('Stonefellow dispatch pending failed: ' . $e->getMessage());
  }
  return $result;
}

function sf_notify_recent_logs(int $limit = 100): array {
  $limit = max(1, min(200, $limit));
  if (sf_notify_ready()) {
    try {
      return sf_db()->query("SELECT nl.*, u.email AS user_email, u.display_name FROM notification_logs nl LEFT JOIN users u ON u.id = nl.user_id ORDER BY nl.created_at DESC, nl.id DESC LIMIT " . (int)$limit)->fetchAll() ?: [];
    } catch (Throwable $e) {
      return [];
    }
  }
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  return array_reverse(array_slice($_SESSION[SF_NOTIFICATION_SESSION_LOG] ?? [], -$limit));
}

function sf_notify_templates(): array {
  if (sf_notify_table_exists('email_templates')) {
    try {
      return sf_db()->query('SELECT * FROM email_templates ORDER BY category ASC, template_key ASC')->fetchAll() ?: [];
    } catch (Throwable $e) {
      return [];
    }
  }
  $rows = [];
  foreach (sf_notify_default_templates() as $key => $template) {
    $rows[] = [
      'template_key' => $key,
      'name' => $template['name'],
      'category' => $template['category'],
      'subject' => $template['subject'],
      'html_body' => $template['html_body'],
      'text_body' => $template['text_body'],
      'variables_json' => json_encode($template['variables'] ?? [], JSON_UNESCAPED_SLASHES),
      'status' => 'active',
    ];
  }
  return $rows;
}

function sf_notify_template_by_id(int $id): ?array {
  if ($id <= 0 || !sf_notify_table_exists('email_templates')) {
    return null;
  }
  try {
    $stmt = sf_db()->prepare('SELECT * FROM email_templates WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  } catch (Throwable $e) {
    return null;
  }
}

function sf_notify_save_template(array $data): bool {
  if (!sf_notify_table_exists('email_templates')) {
    return false;
  }
  $id = isset($data['id']) ? (int)$data['id'] : 0;
  $templateKey = strtolower(trim((string)($data['template_key'] ?? '')));
  $templateKey = preg_replace('/[^a-z0-9_\-]+/', '_', $templateKey) ?: '';
  if ($templateKey === '' || trim((string)($data['subject'] ?? '')) === '' || trim((string)($data['html_body'] ?? '')) === '') {
    return false;
  }
  $variables = trim((string)($data['variables_json'] ?? ''));
  if ($variables !== '') {
    json_decode($variables, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $variables = json_encode(array_filter(array_map('trim', explode(',', $variables))), JSON_UNESCAPED_SLASHES);
    }
  } else {
    $variables = json_encode([], JSON_UNESCAPED_SLASHES);
  }
  $name = trim((string)($data['name'] ?? '')) ?: ucwords(str_replace(['_', '-'], ' ', $templateKey));
  $category = trim((string)($data['category'] ?? 'transactional')) ?: 'transactional';
  $category = preg_replace('/[^a-z0-9_\-]+/i', '_', $category) ?: 'transactional';
  $status = (string)($data['status'] ?? 'active');
  if (!in_array($status, ['active','draft','archived'], true)) {
    $status = 'active';
  }
  try {
    if ($id > 0) {
      $stmt = sf_db()->prepare('UPDATE email_templates SET template_key=?, name=?, category=?, subject=?, html_body=?, text_body=?, variables_json=?, status=?, updated_at=NOW() WHERE id=?');
      return $stmt->execute([$templateKey, $name, $category, trim((string)$data['subject']), (string)$data['html_body'], (string)$data['text_body'], $variables, $status, $id]);
    }
    $stmt = sf_db()->prepare('INSERT INTO email_templates (template_key, name, category, subject, html_body, text_body, variables_json, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    return $stmt->execute([$templateKey, $name, $category, trim((string)$data['subject']), (string)$data['html_body'], (string)$data['text_body'], $variables, $status]);
  } catch (Throwable $e) {
    error_log('Stonefellow save template failed: ' . $e->getMessage());
    return false;
  }
}

function sf_notify_summary(): array {
  $summary = ['templates' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'webhooks' => 0];
  if (!sf_db()) {
    $logs = sf_notify_recent_logs(200);
    foreach ($logs as $log) {
      $status = (string)($log['status'] ?? 'queued');
      if (isset($summary[$status])) {
        $summary[$status]++;
      }
    }
    $summary['templates'] = count(sf_notify_default_templates());
    return $summary;
  }
  try {
    if (sf_notify_table_exists('email_templates')) {
      $summary['templates'] = (int)sf_db()->query('SELECT COUNT(*) FROM email_templates')->fetchColumn();
    }
    if (sf_notify_table_exists('notification_logs')) {
      $rows = sf_db()->query('SELECT status, COUNT(*) AS total FROM notification_logs GROUP BY status')->fetchAll() ?: [];
      foreach ($rows as $row) {
        $status = (string)$row['status'];
        if (isset($summary[$status])) {
          $summary[$status] = (int)$row['total'];
        }
      }
    }
    if (sf_notify_table_exists('notification_webhook_events')) {
      $summary['webhooks'] = (int)sf_db()->query('SELECT COUNT(*) FROM notification_webhook_events')->fetchColumn();
    }
  } catch (Throwable $e) {}
  return $summary;
}
?>
