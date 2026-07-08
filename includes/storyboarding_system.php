<?php
require_once __DIR__ . '/admin_catalog.php';

function sf_story_v1_ready(): bool {
  return sf_admin_db_ready()
    && sf_admin_table_exists('story_seasons')
    && sf_admin_table_exists('story_episodes')
    && sf_admin_table_exists('story_scene_sheets')
    && sf_admin_table_exists('story_scene_cards')
    && sf_admin_table_exists('story_characters');
}

function sf_story_v1_disabled_attr(): string { return sf_story_v1_ready() ? '' : ' disabled'; }
function sf_story_v1_status_options(): array { return ['draft'=>'Draft','active'=>'Active','outline'=>'Outline','in_progress'=>'In Progress','needs_review'=>'Needs Review','ready'=>'Ready','published'=>'Published','archived'=>'Archived']; }
function sf_story_v1_role_options(): array { return ['lead'=>'Lead','supporting'=>'Supporting','guest'=>'Guest','background'=>'Background','antagonist'=>'Antagonist','mentor'=>'Mentor']; }
function sf_story_v1_card_type_options(): array { return ['beat'=>'Beat','action'=>'Action','dialogue'=>'Dialogue','camera'=>'Camera','music'=>'Music','prop'=>'Prop','wardrobe'=>'Wardrobe','transition'=>'Transition','note'=>'Note']; }
function sf_story_v1_status_label(string $status): string { return ucwords(str_replace('_', ' ', $status ?: 'draft')); }

function sf_story_v1_static_seasons(): array {
  return [['id'=>1,'season_number'=>1,'title'=>'Season 1','slug'=>'season-1-story-bible','logline'=>'The band rebuilds itself while every secret threatens the comeback.','description'=>'Primary Stonefellow season planning container.','theme_notes'=>'Found family, second chances, music as confession.','arc_notes'=>'A comeback begins as old wounds reopen.','status'=>'active','sort_order'=>10]];
}
function sf_story_v1_static_episodes(): array {
  return [['id'=>1,'story_season_id'=>1,'episode_number'=>1,'title'=>'First to Fall','slug'=>'first-to-fall-story-outline','logline'=>'A forgotten band gets one last shot, but the past refuses to stay quiet.','synopsis'=>'Pilot episode planning outline for Stonefellow.','runtime_target_minutes'=>48,'production_status'=>'outline','sort_order'=>10,'season_title'=>'Season 1','season_number'=>1]];
}
function sf_story_v1_static_characters(): array {
  return [
    ['id'=>1,'character_name'=>'Jax Stonefellow','slug'=>'jax-stonefellow','actor_name'=>'','role_type'=>'lead','short_bio'=>'Singer and guitarist carrying the weight of the band’s past.','motivation'=>'Wants the music to mean something again.','personality_notes'=>'Charismatic, guarded, funny under pressure.','relationship_notes'=>'Complicated history with the band and everyone who believed in him.','season_arc'=>'Learns whether redemption is possible when fame returns.','image_path'=>'images/cast/cast-jax.png','status'=>'active','sort_order'=>10,'scene_count'=>2],
    ['id'=>2,'character_name'=>'Violet Graves','slug'=>'violet-graves','actor_name'=>'','role_type'=>'supporting','short_bio'=>'Keys player with sharp instincts and a long memory.','motivation'=>'Wants truth without losing the music.','personality_notes'=>'Direct, stylish, emotionally observant.','relationship_notes'=>'Sees through Jax faster than anyone else.','season_arc'=>'Becomes the moral pressure point of the comeback.','image_path'=>'images/cast/cast-violet.png','status'=>'active','sort_order'=>20,'scene_count'=>1],
  ];
}
function sf_story_v1_static_scene_sheets(): array {
  return [
    ['id'=>1,'story_episode_id'=>1,'scene_number'=>1,'scene_title'=>'Cold Open: Desert Road','location_label'=>'Arizona highway','time_of_day'=>'Sunset','scene_summary'=>'Jax drives toward a comeback he is not sure he deserves.','scene_purpose'=>'Open the season with tone, loneliness, and unresolved history.','emotional_beat'=>'Regret under confidence.','conflict_notes'=>'He wants the music back, but fears what the band remembers.','production_notes'=>'Wide desert visual, quiet engine, first guitar cue.','scene_status'=>'outline','sort_order'=>10,'characters'=>['Jax Stonefellow']],
    ['id'=>2,'story_episode_id'=>1,'scene_number'=>2,'scene_title'=>'Backstage Before Soundcheck','location_label'=>'Small venue backstage','time_of_day'=>'Night','scene_summary'=>'The band reunites and the old chemistry returns with sharp edges.','scene_purpose'=>'Bring main characters together and surface conflict.','emotional_beat'=>'Comedy and tension.','conflict_notes'=>'Nobody agrees on who broke the band first.','production_notes'=>'Handheld backstage energy, fast overlapping dialogue.','scene_status'=>'outline','sort_order'=>20,'characters'=>['Jax Stonefellow','Violet Graves']],
  ];
}
function sf_story_v1_static_scene_cards(int $sceneSheetId): array {
  $cards = [
    1 => [
      ['id'=>1,'story_scene_sheet_id'=>1,'card_type'=>'camera','card_title'=>'Wide Desert Establishing Shot','card_body'=>'Open on a long empty highway, heat shimmer, old van moving through gold light.','sort_order'=>10],
      ['id'=>2,'story_scene_sheet_id'=>1,'card_type'=>'music','card_title'=>'First Guitar Motif','card_body'=>'A broken version of the Stonefellow hook plays before the full band sound exists.','sort_order'=>20],
    ],
    2 => [
      ['id'=>3,'story_scene_sheet_id'=>2,'card_type'=>'dialogue','card_title'=>'First Jab','card_body'=>'Violet cuts through the nostalgia with a line that makes everyone laugh and flinch.','sort_order'=>10],
      ['id'=>4,'story_scene_sheet_id'=>2,'card_type'=>'beat','card_title'=>'Band Chemistry Returns','card_body'=>'Everyone talks over each other, but the rhythm proves they still belong together.','sort_order'=>20],
    ],
  ];
  return $cards[$sceneSheetId] ?? [];
}

