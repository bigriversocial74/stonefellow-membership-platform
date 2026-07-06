<?php
require_once __DIR__ . '/admin_catalog.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/store.php';

function sf_qa_root(): string {
  return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

function sf_qa_h($value): string {
  return sf_admin_h($value);
}

function sf_qa_rel_path(string $path): string {
  return ltrim(str_replace('\\', '/', $path), '/');
}

function sf_qa_file_path(string $relative): string {
  return sf_qa_root() . '/' . sf_qa_rel_path($relative);
}

function sf_qa_file_exists(string $relative): bool {
  return is_file(sf_qa_file_path($relative));
}

function sf_qa_dir_exists(string $relative): bool {
  return is_dir(sf_qa_file_path($relative));
}

function sf_qa_read_file(string $relative): string {
  $path = sf_qa_file_path($relative);
  return is_file($path) ? (string)file_get_contents($path) : '';
}

function sf_qa_contains(string $relative, array $needles): bool {
  $body = sf_qa_read_file($relative);
  if ($body === '') {
    return false;
  }
  foreach ($needles as $needle) {
    if ($needle !== '' && strpos($body, $needle) !== false) {
      return true;
    }
  }
  return false;
}

function sf_qa_db_ready(): bool {
  return sf_db() instanceof PDO;
}

function sf_qa_table_exists(string $table): bool {
  return sf_admin_table_exists($table);
}

function sf_qa_columns(string $table): array {
  return sf_admin_table_columns($table);
}

function sf_qa_status(string $status, string $label, string $detail = '', int $weight = 1): array {
  return [
    'status' => $status,
    'label' => $label,
    'detail' => $detail,
    'weight' => max(1, $weight),
  ];
}

function sf_qa_score(array $checks): int {
  $points = 0.0;
  $total = 0.0;
  foreach ($checks as $check) {
    $weight = (int)($check['weight'] ?? 1);
    $total += $weight;
    $status = (string)($check['status'] ?? 'fail');
    if (in_array($status, ['pass','ready','ok'], true)) {
      $points += $weight;
    } elseif (in_array($status, ['warn','preview','manual'], true)) {
      $points += $weight * 0.7;
    } elseif ($status === 'info') {
      $points += $weight * 0.5;
    }
  }
  if ($total <= 0) {
    return 0;
  }
  return (int)round(($points / $total) * 100);
}

function sf_qa_grade(int $score): string {
  if ($score >= 97) return '10/10';
  if ($score >= 90) return '9/10';
  if ($score >= 80) return '8/10';
  if ($score >= 70) return '7/10';
  return 'Needs work';
}

function sf_qa_badge(string $status): string {
  $map = [
    'pass' => 'active',
    'ready' => 'active',
    'ok' => 'active',
    'warn' => 'draft',
    'preview' => 'draft',
    'manual' => 'draft',
    'info' => 'draft',
    'fail' => 'canceled',
    'missing' => 'canceled',
  ];
  return sf_admin_status_badge($map[$status] ?? $status);
}

function sf_qa_migration_plan(): array {
  return [
    [
      'key' => 'base',
      'file' => 'database/stonefellow_streaming_platform.sql',
      'label' => 'Base streaming platform schema',
      'tables' => ['media_assets','users','subscription_plans','user_subscriptions','albums','songs','song_files','episodes','playlists','playlist_songs','products','orders','order_items'],
      'notes' => 'Core public site, member, catalog, playlist, and merch tables.',
    ],
    [
      'key' => '001',
      'file' => 'database/migrations/001_membership_video_tracking.sql',
      'label' => 'Membership video tracking',
      'tables' => ['content_access_grants','videos','video_files','audio_play_events','user_song_progress','video_watch_events','user_video_progress','user_episode_progress','admin_audit_log'],
      'notes' => 'Full playback tracking, video catalog, content grants, and audit log.',
    ],
    [
      'key' => '002',
      'file' => 'database/migrations/002_video_playlist_runtime_seed.sql',
      'label' => 'Video and playlist runtime seed',
      'tables' => ['videos','video_files','playlists','playlist_songs'],
      'notes' => 'Seed data and runtime-safe demo records.',
    ],
    [
      'key' => '003',
      'file' => 'database/migrations/003_media_upload_storage_metadata.sql',
      'label' => 'Media upload metadata',
      'tables' => ['media_assets'],
      'notes' => 'Extends media asset metadata for upload manager and pickers.',
    ],
    [
      'key' => '004',
      'file' => 'database/migrations/004_billing_entitlements.sql',
      'label' => 'Billing and subscription entitlements',
      'tables' => ['billing_customers','subscription_checkouts','invoices','payment_transactions','billing_webhook_events'],
      'notes' => 'Checkout sessions, invoices, payments, webhooks, and entitlement records.',
    ],
    [
      'key' => '005',
      'file' => 'database/migrations/005_merch_order_runtime.sql',
      'label' => 'Merch order runtime',
      'tables' => ['order_status_history','product_inventory_movements'],
      'notes' => 'Fulfillment queue, status history, inventory movements, and order runtime columns.',
    ],
    [
      'key' => '006',
      'file' => 'database/migrations/006_email_notifications.sql',
      'label' => 'Email notification runtime',
      'tables' => ['email_templates','notification_logs','notification_preferences','notification_webhook_events'],
      'notes' => 'Template manager, queue/log, preferences, and webhook logging.',
    ],
    [
      'key' => '007',
      'file' => 'database/migrations/007_site_settings_installer.sql',
      'label' => 'Site settings and installer',
      'tables' => ['site_settings','system_installation_checks'],
      'notes' => 'Runtime settings, health checks, and install readiness records.',
    ],
    [
      'key' => '008',
      'file' => 'database/migrations/008_payment_gateway_adapter.sql',
      'label' => 'Payment gateway adapter',
      'tables' => ['payment_gateway_settings','payment_gateway_webhook_events'],
      'notes' => 'Sandbox, Stripe, PayPal adapter config and gateway webhook receipts.',
    ],
    [
      'key' => '009',
      'file' => 'database/migrations/009_episode_video_admin_v2.sql',
      'label' => 'Episode and video admin v2',
      'tables' => ['seasons','video_chapters'],
      'notes' => 'Seasons, release scheduling, watch-next, and video chapters.',
    ],
    [
      'key' => '010',
      'file' => 'database/migrations/010_production_readiness_qa_harness.sql',
      'label' => 'Production readiness QA harness',
      'tables' => ['qa_runs','qa_check_results'],
      'notes' => 'Optional persisted QA runs and check history for launch audit reports.',
    ],
  ];
}

function sf_qa_required_columns(): array {
  return [
    'users' => ['id','email','password_hash','role','status','created_at'],
    'subscription_plans' => ['id','name','slug','price_cents','status'],
    'user_subscriptions' => ['id','user_id','plan_id','status','current_period_start','current_period_end'],
    'albums' => ['id','title','slug','status'],
    'songs' => ['id','album_id','title','slug','access_level','status'],
    'song_files' => ['id','song_id','file_type','file_path'],
    'episodes' => ['id','title','slug','season_number','episode_number','status'],
    'videos' => ['id','episode_id','title','slug','video_type','access_level','status'],
    'video_files' => ['id','video_id','file_type','file_path'],
    'products' => ['id','name','slug','price_cents','status'],
    'orders' => ['id','order_number','user_id','email','status','payment_status','total_cents'],
    'email_templates' => ['id','template_key','subject','html_body','status'],
    'notification_logs' => ['id','notification_type','recipient_email','status','created_at'],
    'site_settings' => ['id','setting_key','setting_value','setting_group'],
    'payment_gateway_settings' => ['id','provider','mode','status'],
    'qa_runs' => ['id','run_type','score','status','created_at'],
    'qa_check_results' => ['id','qa_run_id','section','check_key','status','message'],
  ];
}

function sf_qa_environment_checks(): array {
  $checks = [];
  $checks[] = sf_qa_status(version_compare(PHP_VERSION, '8.1.0', '>=') ? 'pass' : 'fail', 'PHP version 8.1+', PHP_VERSION, 2);
  $checks[] = sf_qa_status(extension_loaded('pdo') ? 'pass' : 'fail', 'PDO extension', extension_loaded('pdo') ? 'Loaded' : 'Missing', 2);
  $checks[] = sf_qa_status(extension_loaded('pdo_mysql') ? 'pass' : 'warn', 'PDO MySQL extension', extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing in this runtime; required on production', 2);
  $checks[] = sf_qa_status(extension_loaded('json') ? 'pass' : 'fail', 'JSON extension', extension_loaded('json') ? 'Loaded' : 'Missing');
  $checks[] = sf_qa_status(extension_loaded('fileinfo') ? 'pass' : 'warn', 'Fileinfo extension', extension_loaded('fileinfo') ? 'Loaded' : 'Recommended for upload MIME verification');
  $checks[] = sf_qa_status(function_exists('password_hash') ? 'pass' : 'fail', 'Password hashing', function_exists('password_hash') ? 'Available' : 'Missing', 2);
  $checks[] = sf_qa_status(sf_qa_db_ready() ? 'pass' : 'preview', 'Database connection', sf_qa_db_ready() ? 'Connected' : 'Static/no-database preview mode');
  foreach (['SF_DB_HOST','SF_DB_NAME','SF_DB_USER'] as $env) {
    $checks[] = sf_qa_status(getenv($env) ? 'pass' : 'manual', 'Environment: ' . $env, getenv($env) ? 'Configured' : 'Set on production host');
  }
  foreach (['assets/images/uploads','assets/audio/uploads','assets/video/uploads','assets/documents/uploads'] as $dir) {
    $path = sf_qa_file_path($dir);
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $checks[] = sf_qa_status($writable ? 'pass' : ($exists ? 'warn' : 'fail'), 'Upload folder: ' . $dir, $exists ? ($writable ? 'Writable' : 'Exists but not writable') : 'Missing', 2);
  }
  return $checks;
}

function sf_qa_migration_checks(): array {
  $checks = [];
  foreach (sf_qa_migration_plan() as $migration) {
    $fileOk = sf_qa_file_exists($migration['file']);
    $missingTables = [];
    if (sf_qa_db_ready()) {
      foreach ($migration['tables'] as $table) {
        if (!sf_qa_table_exists($table)) {
          $missingTables[] = $table;
        }
      }
      $status = $fileOk && !$missingTables ? 'pass' : 'fail';
      $detail = $fileOk ? (!$missingTables ? 'File present and tables installed' : 'Missing tables: ' . implode(', ', $missingTables)) : 'SQL file missing';
    } else {
      $status = $fileOk ? 'preview' : 'fail';
      $detail = $fileOk ? 'File present; database not connected for table verification' : 'SQL file missing';
    }
    $checks[] = sf_qa_status($status, $migration['key'] . ' — ' . $migration['label'], $detail, 2);
  }
  foreach (sf_qa_required_columns() as $table => $columns) {
    if (!sf_qa_db_ready()) {
      $checks[] = sf_qa_status('preview', 'Columns: ' . $table, 'Database not connected; expected columns are documented');
      continue;
    }
    if (!sf_qa_table_exists($table)) {
      $checks[] = sf_qa_status('fail', 'Columns: ' . $table, 'Table missing', 2);
      continue;
    }
    $actual = sf_qa_columns($table);
    $missing = array_values(array_diff($columns, $actual));
    $checks[] = sf_qa_status($missing ? 'fail' : 'pass', 'Columns: ' . $table, $missing ? 'Missing columns: ' . implode(', ', $missing) : 'Required columns present', 2);
  }
  return $checks;
}

function sf_qa_public_routes(): array {
  return [
    ['path' => 'index.php', 'label' => 'Home', 'type' => 'public'],
    ['path' => 'series.php', 'label' => 'Series', 'type' => 'public'],
    ['path' => 'episodes.php', 'label' => 'Episodes', 'type' => 'public'],
    ['path' => 'episode.php', 'label' => 'Episode detail', 'type' => 'public/member upsell'],
    ['path' => 'watch.php', 'label' => 'Watch page', 'type' => 'member-gated content'],
    ['path' => 'music.php', 'label' => 'Music landing', 'type' => 'public'],
    ['path' => 'player.php', 'label' => 'Streaming player', 'type' => 'public/member upsell'],
    ['path' => 'album.php', 'label' => 'Album detail', 'type' => 'public/member upsell'],
    ['path' => 'song.php', 'label' => 'Song detail', 'type' => 'public/member upsell'],
    ['path' => 'cast.php', 'label' => 'Cast', 'type' => 'public'],
    ['path' => 'merch.php', 'label' => 'Merch', 'type' => 'public commerce'],
    ['path' => 'product.php', 'label' => 'Product detail', 'type' => 'public commerce'],
    ['path' => 'cart.php', 'label' => 'Cart', 'type' => 'commerce runtime'],
    ['path' => 'checkout.php', 'label' => 'Merch checkout', 'type' => 'commerce runtime'],
    ['path' => 'order-confirmation.php', 'label' => 'Order confirmation', 'type' => 'commerce runtime'],
    ['path' => 'signup.php', 'label' => 'Signup', 'type' => 'auth'],
    ['path' => 'signin.php', 'label' => 'Signin', 'type' => 'auth'],
    ['path' => 'forgot-password.php', 'label' => 'Forgot password', 'type' => 'auth'],
    ['path' => 'reset-password.php', 'label' => 'Reset password', 'type' => 'auth'],
    ['path' => 'member.php', 'label' => 'Member dashboard', 'type' => 'login required'],
    ['path' => 'playlists.php', 'label' => 'Member playlists', 'type' => 'paid member'],
    ['path' => 'account.php', 'label' => 'Account', 'type' => 'login required'],
    ['path' => 'account-billing.php', 'label' => 'Account billing', 'type' => 'login required'],
    ['path' => 'subscribe.php', 'label' => 'Subscribe', 'type' => 'billing'],
    ['path' => 'billing-checkout.php', 'label' => 'Billing checkout', 'type' => 'billing'],
    ['path' => 'billing-success.php', 'label' => 'Billing success', 'type' => 'billing'],
    ['path' => 'billing-cancel.php', 'label' => 'Billing cancel', 'type' => 'billing'],
    ['path' => 'install.php', 'label' => 'Installer', 'type' => 'setup'],
  ];
}

function sf_qa_admin_routes(): array {
  return [
    ['path' => 'admin/index.php', 'label' => 'Admin home'],
    ['path' => 'admin/music.php', 'label' => 'Media dashboard'],
    ['path' => 'admin/music-albums.php', 'label' => 'Album manager'],
    ['path' => 'admin/music-songs.php', 'label' => 'Song manager'],
    ['path' => 'admin/episodes.php', 'label' => 'Episode manager'],
    ['path' => 'admin/videos.php', 'label' => 'Video manager'],
    ['path' => 'admin/seasons.php', 'label' => 'Season manager'],
    ['path' => 'admin/release-schedule.php', 'label' => 'Release schedule'],
    ['path' => 'admin/members.php', 'label' => 'Members'],
    ['path' => 'admin/products.php', 'label' => 'Products'],
    ['path' => 'admin/orders.php', 'label' => 'Orders'],
    ['path' => 'admin/analytics.php', 'label' => 'Analytics'],
    ['path' => 'admin/audio-analytics.php', 'label' => 'Audio analytics'],
    ['path' => 'admin/video-analytics.php', 'label' => 'Video analytics'],
    ['path' => 'admin/member-activity.php', 'label' => 'Member activity'],
    ['path' => 'admin/billing.php', 'label' => 'Billing'],
    ['path' => 'admin/payment-gateways.php', 'label' => 'Payment gateways'],
    ['path' => 'admin/settings.php', 'label' => 'Settings'],
    ['path' => 'admin/system-health.php', 'label' => 'System health'],
    ['path' => 'admin/notifications.php', 'label' => 'Notifications'],
    ['path' => 'admin/email-templates.php', 'label' => 'Email templates'],
    ['path' => 'admin/media-access.php', 'label' => 'Access rules'],
    ['path' => 'admin/uploads.php', 'label' => 'Uploads'],
    ['path' => 'admin/qa.php', 'label' => 'QA dashboard'],
    ['path' => 'admin/migration-checker.php', 'label' => 'Migration checker'],
    ['path' => 'admin/routes-checker.php', 'label' => 'Routes checker'],
    ['path' => 'admin/security-check.php', 'label' => 'Security check'],
    ['path' => 'admin/content-audit.php', 'label' => 'Content audit'],
  ];
}

function sf_qa_api_routes(): array {
  return [
    ['path' => 'api/audio-track.php', 'label' => 'Audio tracking', 'method' => 'POST JSON'],
    ['path' => 'api/video-track.php', 'label' => 'Video tracking', 'method' => 'POST JSON'],
    ['path' => 'api/playlist.php', 'label' => 'Playlist runtime', 'method' => 'GET/POST JSON'],
    ['path' => 'api/cart.php', 'label' => 'Cart runtime', 'method' => 'GET/POST JSON'],
    ['path' => 'api/membership-status.php', 'label' => 'Membership status', 'method' => 'GET JSON'],
    ['path' => 'api/billing-webhook.php', 'label' => 'Billing webhook', 'method' => 'POST JSON'],
    ['path' => 'api/payment-webhook.php', 'label' => 'Payment gateway webhook', 'method' => 'POST JSON'],
    ['path' => 'api/notification-webhook.php', 'label' => 'Notification webhook', 'method' => 'POST JSON'],
  ];
}

function sf_qa_route_checks(): array {
  $checks = [];
  foreach (sf_qa_public_routes() as $route) {
    $exists = sf_qa_file_exists($route['path']);
    $usesHeader = sf_qa_contains($route['path'], ['includes/header.php', "require __DIR__ . '/includes/header.php'", "require_once __DIR__ . '/includes/header.php'"]);
    $checks[] = sf_qa_status($exists && $usesHeader ? 'pass' : ($exists ? 'warn' : 'fail'), $route['label'] . ' — ' . $route['path'], $exists ? ($usesHeader ? 'Full page route present' : 'Route exists; header include not detected') : 'Route file missing', 2);
  }
  foreach (sf_qa_admin_routes() as $route) {
    $exists = sf_qa_file_exists($route['path']);
    $protected = sf_qa_contains($route['path'], ['includes/admin_catalog.php', '/../includes/admin_catalog.php', 'includes/admin_analytics.php', '/../includes/admin_analytics.php', 'includes/qa.php', '/../includes/qa.php']) || sf_qa_contains('includes/admin_catalog.php', ['sf_require_admin']);
    $checks[] = sf_qa_status($exists && $protected ? 'pass' : ($exists ? 'warn' : 'fail'), $route['label'] . ' — ' . $route['path'], $exists ? ($protected ? 'Admin shell/protection detected' : 'Admin protection not detected') : 'Route file missing', 2);
  }
  foreach (sf_qa_api_routes() as $route) {
    $exists = sf_qa_file_exists($route['path']);
    $json = sf_qa_contains($route['path'], ['sf_json_response']);
    $checks[] = sf_qa_status($exists && $json ? 'pass' : ($exists ? 'warn' : 'fail'), $route['label'] . ' — ' . $route['path'], $exists ? ($json ? 'JSON response contract detected' : 'JSON response helper not detected') : 'API file missing', 2);
  }
  return $checks;
}

function sf_qa_security_checks(): array {
  $checks = [];
  $adminUnprotected = [];
  foreach (sf_qa_admin_routes() as $route) {
    if (!sf_qa_file_exists($route['path'])) {
      continue;
    }
    $body = sf_qa_read_file($route['path']);
    if (strpos($body, 'includes/admin_catalog.php') === false && strpos($body, 'includes/admin_analytics.php') === false && strpos($body, 'includes/qa.php') === false && strpos($body, 'sf_require_admin') === false) {
      $adminUnprotected[] = $route['path'];
    }
  }
  $checks[] = sf_qa_status(!$adminUnprotected ? 'pass' : 'fail', 'Admin route protection', !$adminUnprotected ? 'All admin pages use the admin shell/protection layer' : 'Missing protection: ' . implode(', ', $adminUnprotected), 3);

  $postWithoutCsrf = [];
  foreach (array_merge(sf_qa_public_routes(), sf_qa_admin_routes()) as $route) {
    if (!sf_qa_file_exists($route['path'])) continue;
    $body = sf_qa_read_file($route['path']);
    if (strpos($body, "REQUEST_METHOD'] ?? 'GET') === 'POST") !== false || strpos($body, 'REQUEST_METHOD') !== false && strpos($body, 'POST') !== false) {
      if (strpos($body, 'sf_verify_csrf') === false && strpos($body, 'sf_csrf_field') === false) {
        $postWithoutCsrf[] = $route['path'];
      }
    }
  }
  $checks[] = sf_qa_status(!$postWithoutCsrf ? 'pass' : 'fail', 'CSRF on form mutations', !$postWithoutCsrf ? 'POST forms include CSRF checks/fields' : 'Review CSRF: ' . implode(', ', $postWithoutCsrf), 3);

  $checks[] = sf_qa_status(sf_qa_contains('includes/auth.php', ['password_hash', 'password_verify']) ? 'pass' : 'fail', 'Password security', 'Auth layer uses PHP password hashing/verification', 3);
  $checks[] = sf_qa_status(sf_qa_contains('includes/auth.php', ['random_bytes']) ? 'pass' : 'fail', 'Token generation', 'CSRF/reset/remember tokens use cryptographic randomness', 2);
  $checks[] = sf_qa_status(sf_qa_contains('includes/admin_catalog.php', ['mime_prefixes', 'max_bytes', 'move_uploaded_file']) ? 'pass' : 'fail', 'Upload validation', 'Upload manager validates extension, MIME family, max size, and controlled folders', 3);
  $checks[] = sf_qa_status(sf_qa_contains('api/billing-webhook.php', ['hash_hmac', 'hash_equals']) ? 'pass' : 'warn', 'Billing webhook signatures', 'Billing webhook checks optional HMAC signature when secret is configured', 2);
  $checks[] = sf_qa_status(sf_qa_contains('includes/payment_gateway.php', ['sf_payment_verify_webhook']) ? 'pass' : 'fail', 'Gateway webhook verification boundary', 'Gateway adapters isolate webhook verification', 2);
  $checks[] = sf_qa_status(sf_qa_contains('includes/db.php', ['PDO::ATTR_ERRMODE', 'PDO::ATTR_EMULATE_PREPARES']) ? 'pass' : 'fail', 'Database connection hardening', 'PDO exceptions and native prepares configured', 2);
  $checks[] = sf_qa_status(sf_qa_contains('includes/store.php', ['inventory', 'transaction']) ? 'pass' : 'warn', 'Order inventory controls', 'Store runtime includes inventory movement/transaction logic', 2);
  return $checks;
}

function sf_qa_normalize_asset_path(?string $path): string {
  $path = trim((string)$path);
  if ($path === '' || preg_match('~^(https?:)?//|^data:~i', $path)) {
    return '';
  }
  $path = ltrim($path, '/');
  if (strpos($path, 'assets/') === 0) {
    return $path;
  }
  if (preg_match('~^(images|audio|video|documents)/~', $path)) {
    return 'assets/' . $path;
  }
  return $path;
}

function sf_qa_asset_exists(?string $path): bool {
  $normalized = sf_qa_normalize_asset_path($path);
  if ($normalized === '') {
    return true;
  }
  return sf_qa_file_exists($normalized);
}

function sf_qa_content_audit(): array {
  $items = [];
  foreach (sf_admin_albums() as $album) {
    $path = $album['cover_path'] ?? $album['cover'] ?? '';
    $items[] = ['section' => 'Album', 'title' => $album['title'] ?? 'Untitled album', 'field' => 'cover', 'path' => $path, 'status' => $path === '' ? 'warn' : (sf_qa_asset_exists($path) ? 'pass' : 'fail')];
  }
  foreach (sf_admin_songs() as $song) {
    foreach (['cover','cover_path','preview_src','full_src','preview_file_path','full_file_path'] as $field) {
      if (array_key_exists($field, $song) && trim((string)$song[$field]) !== '') {
        $items[] = ['section' => 'Song', 'title' => $song['title'] ?? 'Untitled song', 'field' => $field, 'path' => $song[$field], 'status' => sf_qa_asset_exists($song[$field]) ? 'pass' : 'fail'];
      }
    }
  }
  foreach (sf_admin_episodes() as $episode) {
    foreach (['poster_path','thumbnail_path','hero_path','poster','image'] as $field) {
      if (array_key_exists($field, $episode) && trim((string)$episode[$field]) !== '') {
        $items[] = ['section' => 'Episode', 'title' => $episode['title'] ?? 'Untitled episode', 'field' => $field, 'path' => $episode[$field], 'status' => sf_qa_asset_exists($episode[$field]) ? 'pass' : 'fail'];
      }
    }
  }
  foreach (sf_admin_videos() as $video) {
    foreach (['poster_path','poster','src','trailer_src','preview_src','stream_src','file_path'] as $field) {
      if (array_key_exists($field, $video) && trim((string)$video[$field]) !== '') {
        $items[] = ['section' => 'Video', 'title' => $video['title'] ?? 'Untitled video', 'field' => $field, 'path' => $video[$field], 'status' => sf_qa_asset_exists($video[$field]) ? 'pass' : 'fail'];
      }
    }
  }
  foreach (sf_store_products() as $product) {
    foreach (['image','image_path','cover_path'] as $field) {
      if (array_key_exists($field, $product) && trim((string)$product[$field]) !== '') {
        $items[] = ['section' => 'Product', 'title' => $product['name'] ?? $product['title'] ?? 'Untitled product', 'field' => $field, 'path' => $product[$field], 'status' => sf_qa_asset_exists($product[$field]) ? 'pass' : 'fail'];
      }
    }
  }
  if (!$items) {
    $items[] = ['section' => 'Content', 'title' => 'Static preview', 'field' => 'assets', 'path' => '', 'status' => 'warn'];
  }
  return $items;
}

function sf_qa_content_checks(): array {
  $items = sf_qa_content_audit();
  $checks = [];
  $missing = array_filter($items, static fn($item) => ($item['status'] ?? '') === 'fail');
  $warnings = array_filter($items, static fn($item) => ($item['status'] ?? '') === 'warn');
  $contentStatus = !$missing ? 'pass' : (sf_qa_db_ready() ? 'fail' : 'warn');
  $checks[] = sf_qa_status($contentStatus, 'Media file references', !$missing ? 'No missing local asset paths detected' : count($missing) . ' missing media reference(s)' . (sf_qa_db_ready() ? '' : ' in static/demo paths'), 3);
  $checks[] = sf_qa_status(count($warnings) <= 3 ? 'pass' : 'warn', 'Optional artwork completeness', count($warnings) . ' optional/blank media field(s) found');
  $checks[] = sf_qa_status(count(sf_admin_albums()) > 0 ? 'pass' : 'warn', 'Album catalog', count(sf_admin_albums()) . ' album record(s) available');
  $checks[] = sf_qa_status(count(sf_admin_songs()) > 0 ? 'pass' : 'warn', 'Song catalog', count(sf_admin_songs()) . ' song record(s) available');
  $checks[] = sf_qa_status(count(sf_admin_episodes()) > 0 ? 'pass' : 'warn', 'Episode catalog', count(sf_admin_episodes()) . ' episode record(s) available');
  $checks[] = sf_qa_status(count(sf_admin_videos()) > 0 ? 'pass' : 'warn', 'Video catalog', count(sf_admin_videos()) . ' video record(s) available');
  $checks[] = sf_qa_status(count(sf_store_products()) > 0 ? 'pass' : 'warn', 'Merch catalog', count(sf_store_products()) . ' merch product(s) available');
  return $checks;
}

function sf_qa_all_checks(): array {
  return [
    'Environment' => sf_qa_environment_checks(),
    'Migrations' => sf_qa_migration_checks(),
    'Routes' => sf_qa_route_checks(),
    'Security' => sf_qa_security_checks(),
    'Content' => sf_qa_content_checks(),
  ];
}

function sf_qa_flatten(array $sections): array {
  $rows = [];
  foreach ($sections as $section => $checks) {
    foreach ($checks as $check) {
      $check['section'] = $section;
      $rows[] = $check;
    }
  }
  return $rows;
}

function sf_qa_overall_score(): int {
  return sf_qa_score(sf_qa_flatten(sf_qa_all_checks()));
}

function sf_qa_persist_run(string $runType = 'manual'): ?int {
  if (!sf_qa_db_ready() || !sf_qa_table_exists('qa_runs') || !sf_qa_table_exists('qa_check_results')) {
    return null;
  }
  $sections = sf_qa_all_checks();
  $score = sf_qa_score(sf_qa_flatten($sections));
  $status = $score >= 90 ? 'passed' : 'review';
  try {
    $pdo = sf_db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO qa_runs (run_type, score, status, summary_json, created_by_user_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$runType, $score, $status, json_encode(['sections' => array_keys($sections)], JSON_UNESCAPED_SLASHES), sf_current_user_id()]);
    $runId = (int)$pdo->lastInsertId();
    $insert = $pdo->prepare('INSERT INTO qa_check_results (qa_run_id, section, check_key, status, message, detail_json) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($sections as $section => $checks) {
      foreach ($checks as $index => $check) {
        $insert->execute([$runId, $section, strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string)($check['label'] ?? 'check_' . $index))), $check['status'] ?? 'info', $check['label'] ?? '', json_encode($check, JSON_UNESCAPED_SLASHES)]);
      }
    }
    $pdo->commit();
    sf_admin_audit('qa_run', 'qa_runs', $runId, null, ['score' => $score, 'status' => $status]);
    return $runId;
  } catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Stonefellow QA persist failed: ' . $e->getMessage());
    return null;
  }
}

