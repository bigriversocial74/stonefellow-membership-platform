<?php
require_once __DIR__ . '/storyboarding_system.php';

function sf_series_assets_ready(): bool {
  return sf_story_v1_ready()
    && sf_storyboard_ready()
    && sf_admin_table_exists('story_series_assets')
    && sf_admin_table_exists('story_character_series_assets')
    && sf_admin_table_exists('storyboard_series_assets');
}
function sf_series_assets_disabled_attr(): string { return sf_series_assets_ready() ? '' : ' disabled'; }
function sf_series_asset_type_options(): array { return ['instrument'=>'Instrument','prop'=>'Prop','wardrobe'=>'Wardrobe','vehicle'=>'Vehicle','location_item'=>'Location Item','technology'=>'Technology','document'=>'Document','set_decoration'=>'Set Decoration','other'=>'Other']; }
function sf_series_assets_status_options(): array { return ['active'=>'Active','inactive'=>'Inactive','archived'=>'Archived']; }
function sf_series_assets_all(string $status = ''): array {
  if (!sf_series_assets_ready()) return [];
  $params = []; $where = '';
  if ($status !== '') { $where = 'WHERE a.status = ?'; $params[] = $status; }
  return sf_admin_fetch_all("SELECT a.*, COUNT(DISTINCT ca.story_character_id) AS character_count, COUNT(DISTINCT sa.storyboard_id) AS scene_count FROM story_series_assets a LEFT JOIN story_character_series_assets ca ON ca.story_series_asset_id = a.id LEFT JOIN storyboard_series_assets sa ON sa.story_series_asset_id = a.id {$where} GROUP BY a.id ORDER BY a.sort_order ASC, a.asset_name ASC", $params);
}
function sf_series_assets_find(array $assets, int $id): ?array { foreach ($assets as $asset) if ((int)($asset['id'] ?? 0) === $id) return $asset; return null; }
function sf_series_assets_save(array $payload, int $id = 0): int {
  if (!sf_series_assets_ready()) return 0;
  if (sf_admin_column_exists('story_series_assets', 'updated_by_user_id')) $payload['updated_by_user_id'] = sf_current_user_id();
  if ($id <= 0 && sf_admin_column_exists('story_series_assets', 'created_by_user_id')) $payload['created_by_user_id'] = sf_current_user_id();
  if (!sf_admin_build_insert_update('story_series_assets', $payload, $id)) return 0;
  $newId = $id ?: (int)(sf_admin_db()?->lastInsertId() ?: 0);
  sf_admin_audit($id > 0 ? 'update_story_series_asset' : 'create_story_series_asset', 'story_series_asset', $newId, null, $payload);
  return $newId;
}
function sf_series_assets_for_character(int $characterId): array {
  if (!sf_series_assets_ready() || $characterId <= 0) return [];
  return sf_admin_fetch_all('SELECT a.*, ca.assignment_notes, ca.is_primary FROM story_character_series_assets ca INNER JOIN story_series_assets a ON a.id = ca.story_series_asset_id WHERE ca.story_character_id = ? ORDER BY ca.is_primary DESC, a.sort_order ASC, a.asset_name ASC', [$characterId]);
}
function sf_series_asset_ids_for_character(int $characterId): array { return array_map(static fn($row) => (int)($row['id'] ?? 0), sf_series_assets_for_character($characterId)); }
function sf_series_assets_sync_character(int $characterId, array $assetIds, string $notes = ''): void {
  if (!sf_series_assets_ready() || $characterId <= 0) return;
  sf_admin_execute('DELETE FROM story_character_series_assets WHERE story_character_id = ?', [$characterId]);
  foreach (array_unique(array_filter(array_map('intval', $assetIds))) as $assetId) sf_admin_execute('INSERT IGNORE INTO story_character_series_assets (story_character_id, story_series_asset_id, assignment_notes, is_primary) VALUES (?, ?, ?, ?)', [$characterId, $assetId, $notes, 0]);
  sf_admin_audit('sync_character_series_assets', 'story_character', $characterId, null, ['asset_ids'=>array_values(array_unique(array_filter(array_map('intval', $assetIds))))]);
}
function sf_series_assets_for_storyboard(int $storyboardId): array {
  if (!sf_series_assets_ready() || $storyboardId <= 0) return [];
  return sf_admin_fetch_all('SELECT a.*, sa.usage_notes, sa.is_featured FROM storyboard_series_assets sa INNER JOIN story_series_assets a ON a.id = sa.story_series_asset_id WHERE sa.storyboard_id = ? ORDER BY sa.is_featured DESC, a.sort_order ASC, a.asset_name ASC', [$storyboardId]);
}
function sf_series_asset_ids_for_storyboard(int $storyboardId): array { return array_map(static fn($row) => (int)($row['id'] ?? 0), sf_series_assets_for_storyboard($storyboardId)); }
function sf_series_assets_sync_storyboard(int $storyboardId, array $assetIds, string $notes = ''): void {
  if (!sf_series_assets_ready() || $storyboardId <= 0) return;
  sf_admin_execute('DELETE FROM storyboard_series_assets WHERE storyboard_id = ?', [$storyboardId]);
  foreach (array_unique(array_filter(array_map('intval', $assetIds))) as $assetId) sf_admin_execute('INSERT IGNORE INTO storyboard_series_assets (storyboard_id, story_series_asset_id, usage_notes, is_featured) VALUES (?, ?, ?, ?)', [$storyboardId, $assetId, $notes, 0]);
  sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [$storyboardId]);
  sf_admin_audit('sync_storyboard_series_assets', 'storyboard', $storyboardId, null, ['asset_ids'=>array_values(array_unique(array_filter(array_map('intval', $assetIds))))]);
}
function sf_series_assets_for_scene_card(int $sceneId): array {
  if (!sf_series_assets_ready() || !sf_admin_table_exists('storyboard_scene_series_assets') || $sceneId <= 0) return [];
  return sf_admin_fetch_all('SELECT a.*, sa.usage_notes, sa.is_featured FROM storyboard_scene_series_assets sa INNER JOIN story_series_assets a ON a.id = sa.story_series_asset_id WHERE sa.storyboard_scene_id = ? ORDER BY sa.is_featured DESC, a.sort_order ASC, a.asset_name ASC', [$sceneId]);
}
function sf_series_assets_context_for_storyboard(int $storyboardId): string {
  if (!sf_series_assets_ready() || $storyboardId <= 0) return '';
  $rows = sf_series_assets_for_storyboard($storyboardId);
  $lines = [];
  foreach ($rows as $row) {
    $parts = ['Asset: ' . trim((string)($row['asset_name'] ?? ''))];
    if (trim((string)($row['asset_type'] ?? '')) !== '') $parts[] = 'Type: ' . trim((string)$row['asset_type']);
    if (trim((string)($row['short_description'] ?? '')) !== '') $parts[] = 'Description: ' . trim((string)$row['short_description']);
    if (trim((string)($row['continuity_notes'] ?? '')) !== '') $parts[] = 'Continuity: ' . trim((string)$row['continuity_notes']);
    if (trim((string)($row['image_path'] ?? '')) !== '') $parts[] = 'Reference image path: ' . trim((string)$row['image_path']);
    $lines[] = implode(' | ', $parts);
  }
  return implode("\n", $lines);
}
function sf_series_assets_checkbox_list(array $assets, array $checkedIds, string $name, string $disabled = ''): string {
  $html = '<div class="sf-story-v1-characters sf-series-assets-checkboxes">';
  foreach ($assets as $asset) {
    $id = (int)($asset['id'] ?? 0); if ($id <= 0) continue;
    $html .= '<label><input type="checkbox" name="' . sf_admin_h($name) . '[]" value="' . $id . '"' . (in_array($id, $checkedIds, true) ? ' checked' : '') . $disabled . '>' . sf_admin_h($asset['asset_name'] ?? 'Asset') . '</label>';
  }
  return $html . '</div>';
}
?>