function sf_story_v1_counts(): array {
  if (!sf_story_v1_ready()) return ['seasons'=>1,'episodes'=>1,'scenes'=>2,'cards'=>4,'characters'=>2];
  return [
    'seasons' => sf_admin_count_table('story_seasons'),
    'episodes' => sf_admin_count_table('story_episodes'),
    'scenes' => sf_admin_count_table('story_scene_sheets'),
    'cards' => sf_admin_count_table('story_scene_cards'),
    'characters' => sf_admin_count_table('story_characters'),
  ];
}
function sf_story_v1_seasons(): array {
  if (!sf_story_v1_ready()) return sf_story_v1_static_seasons();
  return sf_admin_fetch_all('SELECT * FROM story_seasons ORDER BY sort_order ASC, season_number ASC, id ASC');
}
function sf_story_v1_episodes(?int $seasonId = null): array {
  if (!sf_story_v1_ready()) return sf_story_v1_static_episodes();
  $params = [];
  $where = '';
  if ($seasonId) { $where = 'WHERE e.story_season_id = ?'; $params[] = $seasonId; }
  return sf_admin_fetch_all("SELECT e.*, s.title AS season_title, s.season_number FROM story_episodes e INNER JOIN story_seasons s ON s.id = e.story_season_id {$where} ORDER BY s.sort_order ASC, s.season_number ASC, e.sort_order ASC, e.episode_number ASC, e.id ASC", $params);
}
function sf_story_v1_characters(string $status = ''): array {
  if (!sf_story_v1_ready()) return sf_story_v1_static_characters();
  $params = [];
  $where = '';
  if ($status !== '') { $where = 'WHERE c.status = ?'; $params[] = $status; }
  return sf_admin_fetch_all("SELECT c.*, COUNT(DISTINCT l.story_scene_sheet_id) AS scene_count FROM story_characters c LEFT JOIN story_scene_sheet_characters l ON l.story_character_id = c.id {$where} GROUP BY c.id ORDER BY c.sort_order ASC, c.character_name ASC", $params);
}
function sf_story_v1_scene_sheets(?int $episodeId = null): array {
  if (!sf_story_v1_ready()) return sf_story_v1_static_scene_sheets();
  $params = [];
  $where = '';
  if ($episodeId) { $where = 'WHERE ss.story_episode_id = ?'; $params[] = $episodeId; }
  $rows = sf_admin_fetch_all("SELECT ss.*, COUNT(sc.id) AS card_count FROM story_scene_sheets ss LEFT JOIN story_scene_cards sc ON sc.story_scene_sheet_id = ss.id {$where} GROUP BY ss.id ORDER BY ss.sort_order ASC, ss.scene_number ASC, ss.id ASC", $params);
  if (!$rows) return [];
  $ids = array_map(static fn($row) => (int)$row['id'], $rows);
  $charactersByScene = [];
  if ($ids && sf_admin_table_exists('story_scene_sheet_characters')) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $links = sf_admin_fetch_all("SELECT l.story_scene_sheet_id, c.character_name FROM story_scene_sheet_characters l INNER JOIN story_characters c ON c.id = l.story_character_id WHERE l.story_scene_sheet_id IN ({$placeholders}) ORDER BY c.sort_order ASC, c.character_name ASC", $ids);
    foreach ($links as $link) $charactersByScene[(int)$link['story_scene_sheet_id']][] = (string)$link['character_name'];
  }
  foreach ($rows as &$row) $row['characters'] = $charactersByScene[(int)$row['id']] ?? [];
  return $rows;
}
function sf_story_v1_scene_cards(?int $sceneSheetId = null): array {
  if (!sf_story_v1_ready()) return $sceneSheetId ? sf_story_v1_static_scene_cards($sceneSheetId) : [];
  if (!$sceneSheetId) return [];
  return sf_admin_fetch_all('SELECT * FROM story_scene_cards WHERE story_scene_sheet_id = ? ORDER BY sort_order ASC, id ASC', [$sceneSheetId]);
}
function sf_story_v1_first_id(array $rows): int { return (int)($rows[0]['id'] ?? 0); }
function sf_story_v1_find(array $rows, int $id): ?array { foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row; return null; }