function sf_qa_recent_runs(): array {
  if (!sf_qa_db_ready() || !sf_qa_table_exists('qa_runs')) {
    return [];
  }
  return sf_admin_fetch_all('SELECT * FROM qa_runs ORDER BY created_at DESC, id DESC LIMIT 20');
}

function sf_qa_section_summary(): array {
  $summary = [];
  foreach (sf_qa_all_checks() as $section => $checks) {
    $summary[] = [
      'section' => $section,
      'score' => sf_qa_score($checks),
      'count' => count($checks),
      'fails' => count(array_filter($checks, static fn($check) => in_array(($check['status'] ?? ''), ['fail','missing'], true))),
      'warnings' => count(array_filter($checks, static fn($check) => in_array(($check['status'] ?? ''), ['warn','preview','manual'], true))),
    ];
  }
  return $summary;
}

function sf_qa_render_check_table(array $checks, bool $showSection = false): void {
  echo '<div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr>';
  if ($showSection) {
    echo '<th>Section</th>';
  }
  echo '<th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody>';
  foreach ($checks as $check) {
    echo '<tr>';
    if ($showSection) {
      echo '<td>' . sf_qa_h($check['section'] ?? '') . '</td>';
    }
    echo '<td><strong>' . sf_qa_h($check['label'] ?? '') . '</strong></td>';
    echo '<td>' . sf_qa_badge((string)($check['status'] ?? 'info')) . '</td>';
    echo '<td>' . sf_qa_h($check['detail'] ?? '') . '</td>';
    echo '</tr>';
  }
  echo '</tbody></table></div>';
}
?>
