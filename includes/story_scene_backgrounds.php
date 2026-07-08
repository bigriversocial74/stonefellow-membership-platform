<?php
require_once __DIR__ . '/storyboarding_system.php';

function sf_scene_backgrounds_ready(): bool {
  return sf_story_v1_ready()
    && sf_storyboard_ready()
    && sf_admin_table_exists('story_scene_backgrounds')
    && sf_admin_table_exists('storyboard_scene_backgrounds');
}
function sf_scene_backgrounds_disabled_attr(): string { return sf_scene_backgrounds_ready() ? '' : ' disabled'; }
function sf_scene_background_type_options(): array { return ['interior'=>'Interior','exterior'=>'Exterior','location'=>'Location','stage'=>'Stage','studio'=>'Studio','vehicle'=>'Vehicle Interior','home'=>'Home','bar'=>'Bar / Venue','desert'=>'Desert','street'=>'Street','other'=>'Other']; }
function sf_scene_background_status_options(): array { return ['active'=>'Active','inactive'=>'Inactive','archived'=>'Archived']; }
function sf_scene_backgrounds_all(string $status = ''): array {
  if (!sf_scene_backgrounds_ready()) return [];
  $params = []; $where = '';
  if ($status !== '') { $where = 'WHERE b.status = ?'; $params[] = $status; }
  return sf_admin_fetch_all("SELECT b.*, COUNT(sbg.storyboard_scene_id) AS scene_count FROM story_scene_backgrounds b LEFT JOIN storyboard_scene_backgrounds sbg ON sbg.story_scene_background_id = b.id {$where} GROUP BY b.id ORDER BY b.sort_order ASC, b.background_name ASC", $params);
}
function sf_scene_backgrounds_find(array $backgrounds, int $id): ?array { foreach ($backgrounds as $background) if ((int)($background['id'] ?? 0) === $id) return $background; return null; }
function sf_scene_backgrounds_save(array $payload, int $id = 0): int {
  if (!sf_scene_backgrounds_ready()) return 0;
  if (sf_admin_column_exists('story_scene_backgrounds', 'updated_by_user_id')) $payload['updated_by_user_id'] = sf_current_user_id();
  if ($id <= 0 && sf_admin_column_exists('story_scene_backgrounds', 'created_by_user_id')) $payload['created_by_user_id'] = sf_current_user_id();
  if (!sf_admin_build_insert_update('story_scene_backgrounds', $payload, $id)) return 0;
  $newId = $id ?: (int)(sf_admin_db()?->lastInsertId() ?: 0);
  sf_admin_audit($id > 0 ? 'update_story_scene_background' : 'create_story_scene_background', 'story_scene_background', $newId, null, $payload);
  return $newId;
}
function sf_scene_background_for_scene(int $sceneId): ?array {
  if (!sf_scene_backgrounds_ready() || $sceneId <= 0) return null;
  return sf_admin_fetch_one('SELECT b.*, sbg.usage_notes, sbg.is_primary FROM storyboard_scene_backgrounds sbg INNER JOIN story_scene_backgrounds b ON b.id = sbg.story_scene_background_id WHERE sbg.storyboard_scene_id = ? LIMIT 1', [$sceneId]);
}
function sf_scene_backgrounds_for_scenes(array $sceneIds): array {
  if (!sf_scene_backgrounds_ready()) return [];
  $ids = array_values(array_unique(array_filter(array_map('intval', $sceneIds))));
  if (!$ids) return [];
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $rows = sf_admin_fetch_all("SELECT sbg.storyboard_scene_id, b.* FROM storyboard_scene_backgrounds sbg INNER JOIN story_scene_backgrounds b ON b.id = sbg.story_scene_background_id WHERE sbg.storyboard_scene_id IN ({$placeholders})", $ids);
  $map = [];
  foreach ($rows as $row) $map[(int)($row['storyboard_scene_id'] ?? 0)] = $row;
  return $map;
}
function sf_scene_background_assign_scene(int $sceneId, int $backgroundId, string $notes = ''): array {
  if (!sf_scene_backgrounds_ready()) return ['ok'=>false,'error'=>'scene_backgrounds_not_ready'];
  if ($sceneId <= 0) return ['ok'=>false,'error'=>'scene_required'];
  $scene = sf_admin_fetch_one('SELECT * FROM storyboard_scenes WHERE id = ? LIMIT 1', [$sceneId]);
  if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  sf_admin_execute('DELETE FROM storyboard_scene_backgrounds WHERE storyboard_scene_id = ?', [$sceneId]);
  if ($backgroundId > 0) {
    $background = sf_admin_fetch_one('SELECT * FROM story_scene_backgrounds WHERE id = ? LIMIT 1', [$backgroundId]);
    if (!$background) return ['ok'=>false,'error'=>'background_not_found'];
    sf_admin_execute('INSERT INTO storyboard_scene_backgrounds (storyboard_scene_id, story_scene_background_id, usage_notes, is_primary) VALUES (?, ?, ?, 1)', [$sceneId, $backgroundId, $notes]);
    $location = trim((string)($background['location_label'] ?? ''));
    $time = trim((string)($background['time_of_day'] ?? ''));
    if ($location !== '' || $time !== '') sf_admin_execute('UPDATE storyboard_scenes SET location_label = IF(? <> "", ?, location_label), time_of_day = IF(? <> "", ?, time_of_day), updated_at = NOW() WHERE id = ?', [$location, $location, $time, $time, $sceneId]);
  }
  sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [(int)($scene['storyboard_id'] ?? 0)]);
  sf_admin_audit('assign_scene_background', 'storyboard_scene', $sceneId, null, ['background_id'=>$backgroundId,'usage_notes'=>$notes]);
  return ['ok'=>true,'scene_id'=>$sceneId,'background_id'=>$backgroundId];
}
function sf_scene_background_context_for_scene(int $sceneId): string {
  $background = sf_scene_background_for_scene($sceneId);
  if (!$background) return '';
  $parts = ['Scene background: ' . trim((string)($background['background_name'] ?? ''))];
  foreach (['background_type'=>'Type','location_label'=>'Location','time_of_day'=>'Time of day','short_description'=>'Description','continuity_notes'=>'Continuity','image_path'=>'Reference image path'] as $key => $label) {
    $value = trim((string)($background[$key] ?? ''));
    if ($value !== '') $parts[] = $label . ': ' . $value;
  }
  return implode(' | ', $parts);
}
?>