function sf_story_v1_unique_slug(string $table, string $baseTitle, int $id = 0): string {
  $slug = sf_admin_slugify($baseTitle);
  if (!sf_story_v1_ready() || !sf_admin_table_exists($table)) return $slug;
  $base = $slug;
  $i = 2;
  while (true) {
    $row = sf_admin_fetch_one('SELECT id FROM `' . str_replace('`','',$table) . '` WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $id]);
    if (!$row) return $slug;
    $slug = $base . '-' . $i++;
  }
}
function sf_story_v1_save_row(string $table, array $payload, int $id = 0): int {
  if (!sf_story_v1_ready()) return 0;
  if (sf_admin_column_exists($table, 'updated_by_user_id')) $payload['updated_by_user_id'] = sf_current_user_id();
  if ($id <= 0 && sf_admin_column_exists($table, 'created_by_user_id')) $payload['created_by_user_id'] = sf_current_user_id();
  $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM `' . str_replace('`','',$table) . '` WHERE id = ? LIMIT 1', [$id]) : null;
  if (!sf_admin_build_insert_update($table, $payload, $id)) return 0;
  $newId = $id ?: (int)(sf_admin_db()?->lastInsertId() ?: 0);
  sf_admin_audit($id > 0 ? 'update_' . $table : 'create_' . $table, $table, $newId, $before, $payload);
  return $newId;
}
function sf_story_v1_sync_scene_characters(int $sceneId, array $characterIds): void {
  if (!sf_story_v1_ready() || $sceneId <= 0 || !sf_admin_table_exists('story_scene_sheet_characters')) return;
  sf_admin_execute('DELETE FROM story_scene_sheet_characters WHERE story_scene_sheet_id = ?', [$sceneId]);
  foreach (array_unique(array_filter(array_map('intval', $characterIds))) as $characterId) {
    sf_admin_execute('INSERT IGNORE INTO story_scene_sheet_characters (story_scene_sheet_id, story_character_id) VALUES (?, ?)', [$sceneId, $characterId]);
  }
}
function sf_story_v1_update_scene_order(array $ids): bool {
  if (!sf_story_v1_ready()) return false;
  $order = 10;
  $sceneNumber = 1;
  foreach ($ids as $id) {
    $id = (int)$id;
    if ($id <= 0) continue;
    sf_admin_execute('UPDATE story_scene_sheets SET sort_order = ?, scene_number = ? WHERE id = ?', [$order, $sceneNumber, $id]);
    $order += 10;
    $sceneNumber++;
  }
  return true;
}
function sf_story_v1_update_card_order(array $ids): bool {
  if (!sf_story_v1_ready()) return false;
  $order = 10;
  foreach ($ids as $id) {
    $id = (int)$id;
    if ($id <= 0) continue;
    sf_admin_execute('UPDATE story_scene_cards SET sort_order = ? WHERE id = ?', [$order, $id]);
    $order += 10;
  }
  return true;
}
?>