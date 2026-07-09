<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/membership.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (sf_db() instanceof PDO) {
  sf_require_admin();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false) {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect();
  }
}

function sf_admin_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function sf_admin_db(): ?PDO { return sf_db(); }
function sf_admin_db_ready(): bool { return sf_admin_db() instanceof PDO; }
function sf_admin_flash(?string $type = null, ?string $message = null): array { if ($type !== null && $message !== null) { $_SESSION['sf_admin_flash'][] = ['type'=>$type,'message'=>$message]; return []; } $messages = $_SESSION['sf_admin_flash'] ?? []; unset($_SESSION['sf_admin_flash']); return is_array($messages) ? $messages : []; }
function sf_admin_redirect(?string $url = null): void { $target = $url ?: strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: './'; header('Location: ' . $target); exit; }
function sf_admin_slugify(string $value): string { $value = strtolower(trim($value)); $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: ''; $value = trim($value, '-'); return $value !== '' ? $value : 'item-' . substr(bin2hex(random_bytes(4)), 0, 8); }
function sf_admin_int($value, ?int $default = null): ?int { if ($value === '' || $value === null) return $default; return is_numeric($value) ? (int)$value : $default; }
function sf_admin_nullable_string($value): ?string { $value = trim((string)$value); return $value === '' ? null : $value; }
function sf_admin_checkbox(string $key): int { return isset($_POST[$key]) ? 1 : 0; }
function sf_admin_date_or_null(string $key): ?string { $value = trim((string)($_POST[$key] ?? '')); return $value === '' ? null : $value; }
function sf_admin_datetime_or_null(string $key): ?string { $value = trim((string)($_POST[$key] ?? '')); if ($value === '') return null; return str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : ''); }
function sf_admin_table_exists(string $table): bool { $pdo = sf_admin_db(); if (!$pdo) return false; try { $stmt = $pdo->prepare('SHOW TABLES LIKE ?'); $stmt->execute([$table]); return (bool)$stmt->fetchColumn(); } catch (Throwable $e) { error_log('Stonefellow table check failed for ' . $table . ': ' . $e->getMessage()); return false; } }
function sf_admin_fetch_all(string $sql, array $params = []): array { $pdo = sf_admin_db(); if (!$pdo) return []; try { $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll() ?: []; } catch (Throwable $e) { error_log('Stonefellow admin fetch failed: ' . $e->getMessage()); return []; } }
function sf_admin_fetch_one(string $sql, array $params = []): ?array { $rows = sf_admin_fetch_all($sql, $params); return $rows[0] ?? null; }
function sf_admin_execute(string $sql, array $params = []): bool { $pdo = sf_admin_db(); if (!$pdo) return false; try { $stmt = $pdo->prepare($sql); return $stmt->execute($params); } catch (Throwable $e) { error_log('Stonefellow admin execute failed: ' . $e->getMessage()); sf_admin_flash('error', 'Database action failed: ' . $e->getMessage()); return false; } }
function sf_admin_audit(string $action, string $entityType, ?int $entityId = null, ?array $before = null, ?array $after = null): void { if (!sf_admin_table_exists('admin_audit_log')) return; sf_admin_execute('INSERT INTO admin_audit_log (admin_user_id, action, entity_type, entity_id, before_json, after_json, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)', [sf_current_user_id(), $action, $entityType, $entityId, $before ? json_encode($before, JSON_UNESCAPED_SLASHES) : null, $after ? json_encode($after, JSON_UNESCAPED_SLASHES) : null, $_SERVER['REMOTE_ADDR'] ?? null]); }
function sf_admin_count_table(string $table): int { if (!sf_admin_table_exists($table)) return 0; $row = sf_admin_fetch_one('SELECT COUNT(*) AS total FROM `' . str_replace('`', '', $table) . '`'); return (int)($row['total'] ?? 0); }
function sf_admin_table_columns(string $table): array { $pdo = sf_admin_db(); if (!$pdo || !sf_admin_table_exists($table)) return []; try { $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`'); return array_map(static fn($row) => $row['Field'] ?? '', $stmt->fetchAll() ?: []); } catch (Throwable $e) { error_log('Stonefellow column lookup failed for ' . $table . ': ' . $e->getMessage()); return []; } }
function sf_admin_column_exists(string $table, string $column): bool { return in_array($column, sf_admin_table_columns($table), true); }
function sf_admin_assets(string $type = ''): array { if (!sf_admin_table_exists('media_assets')) return []; $params = []; $where = ''; if ($type !== '') { $where = ' WHERE file_type = ?'; $params[] = $type; } return sf_admin_fetch_all('SELECT * FROM media_assets' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 300', $params); }
function sf_admin_albums(): array { if (!sf_admin_table_exists('albums')) { global $musicAlbum; return [['id'=>1,'title'=>$musicAlbum['title'] ?? 'The Road Is Calling','slug'=>$musicAlbum['slug'] ?? 'the-road-is-calling','artist'=>$musicAlbum['artist'] ?? 'Stonefellow','description'=>$musicAlbum['description'] ?? '','release_date'=>null,'status'=>'published','cover_path'=>$musicAlbum['cover'] ?? '']]; } return sf_admin_fetch_all('SELECT a.*, ma.file_path AS cover_path FROM albums a LEFT JOIN media_assets ma ON ma.id = a.cover_asset_id ORDER BY a.created_at DESC, a.id DESC'); }
function sf_admin_songs(): array { if (!sf_admin_table_exists('songs')) { global $catalogSongs; $rows = []; foreach ($catalogSongs as $song) { $rows[] = ['id'=>$song['id'] ?? 0,'album_title'=>'The Road Is Calling','album_id'=>1,'track_number'=>(int)($song['track'] ?? 0),'title'=>$song['title'] ?? '','slug'=>$song['slug'] ?? '','artist'=>$song['artist'] ?? 'Stonefellow','duration_seconds'=>$song['duration_seconds'] ?? null,'access_level'=>$song['access'] ?? 'subscriber','is_featured'=>!empty($song['is_featured']) ? 1 : 0,'status'=>'published','cover_path'=>$song['cover'] ?? '']; } return $rows; } return sf_admin_fetch_all('SELECT s.*, a.title AS album_title, ma.file_path AS cover_path FROM songs s LEFT JOIN albums a ON a.id = s.album_id LEFT JOIN media_assets ma ON ma.id = s.cover_asset_id ORDER BY COALESCE(s.album_id, 999999), COALESCE(s.track_number, 999999), s.created_at DESC, s.id DESC'); }
function sf_admin_episodes(): array { if (!sf_admin_table_exists('episodes')) { global $episodes; $rows = []; foreach ($episodes as $index => $episode) { $rows[] = ['id'=>$index + 1,'season_number'=>1,'episode_number'=>$index + 1,'title'=>$episode['title'] ?? '','slug'=>$episode['slug'] ?? '','short_description'=>$episode['description'] ?? '','runtime_minutes'=>(int)($episode['runtime'] ?? 0),'status'=>(($episode['badge'] ?? '') === 'Coming Soon') ? 'draft' : 'published']; } return $rows; } return sf_admin_fetch_all('SELECT * FROM episodes ORDER BY season_number ASC, episode_number ASC, id ASC'); }
function sf_admin_videos(): array { if (!sf_admin_table_exists('videos')) { global $videoCatalog; return $videoCatalog; } return sf_admin_fetch_all('SELECT v.*, e.title AS episode_title, e.slug AS episode_slug, ma.file_path AS poster_path FROM videos v LEFT JOIN episodes e ON e.id = v.episode_id LEFT JOIN media_assets ma ON ma.id = v.poster_asset_id ORDER BY COALESCE(e.season_number, 999), COALESCE(e.episode_number, 999), v.video_type ASC, v.created_at DESC, v.id DESC'); }
function sf_admin_file_rows(string $table, string $foreignKey, int $id): array { if (!sf_admin_table_exists($table) || $id <= 0) return []; return sf_admin_fetch_all('SELECT * FROM `' . $table . '` WHERE `' . $foreignKey . '` = ? ORDER BY is_primary DESC, id ASC', [$id]); }
function sf_admin_selected_row(array $rows, string $table, int $editId): ?array { if ($editId <= 0) return null; if (sf_admin_table_exists($table)) return sf_admin_fetch_one('SELECT * FROM `' . $table . '` WHERE id = ? LIMIT 1', [$editId]); foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $editId) return $row; return null; }
function sf_admin_select(string $name, array $options, $selected, string $class = ''): string { $html = '<select name="' . sf_admin_h($name) . '"' . ($class !== '' ? ' class="' . sf_admin_h($class) . '"' : '') . sf_admin_form_disabled_attr() . '>'; foreach ($options as $value => $label) { $html .= '<option value="' . sf_admin_h($value) . '"' . (((string)$value === (string)$selected) ? ' selected' : '') . '>' . sf_admin_h($label) . '</option>'; } return $html . '</select>'; }
function sf_admin_asset_select(string $name, array $assets, $selected, string $type = ''): string { $html = '<select name="' . sf_admin_h($name) . '"' . sf_admin_form_disabled_attr() . '><option value="">No asset selected</option>'; foreach ($assets as $asset) { if ($type !== '' && ($asset['file_type'] ?? '') !== $type) continue; $label = trim((string)($asset['title'] ?? 'Asset #' . ($asset['id'] ?? '')) . ' — ' . (string)($asset['file_path'] ?? '')); $html .= '<option value="' . sf_admin_h($asset['id'] ?? '') . '"' . (((string)($asset['id'] ?? '') === (string)$selected) ? ' selected' : '') . '>' . sf_admin_h($label) . '</option>'; } return $html . '</select>'; }
function sf_admin_relation_select(string $name, array $rows, $selected, string $emptyLabel = 'None'): string { $html = '<select name="' . sf_admin_h($name) . '"' . sf_admin_form_disabled_attr() . '><option value="">' . sf_admin_h($emptyLabel) . '</option>'; foreach ($rows as $row) { $label = (string)($row['title'] ?? $row['name'] ?? ('#' . ($row['id'] ?? ''))); if (isset($row['season_number'], $row['episode_number'])) $label = 'S' . $row['season_number'] . ':E' . $row['episode_number'] . ' — ' . $label; $html .= '<option value="' . sf_admin_h($row['id'] ?? '') . '"' . (((string)($row['id'] ?? '') === (string)$selected) ? ' selected' : '') . '>' . sf_admin_h($label) . '</option>'; } return $html . '</select>'; }
function sf_admin_status_badge(string $status): string { $status = $status ?: 'draft'; return '<span class="sf-admin-status sf-admin-status-' . sf_admin_h(str_replace('_', '-', $status)) . '">' . sf_admin_h(ucfirst(str_replace('_', ' ', $status))) . '</span>'; }

function sf_admin_nav_groups(): array {
  return [
    'business' => ['label'=>'Membership / User / Business Data','short'=>'Business Data','items'=>[
      'index' => ['Admin Home', 'admin/index.php'],
      'members' => ['Members', 'admin/members.php'],
      'member-lifecycle' => ['Member Lifecycle', 'admin/member-lifecycle.php'],
      'support' => ['Support', 'admin/support.php'],
      'entitlements' => ['Entitlements', 'admin/entitlements.php'],
      'access' => ['Access', 'admin/media-access.php'],
      'billing' => ['Billing', 'admin/billing.php'],
      'payments' => ['Payment Gateways', 'admin/payment-gateways.php'],
      'products' => ['Merch Products', 'admin/products.php'],
      'orders' => ['Merch Orders', 'admin/orders.php'],
      'notifications' => ['Notifications', 'admin/notifications.php'],
      'email-templates' => ['Email Templates', 'admin/email-templates.php'],
      'analytics' => ['Analytics', 'admin/analytics.php'],
      'settings' => ['Settings', 'admin/settings.php'],
    ]],
    'content' => ['label'=>'Content / Storyboarding / Characters','short'=>'Content + Story','items'=>[
      'storyboards' => ['Storyboards', 'admin/storyboards.php'],
      'ai-script-assistant' => ['AI Script Producer', 'admin/ai-script-assistant.php'],
      'ai-script-batch-scenes' => ['AI Batch Scenes', 'admin/ai-script-batch-scenes.php'],
      'characters' => ['Characters', 'admin/characters.php'],
      'series-assets' => ['Series Assets', 'admin/series-assets.php'],
      'scene-backgrounds' => ['Scene Backgrounds', 'admin/scene-backgrounds.php'],
      'theme-images' => ['Theme Image Map', 'admin/theme-images.php'],
      'ai-settings' => ['AI Settings', 'admin/ai-settings.php'],
      'music' => ['Media Dashboard', 'admin/music.php'],
      'albums' => ['Albums', 'admin/music-albums.php'],
      'songs' => ['Songs', 'admin/music-songs.php'],
      'episodes' => ['Episodes', 'admin/episodes.php'],
      'videos' => ['Videos', 'admin/videos.php'],
      'seasons' => ['Seasons', 'admin/seasons.php'],
      'release-schedule' => ['Release Schedule', 'admin/release-schedule.php'],
      'publishing' => ['Publishing', 'admin/publishing.php'],
      'uploads' => ['Assets', 'admin/uploads.php'],
      'import' => ['Content Import', 'admin/import.php'],
    ]],
  ];
}
function sf_admin_nav_links(): array { $links = []; foreach (sf_admin_nav_groups() as $group) foreach ($group['items'] as $key => $item) $links[$key] = $item; return $links; }
function sf_admin_shell_start(string $eyebrow, string $title, string $description, string $active = ''): void {
  $groups = sf_admin_nav_groups();
  if ($active === 'story-characters') $active = 'characters';
  $activeGroup = 'business';
  foreach ($groups as $groupKey => $group) if (isset($group['items'][$active])) $activeGroup = $groupKey;
  echo '<section class="sf-admin-shell"><aside class="sf-admin-sidebar"><div class="sf-admin-side-brand"><span>Stonefellow</span><strong>Admin Console</strong></div><div class="sf-admin-nav-tabs" data-admin-nav-tabs>';
  foreach ($groups as $groupKey => $group) echo '<button type="button" class="' . ($groupKey === $activeGroup ? 'is-active' : '') . '" data-admin-nav-tab="' . sf_admin_h($groupKey) . '">' . sf_admin_h($group['short']) . '</button>';
  echo '</div><div class="sf-admin-nav-panels">';
  foreach ($groups as $groupKey => $group) {
    echo '<nav class="sf-admin-side-nav ' . ($groupKey === $activeGroup ? 'is-active' : '') . '" data-admin-nav-panel="' . sf_admin_h($groupKey) . '"><span>' . sf_admin_h($group['label']) . '</span>';
    foreach ($group['items'] as $key => $item) echo '<a class="' . ($active === $key ? 'is-active' : '') . '" href="' . sf_url($item[1]) . '">' . sf_admin_h($item[0]) . '</a>';
    echo '</nav>';
  }
  echo '</div></aside><main class="sf-admin-main"><header class="sf-admin-hero"><div><span class="sf-panel-eyebrow">' . sf_admin_h($eyebrow) . '</span><h1>' . sf_admin_h($title) . '</h1><p>' . sf_admin_h($description) . '</p></div><a href="' . sf_url('index.php') . '">View Site</a></header>';
  $flashes = sf_admin_flash(); if ($flashes) { echo '<div class="sf-admin-flashes">'; foreach ($flashes as $flash) echo '<div class="sf-admin-alert sf-admin-alert-' . sf_admin_h($flash['type'] ?? 'info') . '">' . sf_admin_h($flash['message'] ?? '') . '</div>'; echo '</div>'; }
}
function sf_admin_shell_end(): void { echo '</main></section>'; }
function sf_admin_disabled(): bool { return false; }
function sf_admin_form_disabled_attr(): string { return sf_admin_disabled() ? ' disabled' : ''; }
?>
